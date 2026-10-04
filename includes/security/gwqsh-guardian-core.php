<?php
/**
 * Gracewell Early Guardian core: dependency-free malware analysis kernel.
 *
 * This file contains ONLY plain PHP: no WordPress functions, no classes, no I/O
 * beyond reading a supplied file. It is shared verbatim between the Early
 * Guardian must-use plugin (which loads before normal plugins) and the main
 * QuietShield security engine, so both apply identical high-confidence
 * behavioral detection.
 *
 * The functions here analyze source TEXT only. They never include, require,
 * evaluate or execute any analyzed code.
 *
 * @package GracewellQuietShield
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'GWQSH_GUARDIAN_CORE_VERSION' ) ) {
	define( 'GWQSH_GUARDIAN_CORE_VERSION', '1.1.0' );
}

/**
 * Highest source size (bytes) the lightweight kernel will read.
 */
if ( ! defined( 'GWQSH_GUARDIAN_CORE_MAX_BYTES' ) ) {
	define( 'GWQSH_GUARDIAN_CORE_MAX_BYTES', 524288 ); // 512 KiB.
}

/**
 * Behavioral indicator patterns shared by the Guardian and the engine.
 *
 * Every entry: key => array( 'weight' => int, 'label' => string, 'patterns' => string[] )
 * Weights are small; combinations produce the high-confidence verdicts.
 *
 * @return array
 */
