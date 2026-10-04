<?php
/**
 * Cloaking / traffic-hijack detection rule.
 *
 * Detects code that identifies search-engine bots (or mobile visitors), then
 * serves them different content: redirects to external sites, remote payloads
 * or injected iframes. This behavioral combination was the primary incident
 * pattern and must be detected regardless of file names or obfuscation.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rule: cloaking and bot-based traffic hijacking.
 */
final class GWQSH_Rule_Cloaking implements GWQSH_Detection_Rule {

	/**
	 * Bot identification regex applied to string literals.
	 *
	 * @var string
	 */
	const BOT_RE = '/googlebot|bingbot|slurp|duckduckbot|baiduspider|yandexbot|facebot|ia_archiver|mj12bot|ahrefsbot|semrushbot|facebookexternalhit|whatsapp|crawler|spider|robot/i';

	/**
	 * Rule ID.
	 */
	public function id() {
		return 'GQR-CLOAK';
	}

	/**
	 * Category.
	 */
	public function category() {
		return 'cloaking';
	}

	/**
	 * Explanation.
	 */
	public function explanation() {
		return 'Identifies search-engine crawlers or visitor classes and serves them different content (redirects, remote payloads or injected frames) - the signature behavior of SEO spam and traffic-hijacking malware.';
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

		// 1. Bot detection in string literals AND in the shared kernel indicator
		//    (the kernel scans raw source, so cloaking is caught identically by
		//    the Early Guardian and the full engine).
		$bot_lines = array();
		if ( ! empty( $analysis['strings'] ) ) {
			foreach ( $analysis['strings'] as $string ) {
				if ( preg_match( self::BOT_RE, $string['text'] ) ) {
					$bot_lines[] = (int) $string['line'];
				}
			}
		}
		if ( ! empty( $analysis['indicators']['bot_detection']['lines'] ) ) {
			$bot_lines = array_merge( $bot_lines, array_slice( $analysis['indicators']['bot_detection']['lines'], 0, 8 ) );
		}
		$bot_lines = array_values( array_unique( $bot_lines ) );

		// 2. Visitor inspection: HTTP_USER_AGENT / referer usage.
		$ua_lines = array();
		if ( ! empty( $analysis['indicators']['user_agent_probe']['lines'] ) ) {
			$ua_lines = $analysis['indicators']['user_agent_probe']['lines'];
		}

		// 3. Delivery mechanisms.
		$redirect_lines = array();
		foreach ( array( 'wp_redirect', 'wp_safe_redirect' ) as $fn ) {
			$redirect_lines = array_merge( $redirect_lines, GWQSH_PHP_Analyzer::call_lines( $analysis, $fn ) );
		}
		if ( ! empty( $analysis['indicators']['external_redirect']['lines'] ) ) {
			$redirect_lines = array_merge( $redirect_lines, array_slice( $analysis['indicators']['external_redirect']['lines'], 0, 8 ) );
		}
		if ( ! empty( $analysis['indicators']['js_redirect']['lines'] ) ) {
			$redirect_lines = array_merge( $redirect_lines, array_slice( $analysis['indicators']['js_redirect']['lines'], 0, 8 ) );
		}
		$redirect_lines = array_values( array_unique( $redirect_lines ) );

		$remote_lines = array();
		foreach ( array( 'curl_init', 'curl_exec', 'wp_remote_get', 'wp_remote_post', 'wp_remote_request', 'fsockopen', 'stream_socket_client', 'file_get_contents' ) as $fn ) {
			$remote_lines = array_merge( $remote_lines, GWQSH_PHP_Analyzer::call_lines( $analysis, $fn ) );
		}
		$remote_lines = array_values( array_unique( $remote_lines ) );

		$iframe_lines = array();
		if ( ! empty( $analysis['indicators']['iframe_injection']['lines'] ) ) {
			$iframe_lines = $analysis['indicators']['iframe_injection']['lines'];
		}

		if ( $bot_lines && ( $ua_lines || ! empty( $analysis['taint'] ) ) ) {
			$evidence   = array();
			$evidence[] = 'bot identification strings (lines ' . implode( ', ', array_slice( $bot_lines, 0, 5 ) ) . ')';
			if ( $ua_lines ) {
				$evidence[] = 'HTTP user-agent inspection (lines ' . implode( ', ', array_slice( $ua_lines, 0, 5 ) ) . ')';
			}
			$score  = 8;
			$labels = array( 'search-engine bot identification' );

			$delivered = false;
			if ( $redirect_lines ) {
				$score     += 14;
				$delivered  = true;
				$evidence[] = 'redirects visitors (lines ' . implode( ', ', array_slice( $redirect_lines, 0, 5 ) ) . ')';
				$labels[]   = 'visitor redirection';
			}
			if ( $remote_lines ) {
				$score     += 8;
				$delivered  = true;
				$evidence[] = 'retrieves remote content (lines ' . implode( ', ', array_slice( $remote_lines, 0, 5 ) ) . ')';
				$labels[]   = 'remote payload delivery';
			}
			if ( $iframe_lines ) {
				$score     += 10;
				$delivered  = true;
				$evidence[] = 'injects external iframes (lines ' . implode( ', ', array_slice( $iframe_lines, 0, 5 ) ) . ')';
				$labels[]   = 'iframe injection';
			}

			if ( ! empty( $analysis['hooks']['template_redirect'] ) ) {
				$score     += 4;
				$evidence[] = 'registers the template_redirect hook (line ' . $analysis['hooks']['template_redirect'] . ') to control front-end responses';
			}

			$severity = 'medium';
			if ( $score >= 22 ) {
				$severity = 'critical';
			} elseif ( $score >= 16 ) {
				$severity = 'high';
			}

			if ( $delivered ) {
				$findings[] = array(
					'rule_id'      => $this->id(),
					'category'     => $this->category(),
					'label'        => 'Cloaking: ' . implode( ' + ', $labels ),
					'score'        => $score,
					'severity_hint' => $severity,
					'evidence'     => $evidence,
					'lines'        => array_slice( array_merge( $bot_lines, $ua_lines, $redirect_lines ), 0, 12 ),
				);
			}
		}

		return $findings;
	}
}
