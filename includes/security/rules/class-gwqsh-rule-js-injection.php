<?php
/**
 * Front-end injection rule: iframes, scripts and SEO spam markup.
 *
 * Detects code that emits external iframes, remote script tags, hidden
 * overlays or link-farm markup to visitors - the delivery side of SEO spam
 * malware.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rule: front-end content injection.
 */
final class GWQSH_Rule_Js_Injection implements GWQSH_Detection_Rule {

	/**
	 * Rule ID.
	 */
	public function id() {
		return 'GQR-INJECT';
	}

	/**
	 * Category.
	 */
	public function category() {
		return 'cloaking';
	}

	/**
	 * Explanation.
	 */
	public function explanation() {
		return 'Detects code that injects external iframes, remote scripts, hidden overlays or spam links into front-end responses - the delivery mechanism of SEO-spam malware.';
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

		$iframe_lines = ! empty( $analysis['indicators']['iframe_injection']['lines'] ) ? $analysis['indicators']['iframe_injection']['lines'] : array();

		// Remote <script src>, hidden divs, link injections in string literals.
		$script_lines = array();
		$hidden_lines = array();
		foreach ( (array) $analysis['strings'] as $string ) {
			$text = $string['text'];
			if ( preg_match( '/<script[^>]+src\s*=\s*["\']?(?:https?:)?\/\//i', $text ) ) {
				$script_lines[] = (int) $string['line'];
			}
			if ( preg_match( '/display\s*:\s*none|visibility\s*:\s*hidden|opacity\s*:\s*0(?:\.0+)?\s*(?:;|["\'])|left\s*:\s*-\d{3,}px|text-indent\s*:\s*-\d{3,}px/i', $text )
				&& ! preg_match( '/screen-reader-text|sr-only|a11y|accessibility/i', $text ) ) {
				$hidden_lines[] = (int) $string['line'];
			}
		}
		$script_lines = array_values( array_unique( $script_lines ) );
		$hidden_lines = array_values( array_unique( $hidden_lines ) );

		if ( ! $iframe_lines && ! $script_lines && ! $hidden_lines ) {
			return $findings;
		}

		$score    = 0;
		$evidence = array();
		$lines    = array();
		$labels   = array();

		if ( $iframe_lines ) {
			$score    += 8;
			$labels[]  = 'external iframe injection';
			$evidence[] = 'outputs external <iframe> markup (lines ' . implode( ', ', array_slice( $iframe_lines, 0, 4 ) ) . ')';
			$lines     = array_merge( $lines, $iframe_lines );
		}
		if ( $script_lines ) {
			$score    += 8;
			$labels[]  = 'remote script injection';
			$evidence[] = 'outputs <script src> markup from external hosts (lines ' . implode( ', ', array_slice( $script_lines, 0, 4 ) ) . ')';
			$lines     = array_merge( $lines, $script_lines );
		}
		if ( $hidden_lines ) {
			$score    += 4;
			$labels[]  = 'hidden content styling';
			$evidence[] = 'generates hidden/off-screen content (lines ' . implode( ', ', array_slice( $hidden_lines, 0, 4 ) ) . ')';
			$lines     = array_merge( $lines, $hidden_lines );
		}

		// Delivering via a front-end hook or filter of rendered content.
		$front_hooks = array( 'template_redirect', 'wp_footer', 'wp_head', 'the_content', 'wp_enqueue_scripts', 'init' );
		foreach ( $front_hooks as $hook ) {
			if ( ! empty( $analysis['hooks'][ $hook ] ) ) {
				$score    += 4;
				$evidence[] = 'attaches output to the "' . $hook . '" hook (line ' . $analysis['hooks'][ $hook ] . ')';
				break;
			}
		}

		$severity = 'low';
		if ( $score >= 18 ) {
			$severity = 'high';
		} elseif ( $score >= 12 ) {
			$severity = 'medium';
		}

		$findings[] = array(
			'rule_id'       => $this->id(),
			'category'      => $this->category(),
			'label'         => 'Front-end injection: ' . implode( ' + ', $labels ),
			'score'         => $score,
			'severity_hint' => $severity,
			'evidence'      => $evidence,
			'lines'         => array_slice( array_values( array_unique( $lines ) ), 0, 10 ),
		);

		return $findings;
	}
}