function gwqsh_guardian_core_indicator_patterns() {
	return array(
		'hook_template_redirect' => array(
			'weight' => 2,
			'label'  => 'hooks template_redirect',
			're'     => array(
				'/\b(?:add_action|add_filter)\s*\(\s*["\']template_redirect["\']/i',
			),
		),
		'user_agent_probe'       => array(
			'weight' => 2,
			'label'  => 'inspects HTTP_USER_AGENT',
			're'     => array(
				'/HTTP_USER_AGENT/i',
				'/\$_SERVER\s*\[[^\]]*["\']?(?:HTTP_USER_AGENT|HTTP_REFERER|HTTP_ACCEPT_LANGUAGE)["\']?/i',
				'/getenv\s*\(\s*["\']HTTP_USER_AGENT["\']/i',
			),
		),
		'bot_detection'          => array(
			'weight' => 2,
			'label'  => 'search-engine bot detection (cloaking signal)',
			're'     => array(
				'/googlebot|bingbot|slurp|duckduckbot|baiduspider|yandexbot|facebot|ia_archiver|mj12bot|ahrefsbot|semrushbot/i',
				'/is_bot|isbot|is_google|isgoogle|is_crawler|iscrawler|search_engine_bot/i',
			),
		),
		'mobile_detect'          => array(
			'weight' => 1,
			'label'  => 'mobile-device detection',
			're'     => array(
				'/(?:iPhone|iPad|iPod|Android|MobileOpera|BlackBerry|IEMobile)/i',
				'/window\.location(?:\.href|\s*=\s*["\']?)|location\.replace\s*\(|location\.assign\s*\(/i',
			),
		),
		'remote_fetch'           => array(
			'weight' => 2,
			'label'  => 'retrieves remote content',
			're'     => array(
				'/\bfile_get_contents\s*\(\s*["\'](?:https?|ftps?):\/\//i',
				'/\bcurl_init\s*\(|\bcurl_exec\s*\(|\bCURLOPT_URL\b/i',
				'/\bwp_remote_(?:get|post|request)\s*\(/i',
				'/\bfsockopen\s*\(|\bstream_socket_client\s*\(/i',
				'/["\']https?:\/\/[^"\']{6,}["\']/',
			),
		),
		'external_redirect'      => array(
			'weight' => 2,
			'label'  => 'performs redirects',
			're'     => array(
				'/\bwp_redirect\s*\(|\bwp_safe_redirect\s*\(/i',
				'/header\s*\(\s*["\']\s*(?:HTTP\/[\d.]+\s*)?Location\s*:/i',
				'/\bheader\s*\(\s*["\']\s*Refresh\s*:/i',
				'/<meta[^>]+http-equiv\s*=\s*["\']?refresh/i',
			),
		),
		'js_redirect'            => array(
			'weight' => 1,
			'label'  => 'client-side (JavaScript) redirect',
			're'     => array(
				'/location\.href\s*=|location\.replace\s*\(|location\.assign\s*\(|window\.location\s*=/i',
				'/top\.location|document\.location/i',
			),
		),
		'iframe_injection'       => array(
			'weight' => 2,
			'label'  => 'injects external iframe content',
			're'     => array(
				'/<iframe[^>]+src\s*=\s*["\']?(?:https?:)?\/\//i',
			),
		),
		'write_files'            => array(
			'weight' => 1,
			'label'  => 'writes files',
			're'     => array(
				'/\bfile_put_contents\s*\(|\bfwrite\s*\(|\bfopen\s*\(\s*[^,]+,\s*["\'](?:w|a|c|x)/i',
				'/\bfile_put_contents\s*\(|\bfwrite\s*\(/i',
			),
		),
		'file_replace'           => array(
			'weight' => 2,
			'label'  => 'replaces or renames files',
			're'     => array(
				'/\brename\s*\(\s*|\bcopy\s*\(\s*|\bunlink\s*\(\s*/i',
			),
		),
		'chmod_lock'             => array(
			'weight' => 3,
			'label'  => 'changes file permissions (possible read-only persistence)',
			're'     => array(
				'/\bchmod\s*\(\s*[^,]+,\s*(?:0?444|0444|292|["\']0?444["\'])/i',
				'/\bchmod\s*\(\s*[^,]+,\s*(?:0?555|0555|["\']0?555["\'])/i',
			),
		),
		'existence_check'        => array(
			'weight' => 1,
			'label'  => 'checks for file existence (restore gate)',
			're'     => array(
				'/\bfile_exists\s*\(|\bis_file\s*\(|\bis_readable\s*\(/i',
			),
		),
		'obfuscation'            => array(
			'weight' => 2,
			'label'  => 'obfuscated payload',
			're'     => array(
				'/\beval\s*\(|\bassert\s*\(\s*["\']|\bcreate_function\s*\(|\bpreg_replace\s*\([^)]*[\'"]\/.{1,8}e[\'"]\s*,/i',
				'/\bbase64_decode\s*\(|\bgzinflate\s*\(|\bgzuncompress\s*\(|\bgzdecode\s*\(|\bstr_rot13\s*\(|\bhex2bin\s*\(/i',
				'/\\\x[0-9a-f]{2}(?:\\\x[0-9a-f]{2}){5,}/i',
				'/\bchr\s*\(\s*\d+\s*\)\s*\.\s*(?:\bchr\s*\(\s*\d+\s*\)\s*\.\s*){4,}/i',
			),
		),
		'shell_exec'             => array(
			'weight' => 3,
			'label'  => 'process-execution functions',
			're'     => array(
				'/\bsystem\s*\(|(?<!->)(?<!::)\bshell_exec\s*\(|(?<!->)(?<!::)\bpassthru\s*\(|(?<!->)(?<!::)\bproc_open\s*\(|(?<!->)(?<!::)\bpopen\s*\(|(?<!->)(?<!::)\bpcntl_exec\s*\(|(?<!->)(?<!::)\bexec\s*\(/i',
			),
		),
		'request_input'          => array(
			'weight' => 1,
			'label'  => 'reads request input',
			're'     => array(
				'/\$_GET\b|\$_POST\b|\$_REQUEST\b|\$_COOKIE\b|\$_SERVER\s*\[/i',
				'/php:\/\/input|\bfile_get_contents\s*\(\s*["\']php:\/\/input/i',
			),
		),
		'dynamic_include'        => array(
			'weight' => 2,
			'label'  => 'dynamically includes files',
			're'     => array(
				'/\b(?:include|include_once|require|require_once)\b\s*\(?\s*\$/i',
				'/\b(?:include|include_once|require|require_once)\b\s*\(?\s*["\'][^"\']*["\']\s*\./i',
			),
		),
		'tls_bypass'             => array(
			'weight' => 2,
			'label'  => 'disables TLS certificate verification',
			're'     => array(
				// Matches both array syntax (CURLOPT_SSL_VERIFYPEER => false)
				// and the classic call form (curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false)).
				'/CURLOPT_SSL_VERIFYPEER\s*(?:=>|,)\s*(?:false|FALSE|0)\b/i',
				'/CURLOPT_SSL_VERIFYHOST\s*(?:=>|,)\s*(?:false|FALSE|0)\b/i',
				'/["\']verify["\']\s*=>\s*(?:false|FALSE)\b/i',
			),
		),
		'wp_config_write'        => array(
			'weight' => 3,
			'label'  => 'touches wp-config.php',
			're'     => array(
				'/wp-config\.php/i',
			),
		),
		'htaccess_write'         => array(
			'weight' => 2,
			'label'  => 'touches .htaccess',
			're'     => array(
				'/\.htaccess/i',
			),
		),
		'hidden_backup'          => array(
			'weight' => 2,
			'label'  => 'references hidden backup/dot files',
			're'     => array(
				'/["\'][.\/]*\.[A-Za-z0-9_-]{1,32}\.(?:bak|backup|old|orig|save|php)["\']/i',
				'/\.bak\.php|\.backup\.php|\.old\.php|\.orig\.php/i',
			),
		),
		'index_write'            => array(
			'weight' => 3,
			'label'  => 'targets the WordPress root index.php',
			're'     => array(
				'/ABSPATH\s*\.\s*["\']\/{0,1}index\.php["\']|["\']index\.php["\']|\/index\.php/i',
			),
		),
		'mu_plugin_write'        => array(
			'weight' => 3,
			'label'  => 'writes into the must-use plugin directory',
			're'     => array(
				'/WPMU_PLUGIN_DIR|mu-plugins/i',
			),
		),
		'wp_bootstrap'           => array(
			'weight' => 2,
			'label'  => 'loads the WordPress bootstrap manually',
			're'     => array(
				'/wp-load\.php|wp-blog-header\.php/i',
			),
		),
	);
}

