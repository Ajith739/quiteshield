<?php
/**
 * Obfuscation detection rule.
 *
 * Detects encoded payloads (base64/gzinflate/rot13/hex/chr chains), eval-based
 * execution, variable function indirection and preg_replace /e. Obfuscation
 * alone is suspicious-but-reviewable; combined with execution it escalates.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rule: obfuscated code.
 */
final class GWQSH_Rule_Obfuscation implements GWQSH_Detection_Rule {

	/**
	 * Rule ID.
	 */
	public function id() {
		return 'GQR-OBFUS';
	}

	/**
	 * Category.
	 */
	public function category() {
		return 'obfuscation';
	}

	/**
	 * Explanation.
	 */
	public function explanation() {
		return 'Detects encoded and indirection-based code hiding: eval, base64/gzip/rot13 payloads, hex and chr() string assembly, and variable function calls. Legitimate code rarely needs these combined.';
	}

	/**
	 * Evaluate.
	 *
	 * @param array $analysis Analysis.
	 * @param array $context  Context.
	 * @return array
	 */
	public function evaluate( $analysis, $context ) {
		$findings = array();

		$decode_lines = array();
		foreach ( array( 'base64_decode', 'gzinflate', 'gzuncompress', 'gzdecode', 'str_rot13', 'hex2bin', 'convert_uudecode' ) as $fn ) {
			$decode_lines = array_merge( $decode_lines, GWQSH_PHP_Analyzer::call_lines( $analysis, $fn ) );
		}
		$decode_lines = array_values( array_unique( $decode_lines ) );

		$exec_lines = array();
		foreach ( array( 'eval', 'assert', 'create_function' ) as $fn ) {
			$exec_lines = array_merge( $exec_lines, GWQSH_PHP_Analyzer::call_lines( $analysis, $fn ) );
		}
		$exec_lines = array_values( array_unique( $exec_lines ) );

		$indicators = $analysis['indicators'];
		$hex_blob   = array();
		if ( ! empty( $indicators['obfuscation']['lines'] ) ) {
			// Line-level detail comes from the kernel patterns; split hex/chr specifically.
			foreach ( (array) $analysis['strings'] as $string ) {
				if ( preg_match( '/\\\x[0-9a-fA-F]{2}(?:\\\x[0-9a-fA-F]{2}){4,}/', $string['text'] ) ) {
					$hex_blob[] = (int) $string['line'];
				}
			}
		}
		$var_calls = ! empty( $analysis['variable_calls'] ) ? $analysis['variable_calls'] : array();

		$has_obfuscation_signal = $decode_lines || $exec_lines || $hex_blob || $var_calls || ! empty( $indicators['obfuscation'] );
		if ( ! $has_obfuscation_signal ) {
			return $findings;
		}

		$score    = 0;
		$evidence = array();
		$lines    = array();
		$labels   = array();

		if ( $decode_lines ) {
			$score    += 4;
			$labels[]  = 'encoded payload decoding';
			$evidence[] = 'decodes encoded data: base64/gzip/rot13/hex (lines ' . implode( ', ', array_slice( $decode_lines, 0, 5 ) ) . ')';
			$lines     = array_merge( $lines, $decode_lines );
		}
		if ( $exec_lines ) {
			$score    += 10;
			$labels[]  = 'dynamic code execution';
			$evidence[] = 'executes dynamic code: eval/assert/create_function (lines ' . implode( ', ', array_slice( $exec_lines, 0, 5 ) ) . ')';
			$lines     = array_merge( $lines, $exec_lines );
		}
		if ( $decode_lines && $exec_lines ) {
			$score   += 10;
			$labels[] = 'decodes then executes';
		}
		if ( $hex_blob ) {
			$score    += 6;
			$labels[]  = 'hex-encoded string blobs';
			$evidence[] = 'hex-escaped string blobs used to hide content (lines ' . implode( ', ', array_slice( $hex_blob, 0, 4 ) ) . ')';
			$lines     = array_merge( $lines, $hex_blob );
		}
		if ( $var_calls ) {
			$score    += 4;
			$labels[]  = 'variable function indirection';
			$evidence[] = 'calls functions through variables: $fn(...) dynamic dispatch (' . count( $var_calls ) . ' site(s), e.g. line ' . $var_calls[0]['line'] . ')';
			$lines[]   = (int) $var_calls[0]['line'];
		}
		foreach ( array_slice( (array) $analysis['taint_sinks'], 0, 3 ) as $sink ) {
			if ( 'dynamic code execution' === $sink['kind'] || 'dynamic function call' === $sink['kind'] ) {
				$score    += 12;
				$labels[]  = 'request input reaches dynamic execution';
				$evidence[] = $sink['origin'] . ' input reaches ' . $sink['call'] . ' (line ' . $sink['line'] . ')';
				$lines[]   = (int) $sink['line'];
				break;
			}
		}

		// Long high-entropy base64 blobs are stronger evidence.
		$long_blobs = 0;
		foreach ( (array) $analysis['strings'] as $string ) {
			if ( strlen( $string['text'] ) > 80 && preg_match( '/^["\'][A-Za-z0-9+\/=\s]{60,}["\']$/', $string['text'] ) ) {
				++$long_blobs;
			}
		}
		if ( $long_blobs > 0 && ( $decode_lines || $exec_lines ) ) {
			$score    += 4;
			$evidence[] = $long_blobs . ' large base64-like string blob(s) present';
		}

		if ( $score <= 0 ) {
			return $findings;
		}

		$severity = 'low';
		if ( $score >= 24 ) {
			$severity = 'critical';
		} elseif ( $score >= 16 ) {
			$severity = 'high';
		} elseif ( $score >= 9 ) {
			$severity = 'medium';
		}

		$findings[] = array(
			'rule_id'       => $this->id(),
			'category'      => $this->category(),
			'label'         => 'Obfuscated code: ' . implode( ' + ', array_slice( $labels, 0, 4 ) ),
			'score'         => $score,
			'severity_hint' => $severity,
			'evidence'      => $evidence,
			'lines'         => array_slice( array_values( array_unique( $lines ) ), 0, 12 ),
		);

		return $findings;
	}
}
