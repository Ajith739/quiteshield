<?php
/**
 * Security scanner: orchestrates behavioral malware detection across scopes.
 *
 * Scopes: WordPress core (checksums), must-use plugins, normal plugins,
 * themes, uploads and webroot. Every PHP file is statically analyzed by the
 * token-based analyzer and evaluated by the rule registry; verdicts come from
 * the explainable scoring engine.
 *
 * Performance: bounded file counts per scope, size caps, binary skipping, a
 * content-hash analysis cache, and a wall-clock budget with partial results
 * recorded when the budget is exhausted.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * GWQSH security scanner.
 */
final class GWQSH_Security_Scanner {

	/**
	 * Per-scope PHP file caps.
	 *
	 * @var array
	 */
	private static $scope_caps = array(
		'Plugin'  => 4000,
		'Theme'   => 2000,
		'Uploads' => 2000,
		'MU'      => 200,
		'Root'    => 200,
	);

	/**
	 * Whole-scan wall-clock budget (seconds).
	 */
	const TIME_BUDGET = 90;

	/**
	 * Analysis cache option.
	 */
	const CACHE_OPTION = 'gwqsh_analysis_cache';

	/**
	 * Maximum cached analysis entries.
	 */
	const CACHE_LIMIT = 4000;

	/**
	 * Counters for the current run.
	 *
	 * @var array
	 */
	private static $counters = array();

