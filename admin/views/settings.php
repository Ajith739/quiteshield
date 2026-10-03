<?php
/**
 * Settings administration view.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;
$gwqsh_data      = GWQSH_Admin::get_page_initial_data( 'settings' );
$gwqsh_current_u = wp_get_current_user();
$gwqsh_u_name    = ( $gwqsh_current_u && $gwqsh_current_u->exists() ) ? ( $gwqsh_current_u->display_name ? $gwqsh_current_u->display_name : $gwqsh_current_u->user_login ) : 'Admin';
$gwqsh_u_initial = strtoupper( substr( $gwqsh_u_name, 0, 1 ) );
$gwqsh_u_role    = ! empty( $gwqsh_current_u->roles[0] ) ? ucfirst( $gwqsh_current_u->roles[0] ) : 'Administrator';
?>
<!-- Icon sprite (from dashboard) -->
	<svg width="0" height="0" style="position:absolute" aria-hidden="true">
	<symbol id="i-grid" viewBox="0 0 24 24">
		<rect width="7" height="9" x="3" y="3" rx="1" />
		<rect width="7" height="5" x="14" y="3" rx="1" />
		<rect width="7" height="9" x="14" y="12" rx="1" />
		<rect width="7" height="5" x="3" y="16" rx="1" />
	</symbol>
	<symbol id="i-lock" viewBox="0 0 24 24">
		<rect width="18" height="11" x="3" y="11" rx="2" />
		<path d="M7 11V7a5 5 0 0 1 10 0v4" />
	</symbol>
	<symbol id="i-shield-check" viewBox="0 0 24 24">
		<path
		d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
		<path d="m9 12 2 2 4-4" />
	</symbol>
	<symbol id="i-file" viewBox="0 0 24 24">
		<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
		<path d="M14 2v4a2 2 0 0 0 2 2h4" />
		<path d="M10 9H8" />
		<path d="M16 13H8" />
		<path d="M16 17H8" />
	</symbol>
	<symbol id="i-gear" viewBox="0 0 24 24">
		<path
		d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
		<circle cx="12" cy="12" r="3" />
	</symbol>
	<symbol id="i-list" viewBox="0 0 24 24">
		<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
	</symbol>
	<symbol id="i-activity" viewBox="0 0 24 24">
		<path d="M22 12h-4l-3 9L9 3l-3 9H2" />
	</symbol>
	<symbol id="i-crown" viewBox="0 0 24 24">
		<path
		d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z" />
		<path d="M5 21h14" />
	</symbol>
	<symbol id="i-search" viewBox="0 0 24 24">
		<circle cx="11" cy="11" r="8" />
		<path d="m21 21-4.3-4.3" />
	</symbol>
	<symbol id="i-bell" viewBox="0 0 24 24">
		<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
		<path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
	</symbol>
	<symbol id="i-help" viewBox="0 0 24 24">
		<circle cx="12" cy="12" r="10" />
		<path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3M12 17h.01" />
	</symbol>
	<symbol id="i-arrow" viewBox="0 0 24 24">
		<path d="M5 12h14M12 5l7 7-7 7" />
	</symbol>
	<symbol id="i-check" viewBox="0 0 24 24">
		<path d="M20 6 9 17l-5-5" />
	</symbol>
	<symbol id="i-alert" viewBox="0 0 24 24">
		<circle cx="12" cy="12" r="10" />
		<path d="M12 8v4M12 16h.01" />
	</symbol>
	<symbol id="i-menu" viewBox="0 0 24 24">
		<path d="M4 6h16M4 12h16M4 18h16" />
	</symbol>
	<symbol id="i-refresh" viewBox="0 0 24 24">
		<path d="M21 12a9 9 0 1 1-3-6.7L21 8" />
		<path d="M21 3v5h-5" />
	</symbol>
	<symbol id="i-moon" viewBox="0 0 24 24">
		<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" />
	</symbol>
	<symbol id="i-logout" viewBox="0 0 24 24">
		<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" />
	</symbol>
	<symbol id="i-book" viewBox="0 0 24 24">
		<path
		d="M12 7v14M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z" />
	</symbol>
	<symbol id="i-report" viewBox="0 0 24 24">
		<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
		<path d="M14 2v6h6M16 13H8M16 17H8" />
	</symbol>
	</svg>

	<a class="skip" href="#main">Skip to content</a>

	<header class="topbar">
	<div class="topbar-in">
		<a class="brand" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield' ) ); ?>" aria-label="QuietShield home">
		<img class="logo-mark" src="<?php echo esc_url( GWQSH_URL . 'assets/img/quietshield.svg' ); ?>" alt="" width="40" height="44">
		<span class="brand-txt"><b><span>QuietShield</span></b><small>A Safer WordPress, A Quieter
			Tomorrow</small></span>
		</a>

		<nav class="nav" id="mainNav" aria-label="Main">
		<a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield' ) ); ?>"><svg class="i">
			<use href="#i-grid" />
			</svg><span>Dashboard</span></a>
		<a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-login' ) ); ?>"><svg class="i">
			<use href="#i-lock" />
			</svg><span>Login Protection</span></a>
		<a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-two-factor' ) ); ?>"><svg class="i">
			<use href="#i-shield-check" />
			</svg><span>Two-Factor</span></a>
		<a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-file-integrity' ) ); ?>"><svg class="i">
			<use href="#i-file" />
			</svg><span>File Integrity</span></a>
		<a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-hardening' ) ); ?>"><svg class="i">
			<use href="#i-gear" />
			</svg><span>Hardening</span></a>
		<a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-activity-log' ) ); ?>"><svg class="i">
			<use href="#i-list" />
			</svg><span>Activity Log</span></a>
		</nav>

		<button class="pro-link" data-pro type="button"><svg class="i fill">
			<use href="#i-crown" />
		</svg><span>Pro</span></button>

		<div class="top-actions">

		<div class="menu-wrap">
			<button class="icon-btn" data-menu="notif" type="button" aria-haspopup="true" aria-expanded="false"
			aria-label="Notifications">
			<svg class="i">
				<use href="#i-bell" />
			</svg><span class="dot-badge" id="notifDot">1</span>
			</button>
			<div class="menu menu-notif" id="menu-notif" role="menu">
			<div class="menu-head"><b>Notifications</b><button class="link-btn" id="markRead" type="button">Mark all as
				read</button></div>
			<div class="menu-item">Loading recent events…</div>

			</div>
		</div>

		<div class="menu-wrap help-wrap">
			<button class="icon-btn" data-menu="help" type="button" aria-haspopup="true" aria-expanded="false"
			aria-label="Help"><svg class="i">
				<use href="#i-help" />
			</svg></button>
			<div class="menu" id="menu-help" role="menu"><button class="menu-item" role="menuitem" type="button" data-changelog>Changelog · Version 1.0.0</button>
			<a role="menuitem" class="menu-item" href="https://wordpress.org/support/" target="_blank"
				rel="noopener"><svg class="i">
				<use href="#i-book" />
				</svg><span>Documentation</span></a>
			<a role="menuitem" class="menu-item" href="https://wordpress.org/support/forums/" target="_blank"
				rel="noopener"><svg class="i">
				<use href="#i-help" />
				</svg><span>Get support</span></a>

			</div>
		</div>

		<span class="vsep" aria-hidden="true"></span>

		<div class="menu-wrap">
			<button class="avatar-btn" data-menu="user" type="button" aria-haspopup="true" aria-expanded="false"
			aria-label="Account menu"><span class="avatar"><?php echo esc_html( $gwqsh_u_initial ); ?></span></button>
			<div class="menu" id="menu-user" role="menu">
			<div class="menu-head"><b><?php echo esc_html( $gwqsh_u_name ); ?></b><small><?php echo esc_html( $gwqsh_u_role ); ?></small></div>
			<a role="menuitem" class="menu-item" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-settings' ) ); ?>"><svg class="i">
				<use href="#i-gear" />
				</svg><span>Plugin settings</span></a>
			<button role="menuitem" class="menu-item" id="themeToggle" type="button"><svg class="i">
				<use href="#i-moon" />
				</svg><span id="themeLbl">Dark mode</span></button>
			<a role="menuitem" class="menu-item" href="<?php echo esc_url( wp_logout_url() ); ?>"><svg class="i">
				<use href="#i-logout" />
				</svg><span>Log out</span></a>
			</div>
		</div>

		<button class="icon-btn burger" id="burger" type="button" aria-label="Open menu" aria-expanded="false"
			aria-controls="mainNav"><svg class="i">
			<use href="#i-menu" />
			</svg></button>
		</div>
	</div>
	</header>

	<main id="main">
	<section class="hero" aria-labelledby="heroTitle">
		<canvas class="hero-birds" id="heroBirds" aria-hidden="true"></canvas>
		<div class="wrap hero-in">
		<div class="hero-content">
			<p class="eyebrow">Plugin Settings</p>
			<h1 class="h1" id="heroTitle">Plugin <span class="hl">Settings</span></h1>
			<p class="lede">Configure plugin preferences and tools.</p>
		</div>
		</div>
	</section>

	<div class="wrap page">
		<div class="st-grid">
		<div class="st-main">
			<div class="card st-tabs">
			<div class="seg slim tabs-scroll" role="tablist" aria-label="Settings sections"><button class="seg-btn"
				role="tab" id="t-general" aria-selected="true" aria-controls="panels" data-tab="general"><svg class="i"
					viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
					stroke-linejoin="round" aria-hidden="true">
					<path
					d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
					<circle cx="12" cy="12" r="3" />
				</svg>General</button><button class="seg-btn" role="tab" id="t-security" aria-selected="false"
				aria-controls="panels" data-tab="security"><svg class="i" viewBox="0 0 24 24" fill="none"
					stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
					<path
					d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
				</svg>Security</button><button class="seg-btn" role="tab" id="t-tools" aria-selected="false"
				aria-controls="panels" data-tab="tools"><svg class="i" viewBox="0 0 24 24" fill="none"
					stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
					<path
					d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
				</svg>Tools</button></div>
			</div>
			<div class="st-panels" id="panels" role="tabpanel">
			<section class="card s-card" id="general" data-tab="general" aria-labelledby="h-general">
				<div class="card-head"><span class="tile t-purple"><svg class="i solid" viewBox="0 0 24 24" fill="none"
					stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
					<path
						d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"
						fill="currentColor" />
					<circle cx="12" cy="12" r="3" stroke="#fff" />
					</svg></span>
				<div class="ttl">
					<h2 id="h-general">General Settings</h2>
					<p>Basic configuration for QuietShield plugin.</p>
				</div>
				</div>
				<div class="divide">
				<div class="set-row"><span class="tile sm t-green"><svg class="i" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"
						aria-hidden="true">
						<path d="M12 2v10" />
						<path d="M18.4 6.6a9 9 0 1 1-12.77.04" />
					</svg></span>
					<div class="txt"><b id="l-status">Plugin status</b><small>Enable or disable QuietShield on your
						site.</small></div>
					<div class="switch-set ctl auto"><button class="switch" role="switch" data-key="status"
						aria-checked="true" aria-labelledby="l-status"></button><span
						class="pill switch-pill p-green">Enabled</span></div>
				</div>
				<div class="set-row"><span class="tile sm t-blue"><svg class="i" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"
						aria-hidden="true">
						<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
						<path d="M21 3v5h-5" />
						<path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
						<path d="M8 16H3v5" />
					</svg></span>
					<div class="txt"><b id="l-autoupdate">Automatic updates</b><small>Automatically update to new
						versions.</small></div>
					<div class="switch-set ctl auto"><button class="switch" role="switch" data-key="autoupdate"
						aria-checked="true" aria-labelledby="l-autoupdate"></button><span
						class="pill switch-pill p-green">Enabled</span></div>
				</div>
				<div class="set-row wrap-sm"><span class="tile sm t-purple"><svg class="i" viewBox="0 0 24 24"
						fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"
						stroke-linejoin="round" aria-hidden="true">
						<ellipse cx="12" cy="5" rx="9" ry="3" />
						<path d="M3 5V19A9 3 0 0 0 21 19V5" />
						<path d="M3 12A9 3 0 0 0 21 12" />
					</svg></span><label class="txt" for="retention"><b>Data retention (activity logs)</b><small>Number
						of days to keep activity logs.</small></label>
					<div class="ctl wide"><select class="field field-sm" id="retention" data-key="retention">
						<option value="7">7 days</option>
						<option value="30" selected>30 days</option>
						<option value="90">90 days</option>
						<option value="365">1 year</option>
					</select></div>
				</div>
				<div class="set-row wrap-sm"><span class="tile sm t-amber"><svg class="i" viewBox="0 0 24 24"
						fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"
						stroke-linejoin="round" aria-hidden="true">
						<path d="m5 8 6 6" />
						<path d="m4 14 6-6 2-3" />
						<path d="M2 5h12" />
						<path d="M7 2h1" />
						<path d="m22 22-5-10-5 10" />
						<path d="M14 18h6" />
					</svg></span><label class="txt" for="language"><b>Plugin language</b><small>Select the language for
						the plugin interface.</small></label>
					<div class="ctl wide"><select class="field field-sm" id="language" data-key="language">
						<option value="en_US" selected>English (US)</option>
						<option value="en_GB">English (UK)</option>
						<option value="ta_IN">தமிழ்</option>
						<option value="hi_IN">हिन्दी</option>
						<option value="es_ES">Español</option>
					</select></div>
				</div>
				<div class="set-row wrap-sm"><span class="tile sm t-blue"><svg class="i" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"
						aria-hidden="true">
						<circle cx="12" cy="12" r="10" />
						<polyline points="12 6 12 12 16 14" />
					</svg></span><label class="txt" for="timezone"><b>Timezone</b><small>Set the timezone for logs and
						scheduled tasks.</small></label>
					<div class="ctl wide"><select class="field field-sm" id="timezone" data-key="timezone">
						<option value="Asia/Kolkata" selected>Asia/Kolkata (GMT +5:30)</option>
						<option value="UTC">UTC (GMT +0:00)</option>
						<option value="Europe/London">Europe/London (GMT +1:00)</option>
						<option value="America/New_York">America/New_York (GMT -4:00)</option>
					</select></div>
				</div>
				</div>
			</section>
			<section class="card s-card" id="security" data-tab="security" aria-labelledby="h-security">
				<div class="card-head"><span class="tile t-blue"><svg class="i solid" viewBox="0 0 24 24" fill="none"
					stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
					<path
						d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"
						fill="currentColor" />
					</svg></span>
				<div class="ttl">
					<h2 id="h-security">Security Settings</h2>
					<p>Configure global security preferences.</p>
				</div>
				</div>
				<div class="divide">
				<div class="set-row"><span class="tile sm t-purple"><svg class="i" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"
						aria-hidden="true">
						<path d="M3 3v16a2 2 0 0 0 2 2h16" />
						<path d="M18 17V9" />
						<path d="M13 17V5" />
						<path d="M8 17v-3" />
					</svg></span>
					<div class="txt"><b id="l-telemetry">Telemetry &amp; usage data</b><small>Help us improve QuietShield
						by sharing anonymous data.</small></div>
					<div class="switch-set ctl auto"><button class="switch" role="switch" data-key="telemetry"
						aria-checked="false" aria-labelledby="l-telemetry"></button><span
						class="pill switch-pill p-gray">Disabled</span></div>
				</div>
				<div class="set-row"><span class="tile sm t-amber"><svg class="i solid" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
						aria-hidden="true">
						<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" fill="currentColor" />
						<path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
					</svg></span>
					<div class="txt"><b id="l-notice">Show admin notice for critical issues</b><small>Display an admin
						notice when a serious security issue is detected.</small></div>
					<div class="switch-set ctl auto"><button class="switch" role="switch" data-key="notice"
						aria-checked="true" aria-labelledby="l-notice"></button><span
						class="pill switch-pill p-green">Enabled</span></div>
				</div>
				<div class="set-row"><span class="tile sm t-blue"><svg class="i" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
						aria-hidden="true">
						<circle cx="12" cy="12" r="10" />
						<path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
						<path d="M2 12h20" />
					</svg></span>
					<div class="txt"><b id="l-proxy">Allow IP detection via proxy</b><small>Use X-Forwarded-For /
						CF-Connecting-IP when behind a proxy.</small></div>
					<div class="switch-set ctl auto"><button class="switch" role="switch" data-key="proxy"
						aria-checked="false" aria-labelledby="l-proxy"></button><span
						class="pill switch-pill p-gray">Disabled</span></div>
				</div>
				<div class="set-row"><span class="tile sm t-red"><svg class="i solid" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
						aria-hidden="true">
						<rect width="18" height="11" x="3" y="11" rx="2" ry="2" fill="currentColor" />
						<path d="M7 11V7a5 5 0 0 1 10 0v4" />
					</svg></span>
					<div class="txt"><b id="l-https">Enforce HTTPS for plugin pages</b><small>Redirect plugin pages to
						HTTPS.</small></div>
					<div class="switch-set ctl auto"><button class="switch" role="switch" data-key="https"
						aria-checked="true" aria-labelledby="l-https"></button><span
						class="pill switch-pill p-green">Enabled</span></div>
				</div>
				<div class="set-row"><span class="tile sm t-blue"><svg class="i solid" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
						aria-hidden="true">
						<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" fill="currentColor" />
						<circle cx="9" cy="7" r="4" fill="currentColor" />
						<path d="M22 21v-2a4 4 0 0 0-3-3.87" />
						<path d="M16 3.13a4 4 0 0 1 0 7.75" />
					</svg></span>
					<div class="txt"><b>Bypass for admin IPs</b><small>Always allow these IPs and never apply security
						restrictions.</small></div><a class="btn btn-outline btn-sm ctl auto"
					href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-login#allowlist' ) ); ?>">Manage IPs</a>
				</div>
				</div>
			</section>

			<!-- Appearance section hidden for v1.0 initial release -->
			<section class="card s-card hide" id="appearance" data-tab="appearance" aria-labelledby="h-appearance">
				<div class="card-head"><span class="tile t-purple"><svg class="i" viewBox="0 0 24 24" fill="none"
					stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
					<circle cx="13.5" cy="6.5" r=".5" fill="currentColor" />
					<circle cx="17.5" cy="10.5" r=".5" fill="currentColor" />
					<circle cx="8.5" cy="7.5" r=".5" fill="currentColor" />
					<circle cx="6.5" cy="12.5" r=".5" fill="currentColor" />
					<path
						d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z" />
					</svg></span>
				<div class="ttl">
					<h2 id="h-appearance">Appearance Settings</h2>
					<p>Customize the plugin interface.</p>
				</div>
				</div>
				<div class="divide">
				<div class="set-row wrap-sm"><span class="tile sm t-purple"><svg class="i" viewBox="0 0 24 24"
						fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
						aria-hidden="true">
						<rect width="20" height="14" x="2" y="3" rx="2" />
						<line x1="8" x2="16" y1="21" y2="21" />
						<line x1="12" x2="12" y1="17" y2="21" />
					</svg></span><label class="txt" for="theme"><b>Admin page color scheme</b><small>Choose a color
						theme for plugin pages.</small></label>
					<div class="ctl wide"><select class="field field-sm" id="theme" data-key="theme">
						<option value="light" selected>Light (Default)</option>
						<option value="dark">Dark</option>
						<option value="system">Match system</option>
					</select></div>
				</div>
				<div class="set-row"><span class="tile sm t-blue"><svg class="i" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
						aria-hidden="true">
						<rect width="18" height="7" x="3" y="3" rx="1" />
						<rect width="7" height="7" x="3" y="14" rx="1" />
						<rect width="7" height="7" x="14" y="14" rx="1" />
					</svg></span>
					<div class="txt"><b id="l-compact">Use compact layout</b><small>Reduce spacing for a more compact
						view.</small></div>
					<div class="switch-set ctl auto"><button class="switch" role="switch" data-key="compact"
						aria-checked="false" aria-labelledby="l-compact"></button><span
						class="pill switch-pill p-gray">Disabled</span></div>
				</div>
				<div class="set-row"><span class="tile sm t-amber"><svg class="i" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"
						aria-hidden="true">
						<path
						d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2" />
					</svg></span>
					<div class="txt"><b id="l-anim">Show animated charts</b><small>Enable smooth animations for charts and
						graphs.</small></div>
					<div class="switch-set ctl auto"><button class="switch" role="switch" data-key="anim"
						aria-checked="true" aria-labelledby="l-anim"></button><span
						class="pill switch-pill p-green">Enabled</span></div>
				</div>
				<div class="set-row"><span class="tile sm t-amber"><svg class="i" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"
						aria-hidden="true">
						<path
						d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5" />
						<path d="M9 18h6" />
						<path d="M10 22h4" />
					</svg></span>
					<div class="txt"><b id="l-tips">Display security tips</b><small>Show helpful security tips and
						recommendations.</small></div>
					<div class="switch-set ctl auto"><button class="switch" role="switch" data-key="tips"
						aria-checked="true" aria-labelledby="l-tips"></button><span
						class="pill switch-pill p-green">Enabled</span></div>
				</div>
				</div>
			</section>
			<!-- Advanced section hidden for v1.0 initial release -->
			<section class="card s-card hide" id="advanced" data-tab="advanced" aria-labelledby="h-advanced">
				<div class="card-head"><span class="tile t-blue"><svg class="i" viewBox="0 0 24 24" fill="none"
					stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
					<line x1="21" x2="14" y1="4" y2="4" />
					<line x1="10" x2="3" y1="4" y2="4" />
					<line x1="21" x2="12" y1="12" y2="12" />
					<line x1="8" x2="3" y1="12" y2="12" />
					<line x1="21" x2="16" y1="20" y2="20" />
					<line x1="12" x2="3" y1="20" y2="20" />
					<line x1="14" x2="14" y1="2" y2="6" />
					<line x1="8" x2="8" y1="10" y2="14" />
					<line x1="16" x2="16" y1="18" y2="22" />
					</svg></span>
				<div class="ttl">
					<h2 id="h-advanced">Advanced Settings</h2>
					<p>Options for developers and unusual server setups.</p>
				</div>
				</div>
				<div class="divide">
				<div class="set-row"><span class="tile sm t-purple"><svg class="i" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
						aria-hidden="true">
						<path d="M10 12.5 8 15l2 2.5" />
						<path d="m14 12.5 2 2.5-2 2.5" />
						<path d="M14 2v4a2 2 0 0 0 2 2h4" />
						<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z" />
					</svg></span>
					<div class="txt"><b id="l-debug">Debug logging</b><small>Write detailed plugin logs to
						wp-content/qs-debug.log.</small></div>
					<div class="switch-set ctl auto"><button class="switch" role="switch" data-key="debug"
						aria-checked="false" aria-labelledby="l-debug"></button><span
						class="pill switch-pill p-gray">Disabled</span></div>
				</div>
				<div class="set-row wrap-sm"><span class="tile sm t-blue"><svg class="i" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
						aria-hidden="true">
						<circle cx="12" cy="12" r="10" />
						<path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
						<path d="M2 12h20" />
					</svg></span><label class="txt" for="proxyheader"><b>Trusted proxy header</b><small>Which header
						holds the visitor IP when proxy detection is on.</small></label>
					<div class="ctl wide"><select class="field field-sm" id="proxyheader" data-key="proxyheader">
						<option value="xff" selected>X-Forwarded-For</option>
						<option value="cf">CF-Connecting-IP</option>
						<option value="real">X-Real-IP</option>
					</select></div>
				</div>
				<div class="set-row"><span class="tile sm t-red"><svg class="i" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
						aria-hidden="true">
						<path d="M3 6h18" />
						<path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
						<path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
						<line x1="10" x2="10" y1="11" y2="17" />
						<line x1="14" x2="14" y1="11" y2="17" />
					</svg></span>
					<div class="txt"><b id="l-uninstall">Delete data on uninstall</b><small>Remove all QuietShield
						settings and logs when the plugin is deleted.</small></div>
					<div class="switch-set ctl auto"><button class="switch" role="switch" data-key="uninstall"
						aria-checked="false" aria-labelledby="l-uninstall"></button><span
						class="pill switch-pill p-gray">Disabled</span></div>
				</div>
				</div>
			</section>
			<section class="card s-card" id="tools" data-tab="tools" aria-labelledby="h-tools">
				<div class="card-head"><span class="tile t-amber"><svg class="i" viewBox="0 0 24 24" fill="none"
					stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
					<path
						d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
					</svg></span>
				<div class="ttl">
					<h2 id="h-tools">Tools</h2>
					<p>One-off maintenance tasks.</p>
				</div>
				</div>
				<div class="divide">

				<div class="set-row"><span class="tile sm t-green"><svg class="i" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
						aria-hidden="true">
						<path d="M3 7V5a2 2 0 0 1 2-2h2" />
						<path d="M17 3h2a2 2 0 0 1 2 2v2" />
						<path d="M21 17v2a2 2 0 0 1-2 2h-2" />
						<path d="M7 21H5a2 2 0 0 1-2-2v-2" />
						<path d="M7 12h10" />
					</svg></span>
					<div class="txt"><b>Run file integrity scan</b><small>Verify files against the official core baseline.</small></div><button class="btn btn-outline btn-sm" id="rebuild"
					type="button">Scan</button>
				</div>
				<div class="set-row"><span class="tile sm t-red"><svg class="i" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
						aria-hidden="true">
						<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
						<path d="M3 3v5h5" />
						<path d="M12 7v5l4 2" />
					</svg></span>
					<div class="txt"><b>Clear activity logs</b><small>Permanently delete all stored activity
						events.</small></div><button class="btn btn-danger btn-sm" id="clearLogs" type="button">Clear
					logs</button>
				</div>
				</div>
			</section>
			</div>
		</div>
		<aside class="stack st-side" aria-label="Plugin details">
			<section class="card" aria-labelledby="h-info">
			<div class="card-head" style="align-items:center;margin-bottom:8px"><span class="tile t-purple"><svg
					class="i solid" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
					stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" fill="currentColor" />
					<path d="M14 2v4a2 2 0 0 0 2 2h4" stroke="#fff" />
					<path d="M10 9H8" stroke="#fff" />
					<path d="M16 13H8" stroke="#fff" />
					<path d="M16 17H8" stroke="#fff" />
				</svg></span>
				<div class="ttl">
				<h2 id="h-info">Plugin Information</h2>
				</div>
			</div>
			<dl class="info-list">
				<div>
				<dt>Version</dt>
				<dd>v<?php echo esc_html( GWQSH_VERSION ); ?> (Free)</dd>
				</div>
				<div>
				<dt>Last updated</dt>
				<dd><?php echo esc_html( wp_date( 'M j, Y', filemtime( GWQSH_PLUGIN_FILE ) ) ); ?></dd>
				</div>
				<div>
				<dt>WordPress version</dt>
				<dd><?php echo esc_html( get_bloginfo( 'version' ) ); ?></dd>
				</div>
				<div>
				<dt>PHP version</dt>
				<dd><?php echo esc_html( PHP_VERSION ); ?></dd>
				</div>
				<div>
				<dt>Site URL</dt>
				<dd><?php echo esc_html( home_url( '/' ) ); ?></dd>
				</div>
				<div>
				<dt>Total scans run</dt>
				<dd>
				<?php
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Simple count from custom scan history table.
				echo esc_html( (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . esc_sql( $wpdb->prefix . 'gwqsh_scan_history' ) ) );
				?>
				</dd>
				</div>
				<div>
				<dt>Site security score</dt>
				<dd><a href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-hardening' ) ); ?>" class="score-link"><i class="dot c-green"></i><?php echo esc_html( GWQSH_Admin::score_from_components( GWQSH_Admin::get_score_components() ) ); ?> / 100</a></dd>
				</div>
			</dl>
			</section>
			<!-- Import/Export hidden for v1.0 initial release -->

			<!-- Help section hidden for v1.0 initial release -->
		</aside>
		</div>
	</div>
	</main>

	<footer class="foot">
	<div class="wrap foot-in">
		<div class="foot-brand"><b>Gracewell QuietShield</b><span class="vs"></span><span class="muted">v<?php echo esc_html( GWQSH_VERSION ); ?></span><span
			class="vs"></span><span class="muted tagline">A lightweight
			WordPress security plugin</span></div>
		<nav class="foot-links" aria-label="Footer">
		<a href="https://wordpress.org/support/" target="_blank" rel="noopener">Documentation</a>
		<a href="https://wordpress.org/support/forums/" target="_blank" rel="noopener">Support</a>
		<a href="#" data-toast="Thanks! Feedback form coming soon.">Feedback</a>
		</nav>
	</div>
	</footer>

	<div class="toasts" id="toasts" aria-live="polite"></div>

	<!-- Third-party libs (must load before qs-core since it reads window.gsap/THREE) -->



	<!-- Shared utility library (from login-protection step) -->


	<!-- Page-specific logic -->


	<!-- Hero birds animation (shared, ES module) -->
