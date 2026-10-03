=== Gracewell QuietShield ===
Contributors: gracewell89
Tags: security, login protection, two factor authentication, hardening, activity log
Requires at least: 5.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Login protection, authenticator 2FA, manual file integrity checks, WordPress hardening and activity logging in a responsive security dashboard.

== Description ==

Gracewell QuietShield provides these security tools:

* Login attempt limits, IP lockouts, IP/CIDR allowlisting and an optional custom login URL.
* TOTP authentication compatible with Google Authenticator, Microsoft Authenticator, Authy and other standard authenticator apps. Enrollment requires verification. Secrets are encrypted, backup codes are hashed and single-use, and trusted devices can be revoked.
* Per-user 2FA, optional enrollment for other roles, administrator requirements and individual user setup requirements. Required users must complete setup before normal access resumes.
* Manual WordPress core checksum verification, modified/missing core file reports, suspicious uploads and backdoor-pattern detection in plugin/theme PHP files. Core findings support bounded text differences and checksum-verified restoration. Suspicious files can be quarantined.
* Hardening controls for XML-RPC, the file editor, version disclosure, user enumeration, security headers and executable scripts in uploads. Risk and passed/failed checks are calculated from enabled protections.
* Activity logging, configurable retention, exports and responsive light/dark admin themes.

The scanner uses official WordPress.org core checksums. Plugin and theme checks are signature checks rather than official package integrity verification. Scan scope is bounded on large sites. Suspicious findings require review; they are not proof of malware. No external authentication service receives TOTP secrets.

Uploads execution protection writes Apache 2.4 .htaccess rules. Nginx and other servers require equivalent server configuration. Security headers should be tested with your site's integrations.

== Installation ==

1. Upload the gracewell-quietshield folder to /wp-content/plugins/.
2. Activate Gracewell QuietShield in the WordPress Plugins screen.
3. Open QuietShield and review login protection and hardening settings.
4. Scan the authenticator QR code, verify a six-digit code, then generate and save backup codes.
5. Bookmark your custom login URL and save its private recovery link before enabling URL protection.

== Frequently Asked Questions ==

= Does QuietShield require an API key? =
No. Authenticator verification is local. Core checksums and original core files are fetched from WordPress.org. Admin scripts, animations and QR generation run from bundled local assets. The interface uses system fonts and sends no requests to font or script CDNs.

= What if I lose my authenticator? =
Use an unused backup code. Another administrator can reset your enrollment. Backup codes remain valid after refresh; unused values remain available to their owning authenticated user through encrypted display copies. Save or download them in a safe place. Regeneration invalidates the previous set.

= How can I recover a forgotten custom login URL? =
The private recovery link shown on Login Protection grants temporary access to the standard login form. It still requires your password and any active 2FA. You can also define GWQSH_DISABLE_LOGIN_URL as true in wp-config.php. Disabling Custom Login URL in the plugin immediately restores the standard routes. The configured route adapts to pretty, index.php and plain WordPress permalinks.

= Are scans scheduled? =
No. Run scans manually on File Integrity. WordPress cron still handles log retention and expired lockout cleanup.

= Does the scanner verify every plugin and theme against an original package? =
No. Core files are checked against official checksums. Plugin/theme PHP and uploads are checked for suspicious patterns or executable file types. Files over the signature-scan size limit are skipped. Review scan scope information before interpreting results.

= Which versions have been tested? =
Minimum supported versions are WordPress 5.3 and PHP 7.4. Local testing used WordPress 7.1.2 and PHP 8.2.12. Minimum-version combinations have not been executed.

== Changelog ==

= 1.0.0 =
* Initial public release.
* Login attempt protection, IP allowlisting and configurable custom login URL protection.
* Verified TOTP enrollment, encrypted secrets, single-use hashed backup codes and trusted devices.
* Manual core integrity checks, suspicious-file detection, differences, restoration and quarantine.
* WordPress hardening, calculated risk, activity logs.
* Responsive QuietShield administration with light and dark themes and an in-app changelog.

== Upgrade Notice ==

= 1.0.0 =
Initial public release. Existing development settings and authenticator enrollment are preserved. Obsolete integrity-scan scheduling is removed.
