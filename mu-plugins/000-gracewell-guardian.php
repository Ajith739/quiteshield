<?php
/**
 * Plugin Name: Gracewell Early Guardian
 * Description: Boot-time behavioral guard for must-use plugins. Neutralizes only extremely-high-confidence malware before it can execute; never disables WordPress on failure.
 * Version: 1.1.0
 * Author: Gracewell QuietShield
 * License: GPL-2.0-or-later
 *
 * Gracewell Early Guardian loads before normal must-use plugins (file name
 * ordering 000-) and checks the must-use directory for NEW or CHANGED files.
 * Only new/changed files are hashed and behaviorally analyzed (no full scan
 * on every request). Files are quarantined - never deleted - only when the
 * shared analysis kernel reports extremely-high-confidence malware behavior
 * that matches real incident classes:
 *
 *   - cloaking / traffic hijacking (bot detection + redirects or remote payloads)
 *   - self-healing persistence (critical-file writes + backups / chmod locks)
 *   - web shells (request input reaching process execution)
 *
 * Any internal failure is caught and logged; WordPress continues to boot.
 *
 * Documented limitations (an attacker with full filesystem access can):
 *   - delete or corrupt this Guardian file (WordPress fatals on broken MU
 *     files; verify this file after incidents),
 *   - manipulate timestamps/sizes to avoid change detection (mitigated by
 *     periodic probabilistic full re-verification),
 *   - write persistence outside the must-use directory (the main plugin's
 *     scanner covers those locations during scheduled/manual scans).
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'GWQSH_GUARDIAN_VERSION' ) ) {
	define( 'GWQSH_GUARDIAN_VERSION', '1.1.0' );
}

/**
 * Boot the Early Guardian exactly once per request.
 */
