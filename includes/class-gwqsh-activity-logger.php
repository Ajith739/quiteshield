<?php
/**
 * Real activity logging service for Gracewell QuietShield.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * GWQSH Activity Logger implementation.
 */
final class GWQSH_Activity_Logger {
	/**
	 * Init.
	 */
	public static function init() {
		// Core WordPress authentication and user lifecycle hooks.
		add_action( 'wp_login', array( __CLASS__, 'on_login' ), 10, 2 );
		add_action( 'wp_login_failed', array( __CLASS__, 'on_login_failed' ), 10, 2 );
		add_action( 'wp_logout', array( __CLASS__, 'on_logout' ), 10, 1 );
		add_action( 'activated_plugin', array( __CLASS__, 'on_plugin_activated' ), 10, 2 );
		add_action( 'deactivated_plugin', array( __CLASS__, 'on_plugin_deactivated' ), 10, 2 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'on_upgrade_complete' ), 10, 2 );
		add_action( 'set_user_role', array( __CLASS__, 'on_user_role_changed' ), 10, 3 );
		add_action( 'profile_update', array( __CLASS__, 'on_profile_update' ), 10, 2 );

		// Daily cleanup of expired logs based on retention setting.
		if ( ! wp_next_scheduled( 'gwqsh_daily_log_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'gwqsh_daily_log_cleanup' );
		}
		add_action( 'gwqsh_daily_log_cleanup', array( __CLASS__, 'prune_expired_logs' ) );
	}
	/**
	 * Log.
	 *
	 * @param mixed $event_name Event name.
	 * @param mixed $details Details.
	 * @param mixed $event_type Event type.
	 * @param mixed $user User.
	 * @param mixed $ip Ip.
	 */
	public static function log( $event_name, $details = '', $event_type = 'System', $user = null, $ip = null ) {
		global $wpdb;

		if ( null === $ip ) {
			$ip = GWQSH_Settings::get_client_ip();
		}

		if ( null === $user ) {
			$current_user = wp_get_current_user();
			$user         = ( $current_user && $current_user->exists() ) ? $current_user->user_login : '—';
		}

		$now   = current_time( 'mysql' );
		$table = esc_sql( $wpdb->prefix . 'gwqsh_activity_log' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table,
			array(
				'event_time' => $now,
				'event_name' => sanitize_text_field( $event_name ),
				'event_type' => sanitize_text_field( $event_type ),
				'user_login' => sanitize_text_field( $user ),
				'ip_address' => sanitize_text_field( $ip ),
				'details'    => sanitize_textarea_field( $details ),
			)
		);

		return $wpdb->insert_id;
	}
	/**
	 * On login.
	 *
	 * @param mixed $user_login User login.
	 * @param mixed $user User.
	 */
	public static function on_login( $user_login, $user ) {
		self::log( 'Login successful', 'User logged in successfully', 'User', $user_login );
	}
	/**
	 * On login failed.
	 *
	 * @param mixed $username Username.
	 * @param mixed $error Error.
	 */
	public static function on_login_failed( $username, $error = null ) {
		if ( ( is_wp_error( $error ) && in_array( 'gwqsh_2fa_required', $error->get_error_codes(), true ) ) ||
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Audit observation of a WordPress-authorized upgrade; this input does not authorize an action.
			( null === $error && ! empty( $GLOBALS['gwqsh_2fa_prompt'] ) && empty( $_POST['gwqsh_2fa_code'] ) ) ) {
			return; }
		$ip = GWQSH_Settings::get_client_ip();
		self::log( 'Login attempt blocked', 'Failed login for user: ' . ( $username ? $username : 'unknown' ), 'Security', $username ? $username : '—', $ip );
	}
	/**
	 * On logout.
	 *
	 * @param mixed $user_id User id.
	 */
	public static function on_logout( $user_id ) {
		$user  = get_userdata( $user_id );
		$login = $user ? $user->user_login : '—';
		self::log( 'User logged out', 'Session ended', 'User', $login );
	}
	/**
	 * On plugin activated.
	 *
	 * @param mixed $plugin Plugin.
	 * @param mixed $network_wide Network wide.
	 */
	public static function on_plugin_activated( $plugin, $network_wide ) {
		self::log( 'Plugin installed', 'Activated plugin: ' . plugin_basename( $plugin ), 'Plugin' );
	}
	/**
	 * On plugin deactivated.
	 *
	 * @param mixed $plugin Plugin.
	 * @param mixed $network_wide Network wide.
	 */
	public static function on_plugin_deactivated( $plugin, $network_wide ) {
		self::log( 'Plugin updated', 'Deactivated plugin: ' . plugin_basename( $plugin ), 'Plugin' );
	}
	/**
	 * On upgrade complete.
	 *
	 * @param mixed $upgrader_object Upgrader object.
	 * @param mixed $options Options.
	 */
	public static function on_upgrade_complete( $upgrader_object, $options ) {
		$type   = isset( $options['type'] ) ? $options['type'] : 'item';
		$action = isset( $options['action'] ) ? $options['action'] : 'update';
		self::log( ucfirst( $type ) . ' updated', ucfirst( $type ) . ' ' . $action . ' completed', 'Plugin' );
	}
	/**
	 * On user role changed.
	 *
	 * @param mixed $user_id User id.
	 * @param mixed $role Role.
	 * @param mixed $old_roles Old roles.
	 */
	public static function on_user_role_changed( $user_id, $role, $old_roles ) {
		$user = get_userdata( $user_id );
		$name = $user ? $user->user_login : 'User #' . $user_id;
		$old  = ! empty( $old_roles ) ? implode( ', ', $old_roles ) : 'none';
		self::log( 'User role changed', sprintf( '%s role changed from %s to %s', $name, $old, $role ), 'User' );
	}
	/**
	 * On profile update.
	 *
	 * @param mixed $user_id User id.
	 * @param mixed $old_user_data Old user data.
	 */
	public static function on_profile_update( $user_id, $old_user_data ) {
		$user = get_userdata( $user_id );
		$name = $user ? $user->user_login : 'User #' . $user_id;
		self::log( 'Profile updated', sprintf( '%s profile details updated', $name ), 'User' );
	}
	/**
	 * Query logs.
	 *
	 * @param array $args Args.
	 */
	public static function query_logs( array $args = array() ) {
		global $wpdb;
		$table = esc_sql( $wpdb->prefix . 'gwqsh_activity_log' );

		$defaults = array(
			'days'   => 7,
			'type'   => '',
			'event'  => '',
			'user'   => '',
			'search' => '',
			'order'  => 'DESC',
			'limit'  => 10,
			'offset' => 0,
		);
		$params   = wp_parse_args( $args, $defaults );

		$where        = array( '1=1' );
		$where_values = array();

		if ( ! empty( $params['days'] ) ) {
			$days           = absint( $params['days'] );
			$cutoff         = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
			$where[]        = 'event_time >= %s';
			$where_values[] = $cutoff;
		}

		if ( ! empty( $params['type'] ) ) {
			$where[]        = 'event_type = %s';
			$where_values[] = sanitize_text_field( $params['type'] );
		}

		if ( ! empty( $params['event'] ) ) {
			$where[]        = 'event_name = %s';
			$where_values[] = sanitize_text_field( $params['event'] );
		}

		if ( ! empty( $params['user'] ) ) {
			$where[]        = 'user_login = %s';
			$where_values[] = sanitize_text_field( $params['user'] );
		}

		if ( ! empty( $params['search'] ) ) {
			$search_like    = '%' . $wpdb->esc_like( sanitize_text_field( $params['search'] ) ) . '%';
			$where[]        = '(event_name LIKE %s OR details LIKE %s OR user_login LIKE %s OR ip_address LIKE %s)';
			$where_values[] = $search_like;
			$where_values[] = $search_like;
			$where_values[] = $search_like;
			$where_values[] = $search_like;
		}

		$where_sql = implode( ' AND ', $where );
		$order     = 'ASC' === strtoupper( $params['order'] ) ? 'ASC' : 'DESC';

		// Count query.
		if ( ! empty( $where_values ) ) {
			$count_query = $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}", $where_values ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Internally built WHERE fragments contain placeholders whenever values are supplied.
		} else {
			$count_query =
			"SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
		$total = (int) $wpdb->get_var( $count_query );

		// Data query.
		$limit  = max( 1, absint( $params['limit'] ) );
		$offset = absint( $params['offset'] );

		$data_sql = "SELECT id, event_time, event_name, event_type, user_login, ip_address, details FROM {$table} WHERE {$where_sql} ORDER BY event_time {$order} LIMIT %d, %d";

		$where_values[] = $offset;
		$where_values[] = $limit;
		$data_query     = $wpdb->prepare( $data_sql, $where_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- WHERE fragments and sort direction are internally allowlisted; all values are prepared.

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
		$items = $wpdb->get_results( $data_query, ARRAY_A );

		return array(
			'items' => $items ? $items : array(),
			'total' => $total,
		);
	}
	/**
	 * Get type breakdown.
	 *
	 * @param mixed $days Days.
	 */
	public static function get_type_breakdown( $days = 7 ) {
		global $wpdb;
		$table  = esc_sql( $wpdb->prefix . 'gwqsh_activity_log' );
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT event_type, COUNT(*) as count FROM {$table} WHERE event_time >= %s GROUP BY event_type", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Validated or fixed plugin-owned identifier; dynamic values are prepared, and WordPress 5.3 has no identifier placeholder.
				$cutoff
			),
			ARRAY_A
		);

