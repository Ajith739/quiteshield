<?php
/**
 * Remote payload loader detection rule.
 *
 * Detects code that fetches remote content - especially when the fetched data
 * is written to disk, evaluated, included dynamically or delivered to visitors.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rule: remote payload loading.
 */
final class GWQSH_Rule_Remote_Payload implements GWQSH_Detection_Rule {

	/**
	 * Rule ID.
	 */
	public function id() {
		return 'GQR-REMOTE';
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
		return 'Detects code that downloads content from attacker-controlled servers, including payloads that are then written to the filesystem, executed, included or shown to visitors.';
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

		$remote_calls = array();
		foreach ( array( 'curl_init', 'curl_exec', 'wp_remote_get', 'wp_remote_post', 'wp_remote_request', 'fsockopen', 'stream_socket_client', 'get_headers', 'dns_get_record' ) as $fn ) {
			$lines = GWQSH_PHP_Analyzer::call_lines( $analysis, $fn );
			if ( $lines ) {
				$remote_calls[ $fn ] = $lines;
			}
		}
		// file_get_contents with a URL argument.
		foreach ( GWQSH_PHP_Analyzer::call_lines( $analysis, 'file_get_contents' ) as $call_index => $line ) {
			$args = '';
			if ( ! empty( $analysis['calls']['file_get_contents'][ $call_index ]['args'] ) ) {
				$args = $analysis['calls']['file_get_contents'][ $call_index ]['args'];
			}
			if ( preg_match( '#["\']https?://#i', $args ) ) {
				$remote_calls['file_get_contents(url)'][] = $line;
			}
		}

		if ( empty( $remote_calls ) ) {
			// Referencing an external URL (attribution links, documentation)
			// is not retrieval. This rule only fires when code actually
			// fetches remote content.
			return $findings;
		}

		$evidence  = array();
		$lines_all = array();
		$score     = 4;
		foreach ( $remote_calls as $fn => $lines ) {
			$evidence[]  = $fn . '() remote retrieval (lines ' . implode( ', ', array_slice( $lines, 0, 4 ) ) . ')';
			$lines_all   = array_merge( $lines_all, $lines );
			$score      += 4;
		}

		$urls = $analysis['external_urls'];
		if ( $urls ) {
			$evidence[] = 'external endpoints referenced: ' . implode( ', ', array_slice( $urls, 0, 3 ) );
		}

		$labels = array( 'remote payload retrieval' );

		// Escalators: what happens with the fetched data?
		if ( ! empty( $analysis['taint_sinks'] ) ) {
			foreach ( $analysis['taint_sinks'] as $sink ) {
				if ( in_array( $sink['kind'], array( 'filesystem write', 'dynamic code execution', 'process execution', 'dynamic include' ), true ) ) {
					$score     += 10;
					$labels[]   = 'fetched/request data reaches ' . $sink['kind'] . ' (' . $sink['call'] . ', line ' . $sink['line'] . ')';
					break;
				}
			}
		}
		$write_fns = array( 'file_put_contents', 'fwrite', 'fopen', 'rename', 'copy', 'unlink' );
		$write_lines = array();
		foreach ( $write_fns as $fn ) {
			$write_lines = array_merge( $write_lines, GWQSH_PHP_Analyzer::call_lines( $analysis, $fn ) );
		}
		if ( $write_lines && $remote_calls ) {
			$score    += 6;
			$labels[]  = 'writes files alongside remote retrieval (lines ' . implode( ', ', array_slice( $write_lines, 0, 3 ) ) . ')';
		}
		if ( ! empty( $analysis['calls']['eval'] ) || ! empty( $analysis['calls']['assert'] ) || ! empty( $analysis['calls']['create_function'] ) ) {
			$score    += 10;
			$labels[]  = 'dynamic code execution present';
		}
		if ( ! empty( $analysis['calls']['__dynamic_include'] ) ) {
			$score    += 8;
			$labels[]  = 'dynamic include of variable paths';
		}

		$severity = 'low';
		if ( $score >= 24 ) {
			$severity = 'critical';
		} elseif ( $score >= 16 ) {
			$severity = 'high';
		} elseif ( $score >= 9 ) {
			$severity = 'medium';
		}

		if ( $remote_calls || $score > 4 ) {
			$findings[] = array(
				'rule_id'       => $this->id(),
				'category'      => $this->category(),
				'label'         => 'Remote payload behavior: ' . implode( '; ', $labels ),
				'score'         => $score,
				'severity_hint' => $severity,
				'evidence'      => $evidence,
				'lines'         => array_slice( $lines_all, 0, 12 ),
			);
		}

		return $findings;
	}
}
