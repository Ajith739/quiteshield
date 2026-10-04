<?php
// phpcs:ignoreFile -- Development test suite; excluded from release package.
use PHPUnit\Framework\TestCase;

/**
 * End-to-end scanner run on the disposable WordPress environment.
 */
final class ScannerRunTest extends TestCase {

    private static $planted = array();

    public static function tearDownAfterClass(): void {
        foreach ( self::$planted as $path ) {
            if ( file_exists( $path ) ) {
                @unlink( $path ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- Test cleanup.
            }
        }
        // Remove quarantine records created by this run.
        $metadata = get_option( GWQSH_Quarantine_Manager::METADATA_OPTION, array() );
        if ( is_array( $metadata ) ) {
            $dir = GWQSH_Quarantine_Manager::quarantine_dir();
            foreach ( $metadata as $name => $record ) {
                if ( $dir && is_file( $dir . '/' . $name ) ) {
                    @unlink( $dir . '/' . $name ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- Test cleanup.
                }
            }
        }
        delete_option( GWQSH_Quarantine_Manager::METADATA_OPTION );
        delete_option( GWQSH_Security_Scanner::CACHE_OPTION );
        delete_option( 'gwqsh_last_scan_degraded' );
        // Unresolved findings from a failed run would gate the baseline tests
        // of the NEXT run (QuarantineBaselineTest runs first, alphabetically).
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Disposable test environment cleanup.
        $wpdb->delete( $wpdb->prefix . 'gwqsh_scan_results', array( 'is_resolved' => 0 ) );
    }

    public function test_full_scan_detects_planted_malware_and_reports_counters() {
        global $wpdb;

        // Plant the sanitized incident-style fixture OUTSIDE the plugin's own
        // directory so the scanner's self-exclusion cannot skip it.
        $upload = wp_upload_dir();
        $planted = $upload['basedir'] . '/gwqsh-attack-fixture-' . wp_generate_password( 8, false ) . '.php';
        $fixture = (string) file_get_contents( dirname( __DIR__, 1 ) . '/tests/fixtures/malware/attack-mu-cloaking.php' );
        file_put_contents( $planted, $fixture ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Deliberate detection target in the disposable environment.
        self::$planted[] = $planted;
        $relative = 'wp-content/uploads/' . basename( $planted );

        $summary = GWQSH_Security_Scanner::run();
        $this->assertIsArray( $summary );
        $this->assertGreaterThan( 0, (int) $summary['total'], 'The scanner must inspect files.' );

        // The planted file must be reported as a Critical behavioral finding.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reading back the scan under test.
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT severity, rule_id, risk_score, file_sha256, evidence, status FROM {$wpdb->prefix}gwqsh_scan_results WHERE file_path = %s AND is_resolved = 0",
                '/' . $relative
            )
        );
        $this->assertNotNull( $row, 'Planted malware must produce a scan result.' );
        $this->assertSame( 'critical', $row->severity );
        $this->assertNotSame( '', $row->rule_id );
        $this->assertGreaterThanOrEqual( GWQSH_Scoring_Engine::THRESHOLD_CRITICAL, (int) $row->risk_score );
        $this->assertSame( 64, strlen( (string) $row->file_sha256 ), 'Findings must record the file hash.' );
        $this->assertNotSame( '', (string) $row->evidence, 'Findings must carry evidence lines.' );

        // Counter consistency (analyze_and_score owns the totals).
        $counters = GWQSH_Security_Scanner::counters();
        $this->assertGreaterThan( 0, $counters['total'] );
        $this->assertGreaterThanOrEqual( 1, $counters['suspicious'] );
        $this->assertGreaterThanOrEqual( 1, $counters['critical'] );
        $this->assertSame( 0, $counters['total'] - $summary['clean'] - $summary['modified'] - $summary['missing'] - $summary['suspicious'], 'total must equal clean+modified+missing+suspicious.' );

        // The plugin's own engine files and bundled benign fixtures must not appear as findings.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reading back the scan under test.
        $self_flagged = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}gwqsh_scan_results WHERE is_resolved = 0 AND (file_path LIKE %s OR file_path LIKE %s)", '/wp-content/plugins/quiteshield/%', '/wp-content/mu-plugins/000-gracewell-guardian.php' ) );
        $this->assertSame( 0, $self_flagged, 'The scanner must not flag its own engine or Guardian template.' );

        // Baseline safety gate reacts to the Critical finding.
        $this->assertTrue( GWQSH_Baseline_Manager::has_unresolved_critical() );
        $blocked = GWQSH_Baseline_Manager::establish_mu_baseline( true );
        $this->assertTrue( is_wp_error( $blocked ), 'Baselining must be blocked while the Critical finding is unresolved.' );

        // Quarantine through the public File Integrity API, then verify the record.
        $this->assertTrue( GWQSH_File_Integrity::quarantine_file( $relative ) );
        $this->assertFileDoesNotExist( $planted, 'Quarantine must remove the malware from the tree (never delete it).' );
        $list = GWQSH_Quarantine_Manager::list_quarantined();
        $this->assertNotEmpty( $list );
        $record = reset( $list );
        $this->assertSame( '/' . $relative, $record['original'] );
        $this->assertSame( 'critical', $record['severity'] );
        $this->assertNotSame( '', $record['rule_id'] );
    }

    public function test_scan_history_and_activity_log_records_scan() {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reading back the scan history.
        $history = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}gwqsh_scan_history ORDER BY id DESC LIMIT 1" );
        $this->assertNotNull( $history );
        $this->assertGreaterThan( 0, (int) $history->total_files );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Reading back the activity log.
        $logged = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}gwqsh_activity_log WHERE event_name = %s", 'Critical malware findings' ) );
        $this->assertGreaterThanOrEqual( 1, $logged, 'Critical scans must be logged for administrators.' );
    }
}
