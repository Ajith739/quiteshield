<?php
/**
 * Critical file write detection rule.
 *
 * Detects writes to index.php, .htaccess, wp-config.php, core files and the
 * must-use plugin directory from outside those components (e.g. a theme or
 * plugin file writing to the webroot). Administrators and the engine receive
 * an explainable finding even when no other behavior matches.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rule: writes to critical files.
 */
final class GWQSH_Rule_Critical_Write implements GWQSH_Detection_Rule {

	/**
	 * Rule ID.
	 */
	public function id() {
		return 'GQR-WRITE';
	}

	/**
	 * Category.
	 */
	public function category() {
		return 'persistence';
	}

	/**
	 * Explanation.
	 */
	public function explanation() {
		return 'Flags code that writes or replaces critical WordPress files: the webroot index.php, .htaccess, wp-config.php, core files or must-use plugins - the files malware modifies to take over a site.';
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

		$write_map = array( 'file_put_contents', 'fwrite', 'fopen', 'rename', 'copy', 'touch', 'unlink' );
		$critical  = array();
		$evidence  = array();
		$lines     = array();

		foreach ( $write_map as $fn ) {
			if ( empty( $analysis['calls'][ $fn ] ) ) {
				continue;
			}
			foreach ( $analysis['calls'][ $fn ] as $call ) {
				$target = GWQSH_PHP_Analyzer::classify_write_target( $call['args'] );
				if ( ! in_array( $target['class'], array( 'root_index', 'htaccess', 'wpconfig', 'mu_plugins', 'wp_core', 'server_config' ), true ) ) {
					continue;
				}
				$class = $target['class'];
				if ( ! isset( $critical[ $class ] ) ) {
					$critical[ $class ] = array();
				}
				$critical[ $class ][] = (int) $call['line'];
			}
		}

		if ( empty( $critical ) ) {
			return $findings;
		}

		$names = array(
			'root_index'    => 'root index.php',
			'htaccess'      => '.htaccess',
			'wpconfig'      => 'wp-config.php',
			'mu_plugins'    => 'must-use plugins',
			'wp_core'       => 'WordPress core files',
			'server_config' => 'server configuration',
		);
		$score = 0;
		foreach ( $critical as $class => $class_lines ) {
			$lines     = array_merge( $lines, $class_lines );
			$evidence[] = 'writes to ' . $names[ $class ] . ' (lines ' . implode( ', ', array_slice( $class_lines, 0, 4 ) ) . ')';
			$score     += ( 'wpconfig' === $class || 'mu_plugins' === $class ) ? 10 : 8;
		}

		// Context calibration:
		// - Files that live inside the critical area they manage (a .htaccess
		//   manager editing .htaccess) are reduced when the write is the only
		//   signal, but never silenced.
		// - Writes combined with other attack behavior keep full weight.
		$combo = 0;
		if ( ! empty( $analysis['indicators']['bot_detection'] ) || ! empty( $analysis['indicators']['remote_fetch'] ) ) {
			$combo += 6;
		}
		if ( ! empty( $analysis['taint_sinks'] ) ) {
			foreach ( $analysis['taint_sinks'] as $sink ) {
				if ( 'filesystem write' === $sink['kind'] ) {
					$combo += 6;
					break;
				}
			}
		}

		$component = isset( $context['component'] ) ? $context['component'] : '';
		$severity  = 'medium';
		if ( $combo > 0 ) {
			$score    += $combo;
			$severity = $score >= 20 ? 'high' : 'medium';
		} else {
			// Standalone critical-area write: keep it reviewable but lower.
			$score    = max( 5, (int) ceil( $score / 2 ) );
			$severity = 'low';
			if ( isset( $critical['wpconfig'] ) || isset( $critical['mu_plugins'] ) ) {
				$severity = 'medium';
			}
			$evidence[] = 'no accompanying attack behavior observed; flagged for administrator review';
		}

		if ( 'mu' === $component ) {
			$severity = 'high' === $severity ? 'critical' : 'high';
		}

		$findings[] = array(
			'rule_id'       => $this->id(),
			'category'      => $this->category(),
			'label'         => 'Writes to critical WordPress files',
			'score'         => $score,
			'severity_hint' => $severity,
			'evidence'      => $evidence,
			'lines'         => array_slice( array_values( array_unique( $lines ) ), 0, 10 ),
		);

		return $findings;
	}
}
