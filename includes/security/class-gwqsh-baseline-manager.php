<?php
/**
 * File baseline manager.
 *
 * Maintains trusted SHA-256 baselines for must-use plugins and important
 * root files. Baselines feed the Early Guardian and the scanner: unchanged
 * files never re-raise findings, modified ones escalate for review.
 *
 * Safety rule: a baseline is NEVER established while unresolved Critical
 * findings exist, because that would whitelist live malware.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * GWQSH baseline manager.
 */
final class GWQSH_Baseline_Manager {

	/**
	 * Option holding the MU plugin baseline.
	 */
	const MU_BASELINE_OPTION = 'gwqsh_mu_baseline';

	/**
	 * Option holding important root-file baselines.
	 */
	const ROOT_BASELINE_OPTION = 'gwqsh_root_baseline';

	/**
	 * Important webroot files that get baselined.
	 *
	 * @var array
	 */
	private static $root_files = array( 'index.php', '.htaccess', 'wp-config.php', 'wp-blog-header.php', 'xmlrpc.php', 'wp-cron.php', 'license.txt', 'readme.html' );

	/**
	 * Baseline state for a file.
	 *
	 * @param string $relative_key Baseline key (relative name).
	 * @param string $sha256       Current file hash.
	 * @param string $scope        'mu' or 'root'.
	 * @return string trusted|modified|new|unknown
	 */
	public static function file_state( $relative_key, $sha256, $scope = 'mu' ) {
		$baseline = self::get( $scope );
		if ( empty( $baseline ) ) {
			return 'unknown';
		}
		if ( ! isset( $baseline[ $relative_key ] ) ) {
			return 'new';
		}
		$record = $baseline[ $relative_key ];
		$hash   = is_array( $record ) && isset( $record['sha256'] ) ? $record['sha256'] : $record;
		return ( $hash === $sha256 ) ? 'trusted' : 'modified';
	}

	/**
	 * Get a baseline scope.
	 *
	 * @param string $scope 'mu' or 'root'.
	 * @return array
	 */
	public static function get( $scope ) {
		$option = ( 'root' === $scope ) ? self::ROOT_BASELINE_OPTION : self::MU_BASELINE_OPTION;
		$value  = get_option( $option, array() );
		return is_array( $value ) ? $value : array();
	}

	/**
	 * Replace a whole baseline scope (validated structure).
	 *
	 * @param string $scope  'mu' or 'root'.
	 * @param array  $records file key => array( sha256, size, mtime, recorded ).
	 * @return bool
	 */
	public static function set( $scope, $records ) {
		$option  = ( 'root' === $scope ) ? self::ROOT_BASELINE_OPTION : self::MU_BASELINE_OPTION;
		$cleaned = array();
		if ( is_array( $records ) ) {
			foreach ( $records as $key => $record ) {
				if ( ! is_string( $key ) || '' === $key || false !== strpos( $key, '/' ) ) {
					continue; // MU/top-level keys only for the mu scope.
				}
				$cleaned[ $key ] = array(
					'sha256'   => isset( $record['sha256'] ) && is_string( $record['sha256'] ) ? $record['sha256'] : '',
					'size'     => isset( $record['size'] ) ? (int) $record['size'] : 0,
					'recorded' => isset( $record['recorded'] ) ? (int) $record['recorded'] : time(),
				);
				if ( '' === $cleaned[ $key ]['sha256'] ) {
					unset( $cleaned[ $key ] );
				}
			}
		}
		if ( 'root' === $scope ) {
			update_option( $option, $cleaned, false );
		} else {
			update_option( $option, $cleaned, true ); // Small, hot path for the Guardian.
		}
		return true;
	}

	/**
	 * Establish the must-use plugin baseline from the current directory state.
	 *
	 * @param bool $force Re-baseline even when one exists.
	 * @return array|WP_Error
	 */
	public static function establish_mu_baseline( $force = false ) {
		if ( ! $force && ! empty( self::get( 'mu' ) ) ) {
			return new WP_Error( 'gwqsh_baseline_exists', 'A must-use plugin baseline already exists. Use rebuild to replace it after a clean scan.' );
		}
		if ( ! defined( 'WPMU_PLUGIN_DIR' ) || ! WPMU_PLUGIN_DIR ) {
			return new WP_Error( 'gwqsh_no_mu_dir', 'The must-use plugin directory is not available.' );
		}
		$guard = GWQSH_Path_Guard::directory( WPMU_PLUGIN_DIR );
		if ( ! $guard ) {
			return new WP_Error( 'gwqsh_no_mu_dir', 'The must-use plugin directory is not accessible.' );
		}

		// Safety gate: never baseline over unresolved Critical findings.
		if ( self::has_unresolved_critical() ) {
			return new WP_Error( 'gwqsh_baseline_blocked', 'Cannot establish a baseline while unresolved Critical findings exist. Review and resolve them first.' );
		}

		$records = array();
		$handle  = @opendir( $guard ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Defensive read of plugin-owned directory.
		if ( ! $handle ) {
			return new WP_Error( 'gwqsh_mu_scan_failed', 'Could not read the must-use plugin directory.' );
		}
		$now = time();
		while ( false !== ( $entry = readdir( $handle ) ) ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}
			$path = $guard . '/' . $entry;
			if ( ! is_file( $path ) || is_link( $path ) || '.php' !== substr( $entry, -4 ) ) {
				continue;
			}
			$read = gwqsh_guardian_core_read_file( $path, 5242880 );
			if ( ! is_array( $read ) ) {
				continue;
			}
			$records[ $entry ] = array(
				'sha256'   => $read['sha256'],
				'size'     => $read['size'],
				'recorded' => $now,
			);
		}
		closedir( $handle );

		self::set( 'mu', $records );
		return array(
			'scope'   => 'mu',
			'files'   => count( $records ),
			'when'    => $now,
		);
	}

	/**
	 * Establish the important-root-file baseline.
	 *
	 * @return array|WP_Error
	 */
	public static function establish_root_baseline() {
		if ( self::has_unresolved_critical() ) {
			return new WP_Error( 'gwqsh_baseline_blocked', 'Cannot establish a baseline while unresolved Critical findings exist. Review and resolve them first.' );
		}
		$root    = GWQSH_Path_Guard::root();
		$records = array();
		$now     = time();
		foreach ( self::$root_files as $name ) {
			$path = $root . '/' . $name;
			if ( ! is_file( $path ) || is_link( $path ) ) {
				continue;
			}
			$read = gwqsh_guardian_core_read_file( $path, 5242880 );
			if ( ! is_array( $read ) ) {
				continue;
			}
			$records[ $name ] = array(
				'sha256'   => $read['sha256'],
				'size'     => $read['size'],
				'recorded' => $now,
			);
		}
		self::set( 'root', $records );
		return array( 'scope' => 'root', 'files' => count( $records ), 'when' => $now );
	}

	/**
	 * Are there unresolved Critical scan findings?
	 *
	 * @return bool
	 */
	public static function has_unresolved_critical() {
		global $wpdb;
		$table = $wpdb->prefix . 'gwqsh_scan_results';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Validated plugin-owned table name; one-row safety gate.
		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE is_resolved = 0 AND severity = %s", 'critical' ) );
		return $count > 0;
	}

	/**
	 * Baseline scope summary for reporting.
	 *
	 * @return array
	 */
	public static function status() {
		$mu     = self::get( 'mu' );
		$root   = self::get( 'root' );
		return array(
			'mu'   => array( 'files' => count( $mu ), 'established' => ! empty( $mu ) ),
			'root' => array( 'files' => count( $root ), 'established' => ! empty( $root ) ),
		);
	}
}
