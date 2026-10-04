<?php
/**
 * Quarantine manager: safe, evidence-preserving file isolation.
 *
 * Malicious files are never deleted. They are moved to a random-named,
 * execution-blocked storage area with complete provenance (original path,
 * SHA-256, timestamp, reason, detection metadata) and can be restored only
 * by an administrator through a capability + nonce protected action.
 *
 * This manager supersedes the internal quarantine helpers of
 * GWQSH_File_Integrity while keeping the same storage format, so previously
 * quarantined files remain visible and restorable.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * GWQSH quarantine manager.
 */
final class GWQSH_Quarantine_Manager {

	/**
	 * Metadata option (same key the legacy implementation used).
	 */
	const METADATA_OPTION = 'gwqsh_quarantine_metadata';

	/**
	 * Quarantine a validated file.
	 *
	 * @param string $path      Relative (to ABSPATH) or absolute path.
	 * @param string $reason    Human-readable reason.
	 * @param array  $meta      Extra metadata: severity, rule_id, sha256, source ('scan'|'guardian').
	 * @return array|WP_Error Array with keys file, original, sha256 on success.
	 */
	public static function quarantine_file( $path, $reason = '', $meta = array(), $copy = false ) {
		$validated = GWQSH_Path_Guard::validate( $path, true );
		if ( ! $validated && is_string( $path ) && 0 === strpos( $path, '/' ) ) {
			// Scan results and the UI address files in site-rooted form
			// ('/wp-content/...'); retry as site-relative.
			$validated = GWQSH_Path_Guard::validate( ltrim( $path, '/' ), true );
		}
		if ( ! $validated ) {
			return new WP_Error( 'gwqsh_bad_path', 'The requested path is not a valid file inside the WordPress installation.' );
		}
		$canonical = $validated['canonical'];
		$relative  = $validated['relative'];

		if ( ! is_file( $canonical ) ) {
			return new WP_Error( 'gwqsh_not_file', 'The requested path is not a regular file.' );
		}
		if ( is_link( $canonical ) ) {
			return new WP_Error( 'gwqsh_symlink', 'Symbolic links are never quarantined.' );
		}

		$read = gwqsh_guardian_core_read_file( $canonical, 5242880 );
		if ( ! is_array( $read ) ) {
			return new WP_Error( 'gwqsh_read_failed', 'The file could not be read for quarantine.' );
		}
		$sha256 = isset( $meta['sha256'] ) && 64 === strlen( (string) $meta['sha256'] ) ? $meta['sha256'] : $read['sha256'];

		// Never quarantine the plugin's own engine, the quarantine area itself,
		// or WordPress core bootstrap files (they are restored, not moved).
		$protected = array(
			GWQSH_Path_Guard::root() . '/wp-config.php',
			GWQSH_Path_Guard::root() . '/wp-load.php',
			GWQSH_Path_Guard::root() . '/wp-settings.php',
			GWQSH_Path_Guard::root() . '/wp-blog-header.php',
		);
		if ( in_array( $canonical, $protected, true ) ) {
			return new WP_Error( 'gwqsh_protected', 'Core bootstrap files cannot be quarantined; use checksum-verified restoration instead.' );
		}
		if ( 0 === strpos( $canonical, trailingslashit( GWQSH_PATH ) ) ) {
			return new WP_Error( 'gwqsh_self', 'QuietShield cannot quarantine its own files.' );
		}
		$quarantine_dir = self::quarantine_dir();
		if ( $quarantine_dir && 0 === strpos( $canonical, trailingslashit( $quarantine_dir ) ) ) {
			return new WP_Error( 'gwqsh_in_quarantine', 'The file is already in quarantine storage.' );
		}

		$dir = self::ensure_quarantine_dir();
		if ( is_wp_error( $dir ) ) {
			return $dir;
		}

		$name = gwqsh_guardian_core_quarantine_name();
		$dest = $dir . '/' . $name;

		if ( $copy ) {
			// Backup mode: duplicate content into quarantine; original untouched.
			$written = @file_put_contents( $dest, $read['code'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents,Generic.PHP.NoSilencedErrors.Discouraged -- Validated quarantine write inside plugin-owned storage.
			if ( false === $written ) {
				return new WP_Error( 'gwqsh_move_failed', 'The file could not be copied into quarantine.' );
			}
		} elseif ( ! @rename( $canonical, $dest ) ) { // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.rename_rename -- Atomic move into plugin-owned storage; all paths validated above.
			// Some filesystems disallow rename across devices; fall back to a verified copy + unlink.
			$copied = @file_put_contents( $dest, $read['code'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents,Generic.PHP.NoSilencedErrors.Discouraged -- Validated quarantine write inside plugin-owned storage.
			if ( false === $copied || ! @unlink( $canonical ) ) { // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_unlink -- Copy fallback after validated rename failure.
				if ( file_exists( $dest ) ) {
					@unlink( $dest ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_unlink -- Roll back partial fallback.
				}
				return new WP_Error( 'gwqsh_move_failed', 'The file could not be moved into quarantine.' );
			}
		}

		if ( ! is_file( $dest ) || is_link( $dest ) ) {
			return new WP_Error( 'gwqsh_move_failed', 'Quarantine verification failed.' );
		}

		// Verify content integrity of the quarantined copy.
		$verify = gwqsh_guardian_core_read_file( $dest, 5242880 );
		if ( ! is_array( $verify ) || $verify['sha256'] !== $sha256 ) {
			return new WP_Error( 'gwqsh_hash_mismatch', 'Quarantined copy failed integrity verification.' );
		}

		$metadata = get_option( self::METADATA_OPTION, array() );
		if ( ! is_array( $metadata ) ) {
			$metadata = array();
		}
		$metadata[ $name ] = array(
			'original'   => '/' . $relative,
			'time'       => time(),
			'hash'       => $sha256,
			'reason'     => '' !== $reason ? $reason : 'Suspicious file quarantined by administrator.',
			'source'     => isset( $meta['source'] ) ? $meta['source'] : ( $copy ? 'backup' : 'scan' ),
			'severity'   => isset( $meta['severity'] ) ? $meta['severity'] : '',
			'rule_id'    => isset( $meta['rule_id'] ) ? $meta['rule_id'] : '',
			'score'      => isset( $meta['score'] ) ? (int) $meta['score'] : 0,
			'size'       => isset( $read['size'] ) ? (int) $read['size'] : 0,
		);
		update_option( self::METADATA_OPTION, $metadata, false );

		if ( $copy ) {
			GWQSH_Activity_Logger::log(
				'Restore backup stored',
				'Stored a verified backup of /' . $relative . ' in quarantine storage before checksum-verified restoration.',
				'Security'
			);
		} else {
			GWQSH_Activity_Logger::log(
				'File quarantined',
				'Quarantined /' . $relative . ' (' . ( isset( $meta['source'] ) ? $meta['source'] : 'manual' ) . ( isset( $meta['rule_id'] ) && $meta['rule_id'] ? ', ' . $meta['rule_id'] : '' ) . ')',
				'Security'
			);
		}

		return array(
			'file'     => $name,
			'original' => '/' . $relative,
			'sha256'   => $sha256,
		);
	}

	/**
	 * The canonical quarantine directory (may not exist yet).
	 *
	 * @return string|null
	 */
	public static function quarantine_dir() {
		$hash = self::dir_hash();
		$bases = array( get_temp_dir(), wp_upload_dir()['basedir'] );
		foreach ( $bases as $base ) {
			if ( empty( $base ) ) {
				continue;
			}
			$candidate = trailingslashit( $base ) . 'gwqsh-quarantine-' . $hash;
			$real      = realpath( $candidate );
			if ( $real ) {
				return $real;
			}
		}
		// Not created yet: report the uploads-based planned location.
		$base = wp_upload_dir();
		if ( ! empty( $base['basedir'] ) ) {
			return trailingslashit( $base['basedir'] ) . 'gwqsh-quarantine-' . $hash;
		}
		return null;
	}

	/**
	 * Stable per-site hash used to obscure the quarantine directory name.
	 *
	 * @return string
	 */
	private static function dir_hash() {
		return substr( hash_hmac( 'sha256', home_url(), wp_salt( 'auth' ) ), 0, 20 );
	}

	/**
	 * Create (or verify) the quarantine directory with execution blocked.
	 *
	 * @return string|WP_Error Canonical directory.
	 */
	public static function ensure_quarantine_dir() {
		$hash  = self::dir_hash();
		$error = null;
		foreach ( array( get_temp_dir(), wp_upload_dir()['basedir'] ) as $base ) {
			if ( empty( $base ) ) {
				continue;
			}
			$candidate = trailingslashit( $base ) . 'gwqsh-quarantine-' . $hash;
			$real_base = realpath( $base );
			if ( ! $real_base ) {
				continue;
			}
			if ( ! is_dir( $candidate ) ) {
				if ( ! @mkdir( $candidate, 0750, false ) ) { // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Validated plugin-owned storage.
					continue;
				}
			}
			$real = realpath( $candidate );
			if ( ! $real || is_link( $candidate ) ) {
				continue;
			}
			if ( ! gwqsh_guardian_core_path_within( $real, $real_base ) ) {
				continue;
			}

			self::harden_directory( $real );
			return $real;
		}
		return new WP_Error( 'gwqsh_no_quarantine_dir', 'No suitable quarantine storage location is writable.' );
	}

	/**
	 * Write execution-blocking guards into a quarantine directory.
	 *
	 * @param string $dir Canonical directory.
	 */
	private static function harden_directory( $dir ) {
		$htaccess = $dir . '/.htaccess';
		if ( ! is_file( $htaccess ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Plugin-owned guard file.
			@file_put_contents( $htaccess, "Require all denied\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n" );
		}
		$index = $dir . '/index.php';
		if ( ! is_file( $index ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Plugin-owned guard file.
			@file_put_contents( $index, "<?php\n// Silence is golden.\n" );
		}
	}

	/**
	 * List quarantined files with metadata (bounded).
	 *
	 * @return array name => metadata
	 */
	public static function list_quarantined() {
		$metadata = get_option( self::METADATA_OPTION, array() );
		if ( ! is_array( $metadata ) ) {
			return array();
		}
		$dir = self::quarantine_dir();
		if ( ! $dir || ! is_dir( $dir ) ) {
			return array();
		}
		$result = array();
		foreach ( $metadata as $name => $record ) {
			if ( ! preg_match( '/^[a-f0-9]{32,64}\.quarantine$/D', (string) $name ) ) {
				continue;
			}
			$path = $dir . '/' . $name;
			if ( ! is_file( $path ) || is_link( $path ) ) {
				continue;
			}
			$record['file']    = $name;
			$record['present'] = true;
			$result[ $name ]   = $record;
		}
		return $result;
	}

	/**
	 * Restore a quarantined file to its original location.
	 *
	 * Restore is refused when the original path now exists (safety), the
	 * quarantine record is missing, or the copy fails hash verification.
	 *
	 * @param string $name Quarantined (random) filename.
	 * @return array|WP_Error Array with the restored relative path.
	 */
	public static function restore_file( $name ) {
		if ( ! is_string( $name ) || ! preg_match( '/^[a-f0-9]{32,64}\.quarantine$/D', $name ) ) {
			return new WP_Error( 'gwqsh_bad_name', 'Invalid quarantine file name.' );
		}
		$metadata = get_option( self::METADATA_OPTION, array() );
		if ( ! is_array( $metadata ) || ! isset( $metadata[ $name ] ) ) {
			return new WP_Error( 'gwqsh_no_record', 'No quarantine record exists for this file.' );
		}
		$record = $metadata[ $name ];
		if ( empty( $record['original'] ) || ! is_string( $record['original'] ) ) {
			return new WP_Error( 'gwqsh_bad_record', 'The quarantine record is incomplete.' );
		}

		$dir = self::quarantine_dir();
		if ( ! $dir || ! is_dir( $dir ) ) {
			return new WP_Error( 'gwqsh_no_dir', 'Quarantine storage is not available.' );
		}
		$source = $dir . '/' . $name;
		$real   = realpath( $source );
		if ( ! $real || is_link( $source ) || dirname( $real ) !== $real && dirname( $real ) !== realpath( $dir ) ) {
			return new WP_Error( 'gwqsh_bad_source', 'Quarantined file not found.' );
		}

		$original = $record['original'];
		$validated = GWQSH_Path_Guard::validate( $original, false );
		if ( ! $validated && is_string( $original ) && 0 === strpos( $original, '/' ) ) {
			// Records store the site-rooted form ('/wp-content/...').
			$validated = GWQSH_Path_Guard::validate( ltrim( $original, '/' ), false );
		}
		if ( ! $validated ) {
			return new WP_Error( 'gwqsh_bad_target', 'The recorded original path is no longer valid for this installation.' );
		}
		$target = $validated['canonical'];
		if ( dirname( $target ) !== dirname( $validated['canonical'] ) ) {
			return new WP_Error( 'gwqsh_bad_target', 'Unexpected target location.' );
		}
		if ( file_exists( $target ) ) {
			return new WP_Error( 'gwqsh_target_exists', 'The original location is not empty. Remove or review the current file first.' );
		}

		$read = gwqsh_guardian_core_read_file( $source, 5242880 );
		if ( ! is_array( $read ) ) {
			return new WP_Error( 'gwqsh_read_failed', 'The quarantined file could not be read.' );
		}
		if ( ! empty( $record['hash'] ) && $read['sha256'] !== $record['hash'] ) {
			return new WP_Error( 'gwqsh_hash_mismatch', 'The quarantined file no longer matches its recorded checksum.' );
		}

		// Ensure parent directory exists and is inside the root.
		$parent = dirname( $target );
		if ( ! is_dir( $parent ) ) {
			wp_mkdir_p( $parent );
			$parent_real = realpath( $parent );
			if ( ! $parent_real || ! GWQSH_Path_Guard::is_within_allowed_roots( wp_normalize_path( $parent_real ) ) ) {
				return new WP_Error( 'gwqsh_bad_target', 'Target directory is outside the allowed roots.' );
			}
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Verified restore into validated original location.
		if ( false === @file_put_contents( $target, $read['code'] ) ) {
			return new WP_Error( 'gwqsh_write_failed', 'Restoring the file to its original location failed.' );
		}
		$verify = gwqsh_guardian_core_read_file( $target, 5242880 );
		if ( ! is_array( $verify ) || $verify['sha256'] !== $read['sha256'] ) {
			return new WP_Error( 'gwqsh_verify_failed', 'Restored file failed verification.' );
		}

		@unlink( $source ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_unlink -- Original stays in quarantine metadata history; content was restored and verified.
		unset( $metadata[ $name ] );
		update_option( self::METADATA_OPTION, $metadata, false );

		$relative = GWQSH_Path_Guard::validate( $original, true );
		$display  = $relative ? $relative['relative'] : ltrim( $original, '/' );
		GWQSH_Activity_Logger::log( 'File restored from quarantine', 'Restored /' . $display . ' from quarantine.', 'Security' );

		return array( 'restored' => '/' . $display );
	}
}
