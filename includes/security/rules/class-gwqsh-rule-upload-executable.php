<?php
/**
 * Uploads executable-content rule.
 *
 * Detects executable code inside the uploads directory: PHP extensions,
 * double extensions, PHP tags inside image/text files (polyglots) and
 * .htaccess/.user.ini files that re-enable execution.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rule: executable code in uploads.
 */
final class GWQSH_Rule_Upload_Executable implements GWQSH_Detection_Rule {

	/**
	 * Rule ID.
	 */
	public function id() {
		return 'GQR-UPLX';
	}

	/**
	 * Category.
	 */
	public function category() {
		return 'upload';
	}

	/**
	 * Explanation.
	 */
	public function explanation() {
		return 'Detects executable code inside the uploads directory - PHP files, double extensions, PHP payloads hidden in images (polyglots) and .htaccess rules that re-enable script execution.';
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

		if ( ! isset( $context['component'] ) || 'uploads' !== $context['component'] ) {
			return $findings;
		}

		$file_name = isset( $context['file_name'] ) ? strtolower( (string) $context['file_name'] ) : '';
		$is_php_ext   = (bool) preg_match( '/\.(?:php\d?|phtml|phar|pht|phps|cgi|pl|py|asp|aspx|jsp|sh)$/i', $file_name );
		$double_ext   = (bool) preg_match( '/\.(?:jpg|jpeg|png|gif|webp|pdf|ico|svg|txt|docx?|xlsx?|mp3|mp4)\.(?:php\d?|phtml|phar|pht)$/i', $file_name );
		$ht_like      = (bool) preg_match( '/^\.htaccess$/i', $file_name ) || (bool) preg_match( '/^\.user\.ini$/i', $file_name ) || (bool) preg_match( '/^\.htaccess\./i', $file_name );
		$has_php_tags = ! empty( $analysis['indicators'] ) && isset( $analysis['bytes'] ) && $analysis['bytes'] > 0 && (
			! empty( $analysis['tokens'] ) // Tokenizing succeeded => PHP code present.
			|| false !== strpos( isset( $analysis['first_bytes'] ) ? $analysis['first_bytes'] : '', '<?php' )
		);

		$evidence = array();
		$score    = 0;
		$severity = 'info';

		if ( $double_ext ) {
			$score     = 22;
			$severity  = 'critical';
			$evidence[] = 'double extension designed to bypass upload filters: ' . $context['file_name'];
		} elseif ( $is_php_ext ) {
			$score     = 18;
			$severity  = 'high';
			$evidence[] = 'executable PHP file inside the uploads directory';
		} elseif ( $ht_like ) {
			$score     = 12;
			$severity  = 'medium';
			$evidence[] = 'server configuration file inside uploads can re-enable script execution';
		} elseif ( $has_php_tags ) {
			$score     = 8;
			$severity  = 'medium';
			$evidence[] = 'PHP code detected inside an uploaded non-PHP file (possible polyglot)';
		}

		if ( $score <= 0 ) {
			return $findings;
		}

		// Attack behavior inside the uploaded file escalates.
		$attack = ! empty( $analysis['indicators']['shell_exec'] )
			|| ! empty( $analysis['indicators']['bot_detection'] )
			|| ! empty( $analysis['indicators']['remote_fetch'] )
			|| ! empty( $analysis['known_matches'] );
		if ( $attack ) {
			$score     += 10;
			$severity   = 'critical';
			$evidence[] = 'uploaded executable also contains attack behavior';
		}

		$findings[] = array(
			'rule_id'       => $this->id(),
			'category'      => $this->category(),
			'label'         => 'Executable code in uploads',
			'score'         => $score,
			'severity_hint' => $severity,
			'evidence'      => $evidence,
			'lines'         => array(),
		);

		return $findings;
	}
}
