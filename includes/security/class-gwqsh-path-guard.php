<?php
/**
 * Strict filesystem path validation for all Gracewell QuietShield operations.
 *
 * Every read, write, quarantine or restore performed by the security engine is
 * routed through this guard: traversal attempts, null bytes, stream wrappers,
 * absolute-path injections and symlink escapes are rejected before any I/O.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * GWQSH Path Guard implementation.
 */
final class GWQSH_Path_Guard {

	/**
	 * Canonical WordPress root (ABSPATH) with trailing slash.
	 *
	 * @var string|null
	 */
	private static $root = null;

	/**
	 * Allowed roots (canonical, no trailing slash except root itself).
	 *
	 * @var array|null
	 */
	private static $allowed_roots = null;

	/**
	 * Reset cached roots (used by tests).
	 */
	public static function reset_cache() {
		self::$root         = null;
		self::$allowed_roots = null;
	}

	/**
	 * Normalized ABSPATH.
	 *
	 * @return string Path with trailing slash and forward slashes.
	 */
	public static function root() {
		if ( null === self::$root ) {
			self::$root = wp_normalize_path( untrailingslashit( ABSPATH ) );
		}
		return self::$root;
	}

	/**
	 * Allowed root directories for security-engine I/O.
	 *
	 * @return array List of canonical paths.
	 */
	public static function allowed_roots() {
		if ( null !== self::$allowed_roots ) {
			return self::$allowed_roots;
		}
		$roots   = array( ABSPATH );
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['basedir'] ) ) {
			$roots[] = $uploads['basedir'];
		}
		if ( defined( 'WP_PLUGIN_DIR' ) && WP_PLUGIN_DIR ) {
			$roots[] = WP_PLUGIN_DIR;
		}
		if ( defined( 'WPMU_PLUGIN_DIR' ) && WPMU_PLUGIN_DIR ) {
			$roots[] = WPMU_PLUGIN_DIR;
		}
		if ( function_exists( 'get_theme_root' ) ) {
			$theme_root = get_theme_root();
			if ( $theme_root && ! is_wp_error( $theme_root ) ) {
				$roots[] = $theme_root;
			}
		}
		if ( function_exists( 'get_temp_dir' ) ) {
			$roots[] = get_temp_dir();
		}
		$canonical = array();
		foreach ( $roots as $path ) {
			$real = realpath( $path );
			if ( is_string( $path ) ) {
				$norm = wp_normalize_path( $path );
			} else {
				continue;
			}
			if ( $real ) {
				$canonical[] = wp_normalize_path( $real );
			} elseif ( is_dir( $norm ) ) {
				$canonical[] = $norm;
			}
		}
		self::$allowed_roots = array_values( array_unique( $canonical ) );
		return self::$allowed_roots;
	}

	/**
	 * True when the canonical path is inside one of the allowed roots.
	 *
	 * @param string $canonical_path Canonical (realpath-resolved) path.
	 * @return bool
	 */
	public static function is_within_allowed_roots( $canonical_path ) {
		foreach ( self::allowed_roots() as $root ) {
			$root = wp_normalize_path( $root );
			if ( $canonical_path === $root || 0 === strpos( $canonical_path, $root . '/' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Validate a relative (to ABSPATH) or absolute path supplied by a caller.
	 *
	 * Rules enforced:
	 *  - non-empty string, no null bytes, no backslashes
	 *  - no PHP stream wrappers (anything://)
	 *  - no drive-letter style paths
	 *  - no '.' or '..' path segments after normalization
	 *  - resolved path must stay inside the allowed roots
	 *  - symlinks are never followed outside allowed roots (realpath resolves,
	 *    and the resolved location must be inside an allowed root)
	 *
	 * @param string $path      Relative or absolute path.
	 * @param bool   $must_exist Whether the file must currently exist.
	 * @return array|null Array with 'canonical' and 'relative' keys, or null when invalid.
	 */
	public static function validate( $path, $must_exist = true ) {
		if ( ! is_string( $path ) || '' === $path ) {
			return null;
		}
		if ( false !== strpos( $path, "\0" ) ) {
			return null;
		}
		if ( false !== strpos( $path, '\\' ) ) {
			return null;
		}
		if ( preg_match( '#^[A-Za-z]:#', $path ) ) {
			return null;
		}
		// Reject stream wrappers such as php://, data://, expect://, phar://.
		if ( preg_match( '#^[A-Za-z][A-Za-z0-9+.-]*://#', $path ) ) {
			return null;
		}
		// Reject obvious traversal before normalization.
		if ( false !== strpos( $path, '../' ) || false !== strpos( $path, '..\\' ) ) {
			return null;
		}

		$root = self::root();

		if ( 0 === strpos( $path, '/' ) ) {
			// Absolute path: only allowed when inside an allowed root.
			$normalized = wp_normalize_path( $path );
			$within     = false;
			foreach ( self::allowed_roots() as $allowed ) {
				if ( $normalized === $allowed || 0 === strpos( $normalized, $allowed . '/' ) ) {
					$within = true;
					break;
				}
			}
			if ( ! $within ) {
				return null;
			}
			$absolute = $normalized;
		} else {
			$relative = ltrim( $path, '/' );
			if ( '' === $relative ) {
				return null;
			}
			foreach ( explode( '/', $relative ) as $segment ) {
				if ( '' === $segment || '.' === $segment || '..' === $segment ) {
					return null;
				}
			}
			$absolute = $root . '/' . $relative;
		}

		if ( $must_exist && ! file_exists( $absolute ) ) {
			return null;
		}

		// Resolve the deepest existing ancestor so realpath() works for
		// not-yet-existing targets, then verify the ancestor is inside the root.
		$dir   = dirname( $absolute );
		$limit = 0;
		while ( ! file_exists( $dir ) && $dir !== dirname( $dir ) && ( ++$limit ) < 64 ) {
			$dir = dirname( $dir );
		}
		$real_dir = realpath( $dir );
		if ( ! $real_dir ) {
			return null;
		}
		$real_dir = wp_normalize_path( $real_dir );
		if ( ! self::is_within_allowed_roots( $real_dir ) ) {
			return null;
		}

		$canonical = wp_normalize_path( $real_dir . '/' . basename( $absolute ) );

		if ( file_exists( $absolute ) ) {
			if ( is_link( $absolute ) ) {
				// Symlink: only acceptable when it resolves inside allowed roots
				// AND points at a regular file we are allowed to touch.
				$target = realpath( $absolute );
				if ( ! $target ) {
					return null;
				}
				$target = wp_normalize_path( $target );
				if ( ! self::is_within_allowed_roots( $target ) ) {
					return null;
				}
				$canonical = $target;
			} elseif ( is_file( $absolute ) ) {
				$real = realpath( $absolute );
				if ( ! $real ) {
					return null;
				}
				$real      = wp_normalize_path( $real );
				$canonical = $real;
			} elseif ( is_dir( $absolute ) ) {
				$real = realpath( $absolute );
				if ( ! $real ) {
					return null;
				}
				$canonical = wp_normalize_path( $real );
			}
		}

		if ( ! self::is_within_allowed_roots( $canonical ) ) {
			return null;
		}

		$relative = ltrim( str_replace( $root, '', $canonical ), '/' );
		return array(
			'canonical' => $canonical,
			'relative'  => $relative,
			'absolute'  => $canonical,
		);
	}

	/**
	 * Validate and return only the canonical path (convenience wrapper).
	 *
	 * @param string $path       Path.
	 * @param bool   $must_exist Whether the file must exist.
	 * @return string|null
	 */
	public static function canonical( $path, $must_exist = true ) {
		$resolved = self::validate( $path, $must_exist );
		return $resolved ? $resolved['canonical'] : null;
	}

	/**
	 * Validate that a path (which must already exist) is a regular file,
	 * not a symlink, and inside the allowed roots.
	 *
	 * @param string $path Path.
	 * @return string|null Canonical path or null.
	 */
	public static function regular_file( $path ) {
		$resolved = self::validate( $path, true );
		if ( ! $resolved ) {
			return null;
		}
		$canonical = $resolved['canonical'];
		if ( ! is_file( $canonical ) || is_link( $canonical ) ) {
			return null;
		}
		return $canonical;
	}

	/**
	 * Validate that a path is a directory (not a symlink) inside allowed roots.
	 *
	 * @param string $path Path.
	 * @return string|null Canonical path or null.
	 */
	public static function directory( $path ) {
		$resolved = self::validate( $path, true );
		if ( ! $resolved ) {
			return null;
		}
		$canonical = $resolved['canonical'];
		if ( ! is_dir( $canonical ) || is_link( $canonical ) ) {
			return null;
		}
		return $canonical;
	}
}
