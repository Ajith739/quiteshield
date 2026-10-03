<?php
/**
 * Plugin Name: Gracewell QuietShield
 * Plugin URI: https://gracewell.in/gracewell-quiteshield/
 * Description: A WordPress security plugin providing login protection, two-factor authentication, file integrity monitoring, security hardening, and related security tools.
 * Version: 1.0.0
 * Author: Ajithkumar739
 * Author URI: https://gracewell.in/
 * Contributors: gracewell89
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gracewell-quietshield
 * Domain Path: /languages
 * Requires at least: 5.3
 * Requires PHP: 7.4
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

define( 'GWQSH_PATH', plugin_dir_path( __FILE__ ) );
define( 'GWQSH_URL', plugin_dir_url( __FILE__ ) );
define( 'GWQSH_VERSION', '1.0.0' );
define( 'GWQSH_DB_VERSION', '1.0.0' );
define( 'GWQSH_PLUGIN_FILE', __FILE__ );
define( 'GWQSH_PLUGIN_DIR', GWQSH_PATH );
define( 'GWQSH_PLUGIN_URL', GWQSH_URL );
define( 'GWQSH_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Legacy emergency constants remain supported during the development-prefix transition.
if ( ! defined( 'GWQSH_DISABLE_LOGIN_URL' ) && defined( 'GQS_DISABLE_LOGIN_URL' ) ) {
	define( 'GWQSH_DISABLE_LOGIN_URL', GQS_DISABLE_LOGIN_URL ); }
if ( ! defined( 'GWQSH_TRUSTED_PROXIES' ) && defined( 'GQS_TRUSTED_PROXIES' ) ) {
	define( 'GWQSH_TRUSTED_PROXIES', GQS_TRUSTED_PROXIES ); }

// Load backend modules.
require_once GWQSH_PATH . 'includes/class-gwqsh-migration.php';
require_once GWQSH_PATH . 'includes/class-gwqsh-db.php';
require_once GWQSH_PATH . 'includes/class-gwqsh-crypto.php';
require_once GWQSH_PATH . 'includes/class-gwqsh-settings.php';
require_once GWQSH_PATH . 'includes/class-gwqsh-activity-logger.php';
require_once GWQSH_PATH . 'includes/class-gwqsh-login-protection.php';
require_once GWQSH_PATH . 'includes/class-gwqsh-two-factor.php';
require_once GWQSH_PATH . 'includes/class-gwqsh-hardening.php';
require_once GWQSH_PATH . 'includes/class-gwqsh-file-integrity.php';
require_once GWQSH_PATH . 'includes/class-gwqsh-ajax.php';
require_once GWQSH_PATH . 'includes/class-gwqsh-admin.php';

// Activation and Deactivation lifecycle hooks.
register_activation_hook( __FILE__, 'gwqsh_activate_plugin' );
register_deactivation_hook( __FILE__, 'gwqsh_deactivate_plugin' );
/**
 * Gwqsh activate plugin.
 */
function gwqsh_activate_plugin() {
	GWQSH_Migration::run();
	GWQSH_DB::create_tables();
	GWQSH_Login_Protection::get_allowlist();
	GWQSH_Hardening::sync_uploads_protection( GWQSH_Hardening::is_enabled( 'uploads' ) );
}
/**
 * Gwqsh deactivate plugin.
 */
function gwqsh_deactivate_plugin() {
	wp_clear_scheduled_hook( 'gwqsh_scheduled_integrity_scan' );
	wp_clear_scheduled_hook( 'gwqsh_daily_log_cleanup' );
	wp_clear_scheduled_hook( 'gwqsh_cleanup_lockouts' );
	GWQSH_Hardening::sync_uploads_protection( false );
}

// Bootstrap all modules.
/**
 * Gwqsh bootstrap.
 */
function gwqsh_bootstrap() {
	GWQSH_Migration::run();
	GWQSH_Settings::migrate_removed_features();
	GWQSH_DB::init();
	GWQSH_Activity_Logger::init();
	GWQSH_Login_Protection::init();
	GWQSH_Two_Factor::init();
	GWQSH_Hardening::init();
	GWQSH_File_Integrity::init();
	GWQSH_Ajax::init();
	GWQSH_Admin::init();
}
add_action( 'plugins_loaded', 'gwqsh_bootstrap' );
