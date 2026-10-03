<?php
/**
 * Settings repository and options manager for Gracewell QuietShield.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * GWQSH Settings implementation.
 */
final class GWQSH_Settings {
	/**
	 * Migrate removed features.
	 */
	public static function migrate_removed_features() {
		if ( get_option( 'gwqsh_per_user_2fa_migration' ) ) {
			return; }
		$settings = (array) get_option( 'gwqsh_settings', array() );
		foreach ( array( 'emailnotif', 'email', 'n_failed', 'n_files', 'n_updates' ) as $key ) {
			unset( $settings[ $key ] ); }
		update_option( 'gwqsh_settings', $settings );
		$policy = (array) get_option( 'gwqsh_two_factor_settings', array() );
		unset( $policy['enabled'], $policy['email_notifications'] );
		update_option( 'gwqsh_two_factor_settings', $policy );
		delete_metadata( 'user', 0, 'gwqsh_recovery_email', '', true );
		update_option( 'gwqsh_per_user_2fa_migration', 1, false );
	}

	private const OPTION_KEY = 'gwqsh_settings';
	/**
	 * Get defaults.
	 */
	public static function get_defaults() {
		return array(
			'status'      => true,
			'autoupdate'  => true,
			'retention'   => '30',
			'language'    => 'en_US',
			'timezone'    => 'Asia/Kolkata',
			'telemetry'   => false,
			'notice'      => true,
			'proxy'       => false,
			'https'       => true,
			'theme'       => 'light',
			'compact'     => false,
			'anim'        => true,
			'tips'        => true,
			'debug'       => false,
			'proxyheader' => 'xff',
			'uninstall'   => false,
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
		return array_intersect_key( wp_parse_args( $saved, self::get_defaults() ), self::get_defaults() );
	}
	/**
	 * Get.
	 *
	 * @param mixed $key Key.
	 * @param mixed $default Default.
	 */
	public static function get( $key, $default = null ) {
		$all = self::get_all();
		if ( array_key_exists( $key, $all ) ) {
			return $all[ $key ];
		}
		return $default;
	}
	/**
	 * Set.
	 *
	 * @param mixed $key Key.
	 * @param mixed $value Value.
	 */
	public static function set( $key, $value ) {
		$all         = self::get_all();
		$all[ $key ] = $value;
		return update_option( self::OPTION_KEY, $all );
	}
	/**
	 * Update all.
	 *
	 * @param array $settings Settings.
	 */
	public static function update_all( array $settings ) {
		$current  = self::get_all();
		$defaults = self::get_defaults();

		foreach ( $defaults as $k => $def_val ) {
			if ( isset( $settings[ $k ] ) ) {
				if ( ! is_scalar( $settings[ $k ] ) ) {
					continue; }
				if ( is_bool( $def_val ) ) {
					$current[ $k ] = filter_var( $settings[ $k ], FILTER_VALIDATE_BOOLEAN );
				} else {
					$current[ $k ] = sanitize_text_field( $settings[ $k ] );
				}
			}
		}
		$current['retention']   = (string) min( 3650, max( 1, absint( $current['retention'] ) ) );
		$current['theme']       = in_array( $current['theme'], array( 'light', 'dark', 'system' ), true ) ? $current['theme'] : 'light';
		$current['proxyheader'] = in_array( $current['proxyheader'], array( 'xff', 'cf', 'xreal' ), true ) ? $current['proxyheader'] : 'xff';

		return update_option( self::OPTION_KEY, $current );
	}
	/**
	 * Reset defaults.
	 */
	public static function reset_defaults() {
		return update_option( self::OPTION_KEY, self::get_defaults() );
	}
	/**
	 * Get client ip.
	 */
	public static function get_client_ip() {
		$remote = isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( ! filter_var( $remote, FILTER_VALIDATE_IP ) ) {
			return ''; }
		$trusted   = defined( 'GWQSH_TRUSTED_PROXIES' ) && is_array( GWQSH_TRUSTED_PROXIES ) ? GWQSH_TRUSTED_PROXIES : array();
		$use_proxy = (bool) self::get( 'proxy', false ) && in_array( $remote, $trusted, true );
		$header    = self::get( 'proxyheader', 'xff' );

		$ip = '';
		if ( $use_proxy ) {
			if ( 'cf' === $header && ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
			} elseif ( 'xreal' === $header && ! empty( $_SERVER['HTTP_X_REAL_IP'] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
			} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				$parts = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
				$ip    = trim( $parts[0] );
			}
		}

		if ( empty( $ip ) ) {
			$ip = $remote;
		}

		// Validate IP.
		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}

		return $remote;
	}
	/**
	 * Is active.
	 */
	public static function is_active() {
		return (bool) self::get( 'status', true );
	}
}
