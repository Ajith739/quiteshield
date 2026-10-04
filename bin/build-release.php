<?php
/**
 * Standalone production-package builder; excluded from the release.
 *
 * @package GracewellQuietShield
 */

if ( ! defined( 'ABSPATH' ) ) {
	if ( ! defined( 'PHP_SAPI' ) || 'cli' !== PHP_SAPI ) {
		exit;
	}
}

/**
 * Build production release package.
 */
function gwqsh_build_release() {
	if ( ! class_exists( 'ZipArchive' ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI utility only.
		fwrite( STDERR, "ZipArchive PHP extension is required.\n" );
		exit( 1 );
	}

	$plugin = dirname( __DIR__ );
	$slug   = 'gracewell-quietshield';
	$dist   = dirname( $plugin, 3 ) . '/dist';
	if ( ! empty( $GLOBALS['argv'][1] ) ) {
		$dist = $GLOBALS['argv'][1];
	}

	$files = array();

	try {
		foreach ( array( $slug . '.php', 'uninstall.php', 'readme.txt', 'SECURITY.md', 'THIRD-PARTY-NOTICES.txt' ) as $name ) {
			if ( ! is_file( $plugin . '/' . $name ) ) {
				throw new RuntimeException( 'Missing required release file: ' . $name );
			}
			$files[] = $plugin . '/' . $name;
		}

		foreach ( array( 'admin', 'assets', 'includes', 'languages', 'mu-plugins' ) as $folder ) {
			$folder_path = $plugin . '/' . $folder;
			if ( ! is_dir( $folder_path ) ) {
				continue;
			}
			$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $folder_path, FilesystemIterator::SKIP_DOTS ) );
			foreach ( $iterator as $file ) {
				if ( $file->isLink() ) {
					throw new RuntimeException( 'Release assets must not contain symbolic links.' );
				}
				if ( $file->isFile() ) {
					$relative = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $plugin ) + 1 ) );
					if ( preg_match( '~(?:^|/)(?:\.[^/]+|node_modules|tests)(?:/|$)|\.(?:zip|log|bak|tmp|map)$~i', $relative ) ) {
						throw new RuntimeException( 'Unexpected development file: ' . $relative );
					}
					$files[] = $file->getPathname();
				}
			}
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- CLI utility only.
		if ( ! is_dir( $dist ) && ! mkdir( $dist, 0775, true ) ) {
			throw new RuntimeException( 'Cannot create plugin dist directory.' );
		}

		$archive_path = $dist . '/' . $slug . '-1.1.0.zip';
		$temporary    = $archive_path . '.tmp';
		$zip          = new ZipArchive();

		if ( true !== $zip->open( $temporary, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			throw new RuntimeException( 'Cannot open the production archive for writing.' );
		}

		sort( $files );
		foreach ( $files as $file ) {
			$relative = str_replace( '\\', '/', substr( $file, strlen( $plugin ) + 1 ) );
			if ( ! $zip->addFile( $file, $slug . '/' . $relative ) ) {
				throw new RuntimeException( 'Cannot package file: ' . $relative );
			}
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename,PluginCheck.CodeAnalysis.WriteFile.PluginDirectoryWrite -- CLI utility only.
		if ( ! $zip->close() || ! rename( $temporary, $archive_path ) ) {
			throw new RuntimeException( 'Cannot finalize the production archive.' );
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI utility output.
		echo $archive_path . ' (' . count( $files ) . " files)\n";
	} catch ( Exception $error ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI utility only.
		fwrite( STDERR, 'QuietShield build failed: ' . $error->getMessage() . "\n" );
		exit( 1 );
	}
}

gwqsh_build_release();