/**
 * Known incident indicators (supplemental, never primary).
 *
 * These strings come from a real sanitized incident; they are matched as
 * supplemental evidence only. Behavioral detection remains the primary system.
 *
 * @return array
 */
function gwqsh_guardian_core_known_indicators() {
	return array(
		'ms_gate_heal_restore',
		'ms_panel_self_heal',
		'fm_panel_self_heal_webroot',
		'X-Medusa-Mu-Gate',
		'.ms_index.bak.php',
		'.ms_htaccess.bak',
		'.ms_gate_kontrol_ok.json',
		'ist-dest.txt',
	);
}

/**
 * Fast indicator scan over source text.
 *
 * @param string $code      PHP source text (never executed).
 * @param bool   $collect_lines Whether to capture matching line numbers.
 * @return array key => array( 'label' => string, 'lines' => int[] )
 */
function gwqsh_guardian_core_scan_indicators( $code, $collect_lines = true ) {
	$indicators = array();
	if ( ! is_string( $code ) || '' === $code ) {
		return $indicators;
	}
	$lines = null;
	foreach ( gwqsh_guardian_core_indicator_patterns() as $key => $spec ) {
		$matched_lines = array();
		foreach ( $spec['re'] as $pattern ) {
			$matches = array();
			if ( ! preg_match_all( $pattern, $code, $matches, PREG_OFFSET_CAPTURE ) ) {
				continue;
			}
			if ( $collect_lines ) {
				foreach ( $matches[0] as $match ) {
					$matched_lines[] = gwqsh_guardian_core_line_at( $code, $match[1] );
				}
			} else {
				$matched_lines[] = 1;
			}
		}
		if ( $matched_lines ) {
			$indicators[ $key ] = array(
				'label' => $spec['label'],
				'lines' => $collect_lines ? array_values( array_unique( $matched_lines ) ) : array(),
				'count' => count( $matched_lines ),
			);
		}
	}
	return $indicators;
}

/**
 * Line number for a byte offset (1-based).
 *
 * @param string $code   Source.
 * @param int    $offset Byte offset.
 * @return int
 */
function gwqsh_guardian_core_line_at( $code, $offset ) {
	if ( $offset <= 0 ) {
		return 1;
	}
	$before = substr( $code, 0, $offset );
	$line   = substr_count( $before, "\n" ) + 1;
	return $line;
}

/**
 * Match known incident indicators (supplemental evidence).
 *
 * @param string $code Source.
 * @return array matched indicator => line number.
 */
