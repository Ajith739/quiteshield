<?php
/**
 * Two factor administration view.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;
$gwqsh_data      = GWQSH_Admin::get_page_initial_data( 'two-factor' );
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
				<a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=gracewell-quietshield-two-factor' ) ); ?>" aria-current="page"><svg class="i">
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
					<p class="eyebrow">Two-Factor Authentication</p>
					<h1 class="h1" id="heroTitle">Two-Factor <span class="hl">Authentication</span></h1>
					<p class="lede">Add an extra layer of security to your WordPress login with two-factor authentication.</p>
				</div>
			</div>
		</section>

<div class="wrap page">
<div class="stats tf-stats">
	<article class="card stat"><span class="tile xl t-green"><svg class="i solid" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"  fill="currentColor"/><path d="m9 12 2 2 4-4"  stroke="#fff"/></svg></span>
	<div class="stat-body"><h2 class="stat-lbl">2FA Status</h2><span class="pill <?php echo GWQSH_Two_Factor::effective_enabled( get_current_user_id() ) ? 'p-green' : 'p-amber'; ?>" style="margin:6px 0 6px;height:28px;font-size:13px;border-radius:7px"><?php echo GWQSH_Two_Factor::effective_enabled( get_current_user_id() ) ? 'Enabled' : 'Disabled'; ?></span><p class="stat-meta" style="font-size:13px"><?php echo GWQSH_Two_Factor::effective_enabled( get_current_user_id() ) ? 'Your account is protected with two-factor authentication.' : 'Set up your authenticator to enable protection.'; ?></p></div></article>
	<article class="card stat"><span class="tile xl t-blue"><svg class="i solid" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"  fill="currentColor"/><circle cx="9" cy="7" r="4"  fill="currentColor"/><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /></svg></span>
	<div class="stat-body"><h2 class="stat-lbl">Active Users</h2><div class="stat-num"><span id="activeN">0</span> <small>/ 0</small></div><p class="stat-meta">Users with 2FA enabled</p>
	<div class="bar-row"><div class="bar"><span id="activeBar" data-w="0"></span></div><span id="activePct">0%</span></div></div></article>
	<article class="card stat"><span class="tile xl t-amber"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4" /><path d="m21 2-9.6 9.6" /><circle cx="7.5" cy="15.5" r="5.5" /></svg></span>
	<div class="stat-body"><h2 class="stat-lbl">Backup Codes Used</h2><div class="stat-num"><span id="usedN">0</span> <small>/ 10</small></div><p class="stat-meta">Backup codes remaining</p>
	<div class="bar-row"><div class="bar amber"><span id="usedBar" data-w="0"></span></div><span id="usedPct">0%</span></div></div></article>
	<article class="card stat"><span class="tile xl t-purple"><svg class="i solid" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"  fill="currentColor"/><polyline points="12 6 12 12 16 14"  stroke="#fff"/></svg></span>
	<div class="stat-body"><h2 class="stat-lbl">Last Verification</h2><div class="stat-num" style="font-size:18px">
	<?php
	$gwqsh_last_verified = get_user_meta( get_current_user_id(), 'gwqsh_2fa_last_verified', true );
	echo $gwqsh_last_verified ? esc_html( $gwqsh_last_verified ) : 'Never';
	?>
	</div><p class="stat-meta"></p>
	<p class="stat-meta" style="color:var(--green-600);display:flex;align-items:center;gap:6px;margin-top:6px"><svg class="i" style="width:15px;height:15px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"  fill="currentColor"/><path d="m9 12 2 2 4-4"  stroke="#fff"/></svg><?php echo $gwqsh_last_verified ? 'Successful verification' : 'No verification yet'; ?></p></div></article>
</div>

<div class="grid tf-row1">
	<section class="card" aria-labelledby="h-setup">
	<div class="card-head" style="align-items:center"><span class="tile t-purple"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" /><circle cx="12" cy="12" r="3" /></svg></span>
		<div class="ttl"><h2 id="h-setup">Your 2FA Setup</h2><p>Manage your two-factor authentication settings.</p></div>
		<span class="pill p-green" style="height:32px;padding:0 14px;font-size:13px;border-radius:8px"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"  fill="currentColor"/><path d="m9 12 2 2 4-4"  stroke="#fff"/></svg>Enabled</span></div>


	<div class="setup-grid">
		<div class="panel qr-panel">
		<div class="qr-wrap"><div id="qr" class="qr" role="img" aria-label="QR code for your authenticator app"></div><span class="qr-logo" hidden><svg class="logo-mark" viewBox="0 0 48 54" aria-hidden="true">
<defs><linearGradient id="lgS" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#23357F"/><stop offset="1" stop-color="#0B1440"/></linearGradient>
<linearGradient id="lgC" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#5EEAD4"/><stop offset="1" stop-color="#22D3EE"/></linearGradient></defs>
<path d="M24 2 44 9v17c0 13-8.6 21.5-20 26C12.6 47.5 4 39 4 26V9Z" fill="url(#lgS)" stroke="#3B4FA8" stroke-width="1.5"/>
<path d="M31.5 19.5a10 10 0 1 0 1.2 10.2" fill="none" stroke="url(#lgC)" stroke-width="4.2" stroke-linecap="round"/>
<path d="M25 27.5h8" stroke="url(#lgC)" stroke-width="4.2" stroke-linecap="round"/>
</svg></span></div>
		<div class="qr-info"><h3>Authenticator App</h3><p>Scan this QR code with your authenticator app (Google Authenticator, Authy, 1Password, etc.)</p>
			<div class="keybox"><small>Or enter the key manually</small><div><code class="mono" id="secret"></code><button class="copy-btn" data-copy="#secret" data-copy-label="Setup key copied" aria-label="Copy setup key"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2" ry="2" /><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" /></svg></button></div></div>
			<label class="field-label" for="setupCode">Authentication code</label><input class="field" id="setupCode" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="6-digit code">
			<div class="url-actions"><button class="btn btn-primary" id="personal2fa" type="button">Verify and enable 2FA</button><button class="btn btn-outline" id="verifyReplacement" type="button" hidden>Verify replacement</button></div><p class="muted" id="personal2faStatus" aria-live="polite"></p>
		</div>
		<button class="link-btn regen" id="regenQr" type="button"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" /><path d="M21 3v5h-5" /><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" /><path d="M8 16H3v5" /></svg>Regenerate QR Code</button>
		</div>
		<nav class="method-list divide" aria-label="2FA methods">
		<button class="lrow" type="button" id="mApp"><span class="tile sm t-green"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="20" x="5" y="2" rx="2" ry="2" /><path d="M12 18h.01" /></svg></span><span class="txt"><b>Authenticator App</b><small><?php echo GWQSH_Two_Factor::effective_enabled( get_current_user_id() ) ? 'Connected and working' : 'Awaiting setup'; ?></small></span><svg class="i c-green" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"  fill="currentColor"/><path d="m9 12 2 2 4-4"  stroke="#fff"/></svg></button>
		<a class="lrow" href="#backup"><span class="tile sm t-red"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4" /><path d="m21 2-9.6 9.6" /><circle cx="7.5" cy="15.5" r="5.5" /></svg></span><span class="txt"><b>Backup Codes</b><small id="mCodes">0 of 0 remaining</small></span><svg class="i chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg></a>
		<button class="lrow" type="button" id="mDevices"><span class="tile sm t-blue"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="14" x="2" y="3" rx="2" /><line x1="8" x2="16" y1="21" y2="21" /><line x1="12" x2="12" y1="17" y2="21" /></svg></span><span class="txt"><b>Trusted Devices</b><small id="mDevN">0 trusted devices</small></span><svg class="i chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg></button>

		</nav>
	</div>
	</section>

	<section class="card" id="backup" aria-labelledby="h-backup">
	<div class="card-head" style="align-items:center"><span class="tile t-amber"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4" /><path d="m21 2-9.6 9.6" /><circle cx="7.5" cy="15.5" r="5.5" /></svg></span>
		<div class="ttl"><h2 id="h-backup">Backup Codes</h2><p>Use these one-time codes if you can’t access your authenticator app.</p></div>
		<button class="btn btn-outline btn-sm" id="regenCodes" type="button">Regenerate Codes</button></div>
	<div class="codes-box">
		<ul class="codes" id="codes"></ul>
		<div class="note note-amber"><svg class="i solid" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"  fill="currentColor"/><line x1="12" x2="12" y1="8" y2="12"  stroke="#fff"/><line x1="12" x2="12.01" y1="16" y2="16"  stroke="#fff"/></svg><p><b>Keep these codes in a safe place.</b><br><span class="muted">Each code can only be used once.</span></p></div>
		<div class="codes-actions"><button class="link-btn" id="copyCodes" type="button"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2" ry="2" /><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" /></svg>Copy all</button><button class="link-btn" id="dlCodes" type="button"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" /><polyline points="7 10 12 15 17 10" /><line x1="12" x2="12" y1="15" y2="3" /></svg>Download .txt</button></div>
	</div>
	</section>

	<section class="card" aria-labelledby="h-2fas">
	<div class="card-head" style="align-items:center"><span class="tile t-blue"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" /><circle cx="12" cy="12" r="3" /></svg></span><div class="ttl"><h2 id="h-2fas">2FA Settings</h2></div></div>
	<div class="divide" id="tfSettings">

		<div class="set-row"><span class="tile xs t-green"><svg class="i solid" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"  fill="currentColor"/><path d="m9 12 2 2 4-4"  stroke="#fff"/></svg></span><div class="txt"><b id="s1-l">Require 2FA for administrators</b><small>Force 2FA for all admin users</small></div>
<button class="switch" role="switch" aria-checked="true" aria-labelledby="s1-l" data-policy="require_admin"></button></div>
		<div class="set-row"><span class="tile xs t-amber"><svg class="i solid" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"  fill="currentColor"/><circle cx="9" cy="7" r="4"  fill="currentColor"/><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /></svg></span><div class="txt"><b id="s2-l">Allow 2FA for other roles</b><small>Let editors and authors enable 2FA</small></div>
<button class="switch" role="switch" aria-checked="true" aria-labelledby="s2-l" data-policy="allow_roles"></button></div>
		<div class="set-row"><span class="tile xs t-purple"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="14" x="2" y="3" rx="2" /><line x1="8" x2="16" y1="21" y2="21" /><line x1="12" x2="12" y1="17" y2="21" /></svg></span><div class="txt"><b id="s3-l">Remember device (30 days)</b><small>Reduce friction on trusted devices</small></div>
<button class="switch" role="switch" aria-checked="true" aria-labelledby="s3-l" data-policy="remember_device"></button></div>

	</div>
	<button class="btn btn-primary btn-block" id="save2fa" type="button" style="margin-top:14px">Save Settings <svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14" /><path d="m12 5 7 7-7 7" /></svg></button>
	</section>
</div>

<div class="grid tf-row2">
	<section class="card" aria-labelledby="h-users">
	<div class="card-head" style="align-items:center"><span class="tile t-purple"><svg class="i solid" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"  fill="currentColor"/><circle cx="9" cy="7" r="4"  fill="currentColor"/><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /></svg></span>
		<div class="ttl"><h2 id="h-users">Users and 2FA Status</h2><p>Manage two-factor authentication for all users on your site.</p></div>
		<div class="toolbar"><label class="search"><span class="sr">Search users</span><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8" /><path d="m21 21-4.3-4.3" /></svg><input class="field field-sm" id="uSearch" placeholder="Search users..." type="search"></label>
		<select class="field field-sm" id="uRole" aria-label="Filter by role"><option value="">All Roles</option><option>Administrator</option><option>Editor</option><option>Author</option><option>Contributor</option></select>
		<select class="field field-sm" id="uStatus" aria-label="Filter by status"><option value="">All Status</option><option>Enabled</option><option>Setup incomplete</option><option>Disabled</option></select></div></div>
	<div class="table-wrap"><table class="table"><thead><tr><th>User</th><th>Role</th><th>2FA Status</th><th>Method</th><th>Last Activity</th><th style="text-align:center">Actions</th><th><span class="sr">More</span></th></tr></thead><tbody id="users"></tbody></table></div>
	</section>

	<section class="card tips-card" aria-labelledby="h-tips">
	<div class="card-head" style="align-items:center"><span class="tile t-green round"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5" /><path d="M9 18h6" /><path d="M10 22h4" /></svg></span><div class="ttl"><h2 id="h-tips">Security Tips</h2></div></div>
	<div class="divide">
		<div class="lrow"><span class="tile sm t-green"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="20" x="5" y="2" rx="2" ry="2" /><path d="M12 18h.01" /></svg></span><div class="txt"><b>Use an authenticator app</b><small>Apps like Google Authenticator or 1Password are more secure than SMS.</small></div></div>
		<div class="lrow"><span class="tile sm t-blue"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4" /><path d="m21 2-9.6 9.6" /><circle cx="7.5" cy="15.5" r="5.5" /></svg></span><div class="txt"><b>Save your backup codes</b><small>Store them in a safe place like a password manager.</small></div></div>
		<div class="lrow"><span class="tile sm t-amber"><svg class="i solid" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"  fill="currentColor"/><circle cx="9" cy="7" r="4"  fill="currentColor"/><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /></svg></span><div class="txt"><b>Enable 2FA for all admins</b><small>Prevent unauthorized access to your site.</small></div></div>
		<div class="lrow"><span class="tile sm t-purple"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="14" x="2" y="3" rx="2" /><line x1="8" x2="16" y1="21" y2="21" /><line x1="12" x2="12" y1="17" y2="21" /></svg></span><div class="txt"><b>Remove unused devices</b><small>Regularly review and remove trusted devices.</small></div></div>
	</div>
	<figure class="quote"><span class="tile sm" style="background:var(--surface);color:var(--blue-600)"><svg class="i solid" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"  fill="currentColor"/><path d="m9 12 2 2 4-4"  stroke="#fff"/></svg></span><blockquote>“Security is not a feature,<br>it’s a habit.”<figcaption>— Gracewell QuietShield</figcaption></blockquote>
		<svg viewBox="0 0 120 50" aria-hidden="true"><path d="M0 50 30 18l14 12 22-26 20 20 14-8 20 22v12z" fill="var(--blue-100)" opacity=".7"/><path d="M0 50 24 32l20 10 26-18 24 14 26-4v16z" fill="var(--purple-100)" opacity=".7"/></svg></figure>
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

	<!-- Third-party libs (must load before qs-core since it reads window.gsap/THREE) -->




	<!-- Shared utility library (from login-protection step) -->


	<!-- Page-specific logic -->


	<!-- Hero birds animation (shared, ES module) -->
