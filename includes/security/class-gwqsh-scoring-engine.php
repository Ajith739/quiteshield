<?php
/**
 * Explainable scoring engine.
 *
 * Combines rule findings into a single per-file verdict with a numeric score
 * and one of five severities (Informational, Low, Medium, High, Critical).
 * The output is fully explainable: every point traces to rule evidence.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * GWQSH scoring engine.
 */
final class GWQSH_Scoring_Engine {

	/**
	 * Severity order from weakest to strongest.
	 *
	 * @var array
	 */
	private static $severity_order = array( 'info' => 0, 'low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4 );

	/**
	 * Thresholds for the final score.
	 */
	const THRESHOLD_INFO      = 1;
	const THRESHOLD_LOW       = 6;
	const THRESHOLD_MEDIUM    = 12;
	const THRESHOLD_HIGH      = 20;
	const THRESHOLD_CRITICAL  = 30;

	/**
	 * Score a set of rule findings into one verdict.
	 *
	 * Multi-indicator combination is rewarded; isolated weak signals stay low.
	 * Distinct rule categories compound (attacker behavior spans categories),
	 * while stacked findings within one category add sublinearly.
	 *
	 * @param array $findings Rule findings.
	 * @param array $context  Scan context (component, baseline_state...).
	 * @return array array(
	 *   'score', 'severity' (info|low|medium|high|critical), 'severity_label',
	 *   'label' (primary rule label), 'rule_id' (primary), 'summary' (human text),
	 *   'evidence' (list), 'categories', 'should_auto_baseline_block'
	 * )
	 */
	public static function verdict( $findings, $context = array() ) {
		if ( empty( $findings ) ) {
			return array(
				'score'     => 0,
				'severity'  => 'info',
				'severity_label' => 'Informational',
				'label'     => '',
				'rule_id'   => '',
				'summary'   => 'No behavioral indicators matched.',
				'evidence'  => array(),
				'categories' => array(),
				'lines'     => array(),
			);
		}

		// Primary = highest scoring finding; tie-break by severity order.
		$primary_index = 0;
		$primary_score = -1;
		foreach ( $findings as $i => $finding ) {
			$rank = isset( $finding['score'] ) ? $finding['score'] : 0;
			if ( $rank > $primary_score ) {
				$primary_score = $rank;
				$primary_index = (int) $i;
			}
		}
		$primary  = $findings[ $primary_index ];
		$score    = 0;
		$by_category = array();

		foreach ( $findings as $finding ) {
			$cat = isset( $finding['category'] ) ? $finding['category'] : 'other';
			if ( ! isset( $by_category[ $cat ] ) ) {
				$by_category[ $cat ] = 0;
			}
			$by_category[ $cat ] += isset( $finding['score'] ) ? (int) $finding['score'] : 0;
		}

		// Within-category diminishing returns; across categories full weight:
		// real attacks trip multiple independent behavior classes.
		$category_count = count( $by_category );
		foreach ( $by_category as $cat_score ) {
			$score += $cat_score;
		}
		if ( $category_count >= 2 ) {
			$score += min( 10, 3 * ( $category_count - 1 ) );
		}

		// Location modifiers.
		$component = isset( $context['component'] ) ? $context['component'] : '';
		if ( 'mu' === $component ) {
			$score += 2;
		}
		if ( isset( $context['is_hidden_file'] ) && $context['is_hidden_file'] ) {
			$score += 6;
		}

		// Cap: supplemental-only evidence cannot reach Critical by itself.
		$only_supplemental = true;
		foreach ( $findings as $finding ) {
			if ( 'signature' !== $finding['category'] ) {
				$only_supplemental = false;
				break;
			}
		}
		if ( $only_supplemental ) {
			$score = min( $score, self::THRESHOLD_MEDIUM );
		}

		$severity = self::severity_for_score( $score );

		// Raise severity to the strongest hint when the score is just below.
		$hint_rank = 0;
		foreach ( $findings as $finding ) {
			$hint = isset( $finding['severity_hint'] ) ? $finding['severity_hint'] : 'info';
			if ( isset( self::$severity_order[ $hint ] ) && self::$severity_order[ $hint ] > $hint_rank ) {
				$hint_rank = self::$severity_order[ $hint ];
			}
		}
		if ( self::$severity_order[ $severity ] < $hint_rank && $score >= self::THRESHOLD_MEDIUM ) {
			// Pull up at most one level below the hint.
			$severity = self::severity_for_rank( max( self::$severity_order[ $severity ], $hint_rank - 1 ) );
		}

		$labels = array();
		foreach ( $findings as $finding ) {
			if ( isset( $finding['label'] ) && ! in_array( $finding['label'], $labels, true ) ) {
				$labels[] = $finding['label'];
			}
		}

		$evidence = array();
		$lines    = array();
		foreach ( $findings as $finding ) {
			foreach ( (array) ( isset( $finding['evidence'] ) ? $finding['evidence'] : array() ) as $item ) {
				$evidence[] = $item;
			}
			foreach ( (array) ( isset( $finding['lines'] ) ? $finding['lines'] : array() ) as $line ) {
				$lines[] = (int) $line;
			}
		}
		$lines = array_values( array_unique( array_filter( $lines ) ) );

		$summary = self::severity_label( $severity ) . ' - ' . $primary['label'];
		if ( count( $labels ) > 1 ) {
			$summary .= ' (+' . ( count( $labels ) - 1 ) . ' more indicator group' . ( count( $labels ) > 2 ? 's' : '' ) . ')';
		}

		return array(
			'score'     => (int) $score,
			'severity'  => $severity,
			'severity_label' => self::severity_label( $severity ),
			'label'     => $primary['label'],
			'rule_id'   => $primary['rule_id'],
			'summary'   => $summary,
			'evidence'  => array_slice( $evidence, 0, 12 ),
			'categories' => array_keys( $by_category ),
			'lines'     => array_slice( $lines, 0, 16 ),
			'findings'  => $findings,
		);
	}

	/**
	 * Severity for a score.
	 *
	 * @param int $score Score.
	 * @return string
	 */
	public static function severity_for_score( $score ) {
		if ( $score >= self::THRESHOLD_CRITICAL ) {
			return 'critical';
		}
		if ( $score >= self::THRESHOLD_HIGH ) {
			return 'high';
		}
		if ( $score >= self::THRESHOLD_MEDIUM ) {
			return 'medium';
		}
		if ( $score >= self::THRESHOLD_LOW ) {
			return 'low';
		}
		return 'info';
	}

	/**
	 * Human severity label.
	 *
	 * @param string $severity Severity key.
	 * @return string
	 */
	public static function severity_label( $severity ) {
		$labels = array(
			'info'     => 'Informational',
			'low'      => 'Low',
			'medium'   => 'Medium',
			'high'     => 'High',
			'critical' => 'Critical',
		);
		return isset( $labels[ $severity ] ) ? $labels[ $severity ] : 'Informational';
	}

	/**
	 * Severity key for a rank.
	 *
	 * @param int $rank Rank.
	 * @return string
	 */
	private static function severity_for_rank( $rank ) {
		foreach ( self::$severity_order as $key => $value ) {
			if ( $value === $rank ) {
				return $key;
			}
		}
		return 'info';
	}
}
