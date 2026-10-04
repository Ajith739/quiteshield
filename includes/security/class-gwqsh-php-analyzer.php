<?php
/**
 * Token-based behavioral PHP analyzer for Gracewell QuietShield.
 *
 * Performs static, read-only analysis of PHP source using the tokenizer.
 * It NEVER includes, requires, evaluates or executes analyzed code; every
 * method operates on source text and token arrays only.
 *
 * The analyzer produces a structured evidence map that detection rules and the
 * scoring engine consume to explain WHY a file is dangerous.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * GWQSH PHP Analyzer implementation.
 */
final class GWQSH_PHP_Analyzer {

	/**
	 * Files larger than this are analyzed only up to the cap (recorded).
	 */
	const MAX_ANALYZE_BYTES = 1048576; // 1 MiB.

	/**
	 * Hard cap on tokens; beyond this the token pass degrades to a prefix scan.
	 */
	const MAX_TOKENS = 240000;

	/**
	 * Dangerous process-execution functions.
	 *
	 * @var array
	 */
	private static $shell_functions = array( 'system', 'exec', 'shell_exec', 'passthru', 'proc_open', 'popen', 'pcntl_exec', 'pcntl_fork' );

	/**
	 * Remote-network functions.
	 *
	 * @var array
	 */
	private static $remote_functions = array( 'file_get_contents', 'fopen', 'fsockopen', 'stream_socket_client', 'curl_init', 'curl_exec', 'wp_remote_get', 'wp_remote_post', 'wp_remote_request', 'get_headers', 'dns_get_record', 'readfile', 'file' );

	/**
	 * Redirect functions.
	 *
	 * @var array
	 */
	private static $redirect_functions = array( 'wp_redirect', 'wp_safe_redirect', 'wp_logout', 'header' );

	/**
	 * File-mutation functions.
	 *
	 * @var array
	 */
	private static $write_functions = array( 'file_put_contents', 'fwrite', 'fputcsv', 'ftruncate', 'touch', 'rename', 'copy', 'unlink', 'rmdir', 'mkdir', 'symlink', 'link', 'file_put_contents' );

	/**
	 * WordPress-recognized critical write targets.
	 *
	 * @var array
	 */
	private static $critical_targets = array(
		'index.php',
		'.htaccess',
		'wp-config.php',
		'wp-blog-header.php',
		'wp-load.php',
		'wp-settings.php',
		'.user.ini',
		'php.ini',
	);

	/**
	 * Analyze a PHP file from disk (never executed).
	 *
	 * @param string $absolute_path Absolute path (validated by Path Guard).
	 * @param array  $context       Optional: component, file_name, relative.
	 * @return array|null Analysis map or null when unreadable.
	 */
	public static function analyze_file( $absolute_path, $context = array() ) {
		if ( ! is_string( $absolute_path ) || ! is_file( $absolute_path ) || is_link( $absolute_path ) ) {
			return null;
		}
		$size = filesize( $absolute_path );
		if ( false === $size ) {
			return null;
		}
		$read = gwqsh_guardian_core_read_file( $absolute_path, self::MAX_ANALYZE_BYTES );
		if ( ! is_array( $read ) ) {
			return null;
		}
		$analysis               = self::analyze_code( $read['code'], $context );
		$analysis['size']       = $size;
		$analysis['truncated']  = $read['truncated'];
		$analysis['sha256']     = $read['sha256'];
		$analysis['file']       = isset( $context['file_name'] ) ? $context['file_name'] : basename( $absolute_path );
		return $analysis;
	}

