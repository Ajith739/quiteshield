<?php
/**
 * BENIGN FIXTURE — a legitimate must-use plugin.
 *
 * Represents common, harmless MU-plugin behaviors: options, cron scheduling,
 * caching helpers, admin notices. The engine must NOT flag this file
 * (false-positive regression guard).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'gwqsh_fixture_mu_init' );
/**
 * Init.
 */
function gwqsh_fixture_mu_init() {
	if ( ! get_option( 'gwqsh_fixture_mu_installed_at' ) ) {
		update_option( 'gwqsh_fixture_mu_installed_at', time(), false );
	}
	if ( ! wp_next_scheduled( 'gwqsh_fixture_mu_daily' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'gwqsh_fixture_mu_daily' );
	}
}

add_action( 'gwqsh_fixture_mu_daily', 'gwqsh_fixture_mu_cleanup' );
/**
 * Cleanup.
 */
function gwqsh_fixture_mu_cleanup() {
	$logs = get_option( 'gwqsh_fixture_mu_log', array() );
	if ( is_array( $logs ) && count( $logs ) > 100 ) {
		update_option( 'gwqsh_fixture_mu_log', array_slice( $logs, -50 ), false );
	}
}

add_action( 'admin_notices', 'gwqsh_fixture_mu_notice' );
/**
 * Notice.
 */
function gwqsh_fixture_mu_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$when = (int) get_option( 'gwqsh_fixture_mu_installed_at', 0 );
	if ( $when && ( time() - $when ) < DAY_IN_SECONDS ) {
		echo '<div class="notice notice-info"><p>Fixture MU plugin active since ' . esc_html( gmdate( 'Y-m-d', $when ) ) . '.</p></div>';
	}
}