function gwqsh_guardian_core_known_indicator_matches( $code ) {
	$matches = array();
	if ( ! is_string( $code ) ) {
		return $matches;
	}
	foreach ( gwqsh_guardian_core_known_indicators() as $needle ) {
		$pos = stripos( $code, $needle );
		if ( false !== $pos ) {
			$matches[ $needle ] = gwqsh_guardian_core_line_at( $code, $pos );
		}
	}
	return $matches;
}

/**
 * Extract external URLs referenced in source (bounded).
 *
 * @param string $code Source.
 * @param int    $limit Maximum URLs returned.
 * @return array
 */
function gwqsh_guardian_core_external_urls( $code, $limit = 10 ) {
	$urls = array();
	if ( ! is_string( $code ) ) {
		return $urls;
	}
	if ( preg_match_all( '#https?://[A-Za-z0-9.\-]+[A-Za-z0-9]{2}(?::\d+)?(?:/[^\s"\']{0,120})?#i', $code, $m ) ) {
		foreach ( array_slice( $m[0], 0, $limit ) as $url ) {
			$urls[] = $url;
		}
	}
	return $urls;
}

/**
 * Score analyzed indicators into a confidence verdict.
 *
 * Combinations matter: single weak indicators stay informational, while
 * combinations that characterize real incident behavior (cloaking, self-heal,
 * web shell) escalate aggressively.
 *
 * @param array $indicators Result of gwqsh_guardian_core_scan_indicators().
 * @param array $context    Optional context: 'component' (mu|plugin|theme|uploads|root), 'known_matches'.
 * @return array array( 'score', 'confidence', 'reasons' )
 */
