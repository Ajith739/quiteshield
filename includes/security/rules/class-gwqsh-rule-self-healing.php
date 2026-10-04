<?php
/**
 * Self-healing persistence detection rule.
 *
 * Detects the incident's core persistence mechanism: code that checks whether
 * a critical file (root index.php, .htaccess, wp-config.php, MU plugins) still
 * exists in its malicious form, restores it from a hidden backup copy, and
 * locks the restored file with read-only permissions.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rule: self-healing persistence.
 */
final class GWQSH_Rule_Self_Healing implements GWQSH_Detection_Rule {

	/**
	 * Rule ID.
	 */
	public function id() {
		return 'GQR-HEAL';
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
		return 'Detects restore-and-protect loops: existence checks on critical files, restoration from hidden backups, and read-only permission locks (chmod 0444) that keep reinfecting the site after cleanup.';
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

		// Write calls whose target classification marks critical surfaces.
		$critical_writes = array(); // class => lines.
		$write_map       = array(
			'file_put_contents' => true,
			'fwrite'            => true,
			'fopen'             => true,
			'rename'            => true,
			'copy'              => true,
			'touch'             => true,
		);
		foreach ( $write_map as $fn => $unused ) {
			if ( empty( $analysis['calls'][ $fn ] ) ) {
				continue;
			}
			foreach ( $analysis['calls'][ $fn ] as $call ) {
				$target = GWQSH_PHP_Analyzer::classify_write_target( $call['args'] );
				$class  = $target['class'];
				if ( in_array( $class, array( 'root_index', 'htaccess', 'wpconfig', 'mu_plugins', 'hidden', 'wp_core', 'server_config' ), true ) ) {
					if ( ! isset( $critical_writes[ $class ] ) ) {
						$critical_writes[ $class ] = array();
					}
					$critical_writes[ $class ][] = (int) $call['line'];
				}
			}
		}

		if ( empty( $critical_writes ) ) {
			return $findings;
		}

		// Companion signals.
		$existence_lines = array();
		foreach ( array( 'file_exists', 'is_file', 'is_readable', 'is_dir' ) as $fn ) {
			$existence_lines = array_merge( $existence_lines, GWQSH_PHP_Analyzer::call_lines( $analysis, $fn ) );
		}
		$chmod_lines = GWQSH_PHP_Analyzer::call_lines( $analysis, 'chmod' );
		$chmod_lock  = ! empty( $analysis['indicators']['chmod_lock']['lines'] ) ? $analysis['indicators']['chmod_lock']['lines'] : array();
		$backup_refs = ! empty( $analysis['indicators']['hidden_backup']['lines'] ) ? $analysis['indicators']['hidden_backup']['lines'] : array();
		$read_calls  = array();
		foreach ( array( 'file_get_contents', 'fread', 'fopen', 'readfile' ) as $fn ) {
			$read_calls = array_merge( $read_calls, GWQSH_PHP_Analyzer::call_lines( $analysis, $fn ) );
		}

		$score   = 0;
		$evidence = array();
		$lines   = array();
		$labels  = array();

		$target_names = array(
			'root_index'    => 'the WordPress root index.php',
			'htaccess'      => '.htaccess',
			'wpconfig'      => 'wp-config.php',
			'mu_plugins'    => 'must-use plugin files',
			'hidden'        => 'hidden backup files',
			'wp_core'       => 'WordPress core files',
			'server_config' => 'server configuration files',
		);
		foreach ( $critical_writes as $class => $write_lines ) {
			$score    += 'hidden' === $class ? 6 : 10;
			$labels[]  = 'writes ' . ( isset( $target_names[ $class ] ) ? $target_names[ $class ] : $class );
			$evidence[] = 'write to ' . ( isset( $target_names[ $class ] ) ? $target_names[ $class ] : $class ) . ' (lines ' . implode( ', ', array_slice( $write_lines, 0, 4 ) ) . ')';
			$lines     = array_merge( $lines, $write_lines );
		}

		if ( $existence_lines ) {
			$score    += 4;
			$labels[]  = 'existence checks';
			$evidence[] = 'checks target files before rewriting them (lines ' . implode( ', ', array_slice( $existence_lines, 0, 4 ) ) . ')';
			$lines     = array_merge( $lines, array_slice( $existence_lines, 0, 4 ) );
		}
		if ( $read_calls && ( isset( $critical_writes['hidden'] ) || $backup_refs ) ) {
			$score    += 4;
			$labels[]  = 'reads backup content';
			$evidence[] = 'reads content used to restore tampered files (lines ' . implode( ', ', array_slice( $read_calls, 0, 4 ) ) . ')';
		}
		if ( $backup_refs ) {
			$score    += 8;
			$labels[]  = 'hidden backup files';
			$evidence[] = 'references hidden backup file names (lines ' . implode( ', ', array_slice( $backup_refs, 0, 4 ) ) . ')';
			$lines     = array_merge( $lines, array_slice( $backup_refs, 0, 4 ) );
		}
		if ( $chmod_lock ) {
			$score    += 8;
			$labels[]  = 'read-only permission lock';
			$evidence[] = 'locks files with read-only permissions (chmod 0444/0555, lines ' . implode( ', ', array_slice( $chmod_lock, 0, 4 ) ) . ')';
			$lines     = array_merge( $lines, array_slice( $chmod_lock, 0, 4 ) );
		} elseif ( $chmod_lines ) {
			$evidence[] = 'changes file permissions (lines ' . implode( ', ', array_slice( $chmod_lines, 0, 3 ) ) . ')';
		}
		if ( ! empty( $analysis['indicators']['file_replace'] ) ) {
			$score    += 3;
			$evidence[] = 'renames/copies/deletes files as part of the repair flow';
		}

		$severity = 'high';
		if ( $score >= 24 || ( $backup_refs && $chmod_lock ) ) {
			$severity = 'critical';
		}

		$findings[] = array(
			'rule_id'       => $this->id(),
			'category'      => $this->category(),
			'label'         => 'Self-healing persistence: ' . implode( ' + ', array_slice( $labels, 0, 4 ) ),
			'score'         => $score,
			'severity_hint' => $severity,
			'evidence'      => $evidence,
			'lines'         => array_slice( array_values( array_unique( $lines ) ), 0, 14 ),
		);

		return $findings;
	}
}