function gwqsh_early_guardian_boot() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;

	try {
		gwqsh_early_guardian_check_mu_plugins();
	} catch ( Throwable $e ) {
		// The Guardian must never take the site down.
		error_log( 'Gracewell Early Guardian: internal error (' . esc_html( get_class( $e ) ) . '): ' . esc_html( substr( (string) $e->getMessage(), 0, 200 ) ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.Security.EscapeOutput.OutputNotEscaped -- error_log writes to the log, esc_html'd.
	}
}

/**
 * Load the shared analysis kernel (single source of truth with the main
 * plugin). Falls back to a minimal inline baseline/indicator check when the
 * main plugin directory is absent.
 *
 * @return bool True when the full kernel is available.
 */
function gwqsh_early_guardian_load_kernel() {
	if ( function_exists( 'gwqsh_guardian_core_score' ) ) {
		return true;
	}
	if ( ! defined( 'WP_PLUGIN_DIR' ) || ! WP_PLUGIN_DIR || ! is_dir( WP_PLUGIN_DIR ) ) {
		return false;
	}
	$root = (string) WP_PLUGIN_DIR;
	// Canonical directory names first, then a bounded one-level search for
	// installations where the plugin folder carries another name. Loading by
	// path is acceptable within the documented threat model: an attacker able
	// to write into the plugins root can already plant an executable plugin
	// there, which runs on every request regardless of the Guardian.
	$candidates = array(
		$root . '/gracewell-quietshield/includes/security/gwqsh-guardian-core.php',
		$root . '/quiteshield/includes/security/gwqsh-guardian-core.php',
	);
	$found = glob( $root . '/*/includes/security/gwqsh-guardian-core.php' );
	if ( is_array( $found ) && $found ) {
		sort( $found );
		$candidates = array_merge( $candidates, $found );
	}
	$root_real = realpath( $root );
	if ( ! $root_real ) {
		return false;
	}
	$root_real .= DIRECTORY_SEPARATOR;
	foreach ( $candidates as $file ) {
		if ( ! is_string( $file ) || '' === $file || ! is_file( $file ) || is_link( $file ) || ! is_readable( $file ) ) {
			continue;
		}
		$real = realpath( $file );
		// Containment: the kernel must physically live inside the plugins root
		// (rejects symlinked plugin folders pointing outside the tree).
		if ( ! $real || 0 !== strpos( $real . DIRECTORY_SEPARATOR, $root_real ) ) {
			continue;
		}
		require_once $real;
		if ( function_exists( 'gwqsh_guardian_core_score' ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Bounded read + hash with the kernel's exact contract, so file hashes stay
 * comparable across kernel and degraded modes.
 *
 * @param string $path      Absolute file path (caller-validated).
 * @param int    $max_bytes Read cap in bytes.
 * @return array|null array( 'code', 'size', 'truncated', 'sha256' ) or null.
 */
function gwqsh_early_guardian_read( $path, $max_bytes ) {
	if ( function_exists( 'gwqsh_guardian_core_read_file' ) ) {
		return gwqsh_guardian_core_read_file( $path, $max_bytes );
	}
	// Degraded fallback: mirrors gwqsh_guardian_core_read_file() exactly.
	if ( ! is_string( $path ) || '' === $path || ! is_file( $path ) || is_link( $path ) ) {
		return null;
	}
	$size = filesize( $path );
	if ( false === $size ) {
		return null;
	}
	$handle = fopen( $path, 'rb' );
	if ( ! $handle ) {
		return null;
	}
	$to_read   = min( $size, $max_bytes );
	$buffer    = '';
	$remaining = $to_read;
	while ( $remaining > 0 && ! feof( $handle ) ) {
		$part = fread( $handle, min( 65536, $remaining ) );
		if ( false === $part || '' === $part ) {
			break;
		}
		$buffer    .= $part;
		$remaining -= strlen( $part );
	}
	fclose( $handle );
	return array(
		'code'      => $buffer,
		'size'      => $size,
		'truncated' => $size > strlen( $buffer ),
		'sha256'    => hash( 'sha256', $buffer ),
	);
}

/**
 * Check must-use plugins for new or changed files; quarantine only
 * extremely-high-confidence malware.
 */
function gwqsh_early_guardian_check_mu_plugins() {
	if ( ! defined( 'WPMU_PLUGIN_DIR' ) || ! WPMU_PLUGIN_DIR ) {
		return;
	}
	$dir = WPMU_PLUGIN_DIR;
	if ( ! is_dir( $dir ) || is_link( $dir ) ) {
		return;
	}

	$kernel = gwqsh_early_guardian_load_kernel();

	$state_raw = get_option( 'gwqsh_guardian_state', null );
	$state     = is_array( $state_raw ) ? $state_raw : array();
	$files     = isset( $state['files'] ) && is_array( $state['files'] ) ? $state['files'] : array();
	$dir_mtime = isset( $state['dir_mtime'] ) ? (int) $state['dir_mtime'] : 0;
	$full_at   = isset( $state['full_verify_at'] ) ? (int) $state['full_verify_at'] : 0;

	$now        = time();
	$current_mtime = (int) @filemtime( $dir ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_filemtime -- Bounded stat of a core-defined directory.
	$dir_changed = ( $current_mtime > 0 && $current_mtime !== $dir_mtime );
	// Probabilistic full re-verification (also covers timestamp manipulation
	// and stale state): on average once every ~40 requests or after 6 hours.
	$force_full = ( $now - $full_at > 6 * HOUR_IN_SECONDS ) || ( 0 === mt_rand( 0, 39 ) );

	// Fast path: nothing changed structurally, and every cached file still
	// matches its recorded mtime/size.
	if ( ! $dir_changed && ! $force_full && ! empty( $files ) ) {
		$dirty = false;
		foreach ( $files as $name => $record ) {
			$path = $dir . '/' . $name;
			if ( ! is_file( $path ) ) {
				$dirty = true; // A file disappeared.
				break;
			}
			$mtime = (int) @filemtime( $path ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_filemtime
			$size  = (int) @filesize( $path ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_filesize
			if ( $mtime !== (int) $record['mtime'] || $size !== (int) $record['size'] ) {
				$dirty = true;
				break;
			}
		}
		if ( ! $dirty ) {
			return; // No change since the last request: zero-cost exit.
		}
	}

	$baseline_raw = get_option( 'gwqsh_mu_baseline', array() );
	$baseline     = is_array( $baseline_raw ) ? $baseline_raw : array();

	// Enumerate current top-level PHP files (what WordPress will include).
	$handle = @opendir( $dir ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_opendir -- Bounded read of the must-use directory.
	if ( ! $handle ) {
		return;
	}
	$current = array();
	while ( false !== ( $entry = readdir( $handle ) ) ) {
		if ( '.' === $entry || '..' === $entry ) {
			continue;
		}
		$path = $dir . '/' . $entry;
		if ( is_file( $path ) && ! is_link( $path ) && '.php' === strtolower( substr( $entry, -4 ) ) ) {
			$current[ $entry ] = $path;
		}
	}
	closedir( $handle );
	ksort( $current );

	$new_state  = array();
	$state_dirty = false;
	$quarantined = array();

	foreach ( $current as $name => $path ) {
		$mtime = (int) @filemtime( $path ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_filemtime
		$size  = (int) @filesize( $path ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_filesize

		$known   = isset( $files[ $name ] ) && is_array( $files[ $name ] ) ? $files[ $name ] : null;
		$unchanged = $known
			&& (int) $known['mtime'] === $mtime
			&& (int) $known['size'] === $size
			&& ! $force_full
			&& in_array( $known['verdict'], array( 'ok', 'trusted', 'review' ), true );

		if ( $unchanged ) {
			$new_state[ $name ] = $known;
			continue;
		}

		// The file is new or changed: hash and analyze it now.
		$read = gwqsh_early_guardian_read( $path, 524288 );
		if ( ! is_array( $read ) ) {
			$new_state[ $name ] = array( 'mtime' => $mtime, 'size' => $size, 'verdict' => 'unreadable', 'at' => $now );
			$state_dirty = true;
			continue;
		}

		$base_hash = null;
		if ( isset( $baseline[ $name ] ) ) {
			$base_hash = is_array( $baseline[ $name ] ) && isset( $baseline[ $name ]['sha256'] ) ? $baseline[ $name ]['sha256'] : ( is_string( $baseline[ $name ] ) ? $baseline[ $name ] : null );
		}

		if ( null !== $base_hash && $base_hash === $read['sha256'] ) {
			// Trusted baseline match: no action, ever.
			$new_state[ $name ] = array( 'mtime' => $mtime, 'size' => $size, 'verdict' => 'trusted', 'at' => $now );
			$state_dirty = true;
			continue;
		}

		$known_hash = $known && isset( $known['sha256'] ) ? $known['sha256'] : null;
		if ( $known_hash === $read['sha256'] && ! $force_full ) {
			// Same content as the previously seen version (e.g. only mtime changed).
			$new_state[ $name ] = array( 'mtime' => $mtime, 'size' => $size, 'sha256' => $read['sha256'], 'verdict' => $known['verdict'], 'at' => $known['at'] );
			$state_dirty = true;
			continue;
		}

		// Deep behavioral analysis (source text only; never executed).
		$context = array(
			'component'      => 'mu',
			'baseline_state' => ( null !== $base_hash ) ? 'modified' : 'new',
			'known_matches'  => $kernel ? gwqsh_guardian_core_known_indicator_matches( $read['code'] ) : array(),
		);

		$verdict = 'ok';
		if ( $kernel ) {
			$indicators = gwqsh_guardian_core_scan_indicators( $read['code'] );
			$scored     = gwqsh_guardian_core_score( $indicators, $context );

			if ( 'critical' === $scored['confidence'] && gwqsh_guardian_core_should_neutralize( $indicators, $context ) ) {
				$verdict = 'neutralized';
				$moved   = gwqsh_early_guardian_quarantine( $path, $name, $read, $scored );
				if ( $moved ) {
					$quarantined[] = $name;
					gwqsh_early_guardian_record( 'blocked', $name, $read['sha256'], $scored );
					continue; // Do not keep state for the moved file.
				}
				// Quarantine failed: mark for review so the main plugin surfaces it.
				$verdict = 'review';
				gwqsh_early_guardian_record( 'block_failed', $name, $read['sha256'], $scored );
			} elseif ( 'high' === $scored['confidence'] || 'medium' === $scored['confidence'] ) {
				$verdict = 'review';
				gwqsh_early_guardian_maybe_record_review( $name, $read['sha256'], $scored );
			}
		} elseif ( ! empty( $context['known_matches'] ) ) {
			// Degraded mode (no kernel): known incident indicators only.
			$verdict = 'review';
			gwqsh_early_guardian_maybe_record_review( $name, $read['sha256'], array( 'score' => 40, 'confidence' => 'critical', 'reasons' => array( 'known incident indicators present (degraded mode)' ) ) );
		}

		if ( null !== $base_hash && $base_hash !== $read['sha256'] && 'ok' === $verdict ) {
			// Modified trusted file with no attack behavior: review item.
			$verdict = 'review';
			gwqsh_early_guardian_maybe_record_review( $name, $read['sha256'], array( 'score' => 8, 'confidence' => 'low', 'reasons' => array( 'trusted must-use file modified' ) ) );
		}

		$new_state[ $name ] = array( 'mtime' => $mtime, 'size' => $size, 'sha256' => $read['sha256'], 'verdict' => $verdict, 'at' => $now );
		$state_dirty = true;
	}

	// Persist state only when something actually changed.
	if ( $state_dirty || $dir_changed || $force_full || count( $new_state ) !== count( $files ) ) {
		update_option(
			'gwqsh_guardian_state',
			array(
				'dir_mtime'      => $current_mtime,
				'full_verify_at' => $force_full ? $now : $full_at,
				'files'          => $new_state,
			),
			true // Autoload: hot path, kept small.
		);
	}

	if ( ! empty( $quarantined ) ) {
		gwqsh_early_guardian_mute_include_warnings( $quarantined );
	}
}

/**
 * Record a review event without flooding the log (at most once per file hash).
 *
 * @param string $name   File name.
 * @param string $sha256 Content hash.
 * @param array  $scored Scored verdict.
 */
function gwqsh_early_guardian_maybe_record_review( $name, $sha256, $scored ) {
	$seen = get_option( 'gwqsh_guardian_review_seen', array() );
	if ( ! is_array( $seen ) ) {
		$seen = array();
	}
	if ( isset( $seen[ $sha256 ] ) ) {
		return;
	}
	$seen[ $sha256 ] = array( 'file' => $name, 'at' => time() );
	if ( count( $seen ) > 200 ) {
		$seen = array_slice( $seen, -200, null, true );
	}
	update_option( 'gwqsh_guardian_review_seen', $seen, false );
	gwqsh_early_guardian_record( 'review', $name, $sha256, $scored );
}

/**
 * Quarantine a neutralized file (move, never delete).
 *
 * @param string $path     Absolute path (already validated: regular file, no symlink).
 * @param string $name     Basename.
 * @param array  $read     Read result with code + sha256.
 * @param array  $scored   Scored verdict.
 * @return bool
 */
function gwqsh_early_guardian_quarantine( $path, $name, $read, $scored ) {
	// Path safety: canonical checks before any write.
	$real = realpath( $path );
	$dir_real = realpath( dirname( $path ) );
	if ( ! $real || ! $dir_real || is_link( $path ) || dirname( $real ) !== $dir_real ) {
		return false;
	}
	if ( false !== strpos( $name, "\0" ) || false !== strpos( $name, '/' ) || false !== strpos( $name, '\\' ) ) {
		return false;
	}
	// Never quarantine the Guardian itself.
	if ( '000-gracewell-guardian.php' === $name ) {
		return false;
	}

	$hash = substr( hash_hmac( 'sha256', home_url(), wp_salt( 'auth' ) ), 0, 20 );
	$bases = array();
	if ( function_exists( 'wp_upload_dir' ) ) {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['basedir'] ) ) {
			$bases[] = $uploads['basedir'];
		}
	}
	$bases[] = sys_get_temp_dir();

	foreach ( $bases as $base ) {
		if ( ! is_string( $base ) || '' === $base ) {
			continue;
		}
		$dir = trailingslashit( $base ) . 'gwqsh-quarantine-' . $hash;
		if ( ! is_dir( $dir ) && ! @mkdir( $dir, 0750, false ) ) { // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Validated plugin-owned storage.
			continue;
		}
		$dir_real = realpath( $dir );
		if ( ! $dir_real || is_link( $dir ) ) {
			continue;
		}
		// Execution-blocking guards.
		if ( ! is_file( $dir_real . '/.htaccess' ) ) {
			@file_put_contents( $dir_real . '/.htaccess', "Require all denied\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents,Generic.PHP.NoSilencedErrors.Discouraged -- Plugin-owned guard file.
		}
		if ( ! is_file( $dir_real . '/index.php' ) ) {
			@file_put_contents( $dir_real . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents,Generic.PHP.NoSilencedErrors.Discouraged -- Plugin-owned guard file.
		}

		$dest = $dir_real . '/' . gwqsh_guardian_core_quarantine_name();
		if ( ! @rename( $real, $dest ) ) { // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.rename_rename -- Atomic move into validated plugin-owned storage.
			continue;
		}
		if ( ! is_file( $dest ) || is_link( $dest ) ) {
			return false;
		}

		// Metadata in the SAME format the main plugin reads.
		$meta = get_option( 'gwqsh_quarantine_metadata', array() );
		if ( ! is_array( $meta ) ) {
			$meta = array();
		}
		$meta[ basename( $dest ) ] = array(
			'original' => '/wp-content/mu-plugins/' . $name,
			'time'     => time(),
			'hash'     => $read['sha256'],
			'reason'   => 'Early Guardian: ' . ( ! empty( $scored['reasons'] ) ? implode( '; ', array_slice( (array) $scored['reasons'], 0, 3 ) ) : 'extremely-high-confidence malware behavior' ),
			'source'   => 'guardian',
			'severity' => 'critical',
			'rule_id'  => 'GUARDIAN',
			'score'    => isset( $scored['score'] ) ? (int) $scored['score'] : 0,
			'size'     => isset( $read['size'] ) ? (int) $read['size'] : 0,
		);
		update_option( 'gwqsh_quarantine_metadata', $meta, false );

		// Record a scan finding so the File Integrity UI lists it.
		gwqsh_early_guardian_insert_finding( $name, $read, $scored );
		return true;
	}
	return false;
}

/**
 * Insert a Critical scan-result row when the plugin's table exists.
 *
 * @param string $name   File basename.
 * @param array  $read   Read result.
 * @param array  $scored Scored verdict.
 */
function gwqsh_early_guardian_insert_finding( $name, $read, $scored ) {
	global $wpdb;
	if ( ! isset( $wpdb ) || ! property_exists( $wpdb, 'prefix' ) || ! is_string( $wpdb->prefix ) || '' === $wpdb->prefix ) {
		return;
	}
	$table = $wpdb->prefix . 'gwqsh_scan_results';
	if ( preg_match( '/^[A-Za-z0-9_]+$/D', $table ) !== 1 ) {
		return;
	}
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table existence verified; fixed plugin-owned identifier.
	$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
	if ( $exists !== $table ) {
		return;
	}
	$wpdb->insert(
		$table, // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Validated fixed identifier above.
		array(
			'file_path'   => '/wp-content/mu-plugins/' . $name,
			'file_type'   => 'MU Plugin',
			'status'      => 'Suspicious',
			'details'     => '[Critical] Early Guardian neutralized: ' . ( ! empty( $scored['reasons'] ) ? implode( '; ', array_slice( (array) $scored['reasons'], 0, 3 ) ) : 'extremely-high-confidence malware behavior' ),
			'detected_at' => current_time( 'mysql' ),
			'is_resolved' => 0,
			'severity'    => 'critical',
			'rule_id'     => 'GUARDIAN',
			'risk_score'  => isset( $scored['score'] ) ? (int) $scored['score'] : 0,
			'file_sha256' => isset( $read['sha256'] ) ? $read['sha256'] : '',
			'evidence'    => ! empty( $scored['reasons'] ) ? implode( "\n", array_map( 'sanitize_text_field', array_slice( (array) $scored['reasons'], 0, 5 ) ) ) : '',
		)
	);
}

/**
 * Record a Guardian event in the activity log (when available) and the
 * bounded Guardian event list.
 *
 * @param string $kind   Event kind: blocked|review|block_failed.
 * @param string $name   File basename.
 * @param string $sha256 Hash.
 * @param array  $scored Scored verdict.
 */
function gwqsh_early_guardian_record( $kind, $name, $sha256, $scored ) {
	// Bounded event list option (always available, even without the plugin).
	$events = get_option( 'gwqsh_guardian_events', array() );
	if ( ! is_array( $events ) ) {
		$events = array();
	}
	$reason = ! empty( $scored['reasons'] ) ? implode( '; ', array_slice( (array) $scored['reasons'], 0, 3 ) ) : ( isset( $scored['confidence'] ) ? $scored['confidence'] . ' confidence' : '' );
	array_unshift( $events, array(
		'kind'    => $kind,
		'file'    => $name,
		'hash'    => $sha256,
		'score'   => isset( $scored['score'] ) ? (int) $scored['score'] : 0,
		'reason'  => $reason,
		'at'      => time(),
	) );
	$events = array_slice( $events, 0, 50 );
	update_option( 'gwqsh_guardian_events', $events, false );

	// Activity log table (when the main plugin created it).
	global $wpdb;
	if ( ! isset( $wpdb ) || ! is_string( $wpdb->prefix ) || '' === $wpdb->prefix ) {
		return;
	}
	$table = $wpdb->prefix . 'gwqsh_activity_log';
	if ( preg_match( '/^[A-Za-z0-9_]+$/D', $table ) !== 1 ) {
		return;
	}
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table existence verified; fixed plugin-owned identifier.
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) !== $table ) {
		return;
	}
	$labels = array(
		'blocked'       => 'Early Guardian neutralized malware',
		'review'        => 'Early Guardian flagged file for review',
		'block_failed'  => 'Early Guardian quarantine failed',
	);
	$wpdb->insert(
		$table, // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Validated fixed identifier above.
		array(
			'event_time' => current_time( 'mysql' ),
			'event_name' => isset( $labels[ $kind ] ) ? $labels[ $kind ] : 'Early Guardian event',
			'event_type' => 'Security',
			'user_login' => '—',
			'ip_address' => '—',
			'details'    => 'Must-use plugin ' . $name . ': ' . $reason,
		)
	);
}

/**
 * Suppress the include-warnings WordPress will emit for the moved files.
 *
 * WordPress builds the must-include list before loading this Guardian, so a
 * quarantined file produces a harmless "failed to open stream" warning. This
 * handler mutes exactly those errors and restores itself otherwise.
 *
 * @param array $names Basenames that were quarantined.
 */
function gwqsh_early_guardian_mute_include_warnings( $names ) {
	$GLOBALS['gwqsh_guardian_muted_names'] = $names;
	set_error_handler( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_error_handler -- Scoped, self-restoring suppression of expected include warnings only.
		static function ( $errno, $errstr ) {
			if ( ! is_string( $errstr ) ) {
				return false;
			}
			if ( false !== strpos( $errstr, 'failed to open stream' ) || false !== strpos( $errstr, 'Failed opening' ) ) {
				$muted = isset( $GLOBALS['gwqsh_guardian_muted_names'] ) && is_array( $GLOBALS['gwqsh_guardian_muted_names'] ) ? $GLOBALS['gwqsh_guardian_muted_names'] : array();
				foreach ( $muted as $name ) {
					if ( false !== strpos( $errstr, $name ) ) {
						return true; // Exactly our moved files: mute.
					}
				}
			}
			restore_error_handler();
			return false; // Everything else: normal handling.
		}
	);
}

gwqsh_early_guardian_boot();
