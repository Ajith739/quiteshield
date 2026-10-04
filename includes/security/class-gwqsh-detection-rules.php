<?php
/**
 * Detection rule registry.
 *
 * Instantiates the rule set once and evaluates it against analysis maps.
 * Adding a new rule means adding a class and listing it here; the scanner,
 * scoring engine and UI need no other changes.
 *
 * @package GracewellQuietShield
 */

defined( 'ABSPATH' ) || exit;

/**
 * GWQSH detection rules registry.
 */
final class GWQSH_Detection_Rules {

	/**
	 * Cached rule instances.
	 *
	 * @var array|null
	 */
	private static $rules = null;

	/**
	 * All rule classes, in evaluation order.
	 *
	 * @var array
	 */
	private static $rule_classes = array(
		'GWQSH_Rule_Cloaking',
		'GWQSH_Rule_Remote_Payload',
		'GWQSH_Rule_Tls_Bypass',
		'GWQSH_Rule_Self_Healing',
		'GWQSH_Rule_Critical_Write',
		'GWQSH_Rule_Web_Shell',
		'GWQSH_Rule_Obfuscation',
		'GWQSH_Rule_Dynamic_Include',
		'GWQSH_Rule_Js_Injection',
		'GWQSH_Rule_Mu_Persistence',
		'GWQSH_Rule_Upload_Executable',
		'GWQSH_Rule_Signature',
	);

	/**
	 * Registry of instantiated rules.
	 *
	 * @return array
	 */
	public static function registry() {
		if ( null === self::$rules ) {
			self::$rules = array();
			foreach ( self::$rule_classes as $class ) {
				if ( class_exists( $class ) ) {
					self::$rules[] = new $class();
				}
			}
		}
		return self::$rules;
	}

	/**
	 * Evaluate all applicable rules.
	 *
	 * @param array $analysis Analysis map.
	 * @param array $context  Scan context.
	 * @return array Findings from all rules.
	 */
	public static function evaluate_all( $analysis, $context ) {
		$findings = array();
		foreach ( self::registry() as $rule ) {
			try {
				$rule_findings = $rule->evaluate( $analysis, $context );
				if ( is_array( $rule_findings ) ) {
					foreach ( $rule_findings as $finding ) {
						$finding['rule_id'] = $rule->id();
						$finding['category'] = $rule->category();
						$finding['rule_explanation'] = $rule->explanation();
						$findings[] = $finding;
					}
				}
			} catch ( Exception $e ) {
				// A broken rule must never abort a scan.
				continue;
			} catch ( Error $er ) {
				continue;
			}
		}
		return $findings;
	}

	/**
	 * Rule metadata for UI/reporting purposes.
	 *
	 * @return array id => array( category, explanation )
	 */
	public static function rule_catalog() {
		$catalog = array();
		foreach ( self::registry() as $rule ) {
			$catalog[ $rule->id() ] = array(
				'category'    => $rule->category(),
				'explanation' => $rule->explanation(),
			);
		}
		return $catalog;
	}
}
