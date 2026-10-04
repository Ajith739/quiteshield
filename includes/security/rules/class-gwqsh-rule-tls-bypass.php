<?php
/**
 * TLS verification bypass detection rule.
 *
 * Detects disabling of certificate verification (CURLOPT_SSL_VERIFYPEER /
 * CURLOPT_SSL_VERIFYHOST false, 'verify' => false) - a hallmark of malware
 * that talks to attacker-controlled endpoints, and a serious weakness in
 * legitimate code.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rule: TLS verification bypass.
 */
final class GWQSH_Rule_Tls_Bypass implements GWQSH_Detection_Rule {

	/**
	 * Rule ID.
	 */
	public function id() {
		return 'GQR-TLS';
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
		return 'Detects disabled TLS certificate verification, which enables undetected communication with attacker-controlled servers and weakens transport security.';
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

		$tls_lines = array();
		if ( ! empty( $analysis['indicators']['tls_bypass']['lines'] ) ) {
			$tls_lines = $analysis['indicators']['tls_bypass']['lines'];
		}
		if ( empty( $tls_lines ) ) {
			return $findings;
		}

		$evidence = array( 'TLS certificate verification disabled (lines ' . implode( ', ', array_slice( $tls_lines, 0, 4 ) ) . ')' );
		$score    = 6;
		$severity = 'low';
		$label    = 'TLS certificate verification disabled';

		// Escalate when combined with remote retrieval or obfuscation.
		$remote = GWQSH_PHP_Analyzer::has_call( $analysis, 'curl_exec' )
			|| GWQSH_PHP_Analyzer::has_call( $analysis, 'curl_init' )
			|| GWQSH_PHP_Analyzer::has_call( $analysis, 'wp_remote_get' )
			|| ! empty( $analysis['indicators']['remote_fetch'] );
		$obfuscated = ! empty( $analysis['indicators']['obfuscation'] );
		if ( $remote ) {
			$score    += 8;
			$severity = 'medium';
			$label    .= ' for outbound requests';
			$evidence[] = 'combined with outbound remote requests';
		}
		if ( $obfuscated ) {
			$score    += 6;
			$severity = 'high';
			$label    .= ' in obfuscated code';
			$evidence[] = 'combined with obfuscated payload handling';
		}
		if ( ! empty( $context['component'] ) && 'mu' === $context['component'] ) {
			$score    += 4;
			$evidence[] = 'runs from the must-use plugin directory';
		}

		if ( $score >= 20 ) {
			$severity = 'high';
		}

		$findings[] = array(
			'rule_id'       => $this->id(),
			'category'      => $this->category(),
			'label'         => $label,
			'score'         => $score,
			'severity_hint' => $severity,
			'evidence'      => $evidence,
			'lines'         => array_slice( $tls_lines, 0, 8 ),
		);

		return $findings;
	}
}
