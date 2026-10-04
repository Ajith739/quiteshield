<?php
/**
 * .htaccess auditor.
 *
 * Reviews the webroot .htaccess for malware patterns: search-engine bot
 * redirects (cloaking), external RewriteRule targets, hidden PHP gates,
 * disabled directory listings combined with execution re-enabling, and
 * suspicious AddHandler types. QuietShield's own managed marker block is
 * excluded from findings.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * GWQSH .htaccess auditor.
 */
final class GWQSH_Htaccess_Auditor {

	/**
	 * Audit a .htaccess file (never executed, text only).
	 *
	 * @param string $path Absolute path.
	 * @return array array( 'exists', 'size', 'findings' => array( label, severity, severity_label, score, evidence[] ) )
	 */
	public static function audit( $path ) {
		$result = array(
			'exists'   => false,
			'size'     => 0,
			'findings' => array(),
		);
		if ( ! is_string( $path ) || '' === $path || ! is_file( $path ) || is_link( $path ) ) {
			return $result;
		}
		$read = gwqsh_guardian_core_read_file( $path, 1048576 );
		if ( ! is_array( $read ) ) {
			return $result;
		}
		$result['exists'] = true;
		$result['size']   = $read['size'];
		$content          = $read['code'];

		// Remove QuietShield's own managed block for a fair audit.
		$managed = preg_replace( '/^# BEGIN Gracewell QuietShield\R.*?^# END Gracewell QuietShield\R?/ms', '', $content );
		if ( null !== $managed && $managed !== $content ) {
			$content = $managed;
		}

		$lines    = explode( "\n", $content );
		$findings = array();

		// 1. Bot-condition redirects (cloaking via RewriteCond HTTP_USER_AGENT).
		$bot_conds = array();
		foreach ( $lines as $i => $line ) {
			if ( preg_match( '/RewriteCond\s+%\{HTTP_USER_AGENT\}/i', $line ) && preg_match( '/googlebot|bingbot|slurp|baiduspider|yandexbot|crawler|spider|bot/i', $line ) ) {
				$bot_conds[] = $i + 1;
			}
		}
		$redirects = array();
		foreach ( $lines as $i => $line ) {
			if ( preg_match( '/RewriteRule\s+.*\[.*R=30[12]/i', $line ) || preg_match( '/Redirect(?:Permanent|Temp)?\s+/i', $line ) ) {
				$redirects[] = $i + 1;
			}
		}
		if ( $bot_conds && $redirects ) {
			$findings[] = array(
				'label'          => 'Cloaking redirect rules for search-engine bots',
				'severity'       => 'critical',
				'severity_label' => 'Critical',
				'score'          => 34,
				'evidence'       => array(
					'bot user-agent conditions on lines ' . implode( ', ', array_slice( $bot_conds, 0, 5 ) ),
					'redirect rules on lines ' . implode( ', ', array_slice( $redirects, 0, 5 ) ),
				),
			);
		}

		// 2. External RewriteRule targets (rewrites to other domains).
		$external = array();
		foreach ( $lines as $i => $line ) {
			if ( preg_match( '/RewriteRule\s+\S+\s+https?:\/\/(?!127\.0\.0\.1|localhost)/i', $line ) ) {
				$external[] = $i + 1;
			}
		}
		if ( $external ) {
			$findings[] = array(
				'label'          => 'Rewrite rules pointing to an external domain',
				'severity'       => 'high',
				'severity_label' => 'High',
				'score'          => 24,
				'evidence'       => array( 'external rewrite targets on lines ' . implode( ', ', array_slice( $external, 0, 5 ) ) ),
			);
		}

		// 3. PHP handlers/gates: AddHandler php, php_value auto_prepend_file.
		$gates = array();
		foreach ( $lines as $i => $line ) {
			if ( preg_match( '/AddHandler\s+.*(application\/x-httpd-php|php)/i', $line ) && preg_match( '/\.(?:jpg|jpeg|png|gif|ico|css|js|txt|html|svg)/i', $line ) ) {
				$gates[] = 'image/text type mapped to PHP handler (line ' . ( $i + 1 ) . ')';
			}
			if ( preg_match( '/php_value\s+auto_(?:prepend|append)_file/i', $line ) ) {
				$gates[] = 'auto_prepend/append_file injection (line ' . ( $i + 1 ) . ')';
			}
			if ( preg_match( '/^php_flag\s+allow_url_include\s+on/i', $line ) ) {
				$gates[] = 'allow_url_include switched on (line ' . ( $i + 1 ) . ')';
			}
		}
		if ( $gates ) {
			$findings[] = array(
				'label'          => 'Execution re-enabling directives',
				'severity'       => 'high',
				'severity_label' => 'High',
				'score'          => 22,
				'evidence'       => array_slice( $gates, 0, 5 ),
			);
		}

		// 4. Known incident file references (supplemental).
		$known = gwqsh_guardian_core_known_indicator_matches( $content );
		if ( ! empty( $known ) ) {
			$findings[] = array(
				'label'          => 'Known incident indicators in .htaccess',
				'severity'       => 'high',
				'severity_label' => 'High',
				'score'          => 18,
				'evidence'       => array( 'indicators present: ' . implode( ', ', array_slice( array_keys( $known ), 0, 4 ) ) ),
			);
		}

		$result['findings'] = $findings;
		return $result;
	}
}
