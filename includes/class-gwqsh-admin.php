<?php
/**
 * Admin screens, asset enqueuing, and view rendering for Gracewell QuietShield.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * GWQSH Admin implementation.
 */
final class GWQSH_Admin {

	private const PAGES = array(
		'dashboard'        => array( 'gracewell-quietshield', 'Dashboard' ),
		'login-protection' => array( 'gracewell-quietshield-login', 'Login Protection' ),
		'two-factor'       => array( 'gracewell-quietshield-two-factor', 'Two-Factor' ),
		'file-integrity'   => array( 'gracewell-quietshield-file-integrity', 'File Integrity' ),
		'hardening'        => array( 'gracewell-quietshield-hardening', 'Hardening' ),
		'activity-log'     => array( 'gracewell-quietshield-activity-log', 'Activity Log' ),
		'settings'         => array( 'gracewell-quietshield-settings', 'Settings' ),
	);

	/**
	 * Hooks.
	 *
	 * @var array
	 */
	private static $hooks = array();
	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'admin_body_class', array( __CLASS__, 'body_class' ) );
		add_filter( 'script_loader_tag', array( __CLASS__, 'module_script' ), 10, 3 );
		add_action( 'admin_head', array( __CLASS__, 'theme_bootstrap' ), 1 );
	}
	/**
	 * Menu.
	 */
	public static function menu() {
		self::$hooks['dashboard'] = add_menu_page(
			'QuietShield Dashboard',
			'QuietShield',
			'manage_options',
			self::PAGES['dashboard'][0],
			array( __CLASS__, 'render' ),
			'data:image/svg+xml;base64,' . base64_encode( file_get_contents( GWQSH_PATH . 'assets/img/quietshield-menu.svg' ) ),
			81
		);

		foreach ( self::PAGES as $key => $page ) {
			self::$hooks[ $key ] = add_submenu_page(
				self::PAGES['dashboard'][0],
				$page[1],
				$page[1],
				'two-factor' === $key ? 'read' : 'manage_options',
				$page[0],
				array( __CLASS__, 'render' )
			);
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			self::$hooks['two-factor'] = add_submenu_page( 'profile.php', 'Your 2FA Setup', 'Your 2FA Setup', 'read', self::PAGES['two-factor'][0], array( __CLASS__, 'render' ) );
		}
	}
	/**
	 * Current page.
	 */
	public static function current_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page selection; admin page registration checks its capability and AJAX mutations require a nonce.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		foreach ( self::PAGES as $key => $definition ) {
			if ( $page === $definition[0] ) {
				return $key;
			}
		}
		return null;
	}
	/**
	 * Render.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) && ! ( 'two-factor' === self::current_page() && current_user_can( 'read' ) ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'gracewell-quietshield' ) );
		}

		$page = self::current_page();
		if ( null === $page ) {
			return;
		}

		$current_user = wp_get_current_user();
		$user_login   = $current_user && $current_user->exists() ? $current_user->user_login : 'admin';
		$user_initial = strtoupper( substr( $user_login, 0, 1 ) );
		$user_role    = ( ! empty( $current_user->roles ) ) ? ucfirst( $current_user->roles[0] ) : 'Administrator';
		$logout_url   = wp_logout_url();

		echo '<div id="gracewell-quietshield-app" data-page="' . esc_attr( $page ) . '">';
		require GWQSH_PATH . 'admin/views/' . $page . '.php';
		echo '</div>';
	}
	/**
	 * Body class.
	 *
	 * @param mixed $classes Classes.
	 */
	public static function body_class( $classes ) {
		return null !== self::current_page() ? $classes . ' gwqsh-admin' : $classes;
	}
	/**
	 * Theme bootstrap.
	 */
	public static function theme_bootstrap() {
		if ( null === self::current_page() ) {
			return;
		}
		echo '<script>try{var gwqshTheme=localStorage.getItem("gwqsh-theme")||localStorage.getItem("qs-theme");if(gwqshTheme){document.documentElement.dataset.theme=gwqshTheme;}var gwqshDensity=localStorage.getItem("gwqsh-density")||localStorage.getItem("qs-density");if(gwqshDensity){document.documentElement.dataset.density=gwqshDensity;}}catch(e){}</script>';
	}
	/**
	 * Assets.
	 *
	 * @param mixed $hook Hook.
	 */
	public static function assets( $hook ) {
		$page = array_search( $hook, self::$hooks, true );
		if ( false === $page ) {
			return;
		}

		// Scoped Stylesheets.
		$styles = array( 'dashboard' );
		if ( 'dashboard' !== $page ) {
			$styles[] = 'page-fixes';
			$styles[] = $page;
		}
		if ( 'activity-log' === $page ) {
			$styles = array( 'dashboard', 'activity-log', 'page-fixes' );
		}

		$dependency = array();
		foreach ( $styles as $style ) {
			$handle = 'gwqsh-' . $style . '-style';
			wp_enqueue_style( $handle, GWQSH_URL . 'assets/css/scoped/' . $style . '.css', $dependency, GWQSH_VERSION );
			$dependency = array( $handle );
		}
		wp_enqueue_style( 'gwqsh-admin-compat', GWQSH_URL . 'assets/css/admin-compat.css', $dependency, GWQSH_VERSION );

		// Vendor scripts.
		wp_enqueue_script( 'gwqsh-motion', GWQSH_URL . 'assets/js/qs-motion.js', array(), GWQSH_VERSION, true );

		if ( 'two-factor' === $page ) {
			wp_enqueue_script( 'gwqsh-qrcode', GWQSH_URL . 'assets/js/qrcode.js', array(), GWQSH_VERSION, true );
		}

		$dependencies = array( 'gwqsh-motion' );
		if ( 'two-factor' === $page ) {
			$dependencies[] = 'gwqsh-qrcode';
		}

		// Configuration & page routing dictionary.
		$page_urls = array();
		foreach ( self::PAGES as $key => $definition ) {
			$page_urls[ $key ] = admin_url( 'admin.php?page=' . $definition[0] );
		}

		$current_user = wp_get_current_user();
		$user_login   = ( $current_user && $current_user->exists() ) ? $current_user->user_login : 'admin';
		$user_role    = ( ! empty( $current_user->roles ) ) ? ucfirst( $current_user->roles[0] ) : 'Administrator';

		$config_obj = array(
			'ajax_url'      => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'gwqsh_nonce' ),
			'pages'         => $page_urls,
			'current'       => $page,
			'home_url'      => home_url( '/' ),
			'login_base'    => GWQSH_Login_Protection::custom_login_base(),
			'login_suffix'  => get_option( 'permalink_structure' ) ? '/' : '',
			'version'       => GWQSH_VERSION,
			'notifications' => current_user_can( 'manage_options' ) ? GWQSH_Activity_Logger::query_logs(
				array(
					'limit' => 3,
					'days'  => 1,
				)
			)['items'] : array(),
			'logout_url'    => wp_logout_url(),
			'user'          => array(
				'login'   => $user_login,
				'role'    => $user_role,
				'initial' => strtoupper( substr( $user_login, 0, 1 ) ),
			),
		);

		// Prepare real initial dataset for current screen.
		$initial_data = self::get_page_initial_data( $page );

		// Enqueue Core script.
		wp_enqueue_script( 'gwqsh-core', GWQSH_URL . 'assets/js/qs-core.js', $dependencies, GWQSH_VERSION, true );
		wp_add_inline_script(
			'gwqsh-core',
			'window.GWQSH_CONFIG = ' . wp_json_encode( $config_obj ) . ';' .
			'window.GWQSH_PAGES = ' . wp_json_encode( $page_urls ) . ';' .
			'window.GWQSH_DATA = ' . wp_json_encode( $initial_data ) . ';',
			'before'
		);

		// Enqueue Page specific script.
		$handle = 'gwqsh-' . $page . '-script';
		wp_enqueue_script( $handle, GWQSH_URL . 'assets/js/' . $page . '.js', array( 'gwqsh-core' ), GWQSH_VERSION, true );

		// Three.js Hero birds.
		wp_enqueue_script( 'gwqsh-hero-birds', GWQSH_URL . 'assets/js/hero-birds.js', array( $handle ), GWQSH_VERSION, true );
		wp_script_add_data( 'gwqsh-hero-birds', 'type', 'module' );
	}
	/**
	 * Get page initial data.
	 *
	 * @param mixed $page Page.
	 */
	public static function get_page_initial_data( $page ) {
		$data = array();

		switch ( $page ) {
			case 'dashboard':
				$failed_24h   = GWQSH_Login_Protection::get_failed_attempts_24h();
				$locked_ips   = GWQSH_Login_Protection::get_currently_locked_ips();
				$allow_ips    = GWQSH_Login_Protection::get_allowlist();
				$all_users    = GWQSH_Two_Factor::get_all_users_2fa_list();
				$users_count  = count( $all_users );
				$users_2fa_on = 0;
				foreach ( $all_users as $u ) {
					if ( 'Enabled' === $u['st'] ) {
						++$users_2fa_on;
					}
				}
				$scan_summary = GWQSH_File_Integrity::get_summary();
				$hardening    = GWQSH_Hardening::get_checks();
				$hard_on      = count( array_filter( $hardening ) );
				$logs_res     = GWQSH_Activity_Logger::query_logs(
					array(
						'limit' => 6,
						'days'  => 30,
					)
				);

				$score = (int) round(
					( GWQSH_Login_Protection::get_settings()['enabled'] ? 90 : 30 ) * 0.20 +
					( $users_count > 0 ? round( ( $users_2fa_on / $users_count ) * 100 ) : 0 ) * 0.25 +
					( $scan_summary['total'] > 0 && 0 === ( $scan_summary['modified'] + $scan_summary['suspicious'] ) ? 100 : 0 ) * 0.15 +
					round( ( $hard_on / 6 ) * 100 ) * 0.25 +
					0 * 0.15
				);

				$data = array(
					'failed_24h'      => $failed_24h,
					'locked_ips'      => $locked_ips,
					'allow_ips'       => $allow_ips,
					'users_total'     => $users_count,
					'users_2fa'       => $users_2fa_on,
					'scan_summary'    => $scan_summary,
					'hardening'       => $hardening,
					'hardening_on'    => $hard_on,
					'security_score'  => $score,
					'recent_activity' => $logs_res['items'],
					'total_events'    => $logs_res['total'],
					'client_ip'       => GWQSH_Settings::get_client_ip(),
				);
				break;

			case 'login-protection':
				$data = array(
					'settings'     => GWQSH_Login_Protection::get_settings(),
					'locked_ips'   => GWQSH_Login_Protection::get_currently_locked_ips(),
					'allow_ips'    => GWQSH_Login_Protection::get_allowlist(),
					'failed_24h'   => GWQSH_Login_Protection::get_failed_attempts_24h(),
					'chart'        => GWQSH_Login_Protection::get_activity_chart_data( 7 ),
					'recovery_url' => GWQSH_Login_Protection::recovery_url(),
					'client_ip'    => GWQSH_Settings::get_client_ip(),
				);
				break;

			case 'two-factor':
				$current_user = wp_get_current_user();
				$secret       = GWQSH_Two_Factor::get_provisioning_secret( $current_user->ID );
				$data         = array(
					'secret'            => $secret,
					'qr_uri'            => $secret ? GWQSH_Two_Factor::get_totp_uri( $current_user->user_login, $secret ) : '',
					'backup_codes'      => GWQSH_Two_Factor::get_user_backup_codes( $current_user->ID ),
					'users'             => GWQSH_Two_Factor::get_all_users_2fa_list(),
					'devices'           => GWQSH_Two_Factor::get_trusted_devices( $current_user->ID ),
					'settings'          => GWQSH_Two_Factor::get_settings(),
					'can_manage'        => current_user_can( 'manage_options' ),
					'plugin_active'     => GWQSH_Settings::is_active(),
					'effective_enabled' => GWQSH_Two_Factor::effective_enabled( $current_user->ID ),
					'is_enabled'        => GWQSH_Two_Factor::is_user_2fa_enabled( $current_user->ID ),
				);
				break;

			case 'file-integrity':
				$data = array(
					'summary' => GWQSH_File_Integrity::get_summary(),
					'issues'  => GWQSH_File_Integrity::get_scan_issues(),
					'history' => GWQSH_File_Integrity::get_scan_history(),
				);
				break;

			case 'hardening':
				$hardening = GWQSH_Hardening::get_checks();
				$data      = array(
					'hardening' => $hardening,
					'on_count'  => count( array_filter( $hardening ) ),
					'risk'      => GWQSH_Hardening::get_risk(),
				);
				break;

			case 'activity-log':
				$logs_res = GWQSH_Activity_Logger::query_logs(
					array(
						'days'  => 7,
						'limit' => 10,
					)
				);
				$data     = array(
					'items'     => $logs_res['items'],
					'total'     => $logs_res['total'],
					'breakdown' => GWQSH_Activity_Logger::get_type_breakdown( 7 ),
				);
				break;

			case 'settings':
				$data = array(
					'settings' => GWQSH_Settings::get_all(),
				);
				break;
		}

		if ( in_array( $page, array( 'dashboard', 'hardening' ), true ) ) {
			$data['score_components'] = self::get_score_components();
			$data['security_score']   = self::score_from_components( $data['score_components'] );
		}
		return $data;
	}
	/**
	 * Get score components.
	 */
	public static function get_score_components() {
		$users   = get_users( array( 'fields' => 'ID' ) );
		$enabled = count(
			array_filter(
				$users,
				static function ( $id ) {
					return GWQSH_Two_Factor::effective_enabled( $id ) && GWQSH_Two_Factor::get_user_secret( $id );
				}
			)
		);
		$scan    = GWQSH_File_Integrity::get_summary();
		$issues  = $scan['modified'] + $scan['missing'] + $scan['suspicious'];
		return array(
			'login'     => GWQSH_Settings::is_active() && GWQSH_Login_Protection::get_settings()['enabled'] ? 100 : 0,
			'tfa'       => count( $users ) ? (int) round( $enabled / count( $users ) * 100 ) : 0,
			'file'      => $scan['total'] ? max( 0, (int) round( ( $scan['total'] - $issues ) / $scan['total'] * 100 ) ) : 0,
			'hardening' => GWQSH_Hardening::get_risk()['score'],
			'activity'  => wp_next_scheduled( 'gwqsh_daily_log_cleanup' ) ? 100 : 0,
		);
	}
	/**
	 * Score from components.
	 *
	 * @param array $values Values.
	 */
	public static function score_from_components( array $values ) {
		return (int) round( $values['login'] * .2 + $values['tfa'] * .25 + $values['file'] * .15 + $values['hardening'] * .25 + $values['activity'] * .15 );
	}
	/**
	 * Module script.
	 *
	 * @param mixed $tag Tag.
	 * @param mixed $handle Handle.
	 * @param mixed $src Src.
	 */
	public static function module_script( $tag, $handle, $src ) {
		if ( 'gwqsh-hero-birds' !== $handle ) {
			return $tag;
		}
		// Keep WordPress's enqueued tag and its attributes, changing only the script type.
		$tag = preg_replace( '/\s+type=([\"\'])[^\"\']*\1/', '', $tag );
		return preg_replace( '/(<)(script)\b/', '$1$2 type="module"', $tag, 1 );
	}
}
