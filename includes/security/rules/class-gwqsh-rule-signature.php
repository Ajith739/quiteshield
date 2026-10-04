<?php
/**
 * Supplemental known-indicator signature rule.
 *
 * Matches strings from the sanitized real incident. This rule is SUPPLEMENTAL
 * ONLY: behavioral detection remains the primary system, and these signatures
 * alone never produce a Critical verdict.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rule: known incident signatures (supplemental).
 */
final class GWQSH_Rule_Signature implements GWQSH_Detection_Rule {

	/**
	 * Rule ID.
	 */
	public function id() {
		return 'GQR-SIG';
	}

	/**
	 * Category.
	 */
	public function category() {
		return 'signature';
	}

	/**
	 * Explanation.
	 */
	public function explanation() {
		return 'Matches identifiers from a real sanitized incident as supplemental evidence. Behavioral rules remain the primary detection system; signatures alone are never sufficient for a Critical verdict.';
	}

	/**
	 * Evaluate.
	 *
	 * @param array $analysis Analysis.
	 * @param array $context  Context.
	 * @return array
	 */
	public function evaluate( $analysis, $context ) {
		$findings = array();

		$known = $analysis['known_matches'];
		if ( empty( $known ) ) {
			return $findings;
		}

		$evidence = array();
		$lines    = array();
		$score    = 6 + 2 * min( 3, count( $known ) );
		foreach ( array_slice( $known, 0, 5, true ) as $needle => $line ) {
			$lines[]   = (int) $line;
			$evidence[] = 'known incident indicator "' . $needle . '" (line ' . $line . ')';
		}

		$severity = 'medium';
		if ( count( $known ) >= 3 ) {
			$severity = 'high';
		}

		$findings[] = array(
			'rule_id'       => $this->id(),
			'category'      => $this->category(),
			'label'         => 'Known incident indicators (' . count( $known ) . ')',
			'score'         => $score,
			'severity_hint' => $severity,
			'evidence'      => $evidence,
			'lines'         => array_slice( $lines, 0, 8 ),
		);

		return $findings;
	}
}
