<?php
/**
 * wp-config.php auditor.
 *
 * Reviews wp-config.php for post-intrusion persistence: injected includes,
 * remote URL fetches, filter closures with suspicious behavior, disabled
 * updates and unexpected auto_prepend style directives.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * GWQSH wp-config auditor.
 */
final class GWQSH_WpConfig_Auditor {

	/**
	 * Audit wp-config.php (text only; never executed).
	 *
	 * @param string $path Absolute path.
	 * @return array array( 'exists', 'findings' => array(...) )
	 */
	public static function audit( $path ) {
		$result = array(
			'exists'   => false,
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
		$code             = $read['code'];

		$findings = array();
		$lines    = explode( "\n", $code );

		// 1. include/require of unexpected files (anything not wp-settings.php).
		$includes = array();
		foreach ( $lines as $i => $line ) {
			if ( preg_match( '/^\s*(?:include|include_once|require|require_once)\s*\(?([^;]+);/i', $line, $m ) ) {
				$target = trim( $m[1] );
				if ( ! preg_match( '/wp-settings\.php/i', $target ) ) {
					$includes[] = 'line ' . ( $i + 1 ) . ': ' . substr( $target, 0, 80 );
				}
			}
		}
		if ( $includes ) {
			$findings[] = array(
				'label'          => 'Unexpected include in wp-config.php',
				'severity'       => 'high',
				'severity_label' => 'High',
				'score'          => 22,
				'evidence'       => array_slice( $includes, 0, 4 ),
			);
		}

		// 2. Remote URLs / fetches.
		$urls = array_values( array_filter(
			gwqsh_guardian_core_external_urls( $code, 5 ),
			static function ( $url ) {
				// Local development endpoints are not remote references.
				return (bool) preg_match( '#^https?://(?!localhost|127\.0\.0\.1|\[::1\])#i', (string) $url );
			}
		) );
		if ( $urls ) {
			$findings[] = array(
				'label'          => 'Remote URL references in wp-config.php',
				'severity'       => 'high',
				'severity_label' => 'High',
				'score'          => 20,
				'evidence'       => array( 'endpoints: ' . implode( ', ', array_slice( $urls, 0, 3 ) ) ),
			);
		}

		// 3. Known incident indicators (supplemental).
		$known = gwqsh_guardian_core_known_indicator_matches( $code );
		if ( ! empty( $known ) ) {
			$findings[] = array(
				'label'          => 'Known incident indicators in wp-config.php',
				'severity'       => 'critical',
				'severity_label' => 'Critical',
				'score'          => 28,
				'evidence'       => array( 'indicators present: ' . implode( ', ', array_slice( array_keys( $known ), 0, 4 ) ) ),
			);
		}

		// 4. Update-blocking filters (attackers freeze sites to keep access).
		$blocks = array();
		foreach ( $lines as $i => $line ) {
			if ( preg_match( '/automatic_updates|auto_update_(?:core|plugin|theme)/i', $line ) && preg_match( '/__return_false|false|disabled/i', $line ) ) {
				$blocks[] = 'line ' . ( $i + 1 );
			}
		}
		if ( $blocks ) {
			$findings[] = array(
				'label'          => 'Automatic updates disabled inside wp-config.php',
				'severity'       => 'low',
				'severity_label' => 'Low',
				'score'          => 6,
				'evidence'       => array( 'update suppression on lines ' . implode( ', ', array_slice( $blocks, 0, 4 ) ) . ' (verify this was intentional)' ),
			);
		}

		// 5. eval/base64 payload markers.
		if ( preg_match( '/eval\s*\(|base64_decode\s*\(|gzinflate\s*\(/i', $code ) ) {
			$findings[] = array(
				'label'          => 'Encoded or evaluated code in wp-config.php',
				'severity'       => 'critical',
				'severity_label' => 'Critical',
				'score'          => 30,
				'evidence'       => array( 'eval/base64/gzinflate present in wp-config.php' ),
			);
		}

		$result['findings'] = $findings;
		return $result;
	}
}
