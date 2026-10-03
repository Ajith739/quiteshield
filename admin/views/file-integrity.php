<?php
/**
 * File integrity administration view.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;
$gwqsh_data      = GWQSH_Admin::get_page_initial_data( 'file-integrity' );
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
		<a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-file-integrity' ) ); ?>" aria-current="page"><svg class="i">
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
			<p class="eyebrow">File Integrity</p>
			<h1 class="h1" id="heroTitle">File Integrity <span class="hl">Monitor</span></h1>
			<p class="lede">Detect unauthorized changes to your WordPress core files, plugins and themes.</p>
		</div>
		</div>
	</section>

	<div class="wrap page">
		<div class="stats fi-stats">
	<article class="card stat">
	<div class="stat-head">
		<span class="tile t-green round"><svg class="i solid" viewBox="0 0 24 24" fill="none"
			stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" fill="currentColor" />
			<path d="M14 2v4a2 2 0 0 0 2 2h4" stroke="#fff" />
			<path d="M10 9H8" stroke="#fff" />
			<path d="M16 13H8" stroke="#fff" />
			<path d="M16 17H8" stroke="#fff" />
		</svg></span>
		<span class="stat-title">Core Files</span>
		<span class="pill p-green">Monitored</span>
	</div>

	<div class="stat-main">
		<div class="stat-body">
		<div class="stat-num" data-count="<?php echo esc_attr( (int) get_option( 'gwqsh_last_scan_core_total', 0 ) ); ?>">0</div>
		<div class="stat-lbl">Total core files scanned</div>
		</div>
		<svg class="mini-bars c-green" viewBox="0 0 64 32" aria-hidden="true">
		<rect x="2"  y="16" width="4" height="16" rx="1.2"/>
		<rect x="10" y="8"  width="4" height="24" rx="1.2"/>
		<rect x="18" y="14" width="4" height="18" rx="1.2"/>
		<rect x="26" y="6"  width="4" height="26" rx="1.2"/>
		<rect x="34" y="12" width="4" height="20" rx="1.2"/>
		<rect x="42" y="18" width="4" height="14" rx="1.2"/>
		<rect x="50" y="10" width="4" height="22" rx="1.2"/>
		</svg>
	</div>

	<ul class="legend-mini">
		<li><span class="lbl"><i class="dot c-green"></i>Unmodified</span><b data-k="core-ok"><?php echo esc_html( $gwqsh_data['summary']['breakdown']['core-ok'] ?? 0 ); ?></b></li>
		<li><span class="lbl"><i class="dot c-amber"></i>Modified</span><b data-k="core-mod"><?php echo esc_html( $gwqsh_data['summary']['breakdown']['core-mod'] ?? 0 ); ?></b></li>
		<li><span class="lbl"><i class="dot c-red"></i>Missing</span><b data-k="core-miss"><?php echo esc_html( $gwqsh_data['summary']['breakdown']['core-miss'] ?? 0 ); ?></b></li>
	</ul>
	</article>

	<article class="card stat">
	<div class="stat-head">
		<span class="tile t-purple round"><svg class="i solid" viewBox="0 0 24 24" fill="none"
			stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<path d="M15.39 4.39a1 1 0 0 0 1.68-.474 2.5 2.5 0 1 1 3.014 3.015 1 1 0 0 0-.474 1.68l1.683 1.682a2.414 2.414 0 0 1 0 3.414L19.61 15.39a1 1 0 0 1-1.68-.474 2.5 2.5 0 1 0-3.014 3.015 1 1 0 0 1 .474 1.68l-1.683 1.682a2.414 2.414 0 0 1-3.414 0L8.61 19.61a1 1 0 0 0-1.68.474 2.5 2.5 0 1 1-3.014-3.015 1 1 0 0 0 .474-1.68l-1.683-1.682a2.414 2.414 0 0 1 0-3.414L4.39 8.61a1 1 0 0 1 1.68.474 2.5 2.5 0 1 0 3.014-3.015 1 1 0 0 1-.474-1.68l1.683-1.682a2.414 2.414 0 0 1 3.414 0z"
			fill="currentColor" />
		</svg></span>
		<span class="stat-title">Plugins</span>
		<span class="pill p-green">Active</span>
	</div>

	<div class="stat-main">
		<div class="stat-body">
		<div class="stat-num" data-count="0">0</div>
		<div class="stat-lbl">Plugin PHP files checked</div>
		</div>
		<svg class="mini-bars c-purple" viewBox="0 0 64 32" aria-hidden="true">
		<rect x="2"  y="22" width="4" height="10" rx="1.2"/>
		<rect x="10" y="18" width="4" height="14" rx="1.2"/>
		<rect x="18" y="14" width="4" height="18" rx="1.2"/>
		<rect x="26" y="20" width="4" height="12" rx="1.2"/>
		<rect x="34" y="10" width="4" height="22" rx="1.2"/>
		<rect x="42" y="16" width="4" height="16" rx="1.2"/>
		<rect x="50" y="12" width="4" height="20" rx="1.2"/>
		</svg>
	</div>

	<ul class="legend-mini">
		<li><span class="lbl"><i class="dot c-green"></i>Clean</span><b data-k="pl-ok"><?php echo esc_html( $gwqsh_data['summary']['breakdown']['pl-ok'] ?? 0 ); ?></b></li>
		<li><span class="lbl"><i class="dot c-amber"></i>Modified</span><b data-k="pl-mod"><?php echo esc_html( $gwqsh_data['summary']['breakdown']['pl-mod'] ?? 0 ); ?></b></li>
		<li><span class="lbl"><i class="dot c-red"></i>Suspicious</span><b data-k="pl-sus"><?php echo esc_html( $gwqsh_data['summary']['breakdown']['pl-sus'] ?? 0 ); ?></b></li>
	</ul>
	</article>

	<article class="card stat">
	<div class="stat-head">
		<span class="tile t-blue round"><svg class="i" viewBox="0 0 24 24" fill="none"
			stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<circle cx="13.5" cy="6.5" r=".5" fill="currentColor" />
			<circle cx="17.5" cy="10.5" r=".5" fill="currentColor" />
			<circle cx="8.5" cy="7.5" r=".5" fill="currentColor" />
			<circle cx="6.5" cy="12.5" r=".5" fill="currentColor" />
			<path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z" />
		</svg></span>
		<span class="stat-title">Themes</span>
		<span class="pill p-green">Active</span>
	</div>

	<div class="stat-main">
		<div class="stat-body">
		<div class="stat-num" data-count="0">0</div>
		<div class="stat-lbl">Theme PHP files checked</div>
		</div>
		<svg class="mini-bars c-blue" viewBox="0 0 64 32" aria-hidden="true">
		<rect x="2"  y="10" width="4" height="22" rx="1.2"/>
		<rect x="10" y="14" width="4" height="18" rx="1.2"/>
		<rect x="18" y="8"  width="4" height="24" rx="1.2"/>
		<rect x="26" y="16" width="4" height="16" rx="1.2"/>
		<rect x="34" y="12" width="4" height="20" rx="1.2"/>
		<rect x="42" y="18" width="4" height="14" rx="1.2"/>
		<rect x="50" y="14" width="4" height="18" rx="1.2"/>
		</svg>
	</div>

	<ul class="legend-mini">
		<li><span class="lbl"><i class="dot c-green"></i>Clean</span><b data-k="th-ok"><?php echo esc_html( $gwqsh_data['summary']['breakdown']['th-ok'] ?? 0 ); ?></b></li>
		<li><span class="lbl"><i class="dot c-amber"></i>Modified</span><b data-k="th-mod"><?php echo esc_html( $gwqsh_data['summary']['breakdown']['th-mod'] ?? 0 ); ?></b></li>
		<li><span class="lbl"><i class="dot c-red"></i>Suspicious</span><b data-k="th-sus"><?php echo esc_html( $gwqsh_data['summary']['breakdown']['th-sus'] ?? 0 ); ?></b></li>
	</ul>
	</article>

	<article class="card stat">
	<div class="stat-head">
		<span class="tile t-amber round"><svg class="i solid" viewBox="0 0 24 24" fill="none"
			stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"
			fill="currentColor" />
			<path d="m9 12 2 2 4-4" stroke="#fff" />
		</svg></span>
		<span class="stat-title">Uploads Scan</span>
		<span class="pill p-amber"><?php echo esc_html( 'Alerts (' . $gwqsh_data['summary']['breakdown']['up-php'] . ')' ); ?></span>
	</div>

	<div class="stat-main">
		<div class="stat-body">
		<div class="stat-num" data-count="0">0</div>
		<div class="stat-lbl">Upload files checked</div>
		</div>
		<svg class="mini-bars c-amber" viewBox="0 0 64 32" aria-hidden="true">
		<rect x="2"  y="8"  width="4" height="24" rx="1.2"/>
		<rect x="10" y="12" width="4" height="20" rx="1.2"/>
		<rect x="18" y="6"  width="4" height="26" rx="1.2"/>
		<rect x="26" y="14" width="4" height="18" rx="1.2"/>
		<rect x="34" y="10" width="4" height="22" rx="1.2"/>
		<rect x="42" y="16" width="4" height="16" rx="1.2"/>
		<rect x="50" y="12" width="4" height="20" rx="1.2"/>
		</svg>
	</div>

	<ul class="legend-mini">
		<li><span class="lbl"><i class="dot c-green"></i>Clean</span><b data-k="up-ok"><?php echo esc_html( $gwqsh_data['summary']['breakdown']['up-ok'] ?? 0 ); ?></b></li>
		<li><span class="lbl"><i class="dot c-red"></i>PHP files</span><b data-k="up-php"><?php echo esc_html( $gwqsh_data['summary']['breakdown']['up-php'] ?? 0 ); ?></b></li>
		<li><span class="lbl"><i class="dot c-amber"></i>Suspicious</span><b data-k="up-sus"><?php echo esc_html( $gwqsh_data['summary']['breakdown']['up-sus'] ?? 0 ); ?></b></li>
	</ul>
	</article>
</div>

		<div class="grid fi-row1">
		<section class="card" aria-labelledby="h-sum">
			<div class="card-head"><span class="tile t-green"><svg class="i" viewBox="0 0 24 24" fill="none"
				stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
				aria-hidden="true">
				<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z" />
				<path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12" />
				</svg></span>
			<div class="ttl">
				<h2 id="h-sum">Scan Summary</h2>
				<p>Results from the latest scan</p>
			</div>
			</div>
			<div class="sum">
			<div class="ring-box">
				<div class="ring" id="cleanRing" style="--size:170px;--w:18px"></div>
				<div class="ring-center"><b><span id="cleanPct">0</span>%</b><small>Files clean</small></div>
			</div>
			<div class="sum-txt">
				<h3 id="sumHead">Your site looks good!</h3>
				<p class="muted" id="sumP">Loading saved scan results…</p>
				<ul class="sum-list">
				<li><svg class="i c-green" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
					stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<circle cx="12" cy="12" r="10" fill="currentColor" />
					<path d="m9 12 2 2 4-4" stroke="#fff" />
					</svg><b id="nClean"><?php echo esc_html( $gwqsh_data['summary']['clean'] ); ?></b><span>Clean files</span></li>
				<li><svg class="i c-amber" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
					stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<circle cx="12" cy="12" r="10" fill="currentColor" />
					<line x1="12" x2="12" y1="8" y2="12" stroke="#fff" />
					<line x1="12" x2="12.01" y1="16" y2="16" stroke="#fff" />
					</svg><b id="nMod"><?php echo esc_html( $gwqsh_data['summary']['modified'] ); ?></b><span>Modified files</span></li>
				<li><svg class="i c-red" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
					stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<circle cx="12" cy="12" r="10" fill="currentColor" />
					<path d="m15 9-6 6" stroke="#fff" />
					<path d="m9 9 6 6" stroke="#fff" />
					</svg><b id="nMiss"><?php echo esc_html( $gwqsh_data['summary']['missing'] ); ?></b><span>Missing files</span></li>
				<li><svg class="i c-red" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
					stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<circle cx="12" cy="12" r="10" fill="currentColor" />
					<line x1="12" x2="12" y1="8" y2="12" stroke="#fff" />
					<line x1="12" x2="12.01" y1="16" y2="16" stroke="#fff" />
					</svg><b id="nSus"><?php echo esc_html( $gwqsh_data['summary']['suspicious'] ); ?></b><span>Suspicious files</span></li>
				</ul>
			</div>
			</div>
		</section>

		<section class="card" aria-labelledby="h-act">
			<div class="card-head"><span class="tile t-purple"><svg class="i"
				viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
				stroke-linejoin="round" aria-hidden="true">
				<path d="M3 3v16a2 2 0 0 0 2 2h16" />
				<path d="M18 17V9" />
				<path d="M13 17V5" />
				<path d="M8 17v-3" />
				</svg></span>
			<div class="ttl">
				<h2 id="h-act">Scan Activity</h2>
				<p>File scan results over time</p>
			</div>
			<select class="field field-sm" id="actRange" style="width:auto" aria-label="Date range">
				<option value="7">Last 7 days</option>
				<option value="14">Last 14 days</option>
			</select>
			</div>
			<div class="chart-legend left"><span><i class="dot c-green"></i>Clean files</span><span><i
				class="dot c-amber"></i>Modified files</span><span><i class="dot c-red"></i>Suspicious files</span><span
				class="muted" style="margin-left:auto;font-size:11.5px">Issues on right axis</span></div>
			<div id="scanChart"></div>
		</section>

		<section class="card" id="lastscan" aria-labelledby="h-last">
			<div class="card-head"><span class="tile t-green"><svg class="i"
				viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
				stroke-linejoin="round" aria-hidden="true">
				<path d="M8 2v4" />
				<path d="M16 2v4" />
				<rect width="18" height="18" x="3" y="4" rx="2" />
				<path d="M3 10h18" />
				<path d="M8 14h.01" />
				<path d="M12 14h.01" />
				<path d="M16 14h.01" />
				<path d="M8 18h.01" />
				<path d="M12 18h.01" />
				<path d="M16 18h.01" />
				</svg></span>
			<div class="ttl">
				<h2 id="h-last">Last Scan</h2>
				<p id="lastDate"><?php echo esc_html( GWQSH_File_Integrity::get_summary()['last_date'] ? GWQSH_File_Integrity::get_summary()['last_date'] : 'Never' ); ?></p>
			</div>
			<span class="pill p-green" id="scanPill">Completed</span>
			</div>
			<dl class="kv divide">
<div><dt>Duration</dt><dd id="kvDur"><?php echo esc_html( $gwqsh_data['summary']['duration'] ); ?> seconds</dd></div>
<div><dt>Files checked</dt><dd id="kvFiles"><?php echo esc_html( $gwqsh_data['summary']['total'] ); ?></dd></div>
<div><dt>Scan mode</dt><dd>Manual</dd></div>
</dl>
			<div class="scan-prog hide" id="scanProg" aria-live="polite">
			<div class="bar green"><span id="scanBar"></span></div><small id="scanTxt">Starting scan…</small>
			</div>
			<button class="btn btn-primary btn-block" id="runScan" type="button">Run New Scan <svg class="i"
				viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
				stroke-linejoin="round" aria-hidden="true">
				<path d="M5 12h14" />
				<path d="m12 5 7 7-7 7" />
			</svg></button>
		</section>
		</div>

		<div class="grid fi-row2">
		<section class="card" id="results" aria-labelledby="h-res">
			<div class="card-head"><span class="tile t-blue"><svg class="i" viewBox="0 0 24 24"
				fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
				aria-hidden="true">
				<path d="M3 7V5a2 2 0 0 1 2-2h2" />
				<path d="M17 3h2a2 2 0 0 1 2 2v2" />
				<path d="M21 17v2a2 2 0 0 1-2 2h-2" />
				<path d="M7 21H5a2 2 0 0 1-2-2v-2" />
				</svg></span>
			<div class="ttl">
				<h2 id="h-res">Scan Results</h2>
				<p>Files that need attention</p>
			</div>
			<div class="toolbar"><label class="search"><span class="sr">Search files</span><svg class="i"
					viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
					stroke-linejoin="round" aria-hidden="true">
					<circle cx="11" cy="11" r="8" />
					<path d="m21 21-4.3-4.3" />
				</svg><input class="field field-sm" id="fSearch" placeholder="Search files..." type="search"></label>
				<select class="field field-sm" id="fLoc" aria-label="Filter by location">
				<option value="">All Locations</option>
				<option>Core</option>
				<option>Plugin</option>
				<option>Theme</option>
				<option>Uploads</option>
				</select>
				<select class="field field-sm" id="fStat" aria-label="Filter by status">
				<option value="">All Status</option>
				<option>Modified</option>
				<option>Missing</option>
				<option>Suspicious</option>
				</select>
			</div>
			</div>
			<div class="seg slim" role="tablist" id="fTabs" style="margin-bottom:12px">
			<button class="seg-btn" role="tab" aria-selected="true" data-t=""><span>All (<span
					data-n="all">0</span>)</span></button>
			<button class="seg-btn" role="tab" aria-selected="false" data-t="Modified"><i
				class="dot c-green"></i><span>Modified (<span data-n="Modified">0</span>)</span></button>
			<button class="seg-btn" role="tab" aria-selected="false" data-t="Missing"><i
				class="dot c-red"></i><span>Missing (<span data-n="Missing">0</span>)</span></button>
			<button class="seg-btn" role="tab" aria-selected="false" data-t="Suspicious"><i
				class="dot c-red"></i><span>Suspicious (<span data-n="Suspicious">0</span>)</span></button>
			</div>
			<div class="bulk hide" id="bulk"><span id="bulkN">0 selected</span><button class="btn btn-sm btn-neutral"
				id="bulkIgnore" type="button">Mark as reviewed</button><button class="btn btn-sm btn-ghost" id="bulkClear"
				type="button">Clear selection</button></div>
			<div class="table-wrap">
			<table class="table">
				<thead>
				<tr>
					<th style="width:36px"><input type="checkbox" class="checkbox" id="checkAll"
						aria-label="Select all files"></th>
					<th>File Path</th>
					<th>Type</th>
					<th>Status</th>
					<th>Details</th>
					<th><button class="th-sort" id="sortDate" type="button">Modified Date <svg class="i"
						viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
						stroke-linejoin="round" aria-hidden="true">
						<path d="m6 9 6 6 6-6" />
						</svg></button></th>
					<th style="text-align:center">Actions</th>
					<th><span class="sr">More</span></th>
				</tr>
				</thead>
				<tbody id="fBody"></tbody>
			</table>
			</div>
			<div class="res-foot"><span class="muted" id="fInfo"></span>
			<div class="pager" id="fPager"></div>
			</div>
		</section>

		<div class="stack">
			<section class="card" aria-labelledby="h-tools">
			<div class="card-head"><span class="tile t-blue"><svg class="i"
					viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
					stroke-linejoin="round" aria-hidden="true">
					<path
					d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
				</svg></span>
				<div class="ttl">
				<h2 id="h-tools">Actions &amp; Tools</h2>
				</div>
			</div>
			<div class="divide">
				<button class="lrow" id="toolReplace" type="button"><span class="tile sm t-amber"><svg class="i"
					viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
					stroke-linejoin="round" aria-hidden="true">
					<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
					<path d="M21 3v5h-5" />
					<path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
					<path d="M8 16H3v5" />
					</svg></span><span class="txt"><b>Replace Modified Core Files</b><small>Restore original WordPress
					core files.</small></span><svg class="i chev" viewBox="0 0 24 24" fill="none" stroke="currentColor"
					stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path d="m9 18 6-6-6-6" />
				</svg></button>
				<button class="lrow" id="toolQuar" type="button"><span class="tile sm t-red"><svg class="i"
					viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
					stroke-linejoin="round" aria-hidden="true">
					<rect width="8" height="5" x="14" y="17" rx="1" />
					<path
						d="M10 20H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H20a2 2 0 0 1 2 2v2.5" />
					<path d="M20 17v-2a2 2 0 1 0-4 0v2" />
					</svg></span><span class="txt"><b>Quarantine Suspicious Files</b><small>Move suspicious files to a
					safe location.</small></span><svg class="i chev" viewBox="0 0 24 24" fill="none"
					stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
					<path d="m9 18 6-6-6-6" />
				</svg></button>
				<a class="lrow" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-activity-log&type=system' ) ); ?>"><span class="tile sm t-blue"><svg class="i"
					viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
					stroke-linejoin="round" aria-hidden="true">
					<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
					<path d="M14 2v4a2 2 0 0 0 2 2h4" />
					<path d="M10 9H8" />
					<path d="M16 13H8" />
					<path d="M16 17H8" />
					</svg></span><span class="txt"><b>View Scan Logs</b><small>See detailed scan activity
					logs.</small></span><svg class="i chev" viewBox="0 0 24 24" fill="none" stroke="currentColor"
					stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path d="m9 18 6-6-6-6" />
				</svg></a>
			</div>
			</section>
			<section class="card tips-card" aria-labelledby="h-tips">
			<div class="card-head"><span class="tile t-green round"><svg
					class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
					stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path
					d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5" />
					<path d="M9 18h6" />
					<path d="M10 22h4" />
				</svg></span>
				<div class="ttl">
				<h2 id="h-tips">Security Tips</h2>
				</div><button class="link-btn" id="tipsMore" type="button" aria-expanded="false">View All <svg class="i"
					viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
					stroke-linejoin="round" aria-hidden="true">
					<path d="M5 12h14" />
					<path d="m12 5 7 7-7 7" />
				</svg></button>
			</div>
			<div class="divide tips" id="tipList">
				<div class="lrow"><span class="tile xs t-amber round"><svg class="i" viewBox="0 0 24 24" fill="none"
					stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
					<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
					<path d="M14 2v4a2 2 0 0 0 2 2h4" />
					<path d="M10 9H8" />
					<path d="M16 13H8" />
					<path d="M16 17H8" />
					</svg></span>
				<div class="txt"><b>Keep core files unmodified</b><small>Modified core files can be a sign of
					compromise.</small></div>
				</div>
				<div class="lrow"><span class="tile xs t-green round"><svg class="i" viewBox="0 0 24 24" fill="none"
					stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
					<path
						d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
					<path d="m9 12 2 2 4-4" />
					</svg></span>
				<div class="txt"><b>Regularly scan your site</b><small>Schedule automatic scans to stay
					protected.</small></div>
				</div>
				<div class="lrow"><span class="tile xs t-amber round"><svg class="i" viewBox="0 0 24 24" fill="none"
					stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
					<path
						d="M15.39 4.39a1 1 0 0 0 1.68-.474 2.5 2.5 0 1 1 3.014 3.015 1 1 0 0 0-.474 1.68l1.683 1.682a2.414 2.414 0 0 1 0 3.414L19.61 15.39a1 1 0 0 1-1.68-.474 2.5 2.5 0 1 0-3.014 3.015 1 1 0 0 1 .474 1.68l-1.683 1.682a2.414 2.414 0 0 1-3.414 0L8.61 19.61a1 1 0 0 0-1.68.474 2.5 2.5 0 1 1-3.014-3.015 1 1 0 0 0 .474-1.68l-1.683-1.682a2.414 2.414 0 0 1 0-3.414L4.39 8.61a1 1 0 0 1 1.68.474 2.5 2.5 0 1 0 3.014-3.015 1 1 0 0 1-.474-1.68l1.683-1.682a2.414 2.414 0 0 1 3.414 0z" />
					</svg></span>
				<div class="txt"><b>Remove unused plugins and themes</b><small>Inactive code can be a security
					risk.</small></div>
				</div>
				<div class="lrow"><span class="tile xs t-red round"><svg class="i" viewBox="0 0 24 24" fill="none"
					stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
					<rect width="8" height="5" x="14" y="17" rx="1" />
					<path
						d="M10 20H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H20a2 2 0 0 1 2 2v2.5" />
					<path d="M20 17v-2a2 2 0 1 0-4 0v2" />
					</svg></span>
				<div class="txt"><b>Check uploads folder</b><small>PHP files in uploads are often used for
					attacks.</small></div>
				</div>
				<div class="lrow hide extra"><span class="tile xs t-blue round"><svg class="i" viewBox="0 0 24 24"
					fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
					<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
					<polyline points="7 10 12 15 17 10" />
					<line x1="12" x2="12" y1="15" y2="3" />
					</svg></span>
				<div class="txt"><b>Download from trusted sources</b><small>Nulled plugins and themes often ship with
					backdoors.</small></div>
				</div>
				<div class="lrow hide extra"><span class="tile xs t-purple round"><svg class="i" viewBox="0 0 24 24"
					fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
					<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
					<path d="M3 3v5h5" />
					<path d="M12 7v5l4 2" />
					</svg></span>
				<div class="txt"><b>Keep a recent backup</b><small>Restore quickly if a scan finds something
					serious.</small></div>
				</div>
			</div>
			</section>
		</div>
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
