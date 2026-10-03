<?php
/**
 * Login protection administration view.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;
$gwqsh_data      = GWQSH_Admin::get_page_initial_data( 'login-protection' );
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
				<a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-login' ) ); ?>" aria-current="page"><svg class="i">
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
		<section class="hero" aria-labelledby="heroTitle">
			<canvas class="hero-birds" id="heroBirds" aria-hidden="true"></canvas>
			<div class="wrap hero-in">
				<div class="hero-content">
					<p class="eyebrow">Login Protection</p>
					<h1 class="h1" id="heroTitle">Login <span class="hl">Protection</span></h1>
					<p class="lede">Protect your WordPress site from brute-force attacks and suspicious login attempts.
					</p>
				</div>
			</div>
		</section>

		<div class="wrap page">            <div class="stats lp-stats">
				<article class="card stat stat-main" id="protCard">
					<div class="stat-head">
						<span class="tile t-green round" id="protTile"><svg class="i"><use href="#i-shield-check" /></svg></span>
						<span class="stat-title" id="protTitle">Protection Active</span>
						<span class="pill p-green" id="protPill">Enabled</span>
					</div>
					<div class="stat-body">
						<div class="stat-row">
							<span class="stat-lbl">Protection Mode</span>
							<button class="switch" role="switch" aria-checked="true" id="protSwitch"
								aria-label="Brute-force protection"></button>
						</div>
						<p class="stat-meta" id="protDesc">Failed login attempts are monitored.</p>
					</div>
					<div class="stat-foot"><a href="#h-settings">Configure Limits</a><a class="go" href="#h-settings" aria-label="Open Settings"><svg class="i"><use href="#i-arrow" /></svg></a></div>
				</article>

				<article class="card stat">
					<div class="stat-head">
						<span class="tile t-red round"><svg class="i"><use href="#i-lock" /></svg></span>
						<span class="stat-title">Failed Attempts</span>
						<span class="trend bad"><svg class="i"><use href="#i-arrow" /></svg>Comparison unavailable</span>
					</div>
					<div class="stat-body">
						<div class="stat-num" data-count="<?php echo esc_html( $gwqsh_data['failed_24h'] ); ?>"><?php echo esc_html( $gwqsh_data['failed_24h'] ); ?></div>
						<div class="stat-lbl">in the last 24 hours</div>
					</div>
					<div class="stat-foot"><a href="#h-activity">View Activity Chart</a><a class="go" href="#h-activity" aria-label="Open Activity"><svg class="i"><use href="#i-arrow" /></svg></a></div>
				</article>

				<article class="card stat">
					<div class="stat-head">
						<span class="tile t-amber round"><svg class="i"><use href="#i-alert" /></svg></span>
						<span class="stat-title">Currently Locked</span>
						<span class="pill p-red" id="lockedStatPill"><?php echo esc_html( count( $gwqsh_data['locked_ips'] ) ); ?> Locked</span>
					</div>
					<div class="stat-body">
						<div class="stat-num" id="lockedCount" data-count="<?php echo esc_html( count( $gwqsh_data['locked_ips'] ) ); ?>"><?php echo esc_html( count( $gwqsh_data['locked_ips'] ) ); ?></div>
						<div class="stat-lbl">Suspicious IPs blocked</div>
					</div>
					<div class="stat-foot"><a href="#h-locked">View Locked IPs</a><a class="go" href="#h-locked" aria-label="Open Locked IPs"><svg class="i"><use href="#i-arrow" /></svg></a></div>
				</article>

				<article class="card stat">
					<div class="stat-head">
						<span class="tile t-blue round"><svg class="i"><use href="#i-shield-check" /></svg></span>
						<span class="stat-title">Allowlisted IPs</span>
						<span class="pill p-green" id="allowStatPill"><?php echo esc_html( count( $gwqsh_data['allow_ips'] ) ); ?> IPs</span>
					</div>
					<div class="stat-body">
						<div class="stat-num" id="allowCount" data-count="<?php echo esc_html( count( $gwqsh_data['allow_ips'] ) ); ?>"><?php echo esc_html( count( $gwqsh_data['allow_ips'] ) ); ?></div>
						<div class="stat-lbl">Trusted IP addresses</div>
					</div>
					<div class="stat-foot"><a href="#allowlist">Manage Allowlist</a><a class="go" href="#allowlist" aria-label="Open Allowlist"><svg class="i"><use href="#i-arrow" /></svg></a></div>
				</article>

				<article class="card stat">
					<div class="stat-head">
						<span class="tile t-purple round"><svg class="i"><use href="#i-gear" /></svg></span>
						<span class="stat-title">Lockout Duration</span>
						<span class="pill p-green">Active</span>
					</div>
					<div class="stat-body">
						<div class="stat-num" id="lockoutStat">30 min</div>
						<div class="stat-lbl" id="lockoutMeta">After 5 failed attempts</div>
					</div>
					<div class="stat-foot"><a href="#h-settings">Adjust Duration</a><a class="go" href="#h-settings" aria-label="Adjust Duration"><svg class="i"><use href="#i-arrow" /></svg></a></div>
				</article>
			</div>

			<div class="grid lp-grid"><div class="lp-column"><section class="card a-settings" aria-labelledby="h-settings">
					<div class="card-head"><span class="tile t-purple"><svg class="i" viewBox="0 0 24 24" fill="none"
								stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
								aria-hidden="true">
								<path
									d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
								<circle cx="12" cy="12" r="3" />
							</svg></span>
						<div class="ttl">
							<h2 id="h-settings">Protection Settings</h2>
							<p>Configure how login protection works on your site.</p>
						</div>
						<button class="btn btn-outline btn-sm" id="restoreDefaults" type="button">Restore
							Defaults</button>
					</div>
					<div class="divide" id="settingsForm">
						<div class="set-row wrap-sm"><label class="txt" for="maxAttempts"><b>Maximum login
									attempts</b><small>Number of failed attempts before lockout.</small></label>
							<div class="ctl">
								<div class="stepper"><input class="field" type="number" id="maxAttempts" value="5"
										min="1" max="20" aria-label="Maximum login attempts">
									<div class="stepper-btns"><button type="button" data-step="1"
											aria-label="Increase"><svg class="i" viewBox="0 0 24 24" fill="none"
												stroke="currentColor" stroke-width="3" stroke-linecap="round"
												stroke-linejoin="round" aria-hidden="true">
												<path d="m18 15-6-6-6 6" />
											</svg></button><button type="button" data-step="-1"
											aria-label="Decrease"><svg class="i" viewBox="0 0 24 24" fill="none"
												stroke="currentColor" stroke-width="3" stroke-linecap="round"
												stroke-linejoin="round" aria-hidden="true">
												<path d="m6 9 6 6 6-6" />
											</svg></button></div>
								</div>
							</div>
						</div>
						<div class="set-row wrap-sm"><label class="txt" for="timeWindow"><b>Time window</b><small>Time
									period to count failed attempts.</small></label>
							<div class="ctl"><select class="field" id="timeWindow">
									<option value="5">5 minutes</option>
									<option value="10">10 minutes</option>
									<option value="15" selected>15 minutes</option>
									<option value="30">30 minutes</option>
									<option value="60">60 minutes</option>
								</select></div>
						</div>
						<div class="set-row wrap-sm"><label class="txt" for="lockDur"><b>Lockout duration</b><small>How
									long to lock the IP address.</small></label>
							<div class="ctl"><select class="field" id="lockDur">
									<option value="15">15 minutes</option>
									<option value="30" selected>30 minutes</option>
									<option value="60">1 hour</option>
									<option value="120">2 hours</option>
									<option value="360">6 hours</option>
								</select></div>
						</div>
						<div class="set-row wrap-sm"><label class="txt" for="repeatLock"><b>Repeated
									lockouts</b><small>If an IP is locked this many times within 24
									hours.</small></label>
							<div class="ctl">
								<div class="stepper"><input class="field" type="number" id="repeatLock" value="4"
										min="1" max="20" aria-label="Repeated lockouts">
									<div class="stepper-btns"><button type="button" data-step="1"
											aria-label="Increase"><svg class="i" viewBox="0 0 24 24" fill="none"
												stroke="currentColor" stroke-width="3" stroke-linecap="round"
												stroke-linejoin="round" aria-hidden="true">
												<path d="m18 15-6-6-6 6" />
											</svg></button><button type="button" data-step="-1"
											aria-label="Decrease"><svg class="i" viewBox="0 0 24 24" fill="none"
												stroke="currentColor" stroke-width="3" stroke-linecap="round"
												stroke-linejoin="round" aria-hidden="true">
												<path d="m6 9 6 6 6-6" />
											</svg></button></div>
								</div>
							</div>
						</div>
						<div class="set-row wrap-sm"><label class="txt" for="repeatDur"><b>Repeated lockout
									duration</b><small>Lock IP for a longer period after repeated
									lockouts.</small></label>
							<div class="ctl"><select class="field" id="repeatDur">
									<option value="12">12 hours</option>
									<option value="24" selected>24 hours</option>
									<option value="48">48 hours</option>
									<option value="168">7 days</option>
								</select></div>
						</div>
					</div>
					<div class="save-row"><button class="btn btn-primary btn-lg" id="saveSettings" type="button">Save
							Changes <svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor"
								stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<path d="M5 12h14" />
								<path d="m12 5 7 7-7 7" />
							</svg></button>
						<button class="link-btn" id="resetDefaults" type="button"><svg class="i" viewBox="0 0 24 24"
								fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
								stroke-linejoin="round" aria-hidden="true">
								<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
								<path d="M3 3v5h5" />
							</svg>Reset to defaults</button>
					</div>
				</section><section class="card a-allow" id="allowlist" aria-labelledby="h-allow">
					<div class="card-head"><span class="tile t-green sm"><svg class="i solid" viewBox="0 0 24 24"
								fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
								stroke-linejoin="round" aria-hidden="true">
								<path
									d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"
									fill="currentColor" />
								<path d="m9 12 2 2 4-4" stroke="#fff" />
							</svg></span>
						<div class="ttl">
							<h2 id="h-allow">IP Allowlist</h2><span class="pill p-green" id="allowPill"><?php echo esc_html( count( $gwqsh_data['allow_ips'] ) ); ?> IPs</span>
						</div>
						<div class="menu-wrap"><button class="btn btn-neutral btn-sm" data-menu="allow"
								aria-haspopup="true" aria-expanded="false" type="button">Manage <svg class="i"
									viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
									stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
									<path d="m6 9 6 6 6-6" />
								</svg></button>
							<div class="menu" id="menu-allow" role="menu"><button class="menu-item" id="copyAllow"
									role="menuitem" type="button"><svg class="i" viewBox="0 0 24 24" fill="none"
										stroke="currentColor" stroke-width="2" stroke-linecap="round"
										stroke-linejoin="round" aria-hidden="true">
										<rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
										<path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
									</svg><span>Copy all IPs</span></button><button class="menu-item" id="addMine"
									role="menuitem" type="button"><svg class="i" viewBox="0 0 24 24" fill="none"
										stroke="currentColor" stroke-width="2" stroke-linecap="round"
										stroke-linejoin="round" aria-hidden="true">
										<path d="M5 12h14" />
										<path d="M12 5v14" />
									</svg><span>Add my current IP</span></button><button class="menu-item danger"
									id="clearAllow" role="menuitem" type="button"><svg class="i" viewBox="0 0 24 24"
										fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
										stroke-linejoin="round" aria-hidden="true">
										<path d="M3 6h18" />
										<path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
										<path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
										<line x1="10" x2="10" y1="11" y2="17" />
										<line x1="14" x2="14" y1="11" y2="17" />
									</svg><span>Remove all</span></button></div>
						</div>
					</div>
					<p class="muted">Allowlist IP addresses that should never be locked out.</p>
					<ul class="chips" id="chips"></ul>
					<div id="addWrap">
						<button class="add-dash" id="addIpBtn" type="button"><svg class="i" viewBox="0 0 24 24"
								fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
								stroke-linejoin="round" aria-hidden="true">
								<circle cx="12" cy="12" r="10" />
								<path d="M8 12h8" />
								<path d="M12 8v8" />
							</svg>Add IP address</button>
						<form class="add-form hide" id="addForm"><label class="sr" for="ipInput">IP
								address</label><input class="field" id="ipInput" placeholder="e.g. 203.0.113.10"
								autocomplete="off"><button class="btn btn-primary" type="submit">Add</button><button
								class="btn btn-neutral" type="button" id="cancelAdd">Cancel</button></form>
						<p class="err-text" id="ipErr">Enter a valid IPv4 or IPv6 address.</p>
					</div>
				</section><section class="card a-url" id="loginurl" aria-labelledby="h-url">
					<div class="card-head"><span class="tile t-purple sm"><svg class="i" viewBox="0 0 24 24" fill="none"
								stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
								aria-hidden="true">
								<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
								<path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
							</svg></span>
						<div class="ttl">
							<h2 id="h-url">Custom Login URL</h2>
						</div><span class="pill p-green p-dot">Enabled</span>
					</div>
					<p class="muted">Use a custom login URL to hide the default <span
							class="code-inline">wp-login.php</span> and prevent automated attacks.</p>
					<div class="url-input-block">
						<label class="field-label" for="loginSlug">Custom login slug</label>
						<div class="input-group"><span class="pre"><?php echo esc_html( GWQSH_Login_Protection::custom_login_base() ); ?></span><input id="loginSlug" value="<?php echo esc_attr( $gwqsh_data['settings']['custom_slug'] ); ?>"
								spellcheck="false" aria-label="Login path"><button class="copy-btn" id="copyUrl" type="button"
								aria-label="Copy login URL"><svg class="i" viewBox="0 0 24 24" fill="none"
									stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
									aria-hidden="true">
									<rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
									<path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
								</svg></button></div>
					</div>
					<p class="err-text" id="slugErr">Use 3–40 lowercase letters, numbers or hyphens.</p>
					<div class="url-actions"><button class="btn btn-outline" id="changeUrl" type="button">Change
							URL</button><button class="btn btn-neutral hide" id="cancelUrl"
							type="button">Cancel</button></div>
					<div class="note note-blue"><svg class="i solid" viewBox="0 0 24 24" fill="none"
							stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
							aria-hidden="true">
							<circle cx="12" cy="12" r="10" fill="currentColor" />
							<path d="M12 16v-4" stroke="#fff" />
							<path d="M12 8h.01" stroke="#fff" />
						</svg>
						<p>If you lose access to the custom login URL, use your private recovery link <span
								class="code-inline" id="gwqshRecoveryUrl"></span> or disable the plugin from
							<span class="code-inline">wp-config.php</span>.</p>
					</div>
				</section></div><div class="lp-column"><section class="card a-activity" aria-labelledby="h-activity">
					<div class="card-head"><span class="tile t-purple"><svg class="i" viewBox="0 0 24 24" fill="none"
								stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
								aria-hidden="true">
								<path d="M3 3v16a2 2 0 0 0 2 2h16" />
								<path d="M18 17V9" />
								<path d="M13 17V5" />
								<path d="M8 17v-3" />
							</svg></span>
						<div class="ttl">
							<h2 id="h-activity">Login Activity</h2>
							<p id="actSub">Successful vs blocked login attempts (last 7 days)</p>
						</div>
						<select class="field field-sm" id="actRange" aria-label="Date range">
							<option value="7">Last 7 days</option>
							<option value="14">Last 14 days</option>
							<option value="30">Last 30 days</option>
						</select>
					</div>
					<div class="chart-legend"><span><i class="dot c-green"></i>Successful attempts</span><span><i
								class="dot c-red"></i>Blocked attempts</span></div>
					<div id="loginChart"></div>
				</section><section class="card a-locked" aria-labelledby="h-locked">
					<div class="card-head"><span class="tile t-red sm"><svg class="i solid" viewBox="0 0 24 24"
								fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
								stroke-linejoin="round" aria-hidden="true">
								<rect width="18" height="11" x="3" y="11" rx="2" ry="2" fill="currentColor" />
								<path d="M7 11V7a5 5 0 0 1 10 0v4" />
							</svg></span>
						<div class="ttl">
							<h2 id="h-locked">Currently Locked IPs</h2><span class="pill p-red" id="lockedPill">3
								Locked</span>
						</div>
						<a class="btn btn-outline btn-sm" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-activity-log&type=security' ) ); ?>">View All <svg class="i"
								viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
								stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<path d="m9 18 6-6-6-6" />
							</svg></a>
					</div>
					<div class="table-wrap">
						<table class="table tight">
							<thead>
								<tr>
									<th>IP Address</th>
									<th>Attempts</th>
									<th>Locked Until</th>
									<th>Time Remaining</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody id="lockedBody"></tbody>
						</table>
					</div>
				</section><section class="card a-errors" aria-labelledby="h-errors">
					<div class="err-card">
						<span class="tile xl err-tile"><svg class="i solid" viewBox="0 0 24 24" fill="none"
								stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
								aria-hidden="true">
								<path
									d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"
									fill="currentColor" />
								<path d="m9 12 2 2 4-4" stroke="#fff" />
							</svg></span>
						<div class="err-txt">
							<h2 id="h-errors">Generic Login Errors</h2>
							<p>QuietShield shows the same error message for both invalid usernames and passwords. This
								prevents attackers from identifying which usernames exist on your site.</p>
						</div>
						<div class="err-art" aria-hidden="true">
							<svg class="err-hills" viewBox="0 0 400 110" preserveAspectRatio="none">
								<path d="M0 110V70l60-24 50 18 70-40 60 30 50-20 60 26 50-10v60z"
									fill="var(--blue-50)" />
								<path d="M0 110V88l80-18 70 14 90-26 80 20 80-8v40z" fill="var(--purple-50)"
									opacity=".8" />
							</svg>
							<div class="browser">
								<div class="bar-top"><i></i><i></i><i></i></div>
								<div class="browser-body"><span class="lockico"><svg class="i solid" viewBox="0 0 24 24"
											fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
											stroke-linejoin="round" aria-hidden="true">
											<rect width="18" height="11" x="3" y="11" rx="2" ry="2"
												fill="currentColor" />
											<path d="M7 11V7a5 5 0 0 1 10 0v4" />
										</svg></span><span class="fake-input"></span><span
										class="fake-input short"></span></div>
							</div>
							<div class="bubble">Invalid username or password.</div>
						</div>
					</div>
				</section></div>
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

	<!-- Third-party libs (must load before qs-core since it reads window.gsap/THREE) -->



	<!-- Shared utility library (used by every plugin page) -->


	<!-- Page-specific logic -->


	<!-- Hero birds animation (shared, ES module) -->
