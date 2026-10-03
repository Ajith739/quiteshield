<?php
/**
 * Real file integrity scanner and core verifier for Gracewell QuietShield.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * GWQSH File Integrity implementation.
 */
final class GWQSH_File_Integrity {

	private const API_URL = 'https://api.wordpress.org/core/checksums/1.0/';
	/**
	 * Inspected.
	 *
	 * @var array
	 */
	private static $inspected = array(
		'Plugin'  => 0,
		'Theme'   => 0,
		'Uploads' => 0,
	);
	/**
	 * Init.
	 */
	public static function init() {
		// One-time cleanup of the removed development scan scheduler.
		if ( ! get_option( 'gwqsh_manual_scan_migration', false ) ) {
			wp_clear_scheduled_hook( 'gwqsh_scheduled_integrity_scan' );
			delete_option( 'gwqsh_scan_frequency' );
			delete_option( 'gwqsh_scan_time' );
			update_option( 'gwqsh_manual_scan_migration', 1, false );
		}
	}
	/**
	 * Get core checksums.
	 */
	public static function get_core_checksums() {
		global $wp_version;
		$cache_key = 'gwqsh_checksums_' . md5( $wp_version . '|' . get_locale() );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) && ! empty( $cached ) ) {
			if ( isset( $cached[ $wp_version ] ) && is_array( $cached[ $wp_version ] ) ) {
				$cached = $cached[ $wp_version ];
			} elseif ( 1 === count( $cached ) && is_array( reset( $cached ) ) ) {
				$cached = reset( $cached );
			}
			return self::core_only_checksums( $cached );
		}

		$locale = get_locale();
		$url    = add_query_arg(
			array(
				'version' => $wp_version,
				'locale'  => $locale,
			),
			self::API_URL
		);

		$response = wp_remote_get( $url, array( 'timeout' => 20 ) );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			// Fallback without locale.
			$url_fallback = add_query_arg( array( 'version' => $wp_version ), self::API_URL );
			$response     = wp_remote_get( $url_fallback, array( 'timeout' => 20 ) );
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return false;
			}
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['checksums'] ) || ! is_array( $body['checksums'] ) ) {
			return false;
		}

		$checksums = $body['checksums'];
		if ( isset( $checksums[ $wp_version ] ) && is_array( $checksums[ $wp_version ] ) ) {
			$checksums = $checksums[ $wp_version ];
		} elseif ( 1 === count( $checksums ) && is_array( reset( $checksums ) ) ) {
			$checksums = reset( $checksums );
		}

		set_transient( $cache_key, $checksums, WEEK_IN_SECONDS );
		return self::core_only_checksums( $checksums );
	}
	/**
	 * Core only checksums.
	 *
	 * @param array $checksums Checksums.
	 */
	private static function core_only_checksums( array $checksums ) {
		return array_filter(
			$checksums,
			static function ( $hash, $path ) {
				return is_string( $path ) && is_string( $hash ) && preg_match( '/^[a-f0-9]{32}$/D', $hash ) &&
				0 !== strpos( $path, 'wp-content/' ) && ! preg_match( '#(^/|(^|/)\.{1,2}(/|$)|[\\\\\x00:])#', $path );
			},
			ARRAY_FILTER_USE_BOTH
		);
	}
	/**
	 * Get scan history.
	 */
	public static function get_scan_history() {
		global $wpdb;
		$table = esc_sql( $wpdb->prefix . 'gwqsh_scan_history' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Fixed plugin-owned table identifier from the WordPress prefix; all dynamic values use prepare(). WordPress 5.3 has no %i placeholder.
		return $wpdb->get_results( "SELECT scan_date, total_files, clean_files, modified_files, missing_files, suspicious_files FROM {$table} ORDER BY scan_date DESC LIMIT 100", ARRAY_A );
	}
	/**
	 * Run full scan.
	 */
	public static function run_full_scan() {
		self::$inspected = array(
			'Plugin'  => 0,
			'Theme'   => 0,
			'Uploads' => 0,
		);
		global $wpdb;
		$t0 = microtime( true );

		$checksums     = self::get_core_checksums();
		$results_table = esc_sql( $wpdb->prefix . 'gwqsh_scan_results' );
		$history_table = esc_sql( $wpdb->prefix . 'gwqsh_scan_history' );

		if ( ! is_array( $checksums ) ) {
			return new WP_Error( 'checksums_unavailable', 'WordPress core checksums are unavailable.' );
		}
		// Clear previous unresolved issues.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $results_table, array( 'is_resolved' => 0 ) );

		$total_files      = 0;
		$clean_files      = 0;
		$modified_count   = 0;
		$missing_count    = 0;
		$suspicious_count = 0;
		$now              = current_time( 'mysql' );

		// 1. Verify Core Checksums
		if ( is_array( $checksums ) ) {
			foreach ( $checksums as $relative_path => $expected_md5 ) {
				++$total_files;
				$full_path = ABSPATH . $relative_path;

				if ( ! file_exists( $full_path ) ) {
					++$missing_count;
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$wpdb->insert(
						$results_table,
						array(
							'file_path'   => '/' . ltrim( $relative_path, '/' ),
							'file_type'   => 'Core',
							'status'      => 'Missing',
							'details'     => 'Official WordPress core file is missing',
							'detected_at' => $now,
							'is_resolved' => 0,
						)
					);
					continue;
				}

				$actual_md5 = ( is_file( $full_path ) && ! is_link( $full_path ) && is_readable( $full_path ) ) ? md5_file( $full_path ) : false;
				if ( $actual_md5 !== $expected_md5 ) {
					++$modified_count;
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$wpdb->insert(
						$results_table,
						array(
							'file_path'   => '/' . ltrim( $relative_path, '/' ),
							'file_type'   => 'Core',
							'status'      => 'Modified',
							'details'     => 'Official MD5: ' . $expected_md5 . '; current MD5: ' . ( $actual_md5 ? $actual_md5 : 'unreadable or not a regular file' ),
							'detected_at' => $now,
							'is_resolved' => 0,
						)
					);
				} else {
					++$clean_files;
				}
			}
		}

		// Unexpected PHP in core directories is an added-file finding.
		$inspected = 0;
		foreach ( array( 'wp-admin', 'wp-includes' ) as $core_dir ) {
			$directory = ABSPATH . $core_dir;
			if ( ! is_dir( $directory ) || is_link( $directory ) ) {
				continue; }
			$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS ) );
			foreach ( $iterator as $item ) {
				if ( ++$inspected > 25000 ) {
					break 2; }
				if ( ! $item->isFile() || $item->isLink() || 'php' !== strtolower( $item->getExtension() ) ) {
					continue; }
				$relative = substr( wp_normalize_path( $item->getPathname() ), strlen( wp_normalize_path( ABSPATH ) ) );
				if ( isset( $checksums[ $relative ] ) ) {
					continue; }
				++$total_files;
				++$suspicious_count;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->insert(
					$results_table,
					array(
						'file_path'   => '/' . $relative,
						'file_type'   => 'Core',
						'status'      => 'Suspicious',
						'details'     => 'Unexpected PHP file in a WordPress core directory',
						'detected_at' => $now,
						'is_resolved' => 0,
					)
				);
			}
		}

		// 2. Scan Uploads Directory for PHP scripts & suspicious files
		$extra_core   = max( 0, $total_files - count( $checksums ) );
		$upload_dir   = wp_upload_dir();
		$uploads_base = $upload_dir['basedir'];
		if ( is_dir( $uploads_base ) ) {
			$uploads_issues = self::scan_directory_for_suspicious( $uploads_base, 'Uploads' );
			foreach ( $uploads_issues as $issue ) {
				++$suspicious_count;
				++$total_files;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->insert(
					$results_table,
					array(
						'file_path'   => $issue['path'],
						'file_type'   => 'Uploads',
						'status'      => 'Suspicious',
						'details'     => $issue['detail'],
						'detected_at' => $now,
						'is_resolved' => 0,
					)
				);
			}
		}

		// 3. Scan Active Plugins for obvious backdoors / eval patterns
		$plugins_issues = self::scan_plugins_and_themes();
		foreach ( $plugins_issues as $issue ) {
			++$total_files;
			if ( 'Suspicious' === $issue['status'] ) {
				++$suspicious_count;
			} else {
				++$modified_count;
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$results_table,
				array(
					'file_path'   => $issue['path'],
					'file_type'   => $issue['type'],
					'status'      => $issue['status'],
					'details'     => $issue['detail'],
					'detected_at' => $now,
					'is_resolved' => 0,
				)
			);
		}

		$duration = max( 1, (int) round( microtime( true ) - $t0 ) );
		update_option( 'gwqsh_last_scan_time', $now );
		update_option( 'gwqsh_last_scan_duration', $duration );
		update_option( 'gwqsh_last_scan_total', $total_files );
		update_option( 'gwqsh_last_scan_core_total', count( $checksums ) );
		$total_files = count( $checksums ) + $extra_core + array_sum( self::$inspected );
		$clean_files = max( 0, $total_files - $modified_count - $missing_count - $suspicious_count );
		update_option( 'gwqsh_last_scan_total', $total_files );
		update_option( 'gwqsh_last_scan_scope_counts', self::$inspected );

		// Record history entry.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$history_table,
			array(
				'scan_date'        => $now,
				'duration'         => $duration,
				'total_files'      => $total_files,
				'clean_files'      => $clean_files,
				'modified_files'   => $modified_count,
				'missing_files'    => $missing_count,
				'suspicious_files' => $suspicious_count,
			)
		);

		$issues_total = $modified_count + $missing_count + $suspicious_count;
		GWQSH_Activity_Logger::log(
			'File scan completed',
			sprintf( 'Scanned %d files: %d modified, %d missing, %d suspicious', $total_files, $modified_count, $missing_count, $suspicious_count ),
			'System'
		);

		return array(
			'total'      => $total_files,
			'clean'      => $clean_files,
			'modified'   => $modified_count,
			'missing'    => $missing_count,
			'suspicious' => $suspicious_count,
			'duration'   => $duration,
			'date'       => $now,
		);
	}
	/**
	 * Scan directory for suspicious.
	 *
	 * @param mixed $dir Dir.
	 * @param mixed $type Type.
	 */
	private static function scan_directory_for_suspicious( $dir, $type = 'Uploads' ) {
		$issues = array();
		if ( ! is_dir( $dir ) ) {
			return $issues;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $iterator as $item ) {
			if ( $item->isFile() && ! $item->isLink() ) {
				++self::$inspected['Uploads'];
				$ext = strtolower( pathinfo( $item->getFilename(), PATHINFO_EXTENSION ) );
				$rel = str_replace( wp_normalize_path( ABSPATH ), '', wp_normalize_path( $item->getPathname() ) );
				$rel = '/' . ltrim( $rel, '/' );

				// Check for executable script extensions in uploads.
				if ( in_array( $ext, array( 'php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'phar', 'sh' ), true ) ) {
					$issues[] = array(
						'path'   => $rel,
						'type'   => $type,
						'status' => 'Suspicious',
						'detail' => 'Executable script file found in uploads directory',
					);
					continue;
				}

				// Check for malicious payload signatures.
				if ( $item->getSize() < 500000 && in_array( $ext, array( 'php', 'txt', 'html', 'inc' ), true ) ) {
					$content = @file_get_contents( $item->getPathname() ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
					if ( $content && preg_match( '/(eval\s*\(\s*base64_decode|gzinflate\s*\(\s*base64_decode|shell_exec\s*\(|passthru\s*\(|system\s*\()/i', $content ) ) {
						$issues[] = array(
							'path'   => $rel,
							'type'   => $type,
							'status' => 'Suspicious',
							'detail' => 'Contains suspicious obfuscated code or remote execution functions',
						);
					}
				}
			}
		}

		return $issues;
	}
	/**
	 * Scan plugins and themes.
	 */
	private static function scan_plugins_and_themes() {
		$issues = array();
		foreach ( array(
			'Plugin' => WP_PLUGIN_DIR,
			'Theme'  => get_theme_root(),
		) as $scope => $plugins_dir ) {

			if ( is_dir( $plugins_dir ) ) {
				$directory = new RecursiveDirectoryIterator( $plugins_dir, RecursiveDirectoryIterator::SKIP_DOTS );
				$filtered  = new RecursiveCallbackFilterIterator(
					$directory,
					static function ( $item ) {
						// Avoid descending into this plugin's development dependencies, not just reading then skipping each file.
						$path = trailingslashit( wp_normalize_path( $item->getPathname() ) );
						return 0 !== strpos( $path, trailingslashit( wp_normalize_path( GWQSH_PATH ) ) );
					}
				);
				$iterator  = new RecursiveIteratorIterator(
					$filtered
				);

				$count = 0;
				foreach ( $iterator as $item ) {
					if ( $count > 2000 ) {
						break; // Cap to avoid execution timeout on large sites.
					}
					if ( $item->isFile() && ! $item->isLink() && 'php' === strtolower( pathinfo( $item->getFilename(), PATHINFO_EXTENSION ) ) ) {
						// Skip our own plugin.
						if ( false !== strpos( $item->getPathname(), 'gracewell-quietshield' ) ) {
							continue;
						}
						if ( $item->getSize() < 400000 ) {
							++$count;
							++self::$inspected[ $scope ];
							$content = @file_get_contents( $item->getPathname() ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
							if ( $content && preg_match( '/(eval\s*\(\s*base64_decode\s*\(\s*[\'"][A-Za-z0-9+\/=]{40,}[\'"]\s*\)\s*\)|c99shell|r57shell|b374k)/i', $content ) ) {
								$rel      = str_replace( wp_normalize_path( ABSPATH ), '', wp_normalize_path( $item->getPathname() ) );
								$issues[] = array(
									'path'   => '/' . ltrim( $rel, '/' ),
									'type'   => $scope,
									'status' => 'Suspicious',
									'detail' => 'Web shell or obfuscated backdoor pattern detected',
								);
							}
						}
					}
				}
			}
		}
		return $issues;
	}
	/**
	 * Get scan issues.
	 *
	 * @param array $filters Filters.
	 */
	public static function get_scan_issues( array $filters = array() ) {
		global $wpdb;
		$table = esc_sql( $wpdb->prefix . 'gwqsh_scan_results' );

		$where        = array( 'is_resolved = 0' );
		$where_values = array();

		if ( ! empty( $filters['type'] ) ) {
			$where[]        = 'file_type = %s';
			$where_values[] = sanitize_text_field( $filters['type'] );
		}
		if ( ! empty( $filters['status'] ) ) {
			$where[]        = 'status = %s';
			$where_values[] = sanitize_text_field( $filters['status'] );
		}
		if ( ! empty( $filters['search'] ) ) {
			$like           = '%' . $wpdb->esc_like( sanitize_text_field( $filters['search'] ) ) . '%';
			$where[]        = '(file_path LIKE %s OR details LIKE %s)';
			$where_values[] = $like;
			$where_values[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$sql       =
		"SELECT id, file_path as path, file_type as type, status as st, details as det, detected_at as date FROM {$table} WHERE {$where_sql} ORDER BY detected_at DESC LIMIT 1000";

		if ( ! empty( $where_values ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
			$results = $wpdb->get_results( $wpdb->prepare( $sql, $where_values ), ARRAY_A );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
			$results = $wpdb->get_results( $sql, ARRAY_A );
		}

		return $results ? $results : array();
	}
	/**
	 * Get summary.
	 */
	public static function get_summary() {
		global $wpdb;
		$table = esc_sql( $wpdb->prefix . 'gwqsh_scan_results' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
		$issues = $wpdb->get_results(
			"SELECT status, file_type, COUNT(*) as c FROM {$table} WHERE is_resolved = 0 GROUP BY status, file_type", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Validated or fixed plugin-owned identifier; dynamic values are prepared, and WordPress 5.3 has no identifier placeholder.
			ARRAY_A
		);

		$counts = array(
			'Modified'   => 0,
			'Missing'    => 0,
			'Suspicious' => 0,
		);

		$scope_counts = get_option( 'gwqsh_last_scan_scope_counts', array() );
		$breakdown    = array(
			'core-mod'  => 0,
			'core-miss' => 0,
			'core-ok'   => max( 0, (int) get_option( 'gwqsh_last_scan_core_total', 0 ) ),
			'pl-mod'    => 0,
			'pl-sus'    => 0,
			'pl-ok'     => (int) ( $scope_counts['Plugin'] ?? 0 ),
			'th-mod'    => 0,
			'th-sus'    => 0,
			'th-ok'     => (int) ( $scope_counts['Theme'] ?? 0 ),
			'up-php'    => 0,
			'up-ok'     => (int) ( $scope_counts['Uploads'] ?? 0 ),
		);

		if ( $issues ) {
			foreach ( $issues as $row ) {
				$st = $row['status'];
				$tp = $row['file_type'];
				$c  = (int) $row['c'];

				if ( isset( $counts[ $st ] ) ) {
					$counts[ $st ] += $c;
				}

				if ( 'Core' === $tp && 'Modified' === $st ) {
					$breakdown['core-mod'] += $c;
					$breakdown['core-ok']   = max( 0, $breakdown['core-ok'] - $c );
				} elseif ( 'Core' === $tp && 'Missing' === $st ) {
					$breakdown['core-miss'] += $c;
					$breakdown['core-ok']    = max( 0, $breakdown['core-ok'] - $c );
				} elseif ( 'Plugin' === $tp && 'Modified' === $st ) {
					$breakdown['pl-mod'] += $c;
					$breakdown['pl-ok']   = max( 0, $breakdown['pl-ok'] - $c );
				} elseif ( 'Plugin' === $tp && 'Suspicious' === $st ) {
					$breakdown['pl-sus'] += $c;
					$breakdown['pl-ok']   = max( 0, $breakdown['pl-ok'] - $c );
				} elseif ( 'Theme' === $tp && 'Modified' === $st ) {
					$breakdown['th-mod'] += $c;
					$breakdown['th-ok']   = max( 0, $breakdown['th-ok'] - $c );
				} elseif ( 'Theme' === $tp && 'Suspicious' === $st ) {
					$breakdown['th-sus'] += $c;
					$breakdown['th-ok']   = max( 0, $breakdown['th-ok'] - $c );
				} elseif ( 'Uploads' === $tp && 'Suspicious' === $st ) {
					$breakdown['up-php'] += $c;
					$breakdown['up-ok']   = max( 0, $breakdown['up-ok'] - $c );
				}
			}
		}

		$total_files  = (int) get_option( 'gwqsh_last_scan_total', 0 );
		$issues_count = $counts['Modified'] + $counts['Missing'] + $counts['Suspicious'];
		$clean_files  = max( 0, $total_files - $issues_count );

		$last_date = get_option( 'gwqsh_last_scan_time', '' );
		$last_dur  = get_option( 'gwqsh_last_scan_duration', 0 );

		return array(
			'total'        => $total_files,
			'clean'        => $clean_files,
			'modified'     => $counts['Modified'],
			'missing'      => $counts['Missing'],
			'suspicious'   => $counts['Suspicious'],
			'last_date'    => $last_date ? mysql2date( 'M j, Y, h:i A', $last_date ) : '',
			'duration'     => $last_dur,
			'breakdown'    => $breakdown,
			'scope_counts' => get_option(
				'gwqsh_last_scan_scope_counts',
				array(
					'Plugin'  => 0,
					'Theme'   => 0,
					'Uploads' => 0,
				)
			),
		);
	}
	/**
	 * Resolve scan path.
	 *
	 * @param mixed $relative_path Relative path.
	 * @param mixed $required_type Required type.
	 */
	private static function resolve_scan_path( $relative_path, $required_type = '' ) {
		global $wpdb;
		if ( ! is_string( $relative_path ) || '' === $relative_path || false !== strpos( $relative_path, "\0" ) || false !== strpos( $relative_path, '\\' ) ) {
			return false;
		}
		$relative = ltrim( $relative_path, '/' );
		if ( '' === $relative || preg_match( '#(^|/)\.{1,2}(/|$)#', $relative ) || preg_match( '#^[A-Za-z]:#', $relative ) ) {
			return false;
		}
		$root = realpath( ABSPATH );
		$full = ABSPATH . $relative;
		$dir  = dirname( $full );
		while ( ! file_exists( $dir ) && dirname( $dir ) !== $dir && $dir !== $root ) {
			$dir = dirname( $dir );
		}
		$parent = realpath( $dir );
		if ( ! $root || ! $parent || ( $parent !== $root && 0 !== strpos( $parent, $root . DIRECTORY_SEPARATOR ) ) || is_link( $full ) ) {
			return false;
		}
		if ( file_exists( $full ) ) {
			$real = realpath( $full );
			if ( ! $real || 0 !== strpos( $real, $root . DIRECTORY_SEPARATOR ) || ! is_file( $real ) ) {
				return false;
			}
		}
		$table = esc_sql( $wpdb->prefix . 'gwqsh_scan_results' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Fixed plugin-owned table identifier from the WordPress prefix.
		$type = $wpdb->get_var( $wpdb->prepare( "SELECT file_type FROM {$table} WHERE is_resolved = 0 AND (file_path = %s OR file_path = %s) LIMIT 1", '/' . $relative, $relative ) );
		if ( ! $type || ( $required_type && $type !== $required_type ) ) {
			return false;
		}
		return array( $relative, $full, $type );
	}
	/**
	 * Restore core file.
	 *
	 * @param mixed $relative_path Relative path.
	 */
	public static function restore_core_file( $relative_path ) {
		global $wp_version;
		$resolved = self::resolve_scan_path( $relative_path, 'Core' );
		if ( ! $resolved ) {
			return new WP_Error( 'invalid_path', 'Invalid core file path' );
		}
		list( $clean_path, $full_path ) = $resolved;
		$checksums                      = self::get_core_checksums();
		if ( ! is_array( $checksums ) || ! isset( $checksums[ $clean_path ] ) ) {
			return new WP_Error( 'invalid_core_file', 'File is not in official WordPress checksums.' );
		}

		// Fetch official file from WordPress SVN/Git CDN.
		$official_url = "https://core.svn.wordpress.org/tags/{$wp_version}/{$clean_path}";
		$resp         = wp_remote_get( $official_url, array( 'timeout' => 20 ) );

		if ( is_wp_error( $resp ) || 200 !== wp_remote_retrieve_response_code( $resp ) ) {
			return new WP_Error( 'fetch_failed', 'Could not fetch clean file from WordPress.org' );
		}

		$clean_content = wp_remote_retrieve_body( $resp );
		if ( md5( $clean_content ) !== $checksums[ $clean_path ] ) {
			return new WP_Error( 'checksum_mismatch', 'Downloaded file failed checksum verification.' );
		}

		// Backup existing file if present (best-effort backup).
		if ( file_exists( $full_path ) ) {
			self::quarantine_file( $clean_path, true );
		}

		// Ensure parent directory exists.
		if ( ! is_dir( dirname( $full_path ) ) ) {
			wp_mkdir_p( dirname( $full_path ) );
		}

		if ( false === @file_put_contents( $full_path, $clean_content, LOCK_EX ) ) {
			return new WP_Error( 'write_failed', 'Could not restore the core file. Please check file permissions.' );
		}
		self::mark_as_reviewed( $relative_path );

		GWQSH_Activity_Logger::log( 'Core file restored', "Restored official clean copy for {$clean_path}", 'System' );
		return true;
	}
	/**
	 * Quarantine file.
	 *
	 * @param mixed $relative_path Relative path.
	 * @param mixed $is_backup Is backup.
	 */
	public static function quarantine_file( $relative_path, $is_backup = false ) {
		$resolved = self::resolve_scan_path( $relative_path );
		if ( ! $resolved ) {
			return false;
		}
		list( $clean_path, $full_path ) = $resolved;

		if ( ! file_exists( $full_path ) ) {
			return false;
		}

		$hash     = substr( hash_hmac( 'sha256', home_url(), wp_salt( 'auth' ) ), 0, 20 );
		$quar_dir = trailingslashit( get_temp_dir() ) . 'gwqsh-quarantine-' . $hash;
		if ( ! is_dir( $quar_dir ) ) {
			wp_mkdir_p( $quar_dir );
		}
		if ( ! is_dir( $quar_dir ) || ! wp_is_writable( $quar_dir ) ) {
			$upload_dir = wp_upload_dir();
			$quar_dir   = trailingslashit( $upload_dir['basedir'] ) . 'gwqsh-quarantine-' . $hash;
			if ( ! is_dir( $quar_dir ) ) {
				wp_mkdir_p( $quar_dir );
			}
		}

		if ( ! is_dir( $quar_dir ) || is_link( $quar_dir ) || ! wp_is_writable( $quar_dir ) ) {
			return false;
		}

		// Security: prevent web execution in quarantine directory.
		$htaccess = trailingslashit( $quar_dir ) . '.htaccess';
		if ( is_link( $htaccess ) ) {
			return false;
		}
		if ( ! file_exists( $htaccess ) ) {
			if ( false === @file_put_contents( $htaccess, "Order deny,allow\nDeny from all\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n", LOCK_EX ) ) {
				return false;
			}
		}
		$idx = trailingslashit( $quar_dir ) . 'index.php';
		if ( is_link( $idx ) ) {
			return false;
		}
		if ( ! file_exists( $idx ) ) {
			if ( false === @file_put_contents( $idx, "<?php\n// Silence is golden.\n", LOCK_EX ) ) {
				return false;
			}
		}

		$dest_filename = bin2hex( random_bytes( 16 ) ) . '.quarantine';
		$dest          = trailingslashit( $quar_dir ) . $dest_filename;

		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		$filesystem = new WP_Filesystem_Direct( null );
		if ( $is_backup ) {
			$ok = $filesystem->copy( $full_path, $dest, false );
		} else {
			$ok = $filesystem->move( $full_path, $dest, false );
			if ( ! $ok ) {
				return false;
			}
			self::mark_as_reviewed( $relative_path );
			GWQSH_Activity_Logger::log( 'File quarantined', "Moved {$clean_path} to quarantine storage", 'Security' );
		}

		if ( ! $ok ) {
			return false;
		}
		$metadata                   = get_option( 'gwqsh_quarantine_metadata', array() );
		$metadata[ $dest_filename ] = array(
			'original' => $clean_path,
			'time'     => time(),
			'hash'     => hash_file( 'sha256', $dest ),
			'reason'   => $is_backup ? 'restore backup' : 'scan issue',
		);
		update_option( 'gwqsh_quarantine_metadata', $metadata, false );
		return true;
	}
	/**
	 * Get file diff.
	 *
	 * @param mixed $relative_path Relative path.
	 */
	public static function get_file_diff( $relative_path ) {
		global $wp_version;
		$resolved = self::resolve_scan_path( $relative_path, 'Core' );
		if ( ! $resolved ) {
			return new WP_Error( 'invalid_path', 'This file is not an unresolved core finding.' ); }
		list( $clean_path, $full_path ) = $resolved;
		$checksums                      = self::get_core_checksums();
		if ( ! is_array( $checksums ) || ! isset( $checksums[ $clean_path ] ) ) {
			return new WP_Error( 'no_original', 'No official baseline exists for this file.' ); }
		if ( file_exists( $full_path ) && ( ! is_readable( $full_path ) || filesize( $full_path ) > 131072 ) ) {
			return new WP_Error( 'large_file', 'This file is too large or unreadable. Compare the recorded hashes or download an official copy for offline comparison.' );
		}

		$local_content    = file_exists( $full_path ) ? file_get_contents( $full_path ) : '';
		$official_url     = "https://core.svn.wordpress.org/tags/{$wp_version}/{$clean_path}";
		$resp             = wp_remote_get(
			$official_url,
			array(
				'timeout'             => 15,
				'limit_response_size' => 131073,
			)
		);
		$official_content = ( ! is_wp_error( $resp ) && 200 === wp_remote_retrieve_response_code( $resp ) )
			? wp_remote_retrieve_body( $resp )
			: '';
		if ( ! $official_content || strlen( $official_content ) > 131072 || md5( $official_content ) !== $checksums[ $clean_path ] ) {
			return new WP_Error( 'baseline_unavailable', 'Could not retrieve a bounded, checksum-verified official file for comparison.' );
		}
		if ( false === $local_content || false !== strpos( $local_content . $official_content, "\0" ) || substr_count( $local_content, "\n" ) > 1500 || substr_count( $official_content, "\n" ) > 1500 ) {
			return new WP_Error( 'unsupported_diff', 'Binary files and files with more than 1,500 lines require offline comparison.' );
		}

		if ( function_exists( 'wp_text_diff' ) && $official_content ) {
			$diff = wp_text_diff(
				$official_content,
				$local_content,
				array(
					'title_left'  => 'Official WordPress.org',
					'title_right' => 'Local File',
				)
			);
			if ( $diff ) {
				return $diff;
			}
		}

		// Simple unified diff view fallback.
		$local_lines    = explode( "\n", $local_content );
		$official_lines = explode( "\n", $official_content );
		$out            = '<div class="code-block">';
		$out           .= '<span class="cm">--- Official WordPress.org (' . esc_html( $clean_path ) . ")</span>\n";
		$out           .= '<span class="cm">+++ Local File (' . esc_html( $clean_path ) . ")</span>\n";
		$max            = min( 40, max( count( $local_lines ), count( $official_lines ) ) );
		for ( $i = 0; $i < $max; $i++ ) {
			$l = isset( $local_lines[ $i ] ) ? $local_lines[ $i ] : '';
			$o = isset( $official_lines[ $i ] ) ? $official_lines[ $i ] : '';
			if ( $l !== $o ) {
				if ( $o ) {
					$out .= '<span class="del">- ' . esc_html( $o ) . "</span>\n";
				}
				if ( $l ) {
					$out .= '<span class="add">+ ' . esc_html( $l ) . "</span>\n";
				}
			} else {
				$out .= '  ' . esc_html( $l ) . "\n";
			}
		}
		$out .= '</div>';
		return $out;
	}
	/**
	 * Mark as reviewed.
	 *
	 * @param mixed $relative_path Relative path.
	 */
	public static function mark_as_reviewed( $relative_path ) {
		global $wpdb;
		if ( ! self::resolve_scan_path( $relative_path ) ) {
			return false; }
		$table = esc_sql( $wpdb->prefix . 'gwqsh_scan_results' );
		$path1 = '/' . ltrim( $relative_path, '/' );
		$path2 = ltrim( $relative_path, '/' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Fixed plugin-owned table identifier from the WordPress prefix; all dynamic values use prepare(). WordPress 5.3 has no %i placeholder.
				"UPDATE {$table} SET is_resolved = 1 WHERE file_path = %s OR file_path = %s",
				$path1,
				$path2
			)
		);
		return true;
	}
}
