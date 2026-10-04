<?php
/**
 * Must-use plugin persistence rule.
 *
 * Flags new or modified files in WPMU_PLUGIN_DIR. New files with normal,
 * well-formed plugin behavior (hooks, options, standard API use) are reported
 * informationally for administrator review; files carrying attack behavior
 * escalate via this rule's multiplier.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rule: must-use plugin persistence.
 */
final class GWQSH_Rule_Mu_Persistence implements GWQSH_Detection_Rule {

	/**
	 * Rule ID.
	 */
	public function id() {
		return 'GQR-MU';
	}

	/**
	 * Category.
	 */
	public function category() {
		return 'persistence';
	}

	/**
	 * Explanation.
	 */
	public function explanation() {
		return 'Monitors the must-use plugin directory, which loads before normal plugins and is a favorite persistence location. New or changed files there are surfaced for review, and files combining MU placement with attack behavior escalate.';
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

		if ( ! isset( $context['component'] ) || 'mu' !== $context['component'] ) {
			return $findings;
		}

		$baseline_state = isset( $context['baseline_state'] ) ? $context['baseline_state'] : 'unknown'; // unknown|new|modified|trusted.

		if ( 'trusted' === $baseline_state ) {
			return $findings;
		}

		// Attack behavior present? Only TOKEN-VERIFIED signals count here:
		// raw regex indicators can match comments, and known incident strings
		// are supplemental signatures that must never escalate placement alone.
		$attack = ! empty( $analysis['backtick_shell'] )
			|| ! empty( $analysis['taint_sinks'] )
			|| ! empty( $analysis['variable_calls'] );

		if ( ! $attack ) {
			$attack_calls = array(
				'curl_init', 'curl_exec', 'wp_remote_get', 'wp_remote_post', 'wp_remote_request',
				'fsockopen', 'stream_socket_client', 'file_put_contents', 'fopen', 'fwrite',
				'chmod', 'rename', 'copy', 'unlink', 'symlink', 'touch', 'eval', 'assert',
				'system', 'shell_exec', 'passthru', 'proc_open', 'popen', 'create_function',
			);
			foreach ( $attack_calls as $fn ) {
				if ( GWQSH_PHP_Analyzer::has_call( $analysis, $fn ) ) {
					$attack = true;
					break;
				}
			}
		}

		if ( ! $attack && ! empty( $analysis['strings'] ) ) {
			foreach ( $analysis['strings'] as $string ) {
				if ( preg_match( GWQSH_Rule_Cloaking::BOT_RE, (string) $string['text'] ) ) {
					$attack = true; // Bot-classification logic in a must-use file.
					break;
				}
			}
		}

		if ( $attack ) {
			$findings[] = array(
				'rule_id'       => $this->id(),
				'category'      => $this->category(),
				'label'         => ( 'modified' === $baseline_state ? 'Modified must-use plugin with attack behavior' : 'New must-use plugin with attack behavior' ),
				'score'         => 12,
				'severity_hint' => 'high',
				'evidence'      => array(
					'must-use plugins load on every request before normal plugins',
					( 'modified' === $baseline_state ? 'file differs from the trusted baseline' : 'file is not part of the trusted baseline' ),
				),
				'lines'         => array(),
			);
			return $findings;
		}

		// New/changed MU file with ordinary behavior: informational only.
		$findings[] = array(
			'rule_id'       => $this->id(),
			'category'      => $this->category(),
			'label'         => ( 'modified' === $baseline_state ? 'Modified must-use plugin (review)' : 'New must-use plugin (review)' ),
			'score'         => 2,
			'severity_hint' => 'info',
			'evidence'      => array(
				( 'modified' === $baseline_state ? 'file differs from the trusted baseline' : 'file is not part of the trusted baseline' ),
				'no attack behavior detected; administrator review recommended',
			),
			'lines'         => array(),
		);

		return $findings;
	}
}