		$counts = array(
			'Security' => 0,
			'User'     => 0,
			'Plugin'   => 0,
			'System'   => 0,
		);

		if ( $results ) {
			foreach ( $results as $row ) {
				if ( isset( $counts[ $row['event_type'] ] ) ) {
					$counts[ $row['event_type'] ] = (int) $row['count'];
				}
			}
		}

		return $counts;
	}
	/**
	 * Clear all.
	 */
	public static function clear_all() {
		global $wpdb;
		$table = esc_sql( $wpdb->prefix . 'gwqsh_activity_log' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Fixed plugin-owned table identifier from the WordPress prefix; all dynamic values use prepare(). WordPress 5.3 has no %i placeholder.
		$wpdb->query( "TRUNCATE TABLE {$table}" );
		self::log( 'Activity logs cleared', 'All historical activity logs truncated by administrator', 'System' );
		return true;
	}
	/**
	 * Prune expired logs.
	 */
	public static function prune_expired_logs() {
		global $wpdb;
		$retention = absint( GWQSH_Settings::get( 'retention', 30 ) );
		if ( $retention <= 0 ) {
			return;
		}

		$table  = esc_sql( $wpdb->prefix . 'gwqsh_activity_log' );
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$retention} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Fixed plugin-owned table identifier from the WordPress prefix; all dynamic values use prepare(). WordPress 5.3 has no %i placeholder.
				"DELETE FROM {$table} WHERE event_time < %s",
				$cutoff
			)
		);
	}
}
