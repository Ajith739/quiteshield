<?php
/**
 * Detection rule interface: the pluggable unit of the QuietShield engine.
 *
 * Every behavioral detection implements this interface. A rule examines the
 * static analysis map (never live code) and returns explainable findings with
 * a severity contribution, evidence strings and source line numbers.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * Interface GWQSH_Detection_Rule.
 */
interface GWQSH_Detection_Rule {

	/**
	 * Stable rule identifier, e.g. 'GQR-CLOAK'.
	 *
	 * @return string
	 */
	public function id();

	/**
	 * Rule category: cloaking, persistence, payload, shell, obfuscation,
	 * integrity, upload, config or signature.
	 *
	 * @return string
	 */
	public function category();

	/**
	 * Human-readable explanation of what this rule detects and why it matters.
	 *
	 * @return string
	 */
	public function explanation();

	/**
	 * Evaluate the static analysis and return findings.
	 *
	 * A finding is an array:
	 *   rule_id, category, label, score, severity_hint, evidence (string[]), lines (int[]), weight
	 *
	 * @param array $analysis Result of GWQSH_PHP_Analyzer::analyze_file().
	 * @param array $context  Scan context: component, baseline_state, file_name, is_new.
	 * @return array Findings (possibly empty).
	 */
	public function evaluate( $analysis, $context );
}
