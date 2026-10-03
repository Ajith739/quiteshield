<?php
/**
 * Real WordPress security hardening modules for Gracewell QuietShield.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * GWQSH Hardening implementation.
 */
final class GWQSH_Hardening {

	private const OPTION_KEY = 'gwqsh_hardening';
	/**
	 * Get defaults.
	 */
	public static function get_defaults() {
		return array(
			'xmlrpc'  => true,
			'editor'  => true,
			'version' => true,
			'enum'    => true,
			'headers' => false,
			'uploads' => true,
		);
	}
	/**
	 * Get all.
	 */
	public static function get_all() {
		$saved = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, self::get_defaults() );
	}
	/**
	 * Get checks.
	 */
	public static function get_checks() {
		$checks = self::get_all();
		if ( ! GWQSH_Settings::is_active() ) {
			return array_fill_keys( array_keys( self::get_defaults() ), false ); }
		$uploads           = wp_upload_dir();
		$rules             = trailingslashit( $uploads['basedir'] ) . '.htaccess';
		$contents          = is_readable( $rules ) ? file_get_contents( $rules ) : '';
		$checks['uploads'] = ! empty( $checks['uploads'] ) && false !== strpos( (string) $contents, '# BEGIN Gracewell QuietShield' ) && false !== strpos( (string) $contents, 'Require all denied' );
		return array_intersect_key( $checks, self::get_defaults() );
	}
	/**
	 * Get risk.
	 */
	public static function get_risk() {
		$weights = array(
			'xmlrpc'  => 15,
			'editor'  => 15,
			'version' => 10,
			'enum'    => 15,
			'headers' => 33,
			'uploads' => 12,
		);
		$checks  = self::get_checks();
		$score   = 0;
		foreach ( $weights as $key => $weight ) {
			if ( ! empty( $checks[ $key ] ) ) {
				$score += $weight; }
		}
		return array(
			'score'  => $score,
			'passed' => count( array_filter( $checks ) ),
			'failed' => count( $checks ) - count( array_filter( $checks ) ),
			'level'  => $score >= 85 ? 'Low' : ( $score >= 60 ? 'Medium' : ( $score >= 35 ? 'High' : 'Critical' ) ),
		);
	}
	/**
	 * Is enabled.
	 *
	 * @param mixed $feature Feature.
	 */
	public static function is_enabled( $feature ) {
		$all = self::get_all();
		return ! empty( $all[ $feature ] );
	}
	/**
	 * Set.
	 *
	 * @param mixed $feature Feature.
	 * @param mixed $enabled Enabled.
	 */
	public static function set( $feature, $enabled ) {
		if ( ! array_key_exists( $feature, self::get_defaults() ) ) {
			return false; }
		if ( 'uploads' === $feature && ! self::sync_uploads_protection( (bool) $enabled ) ) {
			return false; }
		$all             = self::get_all();
		$all[ $feature ] = (bool) $enabled;
		update_option( self::OPTION_KEY, $all );

		$names = array(
			'xmlrpc'  => 'Disable XML-RPC',
			'editor'  => 'Disable file editor',
			'version' => 'Hide WordPress version',
			'enum'    => 'Block user enumeration',
			'headers' => 'Security headers',
			'uploads' => 'Block PHP in uploads',
		);
		$name  = isset( $names[ $feature ] ) ? $names[ $feature ] : $feature;
		GWQSH_Activity_Logger::log(
			$enabled ? 'Hardening enabled' : 'Hardening disabled',
			sprintf( '%s was turned %s', $name, $enabled ? 'on' : 'off' ),
			'System'
		);

		return true;
	}
	/**
	 * Reset defaults.
	 */
	public static function reset_defaults() {
		if ( ! self::sync_uploads_protection( true ) ) {
			return false; }
		update_option( self::OPTION_KEY, self::get_defaults() );
		GWQSH_Activity_Logger::log( 'Hardening reset', 'Hardening options restored to default values', 'System' );
		return true;
	}
	/**
	 * Revert all.
	 */
	public static function revert_all() {
		if ( ! self::sync_uploads_protection( false ) ) {
			return false; }
		$off = array(
			'xmlrpc'  => false,
			'editor'  => false,
			'version' => false,
			'enum'    => false,
			'headers' => false,
			'uploads' => false,
		);
		update_option( self::OPTION_KEY, $off );
		GWQSH_Activity_Logger::log( 'Hardening reverted', 'All hardening features disabled', 'System' );
		return true;
	}
	/**
	 * Init.
	 */
	public static function init() {
		if ( ! GWQSH_Settings::is_active() ) {
			return;
		}

		// 1. Disable XML-RPC
		if ( self::is_enabled( 'xmlrpc' ) ) {
			add_filter( 'xmlrpc_enabled', '__return_false' );
			add_filter( 'wp_headers', array( __CLASS__, 'remove_pingback_header' ) );
			add_action( 'init', array( __CLASS__, 'block_xmlrpc_requests' ), 1 );
		}

		// 2. Disable file editor
		if ( self::is_enabled( 'editor' ) ) {
			if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Native WordPress core configuration constant; changing its name would disable this protection.
				define( 'DISALLOW_FILE_EDIT', true );
			}
			add_filter( 'user_has_cap', array( __CLASS__, 'remove_editor_caps' ), 10, 3 );
		}

		// 3. Hide WordPress version
		if ( self::is_enabled( 'version' ) ) {
			add_filter( 'the_generator', '__return_empty_string' );
			add_filter( 'script_loader_src', array( __CLASS__, 'remove_version_param' ), 99 );
			add_filter( 'style_loader_src', array( __CLASS__, 'remove_version_param' ), 99 );
		}

		// 4. Block user enumeration
		if ( self::is_enabled( 'enum' ) ) {
			add_action( 'template_redirect', array( __CLASS__, 'block_author_scan' ), 1 );
			add_filter( 'rest_endpoints', array( __CLASS__, 'restrict_rest_users' ) );
		}

		// 5. Security headers
		if ( self::is_enabled( 'headers' ) ) {
			add_action( 'send_headers', array( __CLASS__, 'send_security_headers' ) );
		}

		// 6. Block PHP in uploads
		if ( self::is_enabled( 'uploads' ) ) {
			add_action( 'init', array( __CLASS__, 'check_uploads_php_request' ), 1 );
		}
	}

	/*
	-------------------------------------------------------------
		Feature Hook Callbacks
		-------------------------------------------------------------
	 */
	/**
	 * Remove pingback header.
	 *
	 * @param mixed $headers Headers.
	 */
	public static function remove_pingback_header( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}
	/**
	 * Block xmlrpc requests.
	 */
	public static function block_xmlrpc_requests() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		if ( false !== strpos( $uri, 'xmlrpc.php' ) ) {
			status_header( 403 );
			exit( 'XML-RPC is disabled on this site.' );
		}
	}
	/**
	 * Remove editor caps.
	 *
	 * @param mixed $allcaps Allcaps.
	 * @param mixed $caps Caps.
	 * @param mixed $args Args.
	 */
	public static function remove_editor_caps( $allcaps, $caps, $args ) {
		$allcaps['edit_themes']  = false;
		$allcaps['edit_plugins'] = false;
		$allcaps['edit_files']   = false;
		return $allcaps;
	}
	/**
	 * Remove version param.
	 *
	 * @param mixed $src Src.
	 */
	public static function remove_version_param( $src ) {
		if ( strpos( $src, 'ver=' . get_bloginfo( 'version' ) ) ) {
			$src = remove_query_arg( 'ver', $src );
		}
		return $src;
	}
	/**
	 * Block author scan.
	 */
	public static function block_author_scan() {
		if ( is_admin() || is_user_logged_in() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only public author-enumeration routing check; no settings or data are changed.
		if ( isset( $_REQUEST['author'] ) && is_numeric( $_REQUEST['author'] ) ) {
			wp_safe_redirect( home_url(), 301 );
			exit;
		}

		if ( is_author() ) {
			wp_safe_redirect( home_url(), 301 );
			exit;
		}
	}
	/**
	 * Restrict rest users.
	 *
	 * @param mixed $endpoints Endpoints.
	 */
	public static function restrict_rest_users( $endpoints ) {
		if ( is_user_logged_in() ) {
			return $endpoints;
		}

		if ( isset( $endpoints['/wp/v2/users'] ) ) {
			unset( $endpoints['/wp/v2/users'] );
		}
		if ( isset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] ) ) {
			unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
		}

		return $endpoints;
	}
	/**
	 * Send security headers.
	 */
	public static function send_security_headers() {
		if ( headers_sent() ) {
			return;
		}

		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Permissions-Policy: camera=(), microphone=()' );
		header( 'Content-Security-Policy: upgrade-insecure-requests' );
	}
	/**
	 * Sync uploads protection.
	 *
	 * @param mixed $enable Enable.
	 */
	public static function sync_uploads_protection( $enable ) {
		$upload_dir = wp_upload_dir();
		$basedir    = $upload_dir['basedir'];
		if ( ! is_dir( $basedir ) ) {
			return false;
		}

		$htaccess_file = trailingslashit( $basedir ) . '.htaccess';
		if ( ! $enable && ! file_exists( $htaccess_file ) ) {
			return true; }
		if ( file_exists( $htaccess_file ) && ! wp_is_writable( $htaccess_file ) ) {
			return false; }
		$content = file_exists( $htaccess_file ) ? file_get_contents( $htaccess_file ) : '';
		if ( false === $content ) {
			return false; }
		$content = preg_replace( '/^# BEGIN (?:Gracewell QuietShield|QuietShield Block PHP in Uploads)\R.*?^# END (?:Gracewell QuietShield|QuietShield Block PHP in Uploads)\R?/ms', '', $content );
		if ( $enable ) {
			$content = rtrim( $content ) . "\n# BEGIN Gracewell QuietShield\n<FilesMatch \"\\.(?:php[0-9]?|phtml|phar)$\">\nRequire all denied\n</FilesMatch>\n# END Gracewell QuietShield\n";
		}
		return false !== file_put_contents( $htaccess_file, $content, LOCK_EX );
	}
	/**
	 * Check uploads php request.
	 */
	public static function check_uploads_php_request() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		if ( preg_match( '#/wp-content/uploads/.*\.php#i', $uri ) ) {
			status_header( 403 );
			exit( 'Execution of PHP files in uploads directory is blocked.' );
		}
	}
}
