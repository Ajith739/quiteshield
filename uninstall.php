<?php
/**
 * Uninstall plugin-owned data only when the administrator enabled deletion.
 *
 * @package GracewellQuietShield
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Always stop plugin maintenance, even when preserving settings for reinstall.
foreach ( array( 'gwqsh_', 'gqs_' ) as $gwqsh_prefix ) {
	foreach ( array( 'daily_maintenance', 'scheduled_integrity_scan', 'daily_log_cleanup', 'cleanup_lockouts' ) as $gwqsh_suffix ) {
		wp_clear_scheduled_hook( $gwqsh_prefix . $gwqsh_suffix );
	}
}

// Always remove the deployed Early Guardian must-use plugin (it belongs to this
// plugin); removal is refused when the deployed copy was modified by anyone
// other than Gracewell, so unexpected content stays visible for review.
if ( defined( 'WPMU_PLUGIN_DIR' ) && WPMU_PLUGIN_DIR && is_dir( WPMU_PLUGIN_DIR ) && ! is_link( WPMU_PLUGIN_DIR ) ) {
	$gwqsh_guardian = WPMU_PLUGIN_DIR . '/000-gracewell-guardian.php';
	if ( is_file( $gwqsh_guardian ) && ! is_link( $gwqsh_guardian ) ) {
		$gwqsh_guardian_real = realpath( $gwqsh_guardian );
		$gwqsh_mu_real       = realpath( WPMU_PLUGIN_DIR );
		if ( $gwqsh_guardian_real && $gwqsh_mu_real && dirname( $gwqsh_guardian_real ) === $gwqsh_mu_real ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local plugin-owned file, never a URL.
			$gwqsh_guardian_src = file_get_contents( $gwqsh_guardian_real );
			if ( false !== $gwqsh_guardian_src && false !== strpos( $gwqsh_guardian_src, 'Gracewell Early Guardian' ) ) {
				wp_delete_file( $gwqsh_guardian_real );
			}
		}
	}
}
$gwqsh_settings = get_option( 'gwqsh_settings', get_option( 'gqs_settings', array() ) );
if ( empty( $gwqsh_settings['uninstall'] ) ) {
	return; }
global $wpdb;
foreach ( array( 'gwqsh_', 'gqs_' ) as $gwqsh_prefix ) {
	foreach ( array( 'activity_log', 'lockouts', 'scan_results', 'scan_history' ) as $gwqsh_suffix ) {
		$gwqsh_table = esc_sql( $wpdb->prefix . $gwqsh_prefix . $gwqsh_suffix );
		if ( preg_match( '/^[A-Za-z0-9_]+$/D', $gwqsh_table ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Validated plugin-owned identifier; destructive uninstall explicitly selected by administrator.
			$wpdb->query( "DROP TABLE IF EXISTS `{$gwqsh_table}`" );
		}
	}
}

// Remove only recorded random-name files, including legacy private storage.
$gwqsh_metadata = array_merge( (array) get_option( 'gqs_quarantine_metadata', array() ), (array) get_option( 'gwqsh_quarantine_metadata', array() ) );
$gwqsh_uploads  = wp_upload_dir();
$gwqsh_hash     = substr( hash_hmac( 'sha256', home_url(), wp_salt( 'auth' ) ), 0, 20 );
foreach ( array( get_temp_dir(), $gwqsh_uploads['basedir'] ) as $gwqsh_base ) {
	foreach ( array( 'gwqsh-quarantine-', 'gqs-quarantine-' ) as $gwqsh_prefix ) {
		$gwqsh_real_dir = realpath( trailingslashit( $gwqsh_base ) . $gwqsh_prefix . $gwqsh_hash );
		if ( ! $gwqsh_real_dir || is_link( trailingslashit( $gwqsh_base ) . $gwqsh_prefix . $gwqsh_hash ) ) {
			continue; }
		foreach ( array_keys( $gwqsh_metadata ) as $gwqsh_filename ) {
			if ( ! preg_match( '/^[a-f0-9]{32}\.quarantine$/D', $gwqsh_filename ) ) {
				continue; }
			$gwqsh_candidate       = $gwqsh_real_dir . DIRECTORY_SEPARATOR . $gwqsh_filename;
			$gwqsh_quarantine_path = realpath( $gwqsh_candidate );
			if ( $gwqsh_quarantine_path && dirname( $gwqsh_quarantine_path ) === $gwqsh_real_dir && is_file( $gwqsh_quarantine_path ) && ! is_link( $gwqsh_candidate ) ) {
				wp_delete_file( $gwqsh_quarantine_path ); }
		}
	}
}

// Preserve unrelated server rules; only remove our exact managed marker block.
$gwqsh_htaccess = trailingslashit( $gwqsh_uploads['basedir'] ) . '.htaccess';
if ( is_file( $gwqsh_htaccess ) && ! is_link( $gwqsh_htaccess ) && wp_is_writable( $gwqsh_htaccess ) ) {
 // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local managed .htaccess file, never a URL.
	$gwqsh_contents = file_get_contents( $gwqsh_htaccess );
	if ( false !== $gwqsh_contents ) {
		$gwqsh_cleaned = preg_replace( '/^# BEGIN Gracewell QuietShield\R.*?^# END Gracewell QuietShield\R?/ms', '', $gwqsh_contents );
		if ( $gwqsh_cleaned !== $gwqsh_contents ) {
		 // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Preserve unrelated content with an exclusive local write lock.
			file_put_contents( $gwqsh_htaccess, $gwqsh_cleaned, LOCK_EX );
		}
	}
}

foreach ( array( 'gwqsh_', 'gqs_', '_transient_gwqsh_', '_transient_gqs_', '_transient_timeout_gwqsh_', '_transient_timeout_gqs_' ) as $gwqsh_prefix ) {
 // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Uninstall discovers only plugin-owned names, then uses the option API to invalidate caches.
	$gwqsh_options = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( $gwqsh_prefix ) . '%' ) );
	foreach ( $gwqsh_options as $gwqsh_name ) {
		delete_option( $gwqsh_name ); }
}
foreach ( array( 'gwqsh_', 'gqs_' ) as $gwqsh_prefix ) {
 // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Fetch affected users for explicit metadata cache invalidation after uninstall.
	$gwqsh_users = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", $wpdb->esc_like( $gwqsh_prefix ) . '%' ) );
 // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Prefix-scoped uninstall deletion; invalidated immediately below.
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", $wpdb->esc_like( $gwqsh_prefix ) . '%' ) );
	foreach ( $gwqsh_users as $gwqsh_user_id ) {
		wp_cache_delete( (int) $gwqsh_user_id, 'user_meta' ); }
}
