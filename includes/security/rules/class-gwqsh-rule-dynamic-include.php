<?php
/**
 * Dynamic include detection rule.
 *
 * Detects include/require statements with variable or concatenated paths -
 * a classic backdoor loader (e.g. include( $_GET['page'] . '.php' )) and a
 * remote-file-inclusion risk.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rule: dynamic includes.
 */
final class GWQSH_Rule_Dynamic_Include implements GWQSH_Detection_Rule {

	/**
	 * Rule ID.
	 */
	public function id() {
		return 'GQR-INCL';
	}

	/**
	 * Category.
	 */
	public function category() {
		return 'payload';
	}

	/**
	 * Explanation.
	 */
	public function explanation() {
		return 'Detects include/require statements built from variables or request input, which let attackers load arbitrary code paths (local/remote file inclusion).';
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

		$dynamic = ! empty( $analysis['calls']['__dynamic_include'] ) ? $analysis['calls']['__dynamic_include'] : array();
		if ( empty( $dynamic ) ) {
			return $findings;
		}

		$score     = 5;
		$evidence  = array();
		$lines     = array();
		$has_taint = false;

		foreach ( array_slice( $dynamic, 0, 4 ) as $call ) {
			$lines[]   = (int) $call['line'];
			$evidence[] = 'include/require with dynamic path: ' . $this->shorten( $call['args'] ) . ' (line ' . $call['line'] . ')';
		}
		if ( count( $dynamic ) > 4 ) {
			$evidence[] = count( $dynamic ) . ' dynamic include sites in total';
		}

		// Tainted include path?
		foreach ( (array) $analysis['taint_sinks'] as $sink ) {
			if ( 'dynamic include' === $sink['kind'] ) {
				$has_taint = true;
				$score    += 16;
				$evidence[] = $sink['origin'] . ' input controls the included path (line ' . $sink['line'] . ')';
				break;
			}
		}
		if ( ! $has_taint && ! empty( $analysis['taint'] ) ) {
			// Variables assigned from request input feed the include expression.
			foreach ( $dynamic as $call ) {
				foreach ( (array) $call['arg_vars'] as $var ) {
					if ( isset( $analysis['taint'][ $var ] ) ) {
						$has_taint = true;
						$score    += 16;
						$evidence[] = $analysis['taint'][ $var ]['origin'] . ' input feeds the include path via ' . $var . ' (line ' . $call['line'] . ')';
						break 2;
					}
				}
			}
		}
		if ( ! $has_taint && empty( $analysis['taint'] ) ) {
			// Static-but-computed path: modest contribution.
			$score += 2;
		}

		$severity = $has_taint ? 'critical' : 'low';
		if ( ! $has_taint && $score >= 9 ) {
			$severity = 'medium';
		}

		$findings[] = array(
			'rule_id'       => $this->id(),
			'category'      => $this->category(),
			'label'         => $has_taint ? 'Dynamic include with request-controlled path' : 'Dynamic include paths',
			'score'         => $score,
			'severity_hint' => $severity,
			'evidence'      => $evidence,
			'lines'         => array_slice( $lines, 0, 8 ),
		);

		return $findings;
	}

	/**
	 * Shorten an expression for display.
	 *
	 * @param string $expr Expression.
	 * @return string
	 */
	private function shorten( $expr ) {
		$expr = (string) $expr;
		if ( strlen( $expr ) > 60 ) {
			$expr = substr( $expr, 0, 57 ) . '...';
		}
		return $expr;
	}
}
