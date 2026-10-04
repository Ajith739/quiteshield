# Gracewell QuietShield security notes

## Behavioral security engine (1.1.0)

Detection is primarily behavioral. Files are never executed or included during analysis: the engine reads source text and a token stream (`token_get_all`) only. A shared dependency-free kernel (`includes/security/gwqsh-guardian-core.php`) provides indicator matching, the scoring model and file-reading helpers; a token-based analyzer (`class-gwqsh-php-analyzer.php`) adds precise call-site, hook, string, taint and write-target evidence; pluggable rules (`includes/security/rules/`) implement `GWQSH_Detection_Rule` and contribute explainable findings (rule ID, category, score, evidence, source lines). The scoring engine combines multi-indicator findings into one verdict per file: Informational, Low, Medium, High or Critical, with a numeric score and human-readable summary. Known incident strings are matched as supplemental evidence only and can never produce a Critical verdict on their own.

Rule categories: cloaking/traffic hijacking (bot detection + redirect/remote delivery), remote payload loading, TLS verification bypass, self-healing persistence (critical-file writes + hidden backups + chmod locks), critical file writes, web shells (request input reaching process execution or dynamic includes), obfuscation, dynamic includes, front-end injection (iframes/scripts/hidden content), uploads executables (double extensions, polyglots, server config files), must-use persistence, and supplemental signatures. `.htaccess` and `wp-config.php` are audited for cloaking redirects, execution re-enabling, injected includes and encoded payloads; QuietShield's own managed .htaccess block is excluded from those audits.

Scopes: core checksums (official WordPress.org checksums with graceful offline degradation), must-use plugins (with SHA-256 baselines), plugins, themes, uploads, and the webroot (root-file baselines, unexpected root executables). All filesystem access goes through a strict path guard (traversal, null bytes, stream wrappers, absolute-path injection and symlink escape rejection; all operations confined to validated WordPress roots).

## Early Guardian

The optional Gracewell Early Guardian (`mu-plugins/000-gracewell-guardian.php`, deployed on demand from File Integrity → Actions & Tools) loads before normal must-use plugins. On each request it performs cheap directory and per-file mtime/size comparisons and only hashes/analyzes new or changed files - there is no full scan per request. A probabilistic full re-verification (on average ~1 in 40 requests, or after 6 hours) covers timestamp manipulation. The Guardian quarantines only on extremely-high-confidence behavioral matches: the cloaking/traffic-hijack combination, the self-healing persistence combination, or request input reaching process execution. Everything else (including ordinary new must-use plugins) is recorded as a review event. Quarantine moves the file to the same execution-blocked storage the main plugin uses, records full provenance, files a Critical scan finding, and writes an activity-log entry; WordPress continues to boot. Any Guardian-internal failure is caught and logged without stopping the site.

Documented limitations: an attacker with full filesystem access can delete or corrupt the Guardian file itself (a broken must-use file fatals WordPress - verify this file after incidents), manipulate timestamps/sizes to evade change detection, or write persistence outside the must-use directory (the main scanner covers those locations during manual scans). The Guardian does not load the main plugin and degrades to baseline/indicator checks when the plugin directory is absent.

## Baselines and false positives

Trusted SHA-256 baselines exist for must-use plugins and important root files. Baselines are never established while unresolved Critical findings exist. Baseline-trusted files never raise findings; modified ones are surfaced for administrator review. The engine reduces false positives through multi-indicator combination scoring, within-category diminishing returns, contextual calibration (standalone weak signals stay Low; a plugin legitimately editing one file it owns is reviewed rather than quarantined) and content-hash caching. New must-use plugins with ordinary behavior (hooks, options, standard API use) are reported for review only.

## Quarantine and restore

Quarantine never deletes files. Content is moved (or copied for checksum-verified restore backups) into a per-site random-named directory with Apache deny rules and an index guard; names are unpredictable (`32 hex .quarantine`). Metadata records the original path, SHA-256, timestamp, reason, detection source, severity, rule ID and risk score. Restores are administrator-only (capability + nonce), refuse occupied targets, verify hashes, and are confined to validated roots. The Early Guardian and the main plugin share the same storage and metadata format.

## Custom login recovery

Save the private recovery URL displayed in Login Protection before enabling a custom login URL. It grants temporary access to the normal WordPress login form and does not bypass a password or two-factor authentication. Changing the custom login URL rotates the recovery link. An administrator with server access can define `GWQSH_DISABLE_LOGIN_URL` as `true` in `wp-config.php` if the custom route stops working.