	/**
	 * Analyze raw PHP source (never executed).
	 *
	 * @param string $code    Source code.
	 * @param array  $context Optional context flags.
	 * @return array
	 */
	public static function analyze_code( $code, $context = array() ) {
		$analysis = array(
			'bytes'          => is_string( $code ) ? strlen( $code ) : 0,
			'lines'          => is_string( $code ) ? substr_count( $code, "\n" ) + 1 : 0,
			'tokens'         => 0,
			'truncated'      => false,
			'calls'          => array(),      // name => array( array( line, args, arg_vars ), ... ).
			'hooks'          => array(),      // hook name => line.
			'strings'        => array(),      // collected significant string literals.
			'taint'          => array(),      // variable name => array( origin, line ).
			'taint_sinks'    => array(),      // sink descriptions.
			'backtick_shell' => array(),      // lines containing `...` execution.
			'variable_calls' => array(),      // $fn( ... ) dynamic calls.
			'indicators'     => array(),      // fast indicator map (kernel).
			'external_urls'  => array(),
			'known_matches'  => array(),
			'write_targets'  => array(),      // per-write-call target classification.
			'errors'         => array(),
		);
		if ( ! is_string( $code ) || '' === $code ) {
			return $analysis;
		}

		// Fast kernel indicators first (shared with the Early Guardian).
		$analysis['indicators']    = gwqsh_guardian_core_scan_indicators( $code );
		$analysis['external_urls'] = gwqsh_guardian_core_external_urls( $code );
		$analysis['known_matches'] = gwqsh_guardian_core_known_indicator_matches( $code );

		// Token pass.
		$tokens = @token_get_all( $code ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- Analyzing untrusted source defensively.
		if ( ! is_array( $tokens ) ) {
			$analysis['errors'][] = 'tokenizer failure';
			return $analysis;
		}
		$analysis['tokens'] = count( $tokens );
		if ( count( $tokens ) > self::MAX_TOKENS ) {
			$tokens             = array_slice( $tokens, 0, self::MAX_TOKENS );
			$analysis['truncated'] = true;
			$analysis['errors'][] = 'token cap reached; partial analysis';
		}

		self::collect_from_tokens( $tokens, $analysis );

		return $analysis;
	}

	/**
	 * Walk tokens collecting calls, hooks, strings and taint.
	 *
	 * @param array $tokens   Token stream.
	 * @param array &$analysis Analysis map (modified).
	 */
	private static function collect_from_tokens( $tokens, &$analysis ) {
		$count    = count( $tokens );
		$prev_sig = ''; // Previous significant token id (or 'var:name').
		$line     = 0;

		for ( $i = 0; $i < $count; $i++ ) {
			$token = $tokens[ $i ];
			if ( is_array( $token ) ) {
				$id    = $token[0];
				$text  = $token[1];
				$line  = $token[2];

				if ( T_STRING === $id ) {
					$name = strtolower( $text );
					// Function call? Look ahead for '(' while skipping whitespace/comments.
					$j = $i + 1;
					while ( $j < $count && is_array( $tokens[ $j ] ) && in_array( $tokens[ $j ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
						++$j;
					}
					$is_call = ( $j < $count && '(' === $tokens[ $j ] );
					// Skip function declarations: "function name(".
					$is_declaration = ( T_FUNCTION === $prev_sig );

				if ( $is_call && ! $is_declaration ) {
					$arg_info = self::capture_arguments( $tokens, $j, $count, $analysis );
						$i        = $arg_info['next'];
						$args     = $arg_info['text'];
						$arg_vars = $arg_info['vars'];

						if ( ! isset( $analysis['calls'][ $name ] ) ) {
							$analysis['calls'][ $name ] = array();
						}
						$analysis['calls'][ $name ][] = array(
							'line'     => $line,
							'args'     => $args,
							'arg_vars' => $arg_vars,
						);

						// Hook registration.
						if ( ( 'add_action' === $name || 'add_filter' === $name ) && preg_match( '/^[\s]*(["\'])([^"\']+)\1/', $args, $hook_match ) ) {
							$analysis['hooks'][ $hook_match[2] ] = $line;
						}

						// Taint sinks: dangerous calls receiving tainted variables.
						self::record_taint_sinks( $name, $args, $arg_vars, $line, $analysis );
					}
					$prev_sig = 'string:' . $name;
					continue;
				}

				if ( T_CONSTANT_ENCAPSED_STRING === $id || T_ENCAPSED_AND_WHITESPACE === $id ) {
					self::collect_string( $text, $line, $analysis );
					$prev_sig = $id;
					continue;
				}

				if ( T_VARIABLE === $id ) {
					$var_name = $text;

					// Assignment from a superglobal? $x = $_GET[...]
					$k = $i + 1;
					while ( $k < $count && is_array( $tokens[ $k ] ) && in_array( $tokens[ $k ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
						++$k;
					}
					$is_assign = ( $k < $count && in_array( $tokens[ $k ], array( '=', T_CONCAT_EQUAL, T_DOUBLE_ARROW ), true ) );
					// Accept '=' only (simple local assignments; '==' comparison is skipped below).
					if ( $k < $count && '=' === $tokens[ $k ] && ( $k + 1 >= $count || '=' !== $tokens[ $k + 1 ] ) ) {
						// Find the RHS start.
						$r = $k + 1;
						while ( $r < $count && is_array( $tokens[ $r ] ) && in_array( $tokens[ $r ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
							++$r;
						}
						if ( $r < $count && is_array( $tokens[ $r ] ) && T_VARIABLE === $tokens[ $r ][0] && preg_match( '/^_(GET|POST|REQUEST|COOKIE|SERVER)$/', $tokens[ $r ][1] ) ) {
							if ( ! isset( $analysis['taint'][ $var_name ] ) ) {
								$analysis['taint'][ $var_name ] = array( 'origin' => $tokens[ $r ][1], 'line' => $line );
							}
						}
					}

					// Variable function call: $fn( ... ).
					$v = $i + 1;
					while ( $v < $count && is_array( $tokens[ $v ] ) && in_array( $tokens[ $v ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
						++$v;
					}
							if ( $v < $count && '(' === $tokens[ $v ] && 'string:array(' !== $prev_sig && T_ARRAY !== $prev_sig ) {
								$arg_info = self::capture_arguments( $tokens, $v, $count, $analysis );
						$i        = $arg_info['next'];
						$analysis['variable_calls'][] = array(
							'line' => $line,
							'var'  => $var_name,
							'args' => $arg_info['text'],
						);
						self::record_taint_sinks( '$' . $var_name, $arg_info['text'], $arg_info['vars'], $line, $analysis );
					}

					$prev_sig = 'var:' . $var_name;
					continue;
				}

				if ( T_INCLUDE === $id || T_INCLUDE_ONCE === $id || T_REQUIRE === $id || T_REQUIRE_ONCE === $id ) {
					// Capture the included expression.
					$expr = '';
					$line2 = $line;
					for ( $e = $i + 1; $e < $count && strlen( $expr ) < 300; $e++ ) {
						$tok = $tokens[ $e ];
						if ( is_array( $tok ) ) {
							if ( T_WHITESPACE === $tok[0] ) {
								continue;
							}
							if ( in_array( $tok[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
								continue;
							}
							$expr .= $tok[1];
						} elseif ( ';' === $tok ) {
							break;
						} else {
							$expr .= $tok;
						}
					}
					$dynamic = (bool) preg_match( '/(?:^|[^$]\$|["\']\s*\.|\{\s*\$)/', $expr ) && (bool) preg_match( '/\$[A-Za-z_]/', $expr );
					$analysis['calls'][ $dynamic ? '__dynamic_include' : '__static_include' ][] = array(
						'line'     => $line2,
						'args'     => $expr,
						'arg_vars' => self::vars_in_expr( $expr ),
					);
					$prev_sig = 'include';
					continue;
				}

				$prev_sig = $id;
				continue;
			}

			// Simple character token.
			if ( '(' === $token ) {
				$prev_sig = 'char:(';
				continue;
			}
			if ( '`' === $token ) {
				// Shell-execution backtick OUTSIDE comments and strings (a raw
				// regex over the source would flag Markdown-style backticks in
				// comments, which modern PHP code uses constantly).
				$analysis['backtick_shell'][] = (int) $line;
				continue;
			}
			$prev_sig = 'char:' . $token;
		}
	}

	/**
	 * Capture the argument text for a call whose '(' is at index $open.
	 *
	 * @param array $tokens   Token stream.
	 * @param int   $open     Index of the opening parenthesis.
	 * @param int   $count    Token count.
	 * @param array &$analysis Analysis map (argument string literals are collected).
	 * @return array array( 'text' => string, 'vars' => array, 'next' => int )
	 */
	private static function capture_arguments( $tokens, $open, $count, &$analysis ) {
		$depth = 0;
		$text  = '';
		$vars  = array();
		for ( $i = $open; $i < $count && strlen( $text ) < 600; $i++ ) {
			$token = $tokens[ $i ];
			if ( '(' === $token ) {
				++$depth;
				if ( $depth > 1 ) {
					$text .= '(';
				}
				continue;
			}
			if ( ')' === $token ) {
				--$depth;
				if ( 0 === $depth ) {
					return array(
						'text' => trim( $text ),
						'vars' => $vars,
						'next' => $i,
					);
				}
				$text .= ')';
				continue;
			}
			if ( is_array( $token ) ) {
				if ( T_FUNCTION === $token[0] || ( defined( 'T_FN' ) && T_FN === $token[0] ) ) {
					// A closure or arrow-function argument: stop absorbing and
					// hand control back so the main walker processes the callback
					// body itself (calls inside anonymous callbacks must be
					// recorded, and malware favors this style).
					return array(
						'text' => trim( $text ),
						'vars' => $vars,
						'next' => $i - 1,
					);
				}
				if ( T_WHITESPACE === $token[0] ) {
					$text .= ' ';
					continue;
				}
				if ( in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
					continue;
				}
				if ( T_CONSTANT_ENCAPSED_STRING === $token[0] ) {
					// Argument literals were previously invisible to string-based
					// rules because the call handler jumps past them; collect them
					// here so behavioral evidence is never lost.
					self::collect_string( $token[1], (int) $token[2], $analysis );
				}
				if ( T_VARIABLE === $token[0] ) {
					$vars[] = $token[1];
				}
				$text .= $token[1];
				continue;
			}
			$text .= $token;
		}
		return array(
			'text' => trim( $text ),
			'vars' => $vars,
			'next' => $open,
		);
	}

	/**
	 * Extract variable names from an expression string.
	 *
	 * @param string $expr Expression text.
	 * @return array
	 */
	private static function vars_in_expr( $expr ) {
		if ( preg_match_all( '/\$[A-Za-z_][A-Za-z0-9_]*/', $expr, $m ) ) {
			return array_values( array_unique( $m[0] ) );
		}
		return array();
	}

	/**
	 * Record taint sinks: tainted variables reaching dangerous calls.
	 *
	 * @param string $name     Called function name (or $var form).
	 * @param string $args     Argument text.
	 * @param array  $arg_vars Variables in the call.
	 * @param int    $line     Line number.
	 * @param array  &$analysis Analysis map.
	 */
	private static function record_taint_sinks( $name, $args, $arg_vars, $line, &$analysis ) {
		if ( empty( $analysis['taint'] ) || empty( $arg_vars ) ) {
			return;
		}
		$dangerous = array();
		if ( in_array( $name, self::$shell_functions, true ) ) {
			$dangerous[] = 'process execution';
		}
		if ( in_array( $name, self::$write_functions, true ) ) {
			$dangerous[] = 'filesystem write';
		}
		if ( in_array( $name, array( 'eval', 'assert', 'create_function', 'call_user_func', 'call_user_func_array' ), true ) ) {
			$dangerous[] = 'dynamic code execution';
		}
		if ( 0 === strpos( $name, '$' ) ) {
			$dangerous[] = 'dynamic function call';
		}
		if ( '__dynamic_include' === $name ) {
			$dangerous[] = 'dynamic include';
		}
		if ( in_array( $name, array( 'wp_redirect', 'wp_safe_redirect', 'header' ), true ) && preg_match( '/location/i', $args ) ) {
			$dangerous[] = 'redirect';
		}
		if ( ! $dangerous ) {
			return;
		}
		foreach ( $arg_vars as $var ) {
			if ( isset( $analysis['taint'][ $var ] ) ) {
				$analysis['taint_sinks'][] = array(
					'line'   => $line,
					'call'   => $name,
					'var'    => $var,
					'origin' => $analysis['taint'][ $var ]['origin'],
					'kind'   => implode( ', ', $dangerous ),
				);
				return; // One sink per call is enough evidence.
			}
		}
	}

	/**
	 * Collect significant string literals (bounded).
	 *
	 * @param string $text      String token text.
	 * @param int    $line      Line number.
	 * @param array  &$analysis Analysis map.
	 */
	private static function collect_string( $text, $line, &$analysis ) {
		if ( strlen( $text ) < 4 || count( $analysis['strings'] ) > 4000 ) {
			return;
		}
		$analysis['strings'][] = array( 'text' => $text, 'line' => $line );
	}

	/**
	 * Convenience: does the analysis contain any call to a name?
	 *
	 * @param array  $analysis Analysis.
	 * @param string $name     Lowercased function name.
	 * @return bool
	 */
	public static function has_call( $analysis, $name ) {
		return ! empty( $analysis['calls'][ strtolower( $name ) ] );
	}

	/**
	 * Lines where a function is called.
	 *
	 * @param array  $analysis Analysis.
	 * @param string $name     Lowercased function name.
	 * @return array
	 */
	public static function call_lines( $analysis, $name ) {
		$name = strtolower( $name );
		if ( empty( $analysis['calls'][ $name ] ) ) {
			return array();
		}
		$lines = array();
		foreach ( $analysis['calls'][ $name ] as $call ) {
			$lines[] = (int) $call['line'];
		}
		return $lines;
	}

	/**
	 * Classify a write/rename/copy target argument.
	 *
	 * Returns a structured verdict used by the critical-write rules:
	 * - root_index: ABSPATH/index.php style targets
	 * - htaccess: .htaccess anywhere in webroot
	 * - wpconfig: wp-config.php
	 * - mu_plugins: inside the must-use directory
	 * - wp_core: wp-admin / wp-includes files
	 * - plugin_other / theme_other: another plugin or theme
	 * - hidden: dot files / backup-like names
	 * - other: everything else
	 *
	 * @param string $args Argument text of the write call.
	 * @return array array( 'class' => string, 'detail' => string )
	 */
	public static function classify_write_target( $args ) {
		$args = (string) $args;
		if ( '' === $args ) {
			return array( 'class' => 'unknown', 'detail' => '' );
		}
		$lower = strtolower( $args );

		// Hidden / backup-style filenames.
		if ( preg_match( '/[\'"][.\/]*\.[A-Za-z0-9_\-]{1,40}\.(?:bak|backup|old|orig|save|php|dat|txt)[\'"]|\.bak\.php|\.backup\.php|\.old\.php|\.orig\.php/', $lower ) ) {
			return array( 'class' => 'hidden', 'detail' => $args );
		}
		// wp-config.php.
		if ( false !== strpos( $lower, 'wp-config' ) ) {
			return array( 'class' => 'wpconfig', 'detail' => $args );
		}
		// Webroot index.php / .htaccess: only explicitly webroot-rooted
		// writes count. A name CONCATENATED after another path segment
		// (e.g. SOMEDIR . 'index.php') is a subdirectory guard file written
		// by legitimate plugins, not the webroot file.
		$is_root_ctx = (bool) preg_match( '/abspath|home_path|document_root|site_root/i', $lower );
		$is_concat    = (bool) preg_match( '/(?:[A-Za-z_][A-Za-z0-9_]*|\$\w+|\))\s*\.\s*[\'"](?:\.?htaccess|index\.php)[\'"]/i', $lower );
		if ( false !== strpos( $lower, 'index.php' ) ) {
			if ( $is_root_ctx || ( ! $is_concat && preg_match( '#[\'"]/index\.php[\'"]#i', $lower ) ) ) {
				return array( 'class' => 'root_index', 'detail' => $args );
			}
			return array( 'class' => 'wp_content', 'detail' => $args );
		}
		if ( false !== strpos( $lower, '.htaccess' ) ) {
			if ( $is_root_ctx || ( ! $is_concat && preg_match( '#[\'"]/?\.htaccess[\'"]#i', $lower ) ) ) {
				return array( 'class' => 'htaccess', 'detail' => $args );
			}
			return array( 'class' => 'wp_content', 'detail' => $args );
		}
		// Must-use plugins.
		if ( false !== strpos( $lower, 'wpmu_plugin_dir' ) || false !== strpos( $lower, 'mu-plugins' ) ) {
			return array( 'class' => 'mu_plugins', 'detail' => $args );
		}
		// Core directories.
		if ( false !== strpos( $lower, 'wp-admin' ) || false !== strpos( $lower, 'wp-includes' ) || false !== strpos( $lower, 'wpinc' ) || false !== strpos( $lower, 'admin_url' ) ) {
			return array( 'class' => 'wp_core', 'detail' => $args );
		}
		// Plugin/theme of this installation.
		if ( false !== strpos( $lower, 'plugin_dir' ) || false !== strpos( $lower, 'plugin_dir_path' ) || false !== strpos( $lower, 'plugins_url' ) || false !== strpos( $lower, 'wp_plugin_dir' ) ) {
			return array( 'class' => 'plugin_other', 'detail' => $args );
		}
		if ( false !== strpos( $lower, 'theme_root' ) || false !== strpos( $lower, 'get_theme_root' ) || false !== strpos( $lower, 'stylesheet_directory' ) || false !== strpos( $lower, 'template_directory' ) ) {
			return array( 'class' => 'theme_other', 'detail' => $args );
		}
		// wp-content-level writes.
		if ( false !== strpos( $lower, 'wp-content' ) || false !== strpos( $lower, 'wp_content_dir' ) || false !== strpos( $lower, 'content_url' ) ) {
			return array( 'class' => 'wp_content', 'detail' => $args );
		}
		// Server-level config.
		if ( false !== strpos( $lower, '.user.ini' ) || false !== strpos( $lower, 'php.ini' ) || false !== strpos( $lower, '.htpasswd' ) ) {
			return array( 'class' => 'server_config', 'detail' => $args );
		}
		// Temp uploads dir within a request path.
		if ( false !== strpos( $lower, 'wp_upload_dir' ) || false !== strpos( $lower, 'uploads' ) ) {
			return array( 'class' => 'uploads_area', 'detail' => $args );
		}
		return array( 'class' => 'other', 'detail' => $args );
	}
}