function gwqsh_guardian_core_score( $indicators, $context = array() ) {
	$score  = 0;
	$reason = array();

	$has = static function ( $key ) use ( $indicators ) {
		return isset( $indicators[ $key ] );
	};

	// 1. Cloaking / traffic hijacking combination.
	$cloak = 0;
	if ( $has( 'bot_detection' ) && ( $has( 'user_agent_probe' ) || $has( 'request_input' ) ) ) {
		$cloak += 10;
		$reason[] = 'bot detection paired with visitor/request inspection';
	}
	if ( $cloak && ( $has( 'external_redirect' ) || $has( 'js_redirect' ) || $has( 'remote_fetch' ) || $has( 'iframe_injection' ) ) ) {
		$cloak += 14;
		$reason[] = 'bot detection combined with redirects or remote content delivery';
	}
	if ( $has( 'hook_template_redirect' ) && $cloak >= 10 ) {
		$cloak += 4;
		$reason[] = 'runs on the template_redirect hook (front-end response control)';
	}
	if ( $has( 'mobile_detect' ) && ( $has( 'js_redirect' ) || $has( 'external_redirect' ) ) ) {
		$cloak += 3;
		$reason[] = 'mobile-specific redirect logic';
	}
	$score += $cloak;

	// 2. Self-healing persistence combination.
	$heal = 0;
	$write_critical = $has( 'index_write' ) || $has( 'wp_config_write' ) || $has( 'htaccess_write' ) || $has( 'mu_plugin_write' );
	if ( $write_critical && $has( 'write_files' ) ) {
		$heal += 10;
		$reason[] = 'writes to critical WordPress files';
	}
	if ( $heal && $has( 'existence_check' ) ) {
		$heal += 5;
		$reason[] = 'checks whether a target file exists before writing (restore gate)';
	}
	if ( $heal && $has( 'hidden_backup' ) ) {
		$heal += 8;
		$reason[] = 'references hidden backup files';
	}
	if ( $heal && $has( 'chmod_lock' ) ) {
		$heal += 8;
		$reason[] = 'makes restored files read-only via chmod';
	}
	if ( $heal && $has( 'file_replace' ) ) {
		$heal += 3;
		$reason[] = 'renames, copies or deletes files as part of the repair logic';
	}
	$score += $heal;

	// 3. Remote payload behavior.
	$remote = 0;
	if ( $has( 'remote_fetch' ) ) {
		$remote += 3;
	}
	if ( $has( 'remote_fetch' ) && $has( 'tls_bypass' ) ) {
		$remote += 5;
		$reason[] = 'retrieves remote content with TLS verification disabled';
	}
	if ( $has( 'remote_fetch' ) && $has( 'obfuscation' ) ) {
		$remote += 4;
		$reason[] = 'remote content combined with obfuscated payload handling';
	}
	if ( $has( 'remote_fetch' ) && $has( 'dynamic_include' ) ) {
		$remote += 5;
		$reason[] = 'remote content feeding dynamic includes';
	}
	if ( $has( 'remote_fetch' ) && $has( 'write_files' ) ) {
		$remote += 3;
		$reason[] = 'writes fetched remote content to the filesystem';
	}
	$score += $remote;

	// 4. Web shell behavior.
	$shell = 0;
	if ( $has( 'shell_exec' ) ) {
		$shell += 4;
	}
	if ( $has( 'shell_exec' ) && $has( 'request_input' ) ) {
		$shell += 14;
		$reason[] = 'request-controlled input reaching process-execution functions';
	}
	if ( $has( 'dynamic_include' ) && $has( 'request_input' ) ) {
		$shell += 8;
		$reason[] = 'request-controlled dynamic file inclusion';
	}
	if ( $has( 'wp_bootstrap' ) ) {
		$shell += 3;
		$reason[] = 'manually loads the WordPress bootstrap';
	}
	$score += $shell;

	// 5. Standalone weaker signals.
	if ( $has( 'obfuscation' ) && ! $remote && ! $shell ) {
		$score += 2;
		$reason[] = 'obfuscated code sequences present';
	}
	if ( $has( 'iframe_injection' ) && ! $cloak ) {
		$score += 3;
		$reason[] = 'external iframe markup present';
	}
	if ( $has( 'wp_config_write' ) && $has( 'write_files' ) ) {
		$score += 2; // Counted once more when not part of the heal combo.
	}

	// 6. Known incident strings: supplemental boost only.
	$known = isset( $context['known_matches'] ) && is_array( $context['known_matches'] ) ? $context['known_matches'] : array();
	if ( $known ) {
		$score  += min( 10, 4 + 2 * count( $known ) );
		$reason[] = 'contains known incident indicators: ' . implode( ', ', array_slice( array_keys( $known ), 0, 4 ) );
	}

	// 7. Location context: must-use plugins that execute attack behavior are
	// higher risk; uploads carrying executable code likewise.
	$component = isset( $context['component'] ) ? $context['component'] : '';
	if ( 'mu' === $component && $score >= 10 ) {
		$score  += 4;
		$reason[] = 'executes from the must-use plugin directory (loads before normal plugins)';
	}
	if ( 'uploads' === $component && $score >= 6 ) {
		$score  += 4;
		$reason[] = 'executable code found inside the uploads directory';
	}

	$confidence = 'clean';
	if ( $score >= 30 ) {
		$confidence = 'critical';
	} elseif ( $score >= 18 ) {
		$confidence = 'high';
	} elseif ( $score >= 10 ) {
		$confidence = 'medium';
	} elseif ( $score > 0 ) {
		$confidence = 'low';
	}

	return array(
		'score'      => $score,
		'confidence' => $confidence,
		'reasons'    => $reason,
	);
}

/**
 * The Guardian only auto-quarantines on extremely high confidence: a strong
 * behavioral combination that matches real incident classes, not a bare score.
 *
 * @param array $indicators Indicator array.
 * @param array $context    Context passed to gwqsh_guardian_core_score().
 * @return bool
 */
function gwqsh_guardian_core_should_neutralize( $indicators, $context = array() ) {
	$verdict = gwqsh_guardian_core_score( $indicators, $context );
	if ( 'critical' !== $verdict['confidence'] || $verdict['score'] < 34 ) {
		return false;
	}
	$has = static function ( $key ) use ( $indicators ) {
		return isset( $indicators[ $key ] );
	};
	// Cloaking/traffic-hijack class.
	$cloak_class = $has( 'bot_detection' )
		&& ( $has( 'user_agent_probe' ) || $has( 'request_input' ) )
		&& ( $has( 'external_redirect' ) || $has( 'js_redirect' ) || $has( 'remote_fetch' ) || $has( 'iframe_injection' ) );
	// Self-healing persistence class.
	$heal_class = ( $has( 'index_write' ) || $has( 'wp_config_write' ) || $has( 'htaccess_write' ) || $has( 'mu_plugin_write' ) )
		&& $has( 'write_files' )
		&& ( $has( 'hidden_backup' ) || $has( 'chmod_lock' ) || $has( 'existence_check' ) );
	// Web shell class.
	$shell_class = $has( 'shell_exec' ) && $has( 'request_input' );

	if ( $cloak_class || $heal_class || $shell_class ) {
		return true;
	}
	// Known-bad strings with strong behavioral corroboration.
	$known = isset( $context['known_matches'] ) && is_array( $context['known_matches'] ) ? $context['known_matches'] : array();
	if ( $known && $verdict['score'] >= 40 ) {
		return true;
	}
	return false;
}

