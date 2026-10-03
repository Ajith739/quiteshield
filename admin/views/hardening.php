<?php
/**
 * Hardening administration view.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;
$gwqsh_data      = GWQSH_Admin::get_page_initial_data( 'hardening' );
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
		<a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-hardening' ) ); ?>" aria-current="page"><svg class="i">
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
			<p class="eyebrow">WordPress Hardening</p>
			<h1 class="h1" id="heroTitle">WordPress <span class="hl">Hardening</span></h1>
			<p class="lede">Strengthen your WordPress site security with recommended hardening measures.</p>
		</div>
		</div>
	</section>

	<div class="wrap page">
		<div class="stats hd-stats">
		<article class="card stat">
			<div class="stat-head">
			<span class="tile t-green round"><svg class="i"><use href="#i-shield-check" /></svg></span>
			<span class="stat-title">Hardening Score</span>

			</div>
			<div class="stat-body">
			<div class="stat-num"><span id="scoreN"><?php echo esc_html( GWQSH_Hardening::get_risk()['score'] ); ?></span> <small>/ 100</small></div>
			<div class="bar-row"><div class="bar"><span id="scoreBar" data-w="<?php echo esc_attr( GWQSH_Hardening::get_risk()['score'] ); ?>"></span></div></div>
			<div class="stat-meta" id="scoreMsg">Good! Enable more hardening options.</div>
			</div>
			<div class="stat-foot"><a href="#checklist">Configure Options</a><a class="go" href="#checklist" aria-label="Open Hardening Checklist"><svg class="i"><use href="#i-arrow" /></svg></a></div>
		</article>
		<article class="card stat">
			<div class="stat-head">
			<span class="tile t-teal round"><svg class="i"><use href="#i-gear" /></svg></span>
			<span class="stat-title">Enabled Settings</span>
			<span class="pill p-green"><?php echo esc_html( $gwqsh_data['risk']['passed'] . ' / 6' ); ?></span>
			</div>
			<div class="stat-body">
			<div class="stat-num"><span id="enN"><?php echo esc_html( $gwqsh_data['risk']['passed'] ); ?></span> / 6</div>
			<div class="stat-meta" id="enMsg">Most recommended settings are active.</div>
			</div>
			<div class="stat-foot"><a href="#checklist">View Checklist</a><a class="go" href="#checklist" aria-label="Open Checklist"><svg class="i"><use href="#i-arrow" /></svg></a></div>
		</article>
		<article class="card stat">
			<div class="stat-head">
			<span class="tile t-amber round"><svg class="i"><use href="#i-bell" /></svg></span>
			<span class="stat-title">Pending Actions</span>
			<span class="pill p-amber" id="pendPill"><?php echo esc_html( $gwqsh_data['risk']['failed'] . ' pending' ); ?></span>
			</div>
			<div class="stat-body">
			<div class="stat-num" id="pendN"><?php echo esc_html( $gwqsh_data['risk']['failed'] ); ?></div>
			<div class="stat-meta" id="pendMsg">One important setting left.</div>
			</div>
			<div class="stat-foot"><a href="#checklist">Review Recommendations</a><a class="go" href="#checklist" aria-label="Open Recommendations"><svg class="i"><use href="#i-arrow" /></svg></a></div>
		</article>
		<article class="card stat">
			<div class="stat-head">
			<span class="tile t-blue round"><svg class="i"><use href="#i-lock" /></svg></span>
			<span class="stat-title">Risk Level</span>
			<span class="pill <?php echo 'Low' === $gwqsh_data['risk']['level'] ? 'p-green' : 'p-amber'; ?>" id="riskPill"><?php echo esc_html( $gwqsh_data['risk']['level'] ); ?></span>
			</div>
			<div class="stat-body">
			<div class="stat-num" id="riskN"><?php echo esc_html( $gwqsh_data['risk']['level'] ); ?></div>
			<div class="stat-meta" id="riskMsg">Your site is well protected.</div>
			</div>
			<div class="stat-foot"><a href="#checklist">View Hardening Details</a><a class="go" href="#checklist" aria-label="Open Hardening Details"><svg class="i"><use href="#i-arrow" /></svg></a></div>
		</article>
		</div>

		<div class="hd-grid">
		<div class="stack hd-main">
			<section class="card" id="checklist" aria-labelledby="h-check">
			<div class="card-head" style="align-items:center"><span class="tile t-purple"><svg class="i"
					viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
					stroke-linejoin="round" aria-hidden="true">
					<path
					d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
					<circle cx="12" cy="12" r="3" />
				</svg></span>
				<div class="ttl">
				<h2 id="h-check">Hardening Checklist</h2>
				<p>Enable security features to protect your site. Each option can be reverted anytime.</p>
				</div>
				<button class="btn btn-outline btn-sm" id="resetDef" type="button"><svg class="i" viewBox="0 0 24 24"
					fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
					<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
					<path d="M3 3v5h5" />
				</svg>Reset to Defaults</button>
			</div>
			<div class="table-wrap">
				<table class="table check-table">
				<thead>
					<tr>
					<th>Setting</th>
					<th>Description</th>
					<th>Status</th>
					<th style="text-align:center">Action</th>
					</tr>
				</thead>
				<tbody id="checkBody">
					<tr data-k="xmlrpc">
					<td><span class="set-cell"><span class="tile sm t-amber round"><svg class="i" viewBox="0 0 24 24"
							fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
							stroke-linejoin="round" aria-hidden="true">
							<circle cx="12" cy="12" r="10" />
							<path d="m15 9-6 6" />
							<path d="m9 9 6 6" />
							</svg></span><b id="l-xmlrpc">Disable XML-RPC</b></span></td>
					<td class="desc">Prevents brute force attacks via XML-RPC.</td>
					<td><span class="pill p-status p-green" data-pill>Enabled</span></td>
					<td style="text-align:center"><button class="switch" role="switch" aria-checked="true"
						aria-labelledby="l-xmlrpc"></button></td>
					</tr>
					<tr data-k="editor">
					<td><span class="set-cell"><span class="tile sm t-red round"><svg class="i" viewBox="0 0 24 24"
							fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
							stroke-linejoin="round" aria-hidden="true">
							<path d="M10 12.5 8 15l2 2.5" />
							<path d="m14 12.5 2 2.5-2 2.5" />
							<path d="M14 2v4a2 2 0 0 0 2 2h4" />
							<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z" />
							</svg></span><b id="l-editor">Disable file editor</b></span></td>
					<td class="desc">Block theme and plugin file editing from admin.</td>
					<td><span class="pill p-status p-green" data-pill>Enabled</span></td>
					<td style="text-align:center"><button class="switch" role="switch" aria-checked="true"
						aria-labelledby="l-editor"></button></td>
					</tr>
					<tr data-k="version">
					<td><span class="set-cell"><span class="tile sm t-purple round"><svg class="i" viewBox="0 0 24 24"
							fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
							stroke-linejoin="round" aria-hidden="true">
							<path
								d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49" />
							<path d="M14.084 14.158a3 3 0 0 1-4.242-4.242" />
							<path
								d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143" />
							<path d="m2 2 20 20" />
							</svg></span><b id="l-version">Hide WordPress version</b></span></td>
					<td class="desc">Remove generator meta tags and version query args.</td>
					<td><span class="pill p-status p-green" data-pill>Enabled</span></td>
					<td style="text-align:center"><button class="switch" role="switch" aria-checked="true"
						aria-labelledby="l-version"></button></td>
					</tr>
					<tr data-k="enum">
					<td><span class="set-cell"><span class="tile sm t-blue round"><svg class="i" viewBox="0 0 24 24"
							fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
							stroke-linejoin="round" aria-hidden="true">
							<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
							<circle cx="9" cy="7" r="4" />
							<path d="M22 21v-2a4 4 0 0 0-3-3.87" />
							<path d="M16 3.13a4 4 0 0 1 0 7.75" />
							</svg></span><b id="l-enum">Block user enumeration</b></span></td>
					<td class="desc">Prevent username discovery via ?author=N and REST API.</td>
					<td><span class="pill p-status p-green" data-pill>Enabled</span></td>
					<td style="text-align:center"><button class="switch" role="switch" aria-checked="true"
						aria-labelledby="l-enum"></button></td>
					</tr>
					<tr data-k="headers">
					<td><span class="set-cell"><span class="tile sm t-amber round"><svg class="i" viewBox="0 0 24 24"
							fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
							stroke-linejoin="round" aria-hidden="true">
							<path
								d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
							</svg></span><b id="l-headers">Security headers</b> <button class="info-btn" type="button"
							data-tip="headers" aria-label="What are security headers?"><svg class="i" viewBox="0 0 24 24"
							fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
							stroke-linejoin="round" aria-hidden="true">
							<circle cx="12" cy="12" r="10" />
							<path d="M12 16v-4" />
							<path d="M12 8h.01" />
							</svg></button></span></td>
					<td class="desc">Add security headers (X-Frame, CSP, etc.).</td>
					<td><span class="pill p-status p-amber" data-pill>Not Enabled</span></td>
					<td style="text-align:center"><button class="switch" role="switch" aria-checked="false"
						aria-labelledby="l-headers"></button></td>
					</tr>
					<tr data-k="uploads">
					<td><span class="set-cell"><span class="tile sm t-red round"><svg class="i" viewBox="0 0 24 24"
							fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
							stroke-linejoin="round" aria-hidden="true">
							<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
							<path d="M14 2v4a2 2 0 0 0 2 2h4" />
							<path d="m14.5 12.5-5 5" />
							<path d="m9.5 12.5 5 5" />
							</svg></span><b id="l-uploads">Block PHP in uploads</b></span></td>
					<td class="desc">Prevent execution of PHP files in uploads folder.</td>
					<td><span class="pill p-status p-green" data-pill>Enabled</span></td>
					<td style="text-align:center"><button class="switch" role="switch" aria-checked="true"
						aria-labelledby="l-uploads"></button></td>
					</tr>
				</tbody>
				</table>
			</div>
			</section>
			<div class="grid hd-sub">
			<section class="card" aria-labelledby="h-server">
				<div class="card-head" style="align-items:center"><span class="tile t-purple"><svg class="i"
					viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
					stroke-linejoin="round" aria-hidden="true">
					<rect width="20" height="8" x="2" y="2" rx="2" ry="2" />
					<rect width="20" height="8" x="2" y="14" rx="2" ry="2" />
					<line x1="6" x2="6.01" y1="6" y2="6" />
					<line x1="6" x2="6.01" y1="18" y2="18" />
					</svg></span>
				<div class="ttl">
					<h2 id="h-server">Server Configuration</h2>
					<p>Additional server-level hardening recommendations.</p>
				</div>
				</div>
				<div class="divide">
				<div class="lrow"><span class="tile sm t-blue"><svg class="i" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
						aria-hidden="true">
						<path d="M10 12.5 8 15l2 2.5" />
						<path d="m14 12.5 2 2.5-2 2.5" />
						<path d="M14 2v4a2 2 0 0 0 2 2h4" />
						<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z" />
					</svg></span>
					<div class="txt"><b>Add .htaccess rules</b><small>Prevent common threats.</small></div><button
					class="btn btn-outline btn-sm" id="viewRules" type="button">View Rules</button>
				</div>
				<div class="lrow"><span class="tile sm t-blue"><svg class="i" viewBox="0 0 24 24" fill="none"
						stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
						aria-hidden="true">
						<path
						d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
						<path d="m9 12 2 2 4-4" />
					</svg></span>
					<div class="txt"><b>Nginx configuration</b><small>Use these rules for better security.</small></div>
					<button class="btn btn-outline btn-sm" id="viewNginx" type="button">View Guide</button>
				</div>
				</div>
			</section>
			<section class="card" aria-labelledby="h-undo">
				<div class="card-head" style="align-items:center"><span class="tile t-blue round"><svg class="i"
					viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
					stroke-linejoin="round" aria-hidden="true">
					<circle cx="12" cy="12" r="10" />
					<polyline points="12 6 12 12 16 14" />
					</svg></span>
				<div class="ttl">
					<h2 id="h-undo">Undo Changes</h2>
					<p>All changes made by QuietShield can be reverted.</p>
				</div>
				</div>
				<div class="panel undo"><span class="tile sm t-navy round"><svg class="i" viewBox="0 0 24 24" fill="none"
					stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
					aria-hidden="true">
					<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
					<path d="M3 3v5h5" />
					<path d="M12 7v5l4 2" />
					</svg></span>
				<div><b>Need to revert all hardening changes?</b>
					<p>This will disable all hardening features and remove any files added by the plugin.</p>
					<button class="btn btn-danger" id="revertAll" type="button">Revert All Changes</button>
				</div>
				</div>
			</section>
			</div>
		</div>

		<div class="stack hd-side">
			<section class="card" aria-labelledby="h-break">
			<div class="card-head" style="align-items:center"><span class="tile t-blue round"><svg class="i"
					viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
					stroke-linejoin="round" aria-hidden="true">
					<path
					d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2" />
				</svg></span>
				<div class="ttl">
				<h2 id="h-break">Security Components</h2>
				</div>
			</div>
			<div class="break">
				<div class="ring-box">
				<div class="ring" id="scoreRing" style="--size:156px;--w:16px;--c1:#3B82F6;--c2:#8B5CF6"></div>
				<div class="ring-center"><b id="ringN"><?php echo esc_html( GWQSH_Hardening::get_risk()['score'] ); ?></b><small>/ 100</small></div>
				</div>
				<ul class="break-list">
				<li><i class="dot c-green"></i><span>Login Protection</span><b><?php echo esc_html( $gwqsh_data['score_components']['login'] ); ?></b></li>
				<li><i class="dot c-purple"></i><span>Two-Factor Auth</span><b><?php echo esc_html( $gwqsh_data['score_components']['tfa'] ); ?></b></li>
				<li><i class="dot c-amber"></i><span>File Integrity</span><b><?php echo esc_html( $gwqsh_data['score_components']['file'] ); ?></b></li>
				<li><i class="dot c-red"></i><span>Hardening</span><b id="bHard"><?php echo esc_html( $gwqsh_data['score_components']['hardening'] ); ?></b></li>
				<li><i class="dot" style="color:#6D28D9"></i><span>Activity Log</span><b><?php echo esc_html( $gwqsh_data['score_components']['activity'] ); ?></b></li>
				</ul>
			</div>
			</section>
			<section class="card rec-card" aria-labelledby="h-rec">
			<div class="card-head" style="align-items:center"><span class="tile t-amber round"><svg class="i"
					viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
					stroke-linejoin="round" aria-hidden="true">
					<path
					d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5" />
					<path d="M9 18h6" />
					<path d="M10 22h4" />
				</svg></span>
				<div class="ttl">
				<h2 id="h-rec">Recommended Actions</h2>
				<p>Complete these to improve your security score.</p>
				</div><span class="pill p-amber" id="recPill">1 pending</span>
			</div>
			<div class="rec-list divide" id="recList"></div>
			<button class="link-btn" id="recAll" type="button" style="margin:14px auto 0;display:flex">View All
				Recommendations <svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
				stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M5 12h14" />
				<path d="m12 5 7 7-7 7" />
				</svg></button>
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
