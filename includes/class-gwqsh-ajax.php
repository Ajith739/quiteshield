<?php
/**
 * AJAX controller for Gracewell QuietShield.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * GWQSH Ajax implementation.
 */
final class GWQSH_Ajax {
	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'wp_ajax_gwqsh_action', array( __CLASS__, 'handle_request' ) );
	}
	/**
	 * Handle request.
	 */
	public static function handle_request() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Shape validation only; the central nonce/capability checks immediately below authorize dispatch.
		foreach ( $_POST as $value ) {
			if ( ! is_scalar( $value ) ) {
				wp_send_json_error( array( 'message' => 'Invalid request format.' ), 400 ); }
		}
		if ( false === check_ajax_referer( 'gwqsh_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Your session expired. Reload the page and try again.' ), 403 ); }
		$subaction = isset( $_POST['subaction'] ) ? sanitize_key( wp_unslash( $_POST['subaction'] ) ) : '';

		if ( ! current_user_can( 'manage_options' ) && ! ( current_user_can( 'read' ) && in_array( $subaction, GWQSH_Two_Factor::self_service_actions(), true ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'gracewell-quietshield' ) ), 403 );
		}

		switch ( $subaction ) {
			case 'save_2fa_settings':
				self::save_2fa_settings();
				break;
			case 'disable_own_2fa':
				self::disable_own_2fa();
				break;
			case 'require_user_2fa':
				self::require_user_2fa();
				break;
			// Dashboard.
			case 'get_dashboard_data':
				self::get_dashboard_data();
				break;

			// Login Protection.
			case 'get_login_data':
				self::get_login_data();
				break;
			case 'toggle_login_protection':
				self::toggle_login_protection();
				break;
			case 'save_login_settings':
				self::save_login_settings();
				break;
			case 'unlock_ip':
				self::unlock_ip();
				break;
			case 'add_allow_ip':
				self::add_allow_ip();
				break;
			case 'remove_allow_ip':
				self::remove_allow_ip();
				break;
			case 'clear_allowlist':
				self::clear_allowlist();
				break;
			case 'save_login_slug':
				self::save_login_slug();
				break;

			// Two-Factor.
			case 'get_2fa_data':
				self::get_2fa_data();
				break;
			case 'generate_2fa_secret':
				self::generate_2fa_secret();
				break;
			case 'generate_backup_codes':
				self::generate_backup_codes();
				break;
			case 'confirm_2fa_secret':
				self::confirm_2fa_secret();
				break;
			case 'enable_user_2fa':
				self::enable_user_2fa();
				break;
			case 'reset_user_2fa':
				self::reset_user_2fa();
				break;
			case 'remove_trusted_device':
				self::remove_trusted_device();
				break;

			// File Integrity.
			case 'get_scan_data':
				self::get_scan_data();
				break;
			case 'run_scan':
				self::run_scan();
				break;
			case 'restore_file':
				self::restore_file();
				break;
			case 'quarantine_file':
				self::quarantine_file();
				break;
			case 'get_diff':
				self::get_diff();
				break;
			case 'mark_reviewed':
				self::mark_reviewed();
				break;
			case 'guardian_status':
				self::guardian_status();
				break;
			case 'guardian_install':
				self::guardian_install();
				break;
			case 'guardian_uninstall':
				self::guardian_uninstall();
				break;
			case 'restore_quarantine':
				self::restore_quarantine();
				break;
			case 'establish_baseline':
				self::establish_baseline();
				break;

			// Hardening.
			case 'get_hardening_data':
				self::get_hardening_data();
				break;
			case 'toggle_hardening':
				self::toggle_hardening();
				break;
			case 'reset_hardening':
				self::reset_hardening();
				break;
			case 'revert_hardening':
				self::revert_hardening();
				break;

			// Activity Log.
			case 'get_activity_logs':
				self::get_activity_logs();
				break;
			case 'clear_activity_logs':
				self::clear_activity_logs();
				break;

			// Settings.
			case 'save_settings':
				self::save_settings();
				break;
			case 'reset_settings':
				self::reset_settings();
				break;
			case 'rebuild_baseline':
				self::rebuild_baseline();
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Unknown subaction.', 'gracewell-quietshield' ) ), 400 );
				break;
		}
	}

	/*
	-------------------------------------------------------------
		Dashboard handlers
		-------------------------------------------------------------
	 */
	/**
	 * Get dashboard data.
	 */
	private static function get_dashboard_data() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$failed_24h = GWQSH_Login_Protection::get_failed_attempts_24h();
		$locked_ips = GWQSH_Login_Protection::get_currently_locked_ips();
		$allow_ips  = GWQSH_Login_Protection::get_allowlist();

		$all_users    = GWQSH_Two_Factor::get_all_users_2fa_list();
		$users_count  = count( $all_users );
		$users_2fa_on = 0;
		foreach ( $all_users as $u ) {
			if ( 'Enabled' === $u['st'] ) {
				++$users_2fa_on;
			}
		}

		$scan_summary       = GWQSH_File_Integrity::get_summary();
		$hardening          = GWQSH_Hardening::get_checks();
		$hard_enabled_count = count( array_filter( $hardening ) );

		$logs_result = GWQSH_Activity_Logger::query_logs(
			array(
				'limit' => 6,
				'days'  => 30,
			)
		);

		// Calculate comprehensive security score (0 - 100).
		$score = self::calculate_security_score(
			array(
				'login'     => GWQSH_Login_Protection::get_settings()['enabled'] ? 90 : 30,
				'tfa'       => $users_count > 0 ? round( ( $users_2fa_on / $users_count ) * 100 ) : 0,
				'file'      => $scan_summary['total'] > 0 ? ( 0 === ( $scan_summary['modified'] + $scan_summary['suspicious'] ) ? 100 : 60 ) : 0,
				'hardening' => round( ( $hard_enabled_count / 6 ) * 100 ),
				'activity'  => 0,
			)
		);

		$chart_days = isset( $_POST['days'] ) ? absint( $_POST['days'] ) : 7;
		$chart_data = self::get_dashboard_chart_data( $chart_days );
		$components = GWQSH_Admin::get_score_components();
		$score      = GWQSH_Admin::score_from_components( $components );

		wp_send_json_success(
			array(
				'failed_24h'       => $failed_24h,
				'locked_ips'       => $locked_ips,
				'allow_ips'        => $allow_ips,
				'users_total'      => $users_count,
				'users_2fa'        => $users_2fa_on,
				'scan_summary'     => $scan_summary,
				'hardening'        => $hardening,
				'hardening_on'     => $hard_enabled_count,
				'security_score'   => $score,
				'recent_activity'  => $logs_result['items'],
				'total_events'     => $logs_result['total'],
				'client_ip'        => GWQSH_Settings::get_client_ip(),
				'chart_data'       => $chart_data,
				'score_components' => $components,
			)
		);
	}
	/**
	 * Calculate security score.
	 *
	 * @param array $weights Weights.
	 */
	private static function calculate_security_score( array $weights ) {
		$score = (
			$weights['login'] * 0.20 +
			$weights['tfa'] * 0.25 +
			$weights['file'] * 0.15 +
			$weights['hardening'] * 0.25 +
			$weights['activity'] * 0.15
		);
		return (int) round( min( 100, max( 0, $score ) ) );
	}
	/**
	 * Get dashboard chart data.
	 *
	 * @param mixed $days Days.
	 */
	private static function get_dashboard_chart_data( $days = 7 ) {
		global $wpdb;
		$table_activity = esc_sql( $wpdb->prefix . 'gwqsh_activity_log' );
		$days           = max( 1, min( 30, absint( $days ) ) );
		$cutoff         = gmdate( 'Y-m-d 00:00:00', strtotime( "-{$days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Identifier is the trusted WordPress prefix plus a fixed plugin table; %i requires WordPress 6.2.
		$rows = $wpdb->get_results(
			// phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter -- Fixed plugin table using trusted WordPress prefix; %i is unavailable on WordPress 5.3.
			$wpdb->prepare(
				"SELECT DATE(event_time) as dt, event_name, COUNT(*) as c FROM {$table_activity} WHERE event_time >= %s GROUP BY DATE(event_time), event_name", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Validated or fixed plugin-owned identifier; dynamic values are prepared, and WordPress 5.3 has no identifier placeholder.
				$cutoff
			),
			ARRAY_A
		);

		$date_map = array();
		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$d_str              = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
			$lbl                = gmdate( 'M j', strtotime( "-{$i} days" ) );
			$date_map[ $d_str ] = array(
				'label'  => $lbl,
				'failed' => 0,
				'files'  => 0,
			);
		}

		if ( $rows ) {
			foreach ( $rows as $r ) {
				$dt = $r['dt'];
				if ( isset( $date_map[ $dt ] ) ) {
					if ( 'Login attempt blocked' === $r['event_name'] || 'IP locked out' === $r['event_name'] ) {
						$date_map[ $dt ]['failed'] += (int) $r['c'];
					} elseif ( false !== strpos( $r['event_name'], 'file' ) || false !== strpos( $r['event_name'], 'Scan' ) ) {
						$date_map[ $dt ]['files'] += (int) $r['c'];
					}
				}
			}
		}

		$labels = array();
		$failed = array();
		$files  = array();

		foreach ( $date_map as $item ) {
			$labels[] = $item['label'];
			$failed[] = $item['failed'];
			$files[]  = $item['files'];
		}

		return array(
			'labels' => $labels,
			'failed' => $failed,
			'files'  => $files,
		);
	}

	/*
	-------------------------------------------------------------
		Login Protection handlers
		-------------------------------------------------------------
	 */
	/**
	 * Get login data.
	 */
	private static function get_login_data() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$settings   = GWQSH_Login_Protection::get_settings();
		$locked_ips = GWQSH_Login_Protection::get_currently_locked_ips();
		$allow_ips  = GWQSH_Login_Protection::get_allowlist();
		$failed_24h = GWQSH_Login_Protection::get_failed_attempts_24h();
		$days       = isset( $_POST['days'] ) ? absint( $_POST['days'] ) : 7;
		$chart      = GWQSH_Login_Protection::get_activity_chart_data( $days );

		wp_send_json_success(
			array(
				'settings'     => $settings,
				'locked_ips'   => $locked_ips,
				'allow_ips'    => $allow_ips,
				'failed_24h'   => $failed_24h,
				'chart'        => $chart,
				'recovery_url' => GWQSH_Login_Protection::recovery_url(),
				'client_ip'    => GWQSH_Settings::get_client_ip(),
			)
		);
	}
	/**
	 * Toggle login protection.
	 */
	private static function toggle_login_protection() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$on = ! empty( $_POST['on'] );
		GWQSH_Login_Protection::update_settings( array( 'enabled' => $on ) );
		GWQSH_Activity_Logger::log(
			$on ? 'Settings changed' : 'Setting disabled',
			'Brute-force login protection ' . ( $on ? 'enabled' : 'paused' ),
			'Security'
		);
		wp_send_json_success( array( 'enabled' => $on ) );
	}
	/**
	 * Save login settings.
	 */
	private static function save_login_settings() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$settings = array(
			'max_attempts'     => isset( $_POST['max_attempts'] ) ? absint( $_POST['max_attempts'] ) : 5,
			'time_window'      => isset( $_POST['time_window'] ) ? absint( $_POST['time_window'] ) : 15,
			'lockout_duration' => isset( $_POST['lockout_duration'] ) ? absint( $_POST['lockout_duration'] ) : 30,
			'repeat_lockouts'  => isset( $_POST['repeat_lockouts'] ) ? absint( $_POST['repeat_lockouts'] ) : 4,
			'repeat_duration'  => isset( $_POST['repeat_duration'] ) ? absint( $_POST['repeat_duration'] ) : 24,
		);
		GWQSH_Login_Protection::update_settings( $settings );
		GWQSH_Activity_Logger::log( 'Settings changed', 'Login protection lockout parameters updated', 'System' );
		wp_send_json_success( array( 'settings' => GWQSH_Login_Protection::get_settings() ) );
	}
	/**
	 * Unlock ip.
	 */
	private static function unlock_ip() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$ip = isset( $_POST['ip'] ) ? sanitize_text_field( wp_unslash( $_POST['ip'] ) ) : '';
		if ( empty( $ip ) ) {
			wp_send_json_error( array( 'message' => 'Invalid IP' ) );
		}
		GWQSH_Login_Protection::unlock_ip( $ip );
		wp_send_json_success( array( 'ip' => $ip ) );
	}
	/**
	 * Add allow ip.
	 */
	private static function add_allow_ip() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$ip = isset( $_POST['ip'] ) ? sanitize_text_field( wp_unslash( $_POST['ip'] ) ) : '';
		if ( empty( $ip ) ) {
			wp_send_json_error( array( 'message' => 'Please enter an IP address.' ) );
		}
		$res = GWQSH_Login_Protection::add_to_allowlist( $ip );
		if ( $res ) {
			wp_send_json_success( array( 'allow_ips' => GWQSH_Login_Protection::get_allowlist() ) );
		} else {
			wp_send_json_error( array( 'message' => 'IP is already in allowlist.' ) );
		}
	}
	/**
	 * Remove allow ip.
	 */
	private static function remove_allow_ip() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$ip = isset( $_POST['ip'] ) ? sanitize_text_field( wp_unslash( $_POST['ip'] ) ) : '';
		GWQSH_Login_Protection::remove_from_allowlist( $ip );
		wp_send_json_success( array( 'allow_ips' => GWQSH_Login_Protection::get_allowlist() ) );
	}
	/**
	 * Clear allowlist.
	 */
	private static function clear_allowlist() {
		GWQSH_Login_Protection::clear_allowlist();
		wp_send_json_success( array( 'allow_ips' => GWQSH_Login_Protection::get_allowlist() ) );
	}
	/**
	 * Save login slug.
	 */
	private static function save_login_slug() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$slug    = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : GWQSH_Login_Protection::get_settings()['custom_slug'];
		$enabled = isset( $_POST['enabled'] ) ? '1' === sanitize_text_field( wp_unslash( $_POST['enabled'] ) ) : true;
		if ( ! GWQSH_Login_Protection::valid_slug( $slug ) ) {
			wp_send_json_error( array( 'message' => 'Use 3–40 lowercase letters, numbers or hyphens. Reserved routes and existing page slugs are not allowed.' ), 400 );
		}
		GWQSH_Login_Protection::update_settings(
			array(
				'custom_slug'         => $slug,
				'custom_slug_enabled' => $enabled,
			)
		);
		$recovery_url = GWQSH_Login_Protection::rotate_recovery_key();
		GWQSH_Activity_Logger::log( 'Settings changed', "Custom login URL set to /{$slug}", 'Security' );
		wp_send_json_success(
			array(
				'enabled'      => $enabled,
				'message'      => $enabled ? 'Custom Login URL enabled' : 'Custom Login URL disabled',
				'slug'         => $slug,
				'login_url'    => GWQSH_Login_Protection::custom_login_url( $slug ),
				'recovery_url' => $recovery_url,
			)
		);
	}

	/*
	-------------------------------------------------------------
		Two-Factor handlers
		-------------------------------------------------------------
	 */
	/**
	 * Get 2fa data.
	 */
	private static function get_2fa_data() {
		$current_user = wp_get_current_user();
		$secret       = GWQSH_Two_Factor::get_provisioning_secret( $current_user->ID );
		$uri          = $secret ? GWQSH_Two_Factor::get_totp_uri( $current_user->user_login, $secret ) : '';
		$backup_codes = GWQSH_Two_Factor::get_user_backup_codes( $current_user->ID );
		$users        = GWQSH_Two_Factor::get_all_users_2fa_list();
		$devices      = GWQSH_Two_Factor::get_trusted_devices( $current_user->ID );

		wp_send_json_success(
			array(
				'settings'          => GWQSH_Two_Factor::get_settings(),
				'effective_enabled' => GWQSH_Two_Factor::effective_enabled( $current_user->ID ),
				'secret'            => $secret,
				'qr_uri'            => $uri,
				'backup_codes'      => $backup_codes,
				'users'             => $users,
				'devices'           => $devices,
				'is_enabled'        => GWQSH_Two_Factor::is_user_2fa_enabled( $current_user->ID ),
			)
		);
	}
	/**
	 * Generate 2fa secret.
	 */
	private static function generate_2fa_secret() {
		$current_user = wp_get_current_user();
		$new_secret   = GWQSH_Two_Factor::generate_secret();
		$enabled      = GWQSH_Two_Factor::is_user_2fa_enabled( $current_user->ID );
		$encrypted    = GWQSH_Crypto::encrypt( $new_secret );
		$saved        = $enabled ? ( false !== $encrypted && false !== update_user_meta( $current_user->ID, 'gwqsh_2fa_pending_secret', $encrypted ) ) : GWQSH_Two_Factor::save_user_secret( $current_user->ID, $new_secret );
		if ( ! $saved ) {
			wp_send_json_error( array( 'message' => 'Secure secret storage is unavailable.' ), 500 );
		}
		$uri = GWQSH_Two_Factor::get_totp_uri( $current_user->user_login, $new_secret );
		GWQSH_Activity_Logger::log( '2FA setup started', 'Authenticator QR code generated', 'Security', $current_user->user_login );
		wp_send_json_success(
			array(
				'secret'  => $new_secret,
				'qr_uri'  => $uri,
				'pending' => $enabled,
			)
		);
	}
	/**
	 * Confirm 2fa secret.
	 */
	private static function confirm_2fa_secret() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$user_id = get_current_user_id();
		$pending = GWQSH_Crypto::decrypt( get_user_meta( $user_id, 'gwqsh_2fa_pending_secret', true ) );
		$code    = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
		$slice   = GWQSH_Two_Factor::matching_totp_slice( $pending, $code );
		if ( false === $slice || ! GWQSH_Two_Factor::save_user_secret( $user_id, $pending ) ) {
			wp_send_json_error( array( 'message' => 'Invalid authenticator code.' ), 400 );
		}
		delete_user_meta( $user_id, 'gwqsh_2fa_pending_secret' );
		update_user_meta( $user_id, 'gwqsh_2fa_last_slice', $slice );
		delete_user_meta( $user_id, 'gwqsh_trusted_devices' );
		GWQSH_Activity_Logger::log( '2FA secret regenerated', 'Authenticator secret replaced after verification', 'Security' );
		wp_send_json_success();
	}
	/**
	 * Generate backup codes.
	 */
	private static function generate_backup_codes() {
		$current_user = wp_get_current_user();
		if ( ! GWQSH_Two_Factor::is_user_2fa_enabled( $current_user->ID ) ) {
			wp_send_json_error( array( 'message' => 'Verify your authenticator setup before generating backup codes.' ), 400 ); }
		$new_codes = GWQSH_Two_Factor::generate_backup_codes();
		$records   = GWQSH_Two_Factor::save_user_backup_codes( $current_user->ID, $new_codes );
		if ( false === $records ) {
			wp_send_json_error( array( 'message' => 'Could not save backup codes.' ), 500 ); }
		GWQSH_Activity_Logger::log( 'Backup codes regenerated', '10 new backup codes generated', 'Security', $current_user->user_login );
		wp_send_json_success( array( 'backup_codes' => $records ) );
	}
	/**
	 * Enable user 2fa.
	 */
	private static function enable_user_2fa() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : get_current_user_id();
		if ( get_current_user_id() !== $user_id ) {
			wp_send_json_error( array( 'message' => 'Users must enroll their own authenticator.' ), 403 );
		}
		if ( ! current_user_can( 'manage_options' ) && empty( GWQSH_Two_Factor::get_settings()['allow_roles'] ) && ! GWQSH_Two_Factor::required( $user_id ) ) {
			wp_send_json_error( array( 'message' => 'Optional enrollment for other roles is disabled.' ), 403 ); }
		$code   = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
		$secret = GWQSH_Two_Factor::get_user_secret( $user_id );
		$slice  = GWQSH_Two_Factor::matching_totp_slice( $secret, $code );
		if ( false === $slice ) {
			wp_send_json_error( array( 'message' => 'Enter a valid authenticator code to enable 2FA.' ), 400 );
		}
		if ( ! GWQSH_Two_Factor::enable_user_2fa( $user_id, $secret ) ) {
			wp_send_json_error( array( 'message' => 'Could not enable 2FA securely.' ), 500 );
		}
		update_user_meta( $user_id, 'gwqsh_2fa_last_slice', $slice );
		wp_send_json_success( array( 'users' => GWQSH_Two_Factor::get_all_users_2fa_list() ) );
	}
	/**
	 * Reset user 2fa.
	 */
	private static function reset_user_2fa() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		if ( ! $user_id || ! get_userdata( $user_id ) || ! current_user_can( 'edit_user', $user_id ) ) {
			wp_send_json_error( array( 'message' => 'Invalid user ID' ) );
		}
		if ( get_current_user_id() === $user_id ) {
			wp_send_json_error( array( 'message' => 'Self-service reset requires identity verification.' ), 403 );
		}
		GWQSH_Two_Factor::reset_user_2fa( $user_id );
		wp_send_json_success( array( 'users' => GWQSH_Two_Factor::get_all_users_2fa_list() ) );
	}
	/**
	 * Save 2fa settings.
	 */
	private static function save_2fa_settings() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$settings = GWQSH_Two_Factor::get_settings();
		foreach ( $settings as $key => $value ) {
			if ( isset( $_POST[ $key ] ) ) {
				$settings[ $key ] = '1' === sanitize_text_field( wp_unslash( $_POST[ $key ] ) ); }
		}
		update_option( 'gwqsh_two_factor_settings', $settings, false );
		wp_send_json_success(
			array(
				'settings' => GWQSH_Two_Factor::get_settings(),
				'message'  => '2FA settings saved successfully.',
			)
		);
	}
	/**
	 * Disable own 2fa.
	 */
	private static function disable_own_2fa() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$id = get_current_user_id();
		if ( GWQSH_Two_Factor::required( $id ) ) {
			wp_send_json_error( array( 'message' => 'Your account is required to use 2FA. Ask an administrator to change the requirement.' ), 400 ); }
		$code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
		if ( GWQSH_Two_Factor::is_user_2fa_enabled( $id ) && ! GWQSH_Two_Factor::verify_totp( GWQSH_Two_Factor::get_user_secret( $id ), $code ) && ! GWQSH_Two_Factor::verify_and_burn_backup_code( $id, $code ) ) {
			wp_send_json_error( array( 'message' => 'Enter a valid authentication or backup code.' ), 400 );
		}
		GWQSH_Two_Factor::reset_user_2fa( $id );
		wp_send_json_success( array( 'message' => '2FA disabled successfully.' ) );
	}
	/**
	 * Require user 2fa.
	 */
	private static function require_user_2fa() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		if ( ! $id || ! get_userdata( $id ) || ! current_user_can( 'edit_user', $id ) ) {
			wp_send_json_error( array( 'message' => 'You cannot manage this user.' ), 403 ); }
		update_user_meta( $id, 'gwqsh_2fa_required', ! empty( $_POST['required'] ) ? 1 : 0 );
		wp_send_json_success(
			array(
				'users'   => GWQSH_Two_Factor::get_all_users_2fa_list(),
				'message' => 'User 2FA requirement saved.',
			)
		);
	}
	/**
	 * Remove trusted device.
	 */
	private static function remove_trusted_device() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$index = isset( $_POST['index'] ) ? absint( $_POST['index'] ) : 0;
		GWQSH_Two_Factor::remove_trusted_device( get_current_user_id(), $index );
		wp_send_json_success( array( 'devices' => GWQSH_Two_Factor::get_trusted_devices( get_current_user_id() ) ) );
	}



	/*
	-------------------------------------------------------------
		File Integrity handlers
		-------------------------------------------------------------
	 */
	/**
	 * Get scan data.
	 */
	private static function get_scan_data() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$filters = array(
			'type'   => isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '',
			'status' => isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '',
			'search' => isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '',
		);
		$summary = GWQSH_File_Integrity::get_summary();
		$issues  = GWQSH_File_Integrity::get_scan_issues( $filters );

		wp_send_json_success(
			array(
				'summary' => $summary,
				'issues'  => $issues,
				'history' => GWQSH_File_Integrity::get_scan_history(),
			)
		);
	}
	/**
	 * Run scan.
	 */
	private static function run_scan() {
		$lock = get_option( 'gwqsh_scan_lock', 0 );
		if ( $lock && $lock < time() - 300 ) {
			delete_option( 'gwqsh_scan_lock' ); }
		if ( ! add_option( 'gwqsh_scan_lock', time(), '', false ) ) {
			wp_send_json_error( array( 'message' => 'A scan is already running. Please wait.' ), 409 ); }
		try {
			$result = GWQSH_File_Integrity::run_full_scan(); } catch ( Throwable $e ) {
			$result = new WP_Error( 'scan_failed', 'The scan could not read all directories. Check permissions and try again.' ); } finally {
				delete_option( 'gwqsh_scan_lock' ); }
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ), 503 ); }
			$summary = GWQSH_File_Integrity::get_summary();
			$issues  = GWQSH_File_Integrity::get_scan_issues();
			wp_send_json_success(
				array(
					'result'  => $result,
					'summary' => $summary,
					'issues'  => $issues,
					'history' => GWQSH_File_Integrity::get_scan_history(),
				)
			);
	}
	/**
	 * Restore file.
	 */
	private static function restore_file() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$path = isset( $_POST['path'] ) ? sanitize_text_field( wp_unslash( $_POST['path'] ) ) : '';
		if ( empty( $path ) ) {
			wp_send_json_error( array( 'message' => 'Path missing.' ) );
		}
		$res = GWQSH_File_Integrity::restore_core_file( $path );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ) );
		}
		wp_send_json_success(
			array(
				'summary' => GWQSH_File_Integrity::get_summary(),
				'issues'  => GWQSH_File_Integrity::get_scan_issues(),
			)
		);
	}
	/**
	 * Quarantine file.
	 */
	private static function quarantine_file() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$path = isset( $_POST['path'] ) ? sanitize_text_field( wp_unslash( $_POST['path'] ) ) : '';
		if ( empty( $path ) ) {
			wp_send_json_error( array( 'message' => 'Path missing.' ) );
		}
		if ( ! GWQSH_File_Integrity::quarantine_file( $path ) ) {
			wp_send_json_error( array( 'message' => 'Could not quarantine this scan issue.' ), 400 ); }
		wp_send_json_success(
			array(
				'summary' => GWQSH_File_Integrity::get_summary(),
				'issues'  => GWQSH_File_Integrity::get_scan_issues(),
			)
		);
	}
	/**
	 * Get diff.
	 */
	private static function get_diff() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$path = isset( $_POST['path'] ) ? sanitize_text_field( wp_unslash( $_POST['path'] ) ) : '';
		$diff = GWQSH_File_Integrity::get_file_diff( $path );
		if ( is_wp_error( $diff ) ) {
			wp_send_json_error( array( 'message' => $diff->get_error_message() ), 400 ); }
		wp_send_json_success( array( 'diff' => $diff ) );
	}
	/**
	 * Mark reviewed.
	 */
	private static function mark_reviewed() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$path = isset( $_POST['path'] ) ? sanitize_text_field( wp_unslash( $_POST['path'] ) ) : '';
		if ( ! GWQSH_File_Integrity::mark_as_reviewed( $path ) ) {
			wp_send_json_error( array( 'message' => 'This file is not an unresolved scan finding.' ), 400 ); }
		wp_send_json_success(
			array(
				'summary' => GWQSH_File_Integrity::get_summary(),
				'issues'  => GWQSH_File_Integrity::get_scan_issues(),
			)
		);
	}

	/*
	-------------------------------------------------------------
		Hardening handlers
		-------------------------------------------------------------
	 */
	/**
	 * Get hardening data.
	 */
	private static function get_hardening_data() {
		$hardening = GWQSH_Hardening::get_checks();
		$on_count  = count( array_filter( $hardening ) );
		wp_send_json_success(
			array(
				'hardening' => $hardening,
				'on_count'  => $on_count,
				'risk'      => GWQSH_Hardening::get_risk(),
			)
		);
	}
	/**
	 * Toggle hardening.
	 */
	private static function toggle_hardening() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$feature = isset( $_POST['feature'] ) ? sanitize_key( wp_unslash( $_POST['feature'] ) ) : '';
		$on      = ! empty( $_POST['on'] );
		if ( ! GWQSH_Hardening::set( $feature, $on ) ) {
			wp_send_json_error( array( 'message' => 'Unsupported feature or unable to update its protection.' ), 400 ); }
		$hardening = GWQSH_Hardening::get_checks();
		wp_send_json_success(
			array(
				'hardening' => $hardening,
				'on_count'  => count( array_filter( $hardening ) ),
				'risk'      => GWQSH_Hardening::get_risk(),
			)
		);
	}
	/**
	 * Reset hardening.
	 */
	private static function reset_hardening() {
		if ( ! GWQSH_Hardening::reset_defaults() ) {
			wp_send_json_error( array( 'message' => 'Could not update uploads protection.' ), 500 ); }
		$hardening = GWQSH_Hardening::get_checks();
		wp_send_json_success(
			array(
				'hardening' => $hardening,
				'on_count'  => count( array_filter( $hardening ) ),
				'risk'      => GWQSH_Hardening::get_risk(),
			)
		);
	}
	/**
	 * Revert hardening.
	 */
	private static function revert_hardening() {
		if ( ! GWQSH_Hardening::revert_all() ) {
			wp_send_json_error( array( 'message' => 'Could not remove uploads protection.' ), 500 ); }
		$hardening = GWQSH_Hardening::get_checks();
		wp_send_json_success(
			array(
				'hardening' => $hardening,
				'on_count'  => count( array_filter( $hardening ) ),
				'risk'      => GWQSH_Hardening::get_risk(),
			)
		);
	}

	/*
	-------------------------------------------------------------
		Activity Log handlers
		-------------------------------------------------------------
	 */
	/**
	 * Get activity logs.
	 */
	private static function get_activity_logs() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$days   = isset( $_POST['days'] ) ? absint( $_POST['days'] ) : 7;
		$type   = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
		$event  = isset( $_POST['event'] ) ? sanitize_text_field( wp_unslash( $_POST['event'] ) ) : '';
		$user   = isset( $_POST['user'] ) ? sanitize_text_field( wp_unslash( $_POST['user'] ) ) : '';
		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$order  = isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'DESC';
		$page   = isset( $_POST['page_num'] ) ? max( 1, absint( $_POST['page_num'] ) ) : 1;
		$limit  = isset( $_POST['limit'] ) ? max( 1, absint( $_POST['limit'] ) ) : 10;
		$offset = ( $page - 1 ) * $limit;

		$res = GWQSH_Activity_Logger::query_logs(
			array(
				'days'   => $days,
				'type'   => $type,
				'event'  => $event,
				'user'   => $user,
				'search' => $search,
				'order'  => $order,
				'limit'  => $limit,
				'offset' => $offset,
			)
		);

		$breakdown = GWQSH_Activity_Logger::get_type_breakdown( $days );

		wp_send_json_success(
			array(
				'items'     => $res['items'],
				'total'     => $res['total'],
				'page'      => $page,
				'breakdown' => $breakdown,
			)
		);
	}
	/**
	 * Clear activity logs.
	 */
	private static function clear_activity_logs() {
		GWQSH_Activity_Logger::clear_all();
		wp_send_json_success();
	}

	/*
	-------------------------------------------------------------
		Settings handlers
		-------------------------------------------------------------
	 */
	/**
	 * Save settings.
	 */
	private static function save_settings() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$settings_json = isset( $_POST['settings'] ) && is_string( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$settings      = json_decode( $settings_json, true );
		if ( is_array( $settings ) ) {
			GWQSH_Settings::update_all( $settings );
			GWQSH_Activity_Logger::log( 'Settings changed', 'Plugin configuration updated', 'System' );
			wp_send_json_success( array( 'settings' => GWQSH_Settings::get_all() ) );
		}
		wp_send_json_error( array( 'message' => 'Invalid settings payload' ) );
	}
	/**
	 * Reset settings.
	 */
	private static function reset_settings() {
		GWQSH_Settings::reset_defaults();
		GWQSH_Activity_Logger::log( 'Settings reset', 'All plugin settings reset to defaults', 'System' );
		wp_send_json_success( array( 'settings' => GWQSH_Settings::get_all() ) );
	}
	/**
	 * Rebuild baseline.
	 */
	private static function rebuild_baseline() {
		self::run_scan();
	}
	/**
	 * Early Guardian status overview.
	 */
	private static function guardian_status() {
		wp_send_json_success( array( 'guardian' => GWQSH_Guardian_Manager::overview() ) );
	}
	/**
	 * Install the Early Guardian must-use plugin.
	 */
	private static function guardian_install() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$result = GWQSH_Guardian_Manager::install();
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}
		wp_send_json_success(
			array(
				'message' => 'Early Guardian installed. It now checks must-use plugins before they load.',
				'guardian' => GWQSH_Guardian_Manager::overview(),
			)
		);
	}
	/**
	 * Remove the Early Guardian must-use plugin.
	 */
	private static function guardian_uninstall() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$result = GWQSH_Guardian_Manager::uninstall();
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}
		wp_send_json_success(
			array(
				'message' => 'Early Guardian removed.',
				'guardian' => GWQSH_Guardian_Manager::overview(),
			)
		);
	}
	/**
	 * Restore a quarantined file to its original location.
	 */
	private static function restore_quarantine() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		if ( '' === $name ) {
			wp_send_json_error( array( 'message' => 'Quarantine file name missing.' ) );
		}
		$result = GWQSH_Quarantine_Manager::restore_file( $name );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}
		wp_send_json_success(
			array(
				'message' => 'File restored to its original location.',
				'guardian' => GWQSH_Guardian_Manager::overview(),
				'summary' => GWQSH_File_Integrity::get_summary(),
				'issues'  => GWQSH_File_Integrity::get_scan_issues(),
			)
		);
	}
	/**
	 * Establish trusted baselines for must-use plugins and root files after a
	 * clean scan. Refuses while unresolved Critical findings exist.
	 */
	private static function establish_baseline() {
		check_ajax_referer( 'gwqsh_nonce', 'nonce' );
		$mu   = GWQSH_Baseline_Manager::establish_mu_baseline( true );
		$root = GWQSH_Baseline_Manager::establish_root_baseline();
		foreach ( array( $mu, $root ) as $result ) {
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ), 409 );
			}
		}
		GWQSH_Activity_Logger::log(
			'Trusted baselines established',
			'Baselines recorded for ' . (int) $mu['files'] . ' must-use plugin file(s) and ' . (int) $root['files'] . ' root file(s).',
			'Security'
		);
		wp_send_json_success(
			array(
				'message' => 'Trusted baselines established.',
				'guardian' => GWQSH_Guardian_Manager::overview(),
			)
		);
	}
}
