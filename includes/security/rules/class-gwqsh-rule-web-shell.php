<?php
/**
 * Web shell detection rule.
 *
 * Detects remote-control backdoors: request input (GET/POST/cookies/servers)
 * reaching process execution, dynamic code evaluation or dynamic includes,
 * plus classic shells that bootstrap WordPress manually.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rule: web shell.
 */
final class GWQSH_Rule_Web_Shell implements GWQSH_Detection_Rule {

	/**
	 * Rule ID.
	 */
	public function id() {
		return 'GQR-SHELL';
	}

	/**
	 * Category.
	 */
	public function category() {
		return 'shell';
	}

	/**
	 * Explanation.
	 */
	public function explanation() {
		return 'Detects web-shell backdoors: request-controlled input that reaches process execution, code evaluation or dynamic file inclusion, and standalone scripts that manually bootstrap WordPress.';
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

		$shell_lines = array();
		foreach ( array( 'system', 'exec', 'shell_exec', 'passthru', 'proc_open', 'popen', 'pcntl_exec' ) as $fn ) {
			$shell_lines = array_merge( $shell_lines, GWQSH_PHP_Analyzer::call_lines( $analysis, $fn ) );
		}
		$shell_lines = array_merge( $shell_lines, $analysis['backtick_shell'] );
		$shell_lines = array_values( array_unique( $shell_lines ) );

		$eval_lines = array();
		foreach ( array( 'eval', 'assert', 'create_function', 'preg_replace' ) as $fn ) {
			$eval_lines = array_merge( $eval_lines, GWQSH_PHP_Analyzer::call_lines( $analysis, $fn ) );
		}
		$eval_lines = array_values( array_unique( $eval_lines ) );

		$has_request_input = ! empty( $analysis['indicators']['request_input'] );
		$dynamic_includes  = ! empty( $analysis['calls']['__dynamic_include'] );

		// Primary: request-controlled execution.
		$evidence = array();
		$lines    = array();
		$score    = 0;
		$labels   = array();

		foreach ( (array) $analysis['taint_sinks'] as $sink ) {
			if ( in_array( $sink['kind'], array( 'process execution', 'dynamic code execution', 'dynamic include' ), true ) ) {
				$score    += 18;
				$labels[]  = 'request input reaches ' . $sink['kind'];
				$evidence[] = $sink['origin'] . ' input flows into ' . $sink['call'] . ' (' . $sink['kind'] . ', line ' . $sink['line'] . ')';
				$lines[]   = (int) $sink['line'];
			}
		}

		if ( $shell_lines ) {
			$evidence[] = 'process-execution functions present (lines ' . implode( ', ', array_slice( $shell_lines, 0, 5 ) ) . ')';
			$lines      = array_merge( $lines, $shell_lines );
			if ( $has_request_input ) {
				$score   += 12;
				$labels[] = 'process execution combined with request input';
			} else {
				$score   += 4;
			}
		}
		if ( $eval_lines ) {
			$evidence[] = 'dynamic code evaluation present (lines ' . implode( ', ', array_slice( $eval_lines, 0, 5 ) ) . ')';
			$lines      = array_merge( $lines, array_slice( $eval_lines, 0, 5 ) );
			if ( $has_request_input ) {
				$score   += 8;
				$labels[] = 'code evaluation combined with request input';
			}
		}
		if ( $dynamic_includes && $has_request_input ) {
			$score   += 8;
			$labels[] = 'request-dependent dynamic includes';
		}

		// Standalone bootstrap shells: wp-load/wp-blog-header outside WP context.
		$bootstrap_lines = array();
		if ( ! empty( $analysis['indicators']['wp_bootstrap']['lines'] ) ) {
			$bootstrap_lines = $analysis['indicators']['wp_bootstrap']['lines'];
			$component       = isset( $context['component'] ) ? $context['component'] : '';
			if ( in_array( $component, array( 'uploads', 'root' ), true ) ) {
				$score    += 10;
				$labels[]  = 'manually loads WordPress from an unusual location';
				$evidence[] = 'loads wp-load.php / wp-blog-header.php from ' . $component . ' (lines ' . implode( ', ', array_slice( $bootstrap_lines, 0, 3 ) ) . ')';
				$lines     = array_merge( $lines, array_slice( $bootstrap_lines, 0, 3 ) );
			}
		}

		if ( $score <= 0 ) {
			return $findings;
		}

		$severity = 'medium';
		if ( $score >= 22 ) {
			$severity = 'critical';
		} elseif ( $score >= 12 ) {
			$severity = 'high';
		}

		$findings[] = array(
			'rule_id'       => $this->id(),
			'category'      => $this->category(),
			'label'         => 'Web shell behavior: ' . ( $labels ? implode( ' + ', array_slice( $labels, 0, 3 ) ) : 'command execution present' ),
			'score'         => $score,
			'severity_hint' => $severity,
			'evidence'      => $evidence,
			'lines'         => array_slice( array_values( array_unique( $lines ) ), 0, 12 ),
		);

		return $findings;
	}
}
