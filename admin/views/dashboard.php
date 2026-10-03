<?php
/**
 * Dashboard administration view.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;
$gwqsh_data      = GWQSH_Admin::get_page_initial_data( 'dashboard' );
$gwqsh_current_u = wp_get_current_user();
$gwqsh_u_name    = ( $gwqsh_current_u && $gwqsh_current_u->exists() ) ? ( $gwqsh_current_u->display_name ? $gwqsh_current_u->display_name : $gwqsh_current_u->user_login ) : 'Admin';
$gwqsh_u_initial = strtoupper( substr( $gwqsh_u_name, 0, 1 ) );
$gwqsh_u_role    = ! empty( $gwqsh_current_u->roles[0] ) ? ucfirst( $gwqsh_current_u->roles[0] ) : 'Administrator';
?>
<!-- ============================================================
		Icon sprite (must stay in the document so <use href="#id"> works)
		============================================================ -->
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

	<!-- ============================================================
		Topbar
		============================================================ -->
	<header class="topbar">
		<div class="topbar-in">
			<a class="brand" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield' ) ); ?>" aria-label="QuietShield home">
				<img class="logo-mark" src="<?php echo esc_url( GWQSH_URL . 'assets/img/quietshield.svg' ); ?>" alt="" width="40" height="44">
				<span class="brand-txt"><b><span>QuietShield</span></b><small>A Safer WordPress, A Quieter
						Tomorrow</small></span>
			</a>

			<nav class="nav" id="mainNav" aria-label="Main">
				<a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield' ) ); ?>" aria-current="page"><svg class="i">
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
						<div class="menu-head"><b>Notifications</b><button class="link-btn" id="markRead"
								type="button">Mark all as read</button></div>
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
						<a role="menuitem" class="menu-item" href="https://wordpress.org/support/forums/"
							target="_blank" rel="noopener"><svg class="i">
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
		<!-- ============================================================
			Hero (Three.js birds canvas)
			============================================================ -->
		<section class="hero" aria-labelledby="heroTitle">
			<canvas class="hero-birds" id="heroBirds" aria-hidden="true"></canvas>
			<div class="wrap hero-in">
				<div class="hero-content">
					<p class="eyebrow">Security Dashboard</p>
					<h1 class="h1" id="heroTitle">Your WordPress site is <span class="hl">protected</span></h1>
					<p class="lede">Lightweight. Powerful security. No bloat.</p>
					<div class="hero-actions">
						<button class="btn btn-primary btn-lg" id="runScanBtn" type="button"><span>Run New
								Scan</span><svg class="i">
								<use href="#i-arrow" />
							</svg></button>
						<a class="btn btn-outline btn-lg" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-file-integrity' ) ); ?>"><svg class="i">
								<use href="#i-report" />
							</svg><span>View Reports</span></a>
					</div>
				</div>


			</div>
		</section>

		<div class="wrap page">
			<!-- ============================================================
				Stat cards
				============================================================ -->
			<div class="top-stats">
				<article class="card stat">
					<div class="stat-head">
						<span class="tile t-blue"><svg class="i">
								<use href="#i-lock" />
							</svg></span>
						<span class="stat-title">Login Protection</span><span class="pill p-green">Active</span>
					</div>
					<div class="stat-body">
						<div class="stat-num" data-count="<?php echo esc_html( $gwqsh_data['failed_24h'] ); ?>"><?php echo esc_html( $gwqsh_data['failed_24h'] ); ?></div>
						<div class="stat-lbl">Failed attempts (24h)</div>
					</div>
					<div class="stat-foot">
						<a href="#lockedCard"><span id="statLocked"><?php echo esc_html( count( $gwqsh_data['locked_ips'] ) ); ?></span> Locked IPs</a><span class="sep">|</span>
						<a href="#lockedCard"><span id="statAllow"><?php echo esc_html( count( $gwqsh_data['allow_ips'] ) ); ?></span> Allowlisted IPs</a>
						<a class="go" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-login' ) ); ?>" aria-label="Open Login Protection"><svg class="i">
								<use href="#i-arrow" />
							</svg></a>
					</div>
				</article>

				<article class="card stat">
					<div class="stat-head">
						<span class="tile t-blue"><svg class="i">
								<use href="#i-shield-check" />
							</svg></span>
						<span class="stat-title">Two-Factor Authentication</span><span
							class="pill p-green">Enabled</span>
					</div>
					<div class="stat-body">
						<div class="stat-num"><span data-count="<?php echo esc_html( $gwqsh_data['users_2fa'] ); ?>"><?php echo esc_html( $gwqsh_data['users_2fa'] ); ?></span> / <?php echo esc_html( $gwqsh_data['users_total'] ); ?></div>
						<div class="stat-lbl">Users with 2FA enabled</div>
					</div>
					<div class="stat-foot"><a href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-two-factor' ) ); ?>">Manage 2FA Settings</a><a class="go"
							href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-two-factor' ) ); ?>" aria-label="Manage 2FA"><svg class="i">
								<use href="#i-arrow" />
							</svg></a></div>
				</article>

				<article class="card stat">
					<div class="stat-head">
						<span class="tile t-blue"><svg class="i">
								<use href="#i-file" />
							</svg></span>
						<span class="stat-title">File Integrity Scan</span><span class="pill p-green">Clean</span>
					</div>
					<div class="stat-body">
						<div class="stat-num" data-count="0">0</div>
						<div class="stat-lbl">Modified core files</div>
					</div>
						<div class="stat-foot"><a href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-file-integrity' ) ); ?>">Last scan: <span id="lastScan"><?php echo esc_html( GWQSH_File_Integrity::get_summary()['last_date'] ? GWQSH_File_Integrity::get_summary()['last_date'] : 'Never' ); ?></span></a><a class="go" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-file-integrity' ) ); ?>"
							aria-label="Open File Integrity"><svg class="i">
								<use href="#i-arrow" />
							</svg></a></div>
				</article>

				<article class="card stat">
					<div class="stat-head">
						<span class="tile t-teal"><svg class="i">
								<use href="#i-gear" />
							</svg></span>
						<span class="stat-title">Hardening</span><span class="pill p-green" id="statHardPill">5 /
							6</span>
					</div>
					<div class="stat-body">
						<div class="stat-num"><span id="statHardNum">5</span> / 6</div>
						<div class="stat-lbl">Security toggles enabled</div>
					</div>
					<div class="stat-foot"><a href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-hardening' ) ); ?>">View Hardening Checklist</a><a class="go"
							href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-hardening' ) ); ?>" aria-label="Open Hardening"><svg class="i">
								<use href="#i-arrow" />
							</svg></a></div>
				</article>

				<article class="card stat">
					<div class="stat-head">
						<span class="tile t-blue"><svg class="i">
								<use href="#i-list" />
							</svg></span>
						<span class="stat-title">Activity Log</span><span class="pill p-green">Active</span>
					</div>
					<div class="stat-body">
						<div class="stat-num" id="statEvents" data-count="<?php echo esc_html( $gwqsh_data['total_events'] ); ?>"><?php echo esc_html( $gwqsh_data['total_events'] ); ?></div>
						<div class="stat-lbl">Events in last 30 days</div>
					</div>
					<div class="stat-foot"><a href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-activity-log' ) ); ?>">View All Activity</a><a class="go"
							href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-activity-log' ) ); ?>" aria-label="Open Activity Log"><svg class="i">
								<use href="#i-arrow" />
							</svg></a></div>
				</article>
			</div>

			<!-- ============================================================
				Main grid
				============================================================ -->
			<div class="dash-grid">

				<section class="card a-score" aria-labelledby="h-score">
					<div class="card-head">
						<span class="tile round t-blue"><svg class="i">
								<use href="#i-lock" />
							</svg></span>
						<div class="ttl">
							<h2 id="h-score">Security Score</h2>
						</div>
					</div>
					<div class="score-box">
						<div class="gauge-wrap">
							<svg class="gauge-svg" viewBox="0 0 150 150" aria-hidden="true">
								<defs>
									<linearGradient id="scoreGrad" x1="0%" y1="0%" x2="100%" y2="100%">
										<stop offset="0%" stop-color="#2DD4BF" />
										<stop offset="55%" stop-color="#3B82F6" />
										<stop offset="100%" stop-color="#2F5BEA" />
									</linearGradient>
								</defs>
								<circle class="gauge-track" cx="75" cy="75" r="62" />
								<circle class="gauge-bar" id="scoreBar" cx="75" cy="75" r="62"
									stroke="url(#scoreGrad)" />
							</svg>
							<span class="gauge-dot" style="background:#22C55E;left:4px;bottom:24px"></span>
							<span class="gauge-dot" style="background:#2F5BEA;right:12px;bottom:8px"></span>
							<div class="gauge-inner" role="img" aria-labelledby="scoreVal scoreStatus">
								<div class="gauge-val" id="scoreVal"><?php echo esc_html( $gwqsh_data['security_score'] ); ?></div>
								<div class="gauge-denom">/ 100</div>
								<div class="gauge-status" id="scoreStatus">Good</div>
							</div>
						</div>
						<div class="score-details">
							<h3 id="scoreHeadline">Your site is well protected</h3>
							<p id="scoreHint">Keep going! Enable more hardening options to further improve your
								security.</p>
							<ul class="score-list">
								<li class="score-item"><span class="dot" style="background:#2F5BEA"></span><span>Login
										Protection</span><b>90</b></li>
								<li class="score-item"><span class="dot"
										style="background:#3B82F6"></span><span>Two-Factor
										Authentication</span><b>80</b></li>
								<li class="score-item"><span class="dot" style="background:#14B8A6"></span><span>File
										Integrity</span><b>100</b></li>
								<li class="score-item"><span class="dot"
										style="background:#F59E0B"></span><span>Hardening</span><b id="scoreHard">67</b>
								</li>
								<li class="score-item"><span class="dot"
										style="background:#2F5BEA"></span><span>Activity Log</span><b>80</b></li>
							</ul>
						</div>
					</div>
				</section>

				<section class="card a-chart" aria-labelledby="h-activity">
					<div class="card-head">
						<span class="tile round t-blue"><svg class="i">
								<use href="#i-activity" />
							</svg></span>
						<div class="ttl">
							<h2 id="h-activity">Security Activity</h2>
						</div>
						<select class="field" id="chartRange" aria-label="Select date range">
							<option value="7" selected>Last 7 days</option>
							<option value="14">Last 14 days</option>
							<option value="30">Last 30 days</option>
						</select>
					</div>
					<div class="legend">
						<span><i class="dot" style="background:#2F5BEA"></i>Failed Login Attempts</span>
						<span><i class="dot" style="background:#2DD4BF"></i>File Changes Detected</span>
					</div>
					<div class="chart" id="activityChart">
						<div class="tip" id="chartTip"></div>
					</div>
				</section>

				<section class="card a-locked" id="lockedCard" aria-labelledby="h-locked">
					<div class="card-head">
						<span class="tile round t-blue"><svg class="i">
								<use href="#i-lock" />
							</svg></span>
						<div class="ttl">
							<h2 id="h-locked">Currently Locked</h2><span class="pill p-red" id="lockedPill">3 IPs</span>
						</div>
						<a class="btn btn-neutral btn-sm" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-login' ) ); ?>"><span>View All</span><svg
								class="i">
								<use href="#i-arrow" />
							</svg></a>
					</div>
					<div class="tbl-wrap">
						<table class="tbl locked">
							<thead>
								<tr>
									<th>IP Address</th>
									<th>Attempts</th>
									<th>Locked Until</th>
									<th style="text-align:right">Action</th>
								</tr>
							</thead>
							<tbody id="lockedBody"></tbody>
						</table>
					</div>
					<div class="divider"></div>
					<div class="card-head" style="margin-bottom:10px">
						<span class="tile round t-green"><svg class="i">
								<use href="#i-shield-check" />
							</svg></span>
						<div class="ttl">
							<h2>IP Allowlist</h2><span class="pill p-green" id="allowPill">5 IPs</span>
						</div>
						<button class="btn btn-neutral btn-sm" id="manageAllow" type="button"><span>Manage</span><svg
								class="i">
								<use href="#i-arrow" />
							</svg></button>
					</div>
					<div class="chips" id="allowChips"></div>
				</section>

				<section class="card a-hard" aria-labelledby="h-hard">
					<div class="card-head">
						<span class="tile round t-blue"><svg class="i">
								<use href="#i-gear" />
							</svg></span>
						<div class="ttl">
							<h2 id="h-hard">Hardening Checklist</h2><span class="pill p-green" id="hBadge">5 / 6
								Enabled</span>
						</div>
						<a class="btn btn-neutral btn-sm" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-hardening' ) ); ?>"><span>Configure</span><svg class="i">
								<use href="#i-arrow" />
							</svg></a>
					</div>
					<div id="hList">
						<div class="h-row"><button class="switch" role="switch" aria-checked="true"
								data-h="Disable XML-RPC" aria-label="Disable XML-RPC" type="button"></button>
							<div class="h-info"><b>Disable XML-RPC</b><small>Prevents brute force attacks</small></div>
						</div>
						<div class="h-row"><button class="switch" role="switch" aria-checked="true"
								data-h="Disable file editor" aria-label="Disable file editor" type="button"></button>
							<div class="h-info"><b>Disable file editor</b><small>Block theme and plugin file
									editing</small></div>
						</div>
						<div class="h-row"><button class="switch" role="switch" aria-checked="true"
								data-h="Hide WP version" aria-label="Hide WP version" type="button"></button>
							<div class="h-info"><b>Hide WP version</b><small>Remove generator meta tags</small></div>
						</div>
						<div class="h-row"><button class="switch" role="switch" aria-checked="true"
								data-h="Block user enumeration" aria-label="Block user enumeration"
								type="button"></button>
							<div class="h-info"><b>Block user enumeration</b><small>Prevent username discovery</small>
							</div>
						</div>
						<div class="h-row"><button class="switch" role="switch" aria-checked="true"
								data-h="Security headers" aria-label="Security headers" type="button"></button>
							<div class="h-info"><b>Security headers</b><small>Add security headers (X-Frame, CSP,
									etc.)</small></div>
						</div>
						<div class="h-row"><button class="switch" role="switch" aria-checked="false"
								data-h="Block PHP in uploads" aria-label="Block PHP in uploads" type="button"></button>
							<div class="h-info"><b>Block PHP in uploads</b><small>Prevent PHP execution in upload
									folders</small></div>
						</div>
					</div>
				</section>

				<section class="card a-recent" aria-labelledby="h-recent">
					<div class="card-head">
						<span class="tile round t-blue"><svg class="i">
								<use href="#i-list" />
							</svg></span>
						<div class="ttl">
							<h2 id="h-recent">Recent Activity</h2>
						</div>
						<a class="btn btn-neutral btn-sm" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-activity-log' ) ); ?>"><span>View All</span><svg class="i">
								<use href="#i-arrow" />
							</svg></a>
					</div>
					<div class="tbl-wrap">
						<table class="tbl recent">
							<thead>
								<tr>
									<th>Time</th>
									<th>User</th>
									<th>Event</th>
									<th>Details</th>
									<th>IP</th>
								</tr>
							</thead>
							<tbody id="recentBody"></tbody>
						</table>
					</div>
				</section>

				<section class="card pro-card a-pro" aria-labelledby="h-pro">
					<div class="pro-inner">
						<div class="pro-text">
							<div class="pro-title"><svg class="i fill">
									<use href="#i-crown" />
								</svg>
								<h3 id="h-pro"><span>QuietShield</span> Pro</h3>
							</div>
							<p class="pro-sub">More protection. Greater control.</p>
							<ul class="pro-bullets" id="proBullets"></ul>
							<button class="btn btn-primary" data-pro type="button"><span>Learn More About Pro</span><svg
									class="i">
									<use href="#i-arrow" />
								</svg></button>
						</div>
						<div class="pro-art" aria-hidden="true">
							<svg viewBox="0 0 200 210">
								<defs>
									<linearGradient id="pShield" x1="0" y1="0" x2="1" y2="1">
										<stop offset="0" stop-color="#9DB8FF" />
										<stop offset="1" stop-color="#3B63E6" />
									</linearGradient>
									<linearGradient id="pShield2" x1="0" y1="0" x2="0" y2="1">
										<stop offset="0" stop-color="#DCE6FF" />
										<stop offset="1" stop-color="#9DB5F5" />
									</linearGradient>
									<linearGradient id="pLock" x1="0" y1="0" x2="0" y2="1">
										<stop offset="0" stop-color="#6E8FF5" />
										<stop offset="1" stop-color="#2F5BEA" />
									</linearGradient>
									<radialGradient id="pGlow" cx=".5" cy=".5" r=".5">
										<stop offset="0" stop-color="#BFD1FF" stop-opacity=".9" />
										<stop offset="1" stop-color="#BFD1FF" stop-opacity="0" />
									</radialGradient>
								</defs>
								<ellipse cx="100" cy="186" rx="78" ry="16" fill="url(#pGlow)" />
								<ellipse cx="100" cy="182" rx="56" ry="10" fill="#DCE6FF" />
								<ellipse cx="100" cy="178" rx="56" ry="10" fill="#EEF3FF" stroke="#C9D5F7" />
								<path d="M100 30 158 50v50c0 38-25 62-58 76-33-14-58-38-58-76V50Z"
									fill="url(#pShield)" />
								<path d="M100 42 147 58v42c0 31-20 51-47 63-27-12-47-32-47-63V58Z"
									fill="url(#pShield2)" />
								<path d="M84 96V84a16 16 0 0 1 32 0v12" fill="none" stroke="#2F5BEA" stroke-width="8"
									stroke-linecap="round" />
								<rect x="74" y="94" width="52" height="42" rx="9" fill="url(#pLock)" />
								<circle cx="100" cy="111" r="6" fill="#fff" />
								<rect x="97" y="112" width="6" height="12" rx="3" fill="#fff" />
								<circle cx="36" cy="40" r="3" fill="#F59E0B" opacity=".7" />
								<circle cx="172" cy="30" r="4" fill="#2DD4BF" opacity=".7" />
							</svg>
							<p class="pro-tag">Same simplicity.<br>More power.</p>
						</div>
					</div>
				</section>
			</div>
		</div>
	</main>

	<footer class="foot">
		<div class="wrap foot-in">
			<div class="foot-brand"><b>Gracewell QuietShield</b><span class="vs"></span><span
					class="muted">v<?php echo esc_html( GWQSH_VERSION ); ?></span><span class="vs"></span><span class="muted tagline">A lightweight
					WordPress security plugin</span></div>
			<nav class="foot-links" aria-label="Footer">
				<a href="https://wordpress.org/support/" target="_blank" rel="noopener">Documentation</a>
				<a href="https://wordpress.org/support/forums/" target="_blank" rel="noopener">Support</a>
				<a href="#" data-toast="Thanks! Feedback form coming soon.">Feedback</a>
			</nav>
		</div>
	</footer>

	<div class="toasts" id="toasts" aria-live="polite"></div>

	<!-- ============================================================
		Scripts
		dashboard.js  → all dashboard UI logic (deferred, runs after parse)
		hero-birds.js → Three.js flocking birds (ES module, deferred by default)
		============================================================ -->