## Trusted proxies

By default, the plugin uses a validated `REMOTE_ADDR` and ignores forwarded headers. To trust a proxy, set `GWQSH_TRUSTED_PROXIES` in `wp-config.php` to an array of the proxy's exact IP addresses and enable proxy handling in Settings. Use only addresses controlled by your infrastructure.

## Two-factor data

TOTP secrets and unused backup-code display copies use authenticated encryption with a key derived from WordPress salts (Sodium secretbox or AES-256-GCM). Changing salts can require re-enrollment or intentional backup-code regeneration. Verification uses one-way salted HMAC-SHA256 hashes. Consumption atomically removes the hash and encrypted display copy, retaining only a used marker; regeneration replaces the entire set. Only the owning authenticated user receives displayable codes. Other-user status lists contain counts only.

Legacy plaintext backups are migrated to encrypted display copies and hashes without losing unused codes. Existing hash-only records remain valid but cannot be reconstructed; the interface explains that intentional regeneration is needed to obtain displayable values. No codes are regenerated on refresh.

2FA enforcement follows each user's verified enrollment, without a global 2FA switch or dependency on plugin master status. Required users with incomplete enrollment can access their own setup page until they verify an authenticator code. Password verification issues a five-minute, IP-bound random challenge for the second step. QuietShield sends no emails; native WordPress recovery emails remain intact.

## File scan limits

Scans are manual. Core files are compared against official version/locale checksums. Plugin and theme checks detect known suspicious patterns rather than maintaining content baselines. Uploads are checked for executable extensions. Directory checks are bounded to 2,000 PHP candidates per scope; signature inspection is size-limited. QuietShield excludes its own plugin directory to avoid scanning bundled security-test/development dependencies. These checks cannot establish that every file is malware-free.

Core text differences require a checksum-verified official baseline and are limited to 128 KiB and 1,500 lines per file. All source content is escaped. The initial results table loads at most 1,000 findings, while summary totals include all unresolved findings.

## External services

Core checksum verification and core file restoration contact WordPress.org. All admin scripts, Three.js and the QR library are bundled locally. System fonts are used; opening an admin page does not load fonts or executable scripts from a third-party CDN. No plugin telemetry is sent by the new security services.


## Plugin Check review

The 1.0.0 production ZIP excludes test suites, Composer development dependencies, build tools, PHPUnit caches and configuration files. Its static Plugin Check run has no errors. Remaining warnings concern direct database queries, caching, trusted SQL identifiers and schema creation. Custom table identifiers come only from the validated WordPress prefix and fixed table suffixes; values are prepared or passed to WordPress database methods. Dynamic activity WHERE fragments and sort directions are internally allowlisted. `%i` identifier placeholders are deliberately not used because WordPress 5.3 lacks them.

Fresh lockout, replay, factor-consumption and activity queries are deliberately not cached. Schema changes are restricted to the plugin's own installation tables. AJAX dispatch checks the request nonce and capability before routing; private handlers that read POST also check their nonce. Authentication hooks use WordPress-verified credentials and unguessable expiring challenges; URL routing, author blocking and page selection do not mutate settings. Upgrade hooks run after WordPress core has authorized the operation. These analyzer warnings remain visible for reviewer inspection rather than being suppressed across whole files.

## Development-prefix migration

The canonical directory, bootstrap and text domain are `gracewell-quietshield`. Public declarations, AJAX/nonces, database tables, options, transients, user metadata and cron hooks use `GWQSH_`/`gwqsh_`. A one-time migration renames legacy tables and stored keys without decrypting or modifying payloads; existing current keys take precedence. Conflicting populated tables stop the upgrade rather than overwrite data. Security state is migrated before authentication hooks initialize. Legacy encrypted `gqs1` payloads, trusted-device cookies, recovery bookmarks and in-flight factor input names remain verifiable. Old AJAX actions are not exposed. Legacy `GQS_DISABLE_LOGIN_URL` and `GQS_TRUSTED_PROXIES` constants remain supported; prefer the new `GWQSH_` names. Previously quarantined files remain in their original private storage and are included in intentional uninstall cleanup.

Custom login routes follow WordPress permalink configuration: `/slug/` with pretty permalinks, `/index.php/slug/` with index permalinks, or `/?gwqsh_login=slug` with plain permalinks. The displayed/copied URL and native form action use the same route. No server rewrite configuration is changed by the plugin.
