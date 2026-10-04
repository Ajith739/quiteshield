<?php
/**
 * One-time migration from the development prefix, without changing security payloads.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * GWQSH Migration implementation.
 */
final class GWQSH_Migration {
	/**
	 * Run.
	 */
	public static function run() {
		if ( get_option( 'gwqsh_prefix_migration' ) ) {
			self::request_compatibility();
			return;
		}
		$started = get_option( 'gwqsh_prefix_migration_lock' );
		if ( $started && (int) $started < time() - 300 ) {
			delete_option( 'gwqsh_prefix_migration_lock' );
		}
		if ( ! add_option( 'gwqsh_prefix_migration_lock', time(), '', false ) ) {
			wp_die( esc_html__( 'QuietShield is migrating security data. Please retry shortly.', 'gracewell-quietshield' ), '', array( 'response' => 503 ) );
		}
		try {
			self::tables();
			self::keys();
			self::cron();
			update_option( 'gwqsh_prefix_migration', 1, false );
		} catch ( Exception $error ) {
			delete_option( 'gwqsh_prefix_migration_lock' );
			wp_die( esc_html( $error->getMessage() ), '', array( 'response' => 503 ) );
		}
		delete_option( 'gwqsh_prefix_migration_lock' );
		self::request_compatibility();
	}
	/**
	 * Query.
	 *
	 * @param mixed $sql Sql.
	 * @throws RuntimeException When existing security data cannot be migrated safely.
	 */
	private static function query( $sql ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Migration callers prepare values; identifiers are fixed WordPress tables or validated plugin tables. Cache invalidation follows the migration.
		if ( false === $wpdb->query( $sql ) ) {
			throw new RuntimeException( 'QuietShield could not migrate security data. Existing data was preserved; review database permissions and retry.' );
		}
	}
	/**
	 * Rename existing plugin tables without modifying any records.
	 *
	 * @throws RuntimeException When a rename would risk overwriting security data.
	 */
	private static function tables() {
		global $wpdb;
		foreach ( array( 'activity_log', 'lockouts', 'scan_results', 'scan_history' ) as $suffix ) {
			$old = esc_sql( $wpdb->prefix . 'gqs_' . $suffix );
			$new = esc_sql( $wpdb->prefix . 'gwqsh_' . $suffix );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $old ) ) );
			if ( $exists !== $old ) {
				continue;
			}
			if ( ! preg_match( '/^[A-Za-z0-9_]+$/D', $old . $new ) ) {
				throw new RuntimeException( 'QuietShield found an invalid database prefix.' );
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $new ) ) ) === $new ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Validated identifier; migration must read live state before renaming.
				$count = $wpdb->get_var(
					"SELECT COUNT(*) FROM `{$new}`" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Validated or fixed plugin-owned identifier; dynamic values are prepared, and WordPress 5.3 has no identifier placeholder.
				);
				if ( null === $count || (int) $count > 0 ) {
					throw new RuntimeException( 'QuietShield found both legacy and current security tables. No records were overwritten. Reconcile these tables before continuing.' );
				}
				self::query( "DROP TABLE `{$new}`" );
			}
			// Atomic rename preserves IDs, indexes and every existing record; retries skip completed tables.
			self::query( "RENAME TABLE `{$old}` TO `{$new}`" );
		}
	}
	/**
	 * Keys.
	 *
	 * Row-by-row rename is used instead of a MySQL-only
	 * "UPDATE ... JOIN ... SET alias" statement so the one-time migration
	 * works on every $wpdb driver (including SQLite drop-ins). Semantics are
	 * identical: legacy keys are renamed only when no modern key exists, and
	 * existing modern values are never overwritten.
	 */
	private static function keys() {
		global $wpdb;
		foreach ( array(
			'gqs_'                    => 'gwqsh_',
			'_transient_gqs_'         => '_transient_gwqsh_',
			'_transient_timeout_gqs_' => '_transient_timeout_gwqsh_',
		) as $old => $new ) {
			$cursor = 0;
			do {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- One-time bounded migration; identifiers are fixed WordPress tables and every value is prepared.
				$rows = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT option_id, option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_id > %d ORDER BY option_id LIMIT 200",
						$wpdb->esc_like( $old ) . '%',
						$cursor
					)
				);
				foreach ( $rows as $row ) {
					$cursor = (int) $row->option_id;
					$target = $new . substr( $row->option_name, strlen( $old ) );
					if ( $target === $row->option_name ) {
						continue;
					}
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$modern = $wpdb->get_var( $wpdb->prepare( "SELECT option_id FROM {$wpdb->options} WHERE option_name = %s", $target ) );
					if ( null !== $modern ) {
						wp_cache_delete( $row->option_name, 'options' );
						continue;
					}
					self::query(
						$wpdb->prepare(
							"UPDATE {$wpdb->options} SET option_name = %s WHERE option_id = %d",
							$target,
							(int) $row->option_id
						)
					);
					wp_cache_delete( $row->option_name, 'options' );
					wp_cache_delete( $target, 'options' );
				}
			} while ( count( $rows ) === 200 );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$users = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", $wpdb->esc_like( 'gqs_' ) . '%' ) );
		$cursor = 0;
		do {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- One-time bounded migration; identifiers are fixed WordPress tables and every value is prepared.
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT umeta_id, user_id, meta_key FROM {$wpdb->usermeta} WHERE meta_key LIKE %s AND umeta_id > %d ORDER BY umeta_id LIMIT 200",
					$wpdb->esc_like( 'gqs_' ) . '%',
					$cursor
				)
			);
			foreach ( $rows as $row ) {
				$cursor = (int) $row->umeta_id;
				$target = 'gwqsh_' . substr( $row->meta_key, 4 );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$modern = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT umeta_id FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s",
						(int) $row->user_id,
						$target
					)
				);
				if ( null === $modern ) {
					self::query(
						$wpdb->prepare(
							"UPDATE {$wpdb->usermeta} SET meta_key = %s WHERE umeta_id = %d",
							$target,
							(int) $row->umeta_id
						)
					);
				}
			}
		} while ( count( $rows ) === 200 );
		foreach ( $users as $user_id ) {
			wp_cache_delete( (int) $user_id, 'user_meta' );
		}
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}
	/**
	 * Cron.
	 */
	private static function cron() {
		$cron = get_option( 'cron', array() );
		foreach ( $cron as $timestamp => &$hooks ) {
			if ( ! is_array( $hooks ) ) {
				continue;
			}
			foreach ( array_keys( $hooks ) as $hook ) {
				if ( 0 !== strpos( $hook, 'gqs_' ) ) {
					continue;
				}
				if ( 'gqs_scheduled_integrity_scan' !== $hook ) {
					$new           = 'gwqsh_' . substr( $hook, 4 );
					$hooks[ $new ] = array_merge( $hooks[ $hook ], isset( $hooks[ $new ] ) ? $hooks[ $new ] : array() );
				}
				unset( $hooks[ $hook ] );
			}
		}
		unset( $hooks );
		foreach ( $cron as $timestamp => $hooks ) {
			if ( is_array( $hooks ) && empty( $hooks ) ) {
				unset( $cron[ $timestamp ] );
			}
		}
		update_option( 'cron', $cron );
	}
	/**
	 * Request compatibility.
	 */
	public static function request_compatibility() {
		// Only legacy input names are normalized. Native validation still verifies every token/factor.
		foreach ( array( 'gqs_2fa_code', 'gqs_2fa_challenge', 'gqs_trust_device' ) as $old ) {
			$new = 'gwqsh_' . substr( $old, 4 );
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Legacy input-name normalization only; recovery, trusted-device and password-issued challenge verification remain in the authentication handlers.
			if ( ! isset( $_POST[ $new ] ) && isset( $_POST[ $old ] ) && is_string( $_POST[ $old ] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Legacy input-name normalization only; recovery, trusted-device and password-issued challenge verification remain in the authentication handlers.
				$_POST[ $new ] = sanitize_text_field( wp_unslash( $_POST[ $old ] ) );
			}
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Legacy input-name normalization only; recovery, trusted-device and password-issued challenge verification remain in the authentication handlers.
		if ( ! isset( $_GET['gwqsh_recover'] ) && isset( $_GET['gqs_recover'] ) && is_string( $_GET['gqs_recover'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Legacy input-name normalization only; recovery, trusted-device and password-issued challenge verification remain in the authentication handlers.
			$_GET['gwqsh_recover'] = sanitize_text_field( wp_unslash( $_GET['gqs_recover'] ) );
		}
		if ( ! isset( $_COOKIE['gwqsh_trusted_device'] ) && isset( $_COOKIE['gqs_trusted_device'] ) && is_string( $_COOKIE['gqs_trusted_device'] ) ) {
			$_COOKIE['gwqsh_trusted_device'] = sanitize_text_field( wp_unslash( $_COOKIE['gqs_trusted_device'] ) );
		}
	}
}
