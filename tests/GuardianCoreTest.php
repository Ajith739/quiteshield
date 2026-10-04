<?php
// phpcs:ignoreFile -- Development test suite; excluded from release package.
use PHPUnit\Framework\TestCase;

/**
 * Early Guardian shared kernel: indicator scan, scoring, neutralization gate.
 */
final class GuardianCoreTest extends TestCase {

    private static function fixture( $kind, $name ) {
        return (string) file_get_contents( dirname( __DIR__, 1 ) . '/tests/fixtures/' . $kind . '/' . $name );
    }

    public function test_incident_fixture_hits_neutralize_threshold() {
        $code = self::fixture( 'malware', 'attack-mu-cloaking.php' );
        $ind  = gwqsh_guardian_core_scan_indicators( $code );
        $this->assertNotEmpty( $ind, 'The incident fixture must produce kernel indicators.' );
        $this->assertArrayHasKey( 'bot_detection', $ind );
        $this->assertArrayHasKey( 'user_agent_probe', $ind );
        $this->assertArrayHasKey( 'remote_fetch', $ind );

        $verdict = gwqsh_guardian_core_score( $ind, array( 'known_matches' => gwqsh_guardian_core_known_indicator_matches( $code ) ) );
        $this->assertSame( 'critical', $verdict['confidence'] );
        $this->assertGreaterThanOrEqual( 34, $verdict['score'] );

        $this->assertTrue( gwqsh_guardian_core_should_neutralize( $ind, array( 'known_matches' => gwqsh_guardian_core_known_indicator_matches( $code ) ) ) );
    }

    public function test_indicators_carry_line_numbers() {
        $ind = gwqsh_guardian_core_scan_indicators( self::fixture( 'malware', 'attack-mu-cloaking.php' ) );
        foreach ( $ind as $key => $data ) {
            $this->assertArrayHasKey( 'lines', $data, "Indicator {$key} must record evidence lines." );
        }
    }

    public function test_benign_files_are_clean_in_the_kernel() {
        foreach ( array( 'mu-legit.php', 'plugin-normal.php' ) as $name ) {
            $code    = self::fixture( 'benign', $name );
            $ind     = gwqsh_guardian_core_scan_indicators( $code );
            $verdict = gwqsh_guardian_core_score( $ind );
            $this->assertSame( 'clean', $verdict['confidence'], "{$name} must stay clean in the fast kernel." );
            $this->assertSame( 0, $verdict['score'] );
            $this->assertFalse( gwqsh_guardian_core_should_neutralize( $ind ), 'Benign files must never be neutralized.' );
        }
    }

    public function test_guardian_template_does_not_self_neutralize() {
        $template = (string) file_get_contents( GWQSH_PATH . 'mu-plugins/000-gracewell-guardian.php' );
        $ind      = gwqsh_guardian_core_scan_indicators( $template );
        $this->assertFalse( gwqsh_guardian_core_should_neutralize( $ind ), 'The Guardian must never neutralize itself.' );
    }

    public function test_known_indicator_strings_are_supplemental_only() {
        $code = "<?php\n// ms_gate_heal_restore reference in a comment, nothing else.\n";
        $known = gwqsh_guardian_core_known_indicator_matches( $code );
        $this->assertNotEmpty( $known, 'Known incident strings must be recognized.' );
        $ind = gwqsh_guardian_core_scan_indicators( $code );
        $this->assertFalse( gwqsh_guardian_core_should_neutralize( $ind, array( 'known_matches' => $known ) ), 'Known strings alone must never trigger neutralization.' );
    }

    public function test_external_urls_are_collected() {
        $urls = gwqsh_guardian_core_external_urls( self::fixture( 'malware', 'webshell-generic.php' ) );
        $this->assertIsArray( $urls );
        $joined = wp_json_encode( $urls );
        $this->assertStringContainsString( 'malware.invalid', (string) $joined, 'External endpoints must be extracted for evidence.' );
    }

    public function test_read_file_is_bounded_and_hashes() {
        $path = wp_normalize_path( get_temp_dir() . '/gwqsh-kernel-read-test.php' );
        file_put_contents( $path, "<?php echo 'x';\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test scratch file outside the release tree.
        try {
            $read = gwqsh_guardian_core_read_file( $path, 1024 );
            $this->assertIsArray( $read );
            $this->assertSame( 64, strlen( $read['sha256'] ) );
            $this->assertFalse( $read['truncated'] );

            $truncated = gwqsh_guardian_core_read_file( $path, 4 );
            $this->assertTrue( $truncated['truncated'], 'Short read caps must set the truncated flag.' );
        } finally {
            @unlink( $path ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- Test cleanup.
        }
        $this->assertNull( gwqsh_guardian_core_read_file( get_temp_dir() . '/gwqsh-does-not-exist-' . wp_rand() . '.php' ) );
    }

    public function test_path_within_rejects_escapes() {
        $inside = gwqsh_guardian_core_path_within( ABSPATH . 'wp-content/uploads/x.php', ABSPATH );
        $this->assertTrue( $inside );
        $prefix_trick = gwqsh_guardian_core_path_within( rtrim( ABSPATH, '/' ) . '-evil/x.php', ABSPATH );
        $this->assertFalse( $prefix_trick, 'A sibling directory sharing the prefix must be rejected.' );
    }
}
