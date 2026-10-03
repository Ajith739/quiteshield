<?php
/**
 * Activity log administration view.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;
$gwqsh_data      = GWQSH_Admin::get_page_initial_data( 'activity-log' );
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
		<a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-activity-log' ) ); ?>" aria-current="page"><svg class="i">
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
			<p class="eyebrow">Activity Log</p>
			<h1 class="h1" id="heroTitle">Activity <span class="hl">Log</span></h1>
			<p class="lede">Monitor all security events and user activity on your WordPress site.</p>
		</div>
		</div>
	</section>

	<div class="wrap page">
		<div class="stats al-stats">
		<article class="card stat">
			<div class="stat-head">
			<span class="tile t-blue round"><svg class="i"><use href="#i-list" /></svg></span>
			<span class="stat-title">Total Events</span>

			</div>
			<div class="stat-body">
			<div class="stat-num" data-count="<?php echo esc_html( array_sum( $gwqsh_data['breakdown'] ) ); ?>" data-stat="all"><?php echo esc_html( array_sum( $gwqsh_data['breakdown'] ) ); ?></div>
			<div class="stat-lbl">in the last 7 days</div>
			</div>
			<div class="stat-foot"><a href="#log">View All Logs</a><a class="go" href="#log" aria-label="View Logs"><svg class="i"><use href="#i-arrow" /></svg></a></div>
		</article>

		<article class="card stat">
			<div class="stat-head">
			<span class="tile t-green round"><svg class="i"><use href="#i-shield-check" /></svg></span>
			<span class="stat-title">Security Events</span>

			</div>
			<div class="stat-body">
			<div class="stat-num" data-count="<?php echo esc_html( $gwqsh_data['breakdown']['Security'] ); ?>" data-stat="Security"><?php echo esc_html( $gwqsh_data['breakdown']['Security'] ); ?></div>
			<div class="stat-lbl">Login attempts, blocks, 2FA</div>
			</div>
			<div class="stat-foot"><a href="#log">Filter Security</a><a class="go" href="#log" aria-label="Filter Security"><svg class="i"><use href="#i-arrow" /></svg></a></div>
		</article>

		<article class="card stat">
			<div class="stat-head">
			<span class="tile t-blue round"><svg class="i"><use href="#i-lock" /></svg></span>
			<span class="stat-title">User Events</span>

			</div>
			<div class="stat-body">
			<div class="stat-num" data-count="<?php echo esc_html( $gwqsh_data['breakdown']['User'] ); ?>" data-stat="User"><?php echo esc_html( $gwqsh_data['breakdown']['User'] ); ?></div>
			<div class="stat-lbl">Logins, profile changes, roles</div>
			</div>
			<div class="stat-foot"><a href="#log">Filter Users</a><a class="go" href="#log" aria-label="Filter Users"><svg class="i"><use href="#i-arrow" /></svg></a></div>
		</article>

		<article class="card stat">
			<div class="stat-head">
			<span class="tile t-purple round"><svg class="i"><use href="#i-gear" /></svg></span>
			<span class="stat-title">Plugin & Theme</span>

			</div>
			<div class="stat-body">
			<div class="stat-num" data-count="<?php echo esc_html( $gwqsh_data['breakdown']['Plugin'] ); ?>" data-stat="Plugin"><?php echo esc_html( $gwqsh_data['breakdown']['Plugin'] ); ?></div>
			<div class="stat-lbl">Updates, changes, installs</div>
			</div>
			<div class="stat-foot"><a href="#log">Filter Plugins</a><a class="go" href="#log" aria-label="Filter Plugins"><svg class="i"><use href="#i-arrow" /></svg></a></div>
		</article>

		<article class="card stat">
			<div class="stat-head">
			<span class="tile t-amber round"><svg class="i"><use href="#i-file" /></svg></span>
			<span class="stat-title">System Events</span>

			</div>
			<div class="stat-body">
			<div class="stat-num" data-count="<?php echo esc_html( $gwqsh_data['breakdown']['System'] ); ?>" data-stat="System"><?php echo esc_html( $gwqsh_data['breakdown']['System'] ); ?></div>
			<div class="stat-lbl">Core, settings, scheduled</div>
			</div>
			<div class="stat-foot"><a href="#log">Filter System</a><a class="go" href="#log" aria-label="Filter System"><svg class="i"><use href="#i-arrow" /></svg></a></div>
		</article>
		</div>

		<div class="grid al-grid">
		<section class="card" id="log" aria-labelledby="h-log">
			<div class="card-head"><span class="tile t-blue"><svg class="i" viewBox="0 0 24 24"
				fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
				aria-hidden="true">
				<rect width="18" height="7" x="3" y="3" rx="1" />
				<rect width="7" height="7" x="3" y="14" rx="1" />
				<rect width="7" height="7" x="14" y="14" rx="1" />
				</svg></span>
			<div class="ttl">
				<h2 id="h-log">Activity Log</h2>
				<p>View and search all security-related activities on your site.</p>
			</div>
			<div class="toolbar">
				<div class="menu-wrap"><button class="btn btn-neutral btn-sm" data-menu="range" aria-haspopup="true"
					aria-expanded="false" type="button" style="margin-top: 16px;"><svg class="i" viewBox="0 0 24 24" fill="none"
					stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
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
					</svg><span id="rangeLbl">Last 7 days</span></button>
				<div class="menu" id="menu-range" role="menu" style="left:0;right:auto">
					<button class="menu-item" role="menuitem" data-range="1">Last 24 hours</button><button
					class="menu-item" role="menuitem" data-range="3">Last 3 days</button><button class="menu-item"
					role="menuitem" data-range="7">Last 7 days</button>
				</div>
				</div>
				<select class="field field-sm" id="evSel" aria-label="Filter by event">
				<option value="">All Events</option>
				</select>
				<select class="field field-sm" id="userSel" aria-label="Filter by user">
				<option value="">All Users</option>
				<option>admin</option>
				<option>sarah</option>
				<option>john</option>
				<option>mike</option>
				<option>system</option>
				</select>
				<label class="search"><span class="sr">Search activities</span><svg class="i" viewBox="0 0 24 24"
					fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
					<circle cx="11" cy="11" r="8" />
					<path d="m21 21-4.3-4.3" />
				</svg><input class="field field-sm" id="q" type="search" placeholder="Search activities..."></label>
			</div>
			</div>
			<div class="tab-row">
			<div class="seg" role="tablist" id="tabs">
				<button class="seg-btn" role="tab" aria-selected="true" data-t=""><span>All (<b
					data-c=""><?php echo esc_html( array_sum( $gwqsh_data['breakdown'] ) ); ?></b>)</span></button>
				<button class="seg-btn" role="tab" aria-selected="false" data-t="Security"><svg class="i c-green"
					viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
					stroke-linejoin="round" aria-hidden="true">
					<path
					d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"
					fill="currentColor" />
					<path d="m9 12 2 2 4-4" stroke="#fff" />
				</svg><span>Security (<b data-c="Security"><?php echo esc_html( $gwqsh_data['breakdown']['Security'] ); ?></b>)</span></button>
				<button class="seg-btn" role="tab" aria-selected="false" data-t="User"><svg class="i c-blue"
					viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
					stroke-linejoin="round" aria-hidden="true">
					<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" fill="currentColor" />
					<circle cx="9" cy="7" r="4" fill="currentColor" />
					<path d="M22 21v-2a4 4 0 0 0-3-3.87" />
					<path d="M16 3.13a4 4 0 0 1 0 7.75" />
				</svg><span>Users (<b data-c="User"><?php echo esc_html( $gwqsh_data['breakdown']['User'] ); ?></b>)</span></button>
				<button class="seg-btn" role="tab" aria-selected="false" data-t="Plugin"><svg class="i c-purple"
					viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"
					stroke-linejoin="round" aria-hidden="true">
					<path d="M12 22v-5" />
					<path d="M9 8V2" />
					<path d="M15 8V2" />
					<path d="M18 8v5a4 4 0 0 1-4 4h-4a4 4 0 0 1-4-4V8Z" />
				</svg><span>Plugins (<b data-c="Plugin"><?php echo esc_html( $gwqsh_data['breakdown']['Plugin'] ); ?></b>)</span></button>
				<button class="seg-btn" role="tab" aria-selected="false" data-t="System"><svg class="i c-amber"
					viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
					stroke-linejoin="round" aria-hidden="true">
					<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" fill="currentColor" />
					<path d="M14 2v4a2 2 0 0 0 2 2h4" stroke="#fff" />
					<path d="M10 9H8" stroke="#fff" />
					<path d="M16 13H8" stroke="#fff" />
					<path d="M16 17H8" stroke="#fff" />
				</svg><span>System (<b data-c="System"><?php echo esc_html( $gwqsh_data['breakdown']['System'] ); ?></b>)</span></button>
			</div>
			<button class="btn btn-outline btn-sm" id="export" type="button"><svg class="i" viewBox="0 0 24 24"
				fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
				aria-hidden="true">
				<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
				<polyline points="7 10 12 15 17 10" />
				<line x1="12" x2="12" y1="15" y2="3" />
				</svg>Export Logs</button>
			</div>
			<div class="table-wrap">
			<table class="table log-table">
				<thead>
				<tr>
					<th><button class="th-sort" id="sortT" type="button">Time <svg class="i" viewBox="0 0 24 24"
						fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
						stroke-linejoin="round" aria-hidden="true">
						<path d="m6 9 6 6 6-6" />
						</svg></button></th>
					<th>Event</th>
					<th>Details</th>
					<th>User</th>
					<th>IP Address</th>
					<th>Type</th>
					<th style="text-align:center">Actions</th>
				</tr>
				</thead>
				<tbody id="body"></tbody>
			</table>
			</div>
			<div class="res-foot"><span class="muted" id="info"></span>
			<div class="pager" id="pager"></div>
			</div>
		</section>

		<div class="stack al-side">
			<section class="card" aria-labelledby="h-stats">
			<div class="card-head"><span class="tile t-purple"><svg class="i"
					viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
					stroke-linejoin="round" aria-hidden="true">
					<path d="M3 3v16a2 2 0 0 0 2 2h16" />
					<path d="M18 17V9" />
					<path d="M13 17V5" />
					<path d="M8 17v-3" />
				</svg></span>
				<div class="ttl">
				<h2 id="h-stats">Event Statistics</h2>
				</div>
				<select class="field field-sm" id="statRange" style="width:auto" aria-label="Statistics range">
				<option value="7">Last 7 days</option>
				<option value="3">Last 3 days</option>
				<option value="1">Last 24 hours</option>
				</select>
			</div>
			<div class="ev-stats">
				<div class="donut-box">
				<div id="donut" class="donut"></div>
				<div class="ring-center"><b id="dTotal"><?php echo esc_html( array_sum( $gwqsh_data['breakdown'] ) ); ?></b><small>Total Events</small></div>
				</div>
				<ul class="d-legend" id="dLegend"></ul>
			</div>
			</section>
			<section class="card" aria-labelledby="h-recent">
			<div class="card-head"><span class="tile t-red sm"><svg
					class="i solid" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
					stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path
					d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"
					fill="currentColor" />
					<path d="m9 12 2 2 4-4" stroke="#fff" />
				</svg></span>
				<div class="ttl">
				<h2 id="h-recent">Recent Security Events</h2>
				</div><button class="link-btn" id="viewSec" type="button">View All <svg class="i" viewBox="0 0 24 24"
					fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
					<path d="M5 12h14" />
					<path d="m12 5 7 7-7 7" />
				</svg></button>
			</div>
			<div class="divide" id="recent"></div>
			</section>
			<section class="card help" aria-labelledby="h-help">
			<span class="tile t-blue"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor"
				stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path
					d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5" />
				<path d="M9 18h6" />
				<path d="M10 22h4" />
				</svg></span>
			<div class="txt">
				<h2 id="h-help">Need Help?</h2>
				<p>Learn how to interpret activity logs and respond to security events.</p>
			</div>
			<a class="btn btn-outline" href="https://wordpress.org/support/" target="_blank" rel="noopener">View
				Documentation</a>
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