	/**
	 * Run the full scan and persist results.
	 *
	 * @return array|WP_Error Summary array (compatible with the legacy shape).
	 */
	public static function run() {
		global $wpdb;

		$t0            = microtime( true );
		$results_table = esc_sql( $wpdb->prefix . 'gwqsh_scan_results' );
		$history_table = esc_sql( $wpdb->prefix . 'gwqsh_scan_history' );
		$now           = current_time( 'mysql' );

		self::$counters = array(
			'total'      => 0,
			'issues'     => 0,
			'modified'   => 0,
			'missing'    => 0,
			'suspicious' => 0,
			'critical'   => 0,
			'inspected'  => array(
				'Plugin'  => 0,
				'Theme'   => 0,
				'Uploads' => 0,
				'MU'      => 0,
				'Root'    => 0,
			),
			'degraded'   => array(),
		);

		// Start from a clean slate: mark previous unresolved findings resolved
		// by replacement (same behavior as the legacy scanner).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $results_table, array( 'is_resolved' => 0 ) );

		// 1. Core integrity via official checksums (graceful degradation).
		$checksums = GWQSH_File_Integrity::get_core_checksums();
		$core_ok   = is_array( $checksums );
		if ( $core_ok ) {
			self::scan_core_checksums( $checksums, $results_table, $now );
		} else {
			self::$counters['degraded'][] = 'Core checksums were unavailable (WordPress.org not reachable); core integrity was not verified in this scan.';
		}

		// 2. Must-use plugins (top-level .php files + referenced subdirs).
		self::scan_mu_plugins( $results_table, $now );

		// 3. Plugins and themes (behavioral analysis).
		self::scan_component( 'Plugin', WP_PLUGIN_DIR, $results_table, $now );
		self::scan_component( 'Theme', get_theme_root(), $results_table, $now );

		// 4. Uploads (executables, polyglots, htaccess-like).
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['basedir'] ) && is_dir( $uploads['basedir'] ) ) {
			self::scan_uploads( $uploads['basedir'], $results_table, $now );
		}

		// 5. Webroot (wp-config audit, .htaccess audit, unexpected root files).
		self::scan_webroot( $results_table, $now );

		$duration = max( 1, (int) round( microtime( true ) - $t0 ) );

		$clean = max( 0, self::$counters['total'] - self::$counters['modified'] - self::$counters['missing'] - self::$counters['suspicious'] );

		update_option( 'gwqsh_last_scan_time', $now );
		update_option( 'gwqsh_last_scan_duration', $duration );
		update_option( 'gwqsh_last_scan_total', self::$counters['total'] );
		update_option( 'gwqsh_last_scan_core_total', $core_ok ? count( $checksums ) : 0 );
		update_option( 'gwqsh_last_scan_scope_counts', self::$counters['inspected'] );
		update_option( 'gwqsh_last_scan_degraded', self::$counters['degraded'] );

		// History entry (legacy shape).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$history_table,
			array(
				'scan_date'        => $now,
				'duration'         => $duration,
				'total_files'      => self::$counters['total'],
				'clean_files'      => $clean,
				'modified_files'   => self::$counters['modified'],
				'missing_files'    => self::$counters['missing'],
				'suspicious_files' => self::$counters['suspicious'],
			)
		);

		if ( self::$counters['critical'] > 0 ) {
			GWQSH_Activity_Logger::log(
				'Critical malware findings',
				'Scan completed with ' . self::$counters['critical'] . ' Critical finding(s). Review and quarantine them before establishing baselines.',
				'Security'
			);
		} else {
			GWQSH_Activity_Logger::log( 'File integrity scan completed', 'Scanned ' . self::$counters['total'] . ' files in ' . $duration . 's.', 'Security' );
		}

		$summary            = GWQSH_File_Integrity::get_summary();
		$summary['scan_id'] = $wpdb->insert_id;
		return $summary;
	}

	/**
	 * Core checksum verification (unchanged semantics from the legacy engine).
	 *
	 * @param array  $checksums     path => expected md5.
	 * @param string $results_table Table.
	 * @param string $now           Timestamp.
	 */
	private static function scan_core_checksums( $checksums, $results_table, $now ) {
		global $wpdb;
		foreach ( $checksums as $relative_path => $expected_md5 ) {
			++self::$counters['total'];
			$full_path = ABSPATH . $relative_path;

			if ( ! file_exists( $full_path ) ) {
				++self::$counters['missing'];
				self::insert_finding(
					$results_table,
					array(
						'path'     => '/' . ltrim( $relative_path, '/' ),
						'type'     => 'Core',
						'status'   => 'Missing',
						'details'  => 'Official WordPress core file is missing',
						'severity' => 'medium',
						'rule_id'  => 'GQR-CORE',
						'detected' => $now,
					)
				);
				continue;
			}

			$actual_md5 = ( is_file( $full_path ) && ! is_link( $full_path ) && is_readable( $full_path ) ) ? md5_file( $full_path ) : false;
			if ( $actual_md5 !== $expected_md5 ) {
				++self::$counters['modified'];
				self::insert_finding(
					$results_table,
					array(
						'path'     => '/' . ltrim( $relative_path, '/' ),
						'type'     => 'Core',
						'status'   => 'Modified',
						'details'  => 'Official MD5: ' . $expected_md5 . '; current MD5: ' . ( $actual_md5 ? $actual_md5 : 'unreadable or not a regular file' ),
						'severity' => 'high',
						'rule_id'  => 'GQR-CORE',
						'detected' => $now,
					)
				);
			}
		}

		// Unexpected PHP files inside core directories.
		$inspected = 0;
		foreach ( array( 'wp-admin', 'wp-includes' ) as $core_dir ) {
			$directory = ABSPATH . $core_dir;
			if ( ! is_dir( $directory ) || is_link( $directory ) ) {
				continue;
			}
			$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS ) );
			foreach ( $iterator as $item ) {
				if ( ++$inspected > 25000 ) {
					self::$counters['degraded'][] = 'Core directory inspection stopped at the 25,000 entry safety cap.';
					break 2;
				}
				if ( ! $item->isFile() || $item->isLink() || 'php' !== strtolower( $item->getExtension() ) ) {
					continue;
				}
				$relative = substr( wp_normalize_path( $item->getPathname() ), strlen( wp_normalize_path( ABSPATH ) ) );
				if ( isset( $checksums[ $relative ] ) ) {
					continue;
				}
				++self::$counters['total'];

				// Analyze the unexpected core file behaviorally as well.
				$verdict = self::analyze_and_score( $item->getPathname(), 'core', array( 'file_name' => $item->getFilename() ), $results_table, $now, '/Core/', false );
				if ( ! $verdict ) {
					// analyze_and_score() already owns the suspicious counter
					// for verdicts; count only the fallback finding here.
					++self::$counters['suspicious'];
					self::insert_finding(
						$results_table,
						array(
							'path'     => '/' . $relative,
							'type'     => 'Core',
							'status'   => 'Suspicious',
							'details'  => 'Unexpected PHP file in a WordPress core directory',
							'severity' => 'high',
							'rule_id'  => 'GQR-CORE',
							'detected' => $now,
						)
					);
				}
			}
		}
	}

	/**
	 * Scan must-use plugins.
	 *
	 * @param string $results_table Table.
	 * @param string $now           Timestamp.
	 */
	private static function scan_mu_plugins( $results_table, $now ) {
		if ( ! defined( 'WPMU_PLUGIN_DIR' ) || ! WPMU_PLUGIN_DIR ) {
			return;
		}
		$dir = GWQSH_Path_Guard::directory( WPMU_PLUGIN_DIR );
		if ( ! $dir ) {
			return;
		}

		$handle = @opendir( $dir ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Bounded read of a core-defined directory.
		if ( ! $handle ) {
			self::$counters['degraded'][] = 'The must-use plugin directory could not be read.';
			return;
		}
		$entries = array();
		while ( false !== ( $entry = readdir( $handle ) ) ) {
			$entries[] = $entry;
		}
		closedir( $handle );
		sort( $entries );

		$count = 0;
		foreach ( $entries as $entry ) {
			if ( ++self::$counters['inspected']['MU'] > self::$scope_caps['MU'] ) {
				self::$counters['degraded'][] = 'Must-use plugin inspection stopped at the 200 file safety cap.';
				break;
			}
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}
			$path = $dir . '/' . $entry;
			if ( ! is_file( $path ) || is_link( $path ) || '.php' !== strtolower( substr( $entry, -4 ) ) ) {
				continue;
			}
			++$count;
			$read = gwqsh_guardian_core_read_file( $path, GWQSH_PHP_Analyzer::MAX_ANALYZE_BYTES );
			if ( ! is_array( $read ) ) {
				continue;
			}

			// Our own deployed Early Guardian is exempt from behavioral
			// scoring ONLY while its content hash still matches the bundled
			// template. The check is cryptographic, not name-based: a tampered
			// or outdated copy no longer matches and is scanned like any other
			// must-use file. (Without this, the Guardian's legitimate
			// quarantine/repair behaviors would self-flag as Critical.)
			if ( GWQSH_Guardian_Manager::INSTALL_NAME === $entry ) {
				$template = gwqsh_guardian_core_read_file( GWQSH_PATH . GWQSH_Guardian_Manager::TEMPLATE, 524288 );
				if ( is_array( $template ) && hash_equals( $template['sha256'], $read['sha256'] ) ) {
					continue;
				}
			}

			$state    = GWQSH_Baseline_Manager::file_state( $entry, $read['sha256'], 'mu' );
			$verdicts = self::evaluate_source( $read['code'], array(
				'component'      => 'mu',
				'file_name'      => $entry,
				'baseline_state' => $state,
			) );

			$verdict = GWQSH_Scoring_Engine::verdict( $verdicts, array( 'component' => 'mu' ) );
			if ( 'trusted' !== $state && ( 'info' !== $verdict['severity'] || 'new' === $state || 'modified' === $state ) ) {
				$status = in_array( $verdict['severity'], array( 'medium', 'high', 'critical' ), true ) ? 'Suspicious' : 'Modified';
				if ( 'new' === $state && 'info' === $verdict['severity'] ) {
					$status = 'Suspicious'; // New MU file without baseline: review item.
				}
				$detail = '[' . $verdict['severity_label'] . '] ' . ( '' !== $verdict['summary'] ? $verdict['summary'] : 'Must-use plugin state changed' );
				if ( 'modified' === $state ) {
					$detail = 'Baseline change: ' . $detail;
				}
				++self::$counters['total'];
				++self::$counters['suspicious'];
				if ( 'critical' === $verdict['severity'] ) {
					++self::$counters['critical'];
				}
				self::insert_finding( $results_table, array(
					'path'     => '/wp-content/mu-plugins/' . $entry,
					'type'     => 'MU Plugin',
					'status'   => $status,
					'details'  => $detail,
					'severity' => $verdict['severity'],
					'rule_id'  => $verdict['rule_id'],
					'score'    => $verdict['score'],
					'sha256'   => $read['sha256'],
					'evidence' => $verdict['evidence'],
					'detected' => $now,
				) );
			}
		}

		if ( $count > 0 ) {
			// Record how many were inspected for summary accuracy.
			self::$counters['inspected']['MU'] = max( self::$counters['inspected']['MU'], $count );
		}
	}

	/**
	 * Scan a normal component directory (plugins or themes).
	 *
	 * @param string $scope         'Plugin' or 'Theme'.
	 * @param string $base          Directory.
	 * @param string $results_table Table.
	 * @param string $now           Timestamp.
	 */
	private static function scan_component( $scope, $base, $results_table, $now ) {
		if ( ! is_string( $base ) || '' === $base || ! is_dir( $base ) ) {
			return;
		}
		$own_path = trailingslashit( wp_normalize_path( GWQSH_PATH ) );

		try {
			$directory = new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS );
			$filtered  = new RecursiveCallbackFilterIterator(
				$directory,
				static function ( $item ) use ( $own_path ) {
					$path = trailingslashit( wp_normalize_path( $item->getPathname() ) );
					return 0 !== strpos( $path, $own_path );
				}
			);
			$iterator = new RecursiveIteratorIterator( $filtered );
		} catch ( Exception $e ) {
			self::$counters['degraded'][] = $scope . ' scan could not open the directory.';
			return;
		}

		$component_key = ( 'Plugin' === $scope ) ? 'plugin' : 'theme';
		$started       = microtime( true );
		foreach ( $iterator as $item ) {
			if ( self::$counters['inspected'][ $scope ] >= self::$scope_caps[ $scope ] ) {
				self::$counters['degraded'][] = $scope . ' scan stopped at the ' . self::$scope_caps[ $scope ] . ' file safety cap.';
				break;
			}
			if ( ( microtime( true ) - $started ) > self::TIME_BUDGET ) {
				self::$counters['degraded'][] = $scope . ' scan stopped at the time budget; run another scan to continue.';
				break;
			}
			if ( ! $item->isFile() || $item->isLink() || 'php' !== strtolower( $item->getExtension() ) ) {
				continue;
			}
			$path = $item->getPathname();
			if ( $item->getSize() > GWQSH_PHP_Analyzer::MAX_ANALYZE_BYTES ) {
				++self::$counters['inspected'][ $scope ];
				self::record_partial( $path, $scope, $results_table, $now );
				continue;
			}
			++self::$counters['inspected'][ $scope ];
			self::analyze_and_score( $path, $component_key, array( 'file_name' => $item->getFilename() ), $results_table, $now, '/' . $scope . '/' );
		}
	}

	/**
	 * Scan the uploads directory.
	 *
	 * @param string $base          Uploads basedir.
	 * @param string $results_table Table.
	 * @param string $now           Timestamp.
	 */
	private static function scan_uploads( $base, $results_table, $now ) {
		$guard = GWQSH_Path_Guard::directory( $base );
		if ( ! $guard ) {
			return;
		}
		try {
			$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $guard, FilesystemIterator::SKIP_DOTS ) );
		} catch ( Exception $e ) {
			return;
		}
		$started = microtime( true );
		foreach ( $iterator as $item ) {
			if ( self::$counters['inspected']['Uploads'] >= self::$scope_caps['Uploads'] ) {
				self::$counters['degraded'][] = 'Uploads scan stopped at the 2,000 file safety cap.';
				break;
			}
			if ( ( microtime( true ) - $started ) > self::TIME_BUDGET ) {
				self::$counters['degraded'][] = 'Uploads scan stopped at the time budget; run another scan to continue.';
				break;
			}
			if ( ! $item->isFile() || $item->isLink() ) {
				continue;
			}
			$name  = $item->getFilename();
			$ext   = strtolower( $item->getExtension() );
			$is_exec = (bool) preg_match( '/^(php\d?|phtml|phar|pht|phps|cgi|pl|py|asp|aspx|jsp|sh)$/i', $ext );
			$is_server_cfg = in_array( $name, array( '.htaccess', '.user.ini', '.htaccess.bak', 'php.ini' ), true );

			if ( ! $is_exec && ! $is_server_cfg ) {
				// Polyglot check for a bounded set of common types.
				if ( in_array( $ext, array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'ico', 'pdf', 'svg', 'txt' ), true ) && $item->getSize() < 500000 ) {
					$first = (string) @file_get_contents( $item->getPathname(), false, null, 0, 4096 ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file bounded prefix read.
					if ( false !== strpos( $first, '<?php' ) ) {
						++self::$counters['inspected']['Uploads'];
						self::$counters['total']++;
						++self::$counters['suspicious'];
						$relative = '/' . ltrim( str_replace( wp_normalize_path( ABSPATH ), '', wp_normalize_path( $item->getPathname() ) ), '/' );
						self::insert_finding( $results_table, array(
							'path'     => $relative,
							'type'     => 'Uploads',
							'status'   => 'Suspicious',
							'details'  => '[High] PHP code embedded inside an uploaded media/text file (possible polyglot attack)',
							'severity' => 'high',
							'rule_id'  => 'GQR-UPLX',
							'evidence' => array( 'PHP opening tag found in file prefix' ),
							'detected' => $now,
						) );
					}
				}
				continue;
			}
			++self::$counters['inspected']['Uploads'];
			++self::$counters['total'];

			if ( $is_server_cfg ) {
				// QuietShield's own uploads guard is content-verified and skipped:
				// the marker block must be present and must not re-enable execution.
				if ( '.htaccess' === $name ) {
					$guard = (string) @file_get_contents( $item->getPathname(), false, null, 0, 8192 ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Bounded local read.
					if ( false !== strpos( $guard, '# BEGIN Gracewell QuietShield' )
						&& false !== strpos( $guard, 'Require all denied' )
						&& ! preg_match( '/(?:php_flag\s+engine\s+on|AddHandler|RemoveHandler|auto_prepend|php_value\s+(?:auto_prepend_file|include_path))/i', $guard ) ) {
						--self::$counters['total'];
						continue;
					}
				}
				++self::$counters['suspicious'];
				$relative = '/' . ltrim( str_replace( wp_normalize_path( ABSPATH ), '', wp_normalize_path( $item->getPathname() ) ), '/' );
				self::insert_finding( $results_table, array(
					'path'     => $relative,
					'type'     => 'Uploads',
					'status'   => 'Suspicious',
					'details'  => '[Medium] Server configuration file present in uploads; it can re-enable script execution',
					'severity' => 'medium',
					'rule_id'  => 'GQR-UPLX',
					'detected' => $now,
				) );
				continue;
			}

			// Executable: run the full behavioral pipeline.
			self::analyze_and_score( $item->getPathname(), 'uploads', array( 'file_name' => $name ), $results_table, $now, '/Uploads/' );
		}
	}

	/**
	 * Scan the webroot: wp-config audit, .htaccess audit, unexpected root files.
	 *
	 * @param string $results_table Table.
	 * @param string $now           Timestamp.
	 */
	private static function scan_webroot( $results_table, $now ) {
		$root = GWQSH_Path_Guard::root();

		// Root baseline integrity (index.php, wp-config.php, .htaccess...).
		$root_baseline = GWQSH_Baseline_Manager::get( 'root' );
		if ( ! empty( $root_baseline ) ) {
			foreach ( $root_baseline as $name => $record ) {
				$path = $root . '/' . $name;
				if ( ! is_file( $path ) ) {
					continue;
				}
				$read = gwqsh_guardian_core_read_file( $path, 5242880 );
				if ( ! is_array( $read ) ) {
					continue;
				}
				$hash = is_array( $record ) && isset( $record['sha256'] ) ? $record['sha256'] : $record;
				if ( $hash !== $read['sha256'] ) {
					++self::$counters['total'];
					++self::$counters['modified'];
					self::insert_finding( $results_table, array(
						'path'     => '/' . $name,
						'type'     => 'Root',
						'status'   => 'Modified',
						'details'  => '[High] Trusted root file changed since the baseline was established' . ( 'wp-config.php' === $name ? ' (review changes carefully)' : '' ),
						'severity' => 'high',
						'rule_id'  => 'GQR-BASELINE',
						'sha256'   => $read['sha256'],
						'detected' => $now,
					) );
				}
			}
		}

		// .htaccess audit.
		$htaudit = GWQSH_Htaccess_Auditor::audit( $root . '/.htaccess' );
		if ( ! empty( $htaudit['findings'] ) ) {
			foreach ( $htaudit['findings'] as $finding ) {
				++self::$counters['total'];
				++self::$counters['suspicious'];
				if ( 'critical' === $finding['severity'] ) {
					++self::$counters['critical'];
				}
				self::insert_finding( $results_table, array(
					'path'     => '/.htaccess',
					'type'     => 'Root',
					'status'   => 'Suspicious',
					'details'  => '[' . $finding['severity_label'] . '] ' . $finding['label'],
					'severity' => $finding['severity'],
					'rule_id'  => 'GQR-HTA',
					'score'    => $finding['score'],
					'evidence' => $finding['evidence'],
					'detected' => $now,
				) );
			}
		}

		// wp-config.php audit.
		$wpconfig = GWQSH_WpConfig_Auditor::audit( $root . '/wp-config.php' );
		if ( ! empty( $wpconfig['findings'] ) ) {
			foreach ( $wpconfig['findings'] as $finding ) {
				++self::$counters['total'];
				++self::$counters['suspicious'];
				if ( 'critical' === $finding['severity'] ) {
					++self::$counters['critical'];
				}
				self::insert_finding( $results_table, array(
					'path'     => '/wp-config.php',
					'type'     => 'Root',
					'status'   => 'Suspicious',
					'details'  => '[' . $finding['severity_label'] . '] ' . $finding['label'],
					'severity' => $finding['severity'],
					'rule_id'  => 'GQR-WPCFG',
					'score'    => $finding['score'],
					'evidence' => $finding['evidence'],
					'detected' => $now,
				) );
			}
		}

		// Unexpected root-level PHP files (excluding standard WP files).
		$standard_root = array( 'index.php', 'wp-config.php', 'wp-blog-header.php', 'wp-load.php', 'wp-login.php', 'wp-cron.php', 'wp-mail.php', 'wp-settings.php', 'wp-trackback.php', 'wp-links-opml.php', 'wp-activate.php', 'wp-signup.php', 'wp-comments-post.php', 'xmlrpc.php', 'license.txt', 'readme.html', 'wp-config-sample.php' );
		$handle = @opendir( $root ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_opendir -- Bounded webroot read.
		if ( ! $handle ) {
			return;
		}
		while ( false !== ( $entry = readdir( $handle ) ) ) {
			if ( self::$counters['inspected']['Root'] >= self::$scope_caps['Root'] ) {
				break;
			}
			if ( '.' === $entry || '..' === $entry || ! is_file( $root . '/' . $entry ) || is_link( $root . '/' . $entry ) ) {
				continue;
			}
			$ext = strtolower( substr( $entry, strrpos( $entry, '.' ) === false ? strlen( $entry ) : strrpos( $entry, '.' ) + 1 ) );
			if ( ! in_array( $ext, array( 'php', 'phtml', 'phar' ), true ) ) {
				continue;
			}
			++self::$counters['inspected']['Root'];
			if ( in_array( $entry, $standard_root, true ) ) {
				continue;
			}
			// Non-standard executable in the webroot: analyze behaviorally.
			$verdict = self::analyze_and_score( $root . '/' . $entry, 'root', array( 'file_name' => $entry ), $results_table, $now, '/Root/' );
			if ( ! $verdict ) {
				// Even without behavioral matches, a non-standard root executable is notable.
				++self::$counters['suspicious'];
				self::insert_finding( $results_table, array(
					'path'     => '/' . $entry,
					'type'     => 'Root',
					'status'   => 'Suspicious',
					'details'  => '[Medium] Unexpected executable PHP file in the WordPress webroot',
					'severity' => 'medium',
					'rule_id'  => 'GQR-ROOTX',
					'detected' => $now,
				) );
			}
		}
		closedir( $handle );
	}

	/**
	 * Analyze a file, score it and persist a finding when warranted.
	 *
	 * @param string $path          Absolute file path.
	 * @param string $component     Component key (plugin|theme|uploads|mu|core|root).
	 * @param array  $context       Extra context.
	 * @param string $results_table Table.
	 * @param string $now           Timestamp.
	 * @param string $type_label_prefix Prefix for scope type.
	 * @param bool   $count_total   Whether to add the file to the inspected total.
	 * @return array|null Verdict when a finding was recorded, else null.
	 */
	private static function analyze_and_score( $path, $component, $context, $results_table, $now, $type_label_prefix = '', $count_total = true ) {
		$canonical = GWQSH_Path_Guard::regular_file( $path );
		if ( ! $canonical ) {
			return null;
		}

		$analysis = GWQSH_PHP_Analyzer::analyze_file( $canonical, $context + array( 'component' => $component ) );
		if ( ! is_array( $analysis ) ) {
			return null;
		}
		if ( $count_total ) {
			++self::$counters['total'];
		}

		$verdicts = self::evaluate_cached( $analysis, $context + array( 'component' => $component ) );
		$verdict  = GWQSH_Scoring_Engine::verdict( $verdicts, array(
			'component'      => $component,
			'is_hidden_file' => 0 === strpos( basename( $canonical ), '.' ),
		) );

		if ( 'info' === $verdict['severity'] ) {
			// Informational-only files are not surfaced as findings (keeps
			// dashboards actionable); they remain in the analysis cache.
			return null;
		}

		++self::$counters['suspicious'];
		if ( 'critical' === $verdict['severity'] ) {
			++self::$counters['critical'];
		}

		$type_map = array(
			'plugin' => 'Plugin',
			'theme'  => 'Theme',
			'uploads' => 'Uploads',
			'mu'     => 'MU Plugin',
			'core'   => 'Core',
			'root'   => 'Root',
		);
		$type     = isset( $type_map[ $component ] ) ? $type_map[ $component ] : 'Plugin';
		$relative = '/' . ltrim( str_replace( wp_normalize_path( ABSPATH ), '', wp_normalize_path( $canonical ) ), '/' );

		self::insert_finding( $results_table, array(
			'path'     => $relative,
			'type'     => $type,
			'status'   => 'Suspicious',
			'details'  => '[' . $verdict['severity_label'] . '] ' . $verdict['summary'] . self::truncation_note( $analysis ),
			'severity' => $verdict['severity'],
			'rule_id'  => $verdict['rule_id'],
			'score'    => $verdict['score'],
			'sha256'   => isset( $analysis['sha256'] ) ? $analysis['sha256'] : '',
			'evidence' => $verdict['evidence'],
			'detected' => $now,
		) );

		return $verdict;
	}

	/**
	 * Evaluate rule findings through the content-hash cache.
	 *
	 * @param array $analysis Analysis.
	 * @param array $context  Context.
	 * @return array Findings.
	 */
	private static function evaluate_cached( $analysis, $context ) {
		$sha = isset( $analysis['sha256'] ) ? $analysis['sha256'] : '';
		if ( '' === $sha ) {
			return GWQSH_Detection_Rules::evaluate_all( $analysis, $context );
		}
		$cache = get_option( self::CACHE_OPTION, array() );
		if ( ! is_array( $cache ) ) {
			$cache = array();
		}
		$key = $sha . ':' . ( isset( $context['component'] ) ? $context['component'] : '' ) . ':' . ( isset( $context['baseline_state'] ) ? $context['baseline_state'] : '' );
		if ( isset( $cache[ $key ] ) && is_array( $cache[ $key ] ) && isset( $cache[ $key ]['at'] ) && ( time() - (int) $cache[ $key ]['at'] ) < DAY_IN_SECONDS * 7 ) {
			return $cache[ $key ]['findings'];
		}
		$findings = GWQSH_Detection_Rules::evaluate_all( $analysis, $context );

		$cache[ $key ] = array( 'at' => time(), 'findings' => $findings );
		if ( count( $cache ) > self::CACHE_LIMIT ) {
			uasort( $cache, static function ( $a, $b ) {
				return (int) $a['at'] - (int) $b['at'];
			} );
			$cache = array_slice( $cache, -self::CACHE_LIMIT, null, true );
		}
		update_option( self::CACHE_OPTION, $cache, false );
		return $findings;
	}

	/**
	 * Evaluate rules against a source string (uncached).
	 *
	 * @param string $code    Source.
	 * @param array  $context Context.
	 * @return array
	 */
	public static function evaluate_source( $code, $context ) {
		$analysis = GWQSH_PHP_Analyzer::analyze_code( $code, $context );
		return GWQSH_Detection_Rules::evaluate_all( $analysis, $context );
	}

	/**
	 * Record a "could not fully analyze" finding for oversized files.
	 *
	 * @param string $path          Path.
	 * @param string $scope         Scope label.
	 * @param string $results_table Table.
	 * @param string $now           Timestamp.
	 */
	private static function record_partial( $path, $scope, $results_table, $now ) {
		$relative = '/' . ltrim( str_replace( wp_normalize_path( ABSPATH ), '', wp_normalize_path( $path ) ), '/' );
		++self::$counters['total'];
		++self::$counters['suspicious'];
		self::insert_finding( $results_table, array(
			'path'     => $relative,
			'type'     => $scope,
			'status'   => 'Suspicious',
			'details'  => '[Low] File exceeds the analysis size cap; it could not be fully analyzed. Review manually if unexpected.',
			'severity' => 'low',
			'rule_id'  => 'GQR-LIMIT',
			'detected' => $now,
		) );
	}

	/**
	 * Truncation note for findings.
	 *
	 * @param array $analysis Analysis.
	 * @return string
	 */
	private static function truncation_note( $analysis ) {
		if ( ! empty( $analysis['truncated'] ) ) {
			return ' (file exceeds the analysis size cap; analysis is partial)';
		}
		return '';
	}

	/**
	 * Insert a finding row (new columns included).
	 *
	 * @param string $results_table Table name.
	 * @param array  $f             Finding fields.
	 */
	private static function insert_finding( $results_table, $f ) {
		global $wpdb;
		$row = array(
			'file_path'   => isset( $f['path'] ) ? $f['path'] : '',
			'file_type'   => isset( $f['type'] ) ? $f['type'] : 'Core',
			'status'      => isset( $f['status'] ) ? $f['status'] : 'Suspicious',
			'details'     => isset( $f['details'] ) ? $f['details'] : '',
			'detected_at' => isset( $f['detected'] ) ? $f['detected'] : current_time( 'mysql' ),
			'is_resolved' => 0,
			'severity'    => isset( $f['severity'] ) ? $f['severity'] : '',
			'rule_id'     => isset( $f['rule_id'] ) ? $f['rule_id'] : '',
			'risk_score'  => isset( $f['score'] ) ? (int) $f['score'] : 0,
			'file_sha256' => isset( $f['sha256'] ) ? $f['sha256'] : '',
			'evidence'    => ! empty( $f['evidence'] ) && is_array( $f['evidence'] ) ? implode( "\n", array_map( 'sanitize_text_field', array_slice( $f['evidence'], 0, 8 ) ) ) : '',
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert( $results_table, $row );
	}

	/**
	 * Counters accessor (tests).
	 *
	 * @return array
	 */
	public static function counters() {
		return self::$counters;
	}
}

