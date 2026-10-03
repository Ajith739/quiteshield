<?php
// phpcs:ignoreFile -- Development test suite; excluded from release package.
if ( PHP_SAPI !== 'cli' ) { exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';

defined( 'ABSPATH' ) || exit;
