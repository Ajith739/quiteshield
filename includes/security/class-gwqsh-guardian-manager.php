<?php
/**
 * Early Guardian manager (main-plugin side).
 *
 * Deploys, updates, inspects and removes the Early Guardian must-use plugin.
 * Installation copies the bundled, hash-verified template into
 * WPMU_PLUGIN_DIR; removal only deletes the file when its hash matches the
 * bundled version (a modified Guardian is surfaced for manual review).
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * GWQSH Guardian manager.
 */
final class GWQSH_Guardian_Manager {

	/**
	 * Bundled template file (inside the plugin).
	 */
	const TEMPLATE = 'mu-plugins/000-gracewell-guardian.php';

	/**
	 * Installed file name in WPMU_PLUGIN_DIR.
	 */
	const INSTALL_NAME = '000-gracewell-guardian.php';

	/**
	 * sha256 hashes of previous releases' Guardian templates. The 1.1.0
	 * Guardian is the first release, so this list starts empty; the release
	 * process appends the prior template hash whenever the template changes.
	 *
	 * @var string[]
	 */
	const LEGACY_TEMPLATE_HASHES = array();

	/**
	 * Is the Guardian installed and current?
	 *
	 * @return string 'active' | 'outdated' | 'modified' | 'absent'
	 */
	public static function status() {
		$installed = self::installed_path();
		if ( ! $installed || ! is_file( $installed ) || is_link( $installed ) ) {
			return 'absent';
		}
		$read = gwqsh_guardian_core_read_file( $installed, 524288 );
		if ( ! is_array( $read ) ) {
			return 'modified';
		}
		$template = self::template_hash();
		if ( ! is_string( $template ) ) {
			return 'active'; // Template unreadable: cannot compare; treat as active.
		}
		if ( $read['sha256'] === $template ) {
			return 'active';
		}
		// Distinguish a GENUINE older release from third-party modification.
		// Only cryptographic hashes count: header heuristics can be spoofed by
		// an attacker who keeps our banner while appending malicious code.
		// When a future release changes the template, add the previous
		// template's sha256 here so honest-but-old deployments read 'outdated'
		// and everything else keeps reading 'modified'.
		foreach ( self::LEGACY_TEMPLATE_HASHES as $legacy ) {
			if ( hash_equals( (string) $legacy, $read['sha256'] ) ) {
				return 'outdated';
			}
		}
		return 'modified';
	}