/**
 * Read and hash a file (bounded), returning source + digest.
 *
 * @param string $path Absolute file path (caller-validated).
 * @param int    $max_bytes Read cap.
 * @return array|null array( 'code', 'sha256', 'size', 'truncated' ) or null on failure.
 */
function gwqsh_guardian_core_read_file( $path, $max_bytes = 0 ) {
	if ( ! is_string( $path ) || '' === $path || ! is_file( $path ) || is_link( $path ) ) {
		return null;
	}
	$size = filesize( $path );
	if ( false === $size ) {
		return null;
	}
	if ( 0 >= $max_bytes ) {
		$max_bytes = GWQSH_GUARDIAN_CORE_MAX_BYTES;
	}
	$handle = fopen( $path, 'rb' );
	if ( ! $handle ) {
		return null;
	}
	$to_read   = min( $size, $max_bytes );
	$chunk     = 65536;
	$buffer    = '';
	$remaining = $to_read;
	while ( $remaining > 0 && ! feof( $handle ) ) {
		$part = fread( $handle, min( $chunk, $remaining ) );
		if ( false === $part || '' === $part ) {
			break;
		}
		$buffer    .= $part;
		$remaining -= strlen( $part );
	}
	fclose( $handle );
	return array(
		'code'     => $buffer,
		'size'     => $size,
		'truncated' => $size > strlen( $buffer ),
		'sha256'   => hash( 'sha256', $buffer ),
	);
}

/**
 * Pure string-based path containment check (no filesystem access).
 *
 * Both paths are normalized to forward slashes without resolving symlinks;
 * callers must still run a realpath() verification before any write action.
 *
 * @param string $child Candidate path.
 * @param string $root  Root directory.
 * @return bool
 */
function gwqsh_guardian_core_path_within( $child, $root ) {
	if ( ! is_string( $child ) || ! is_string( $root ) || '' === $child || '' === $root ) {
		return false;
	}
	$child = str_replace( '\\', '/', $child );
	$root  = rtrim( str_replace( '\\', '/', $root ), '/' );
	if ( false !== strpos( $child, "\0" ) ) {
		return false;
	}
	// Collapse redundant separators and resolve . / .. lexically.
	$segments = explode( '/', $child );
	$out      = array();
	foreach ( $segments as $segment ) {
		if ( '' === $segment || '.' === $segment ) {
			continue;
		}
		if ( '..' === $segment ) {
			if ( empty( $out ) ) {
				return false;
			}
			array_pop( $out );
			continue;
		}
		$out[] = $segment;
	}
	$normalized = implode( '/', $out );
	$root_out   = trim( implode( '/', array_filter( explode( '/', $root ), static function ( $s ) {
		return '' !== $s && '.' !== $s;
	} ) ), '/' );
	if ( '' === $normalized || '' === $root_out ) {
		return false;
	}
	return 0 === strpos( $normalized, $root_out . '/' ) || $normalized === $root_out;
}

/**
 * Unpredictable quarantine-safe filename.
 *
 * @return string 32 hex chars + .quarantine
 */
function gwqsh_guardian_core_quarantine_name() {
	if ( function_exists( 'random_bytes' ) ) {
		try {
			return bin2hex( random_bytes( 16 ) ) . '.quarantine';
		} catch ( Exception $e ) {
			// Fall through to the fallback below.
		}
	}
	return hash( 'sha256', uniqid( 'gwqsh', true ) . microtime() . mt_rand() ) . '.quarantine';
}