	/**
	 * Install (or refresh) the Guardian.
	 *
	 * @return array|WP_Error
	 */
	public static function install() {
		if ( ! defined( 'WPMU_PLUGIN_DIR' ) || ! WPMU_PLUGIN_DIR ) {
			return new WP_Error( 'gwqsh_no_mu_dir', 'This WordPress installation does not expose a must-use plugin directory.' );
		}
		$template_path = GWQSH_PATH . self::TEMPLATE;
		if ( ! is_file( $template_path ) || is_link( $template_path ) ) {
			return new WP_Error( 'gwqsh_no_template', 'The bundled Guardian template is missing from the plugin.' );
		}
		$read = gwqsh_guardian_core_read_file( $template_path, 524288 );
		if ( ! is_array( $read ) || '' === $read['code'] ) {
			return new WP_Error( 'gwqsh_bad_template', 'The bundled Guardian template could not be read.' );
		}

		if ( ! is_dir( WPMU_PLUGIN_DIR ) && ! wp_mkdir_p( WPMU_PLUGIN_DIR ) ) {
			return new WP_Error( 'gwqsh_mkdir_failed', 'The must-use plugin directory could not be created.' );
		}
		$guard = GWQSH_Path_Guard::directory( WPMU_PLUGIN_DIR );
		if ( ! $guard ) {
			return new WP_Error( 'gwqsh_mu_unsafe', 'The must-use plugin directory failed security validation.' );
		}

		$target = $guard . '/' . self::INSTALL_NAME;
		if ( file_exists( $target ) && ! is_file( $target ) ) {
			return new WP_Error( 'gwqsh_target_blocked', 'The Guardian destination path is occupied by a non-file entry.' );
		}
		if ( is_link( $target ) ) {
			return new WP_Error( 'gwqsh_target_symlink', 'The Guardian destination is a symbolic link; refusing to follow it.' );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Verified deployment into the must-use directory.
		if ( false === file_put_contents( $target, $read['code'], LOCK_EX ) ) {
			return new WP_Error( 'gwqsh_write_failed', 'Writing the Guardian file failed (check directory permissions).' );
		}

		$verify = gwqsh_guardian_core_read_file( $target, 524288 );
		if ( ! is_array( $verify ) || $verify['sha256'] !== $read['sha256'] ) {
			return new WP_Error( 'gwqsh_verify_failed', 'The deployed Guardian failed hash verification.' );
		}

		// Reset guardian state so the next request evaluates every MU file.
		delete_option( 'gwqsh_guardian_state' );

		GWQSH_Activity_Logger::log( 'Early Guardian installed', 'Boot-time Guardian deployed to the must-use plugin directory.', 'Security' );
		return array(
			'installed' => true,
			'sha256'    => $verify['sha256'],
			'path'      => '/wp-content/mu-plugins/' . self::INSTALL_NAME,
		);
	}

	/**
	 * Remove the Guardian (only when it matches our template or a known older
	 * version; unexpected content is reported instead of deleted).
	 *
	 * @return array|WP_Error
	 */
	public static function uninstall() {
		$installed = self::installed_path();
		if ( ! $installed || ! is_file( $installed ) ) {
			return new WP_Error( 'gwqsh_not_installed', 'The Early Guardian is not installed.' );
		}
		$status = self::status();
		if ( 'modified' === $status ) {
			return new WP_Error( 'gwqsh_modified', 'The installed Guardian does not match any Gracewell version. Review it manually before removal.' );
		}

		$real = realpath( $installed );
		$dir_real = realpath( WPMU_PLUGIN_DIR );
		if ( ! $real || ! $dir_real || is_link( $installed ) || dirname( $real ) !== $dir_real ) {
			return new WP_Error( 'gwqsh_bad_path', 'The Guardian path failed validation.' );
		}

		wp_delete_file( $real );
		delete_option( 'gwqsh_guardian_state' );

		GWQSH_Activity_Logger::log( 'Early Guardian removed', 'Boot-time Guardian removed from the must-use plugin directory.', 'Security' );
		return array( 'removed' => true );
	}

	/**
	 * Guardian overview for the admin UI.
	 *
	 * @return array
	 */
	public static function overview() {
		$status    = self::status();
		$state     = get_option( 'gwqsh_guardian_state', array() );
		$events    = get_option( 'gwqsh_guardian_events', array() );
		$baselines = GWQSH_Baseline_Manager::status();

		$files = array();
		if ( isset( $state['files'] ) && is_array( $state['files'] ) ) {
			foreach ( $state['files'] as $name => $record ) {
				$files[] = array(
					'file'    => $name,
					'verdict' => isset( $record['verdict'] ) ? $record['verdict'] : 'unknown',
				);
			}
		}

		return array(
			'status'          => $status,
			'active'          => ( 'active' === $status ),
			'files'           => $files,
			'events'          => is_array( $events ) ? array_slice( $events, 0, 10 ) : array(),
			'mu_baseline'     => $baselines['mu'],
			'root_baseline'   => $baselines['root'],
			'quarantined'     => array_values( GWQSH_Quarantine_Manager::list_quarantined() ),
		);
	}

	/**
	 * Installed path or null.
	 *
	 * @return string|null
	 */
	private static function installed_path() {
		if ( ! defined( 'WPMU_PLUGIN_DIR' ) || ! WPMU_PLUGIN_DIR || ! is_dir( WPMU_PLUGIN_DIR ) ) {
			return null;
		}
		return WPMU_PLUGIN_DIR . '/' . self::INSTALL_NAME;
	}

	/**
	 * Hash of the bundled template.
	 *
	 * @return string|null
	 */
	private static function template_hash() {
		$path = GWQSH_PATH . self::TEMPLATE;
		if ( ! is_file( $path ) || is_link( $path ) ) {
			return null;
		}
		$read = gwqsh_guardian_core_read_file( $path, 524288 );
		return is_array( $read ) ? $read['sha256'] : null;
	}
}
