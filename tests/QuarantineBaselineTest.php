<?php
// phpcs:ignoreFile -- Development test suite; excluded from release package.
use PHPUnit\Framework\TestCase;

/**
 * Quarantine storage, restore safety, baseline gating, Guardian lifecycle.
 */
final class QuarantineBaselineTest extends TestCase {

    private static $planted = array();
    private static $quarantined = array();
    private static $inserted_rows = 0;

    public static function setUpBeforeClass(): void {
        global $wpdb;
        // Start clean: drop plugin quarantine metadata/fixtures from prior runs.
        delete_option( GWQSH_Quarantine_Manager::METADATA_OPTION );
        delete_option( 'gwqsh_mu_baseline' );
        delete_option( 'gwqsh_root_baseline' );
        // This suite runs before ScannerRunTest (alphabetical order), whose
        // run() would clear stale rows itself. Leftover unresolved findings
        // from a failed prior run must not gate baseline establishment here.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Disposable test environment cleanup.
        $wpdb->delete( $wpdb->prefix . 'gwqsh_scan_results', array( 'is_resolved' => 0 ) );
    }

    public static function tearDownAfterClass(): void {
        global $wpdb;
        foreach ( self::$planted as $path ) {
            if ( file_exists( $path ) ) {
                @unlink( $path ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- Test cleanup.
            }
        }
        $dir = GWQSH_Quarantine_Manager::quarantine_dir();
        foreach ( self::$quarantined as $name ) {
            if ( $dir && is_file( $dir . '/' . $name ) ) {
                @unlink( $dir . '/' . $name ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- Test cleanup.
            }
        }
        delete_option( GWQSH_Quarantine_Manager::METADATA_OPTION );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Remove test rows only.
        $wpdb->delete( $wpdb->prefix . 'gwqsh_scan_results', array( 'severity' => 'critical', 'is_resolved' => 0 ) );
    }

    private function plant_in_uploads( $content = "<?php echo 'quarantine-me';\n" ) {
        $upload = wp_upload_dir();
        $path   = $upload['basedir'] . '/gwqsh-q-' . wp_generate_password( 10, false ) . '.php';
        file_put_contents( $path, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test scratch file.
        self::$planted[] = $path;
        return $path;
    }

    public function test_quarantine_preserves_evidence_and_blocks_execution() {
        $path    = $this->plant_in_uploads();
        $content = (string) file_get_contents( $path );
        $sha     = hash( 'sha256', $content );

        $result = GWQSH_Quarantine_Manager::quarantine_file(
            'wp-content/uploads/' . basename( $path ),
            'Unit test: High finding quarantined by administrator',
            array( 'source' => 'scan', 'severity' => 'high', 'rule_id' => 'GQR-SHELL', 'score' => 41 )
        );
        $this->assertIsArray( $result, 'Quarantine of a valid uploads file must succeed.' );
        $this->assertMatchesRegularExpression( '/^[a-f0-9]{32,64}\.quarantine$/D', $result['file'], 'Quarantine names must be unpredictable hex.' );
        $this->assertSame( $sha, $result['sha256'] );
        $this->assertFalse( file_exists( $path ), 'Original must be moved out of the tree (never deleted).' );
        self::$quarantined[] = $result['file'];

        $list = GWQSH_Quarantine_Manager::list_quarantined();
        $this->assertArrayHasKey( $result['file'], $list );
        $record = $list[ $result['file'] ];
        $this->assertSame( '/wp-content/uploads/' . basename( $path ), $record['original'] );
        $this->assertSame( $sha, $record['hash'] );
        $this->assertSame( 'Unit test: High finding quarantined by administrator', $record['reason'] );
        $this->assertSame( 'GQR-SHELL', $record['rule_id'] );
        $this->assertGreaterThan( 0, (int) $record['time'], 'Original timestamp must be preserved.' );

        // Execution must be blocked inside the quarantine directory.
        $dir = GWQSH_Quarantine_Manager::quarantine_dir();
        $this->assertFileExists( $dir . '/.htaccess', 'Apache denial guard must exist.' );
        $this->assertFileExists( $dir . '/index.php' );
        $this->assertStringContainsString( 'Require all denied', (string) file_get_contents( $dir . '/.htaccess' ) );
    }

    public function test_restore_round_trip_and_safety_checks() {
        $path = $this->plant_in_uploads( "<?php echo 'restore-me';\n" );
        $rel  = 'wp-content/uploads/' . basename( $path );
        $q    = GWQSH_Quarantine_Manager::quarantine_file( $rel, 'Unit test quarantine' );
        $this->assertIsArray( $q );
        self::$quarantined[] = $q['file'];

        // Invalid names and unknown records are refused.
        $this->assertWPError( GWQSH_Quarantine_Manager::restore_file( '../evil.php' ) );
        $this->assertWPError( GWQSH_Quarantine_Manager::restore_file( 'deadbeefdeadbeefdeadbeefdeadbeef.quarantine' ) );

        // Restoring over an existing file is refused.
        file_put_contents( $path, 'replacement content' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test scratch file.
        $blocked = GWQSH_Quarantine_Manager::restore_file( $q['file'] );
        $this->assertWPError( $blocked );
        $this->assertSame( 'gwqsh_target_exists', $blocked->get_error_code() );
        unlink( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_unlink -- Test scratch file.

        $restored = GWQSH_Quarantine_Manager::restore_file( $q['file'] );
        $this->assertIsArray( $restored, 'Restore must succeed once the original location is free.' );
        $this->assertSame( "<?php echo 'restore-me';\n", (string) file_get_contents( $path ), 'Restored content must be byte-identical.' );
        $list = GWQSH_Quarantine_Manager::list_quarantined();
        $this->assertArrayNotHasKey( $q['file'], $list, 'Restored records must leave the quarantine list.' );
    }

    public function test_quarantine_refuses_outside_paths_and_self() {
        $this->assertWPError( GWQSH_Quarantine_Manager::quarantine_file( '/etc/passwd' ) );
        $this->assertWPError( GWQSH_Quarantine_Manager::quarantine_file( '../wp-config.php' ) );
        $this->assertWPError( GWQSH_Quarantine_Manager::quarantine_file( 'wp-config.php' ), 'Core bootstrap files must never be quarantined.' );
        $this->assertWPError( GWQSH_Quarantine_Manager::quarantine_file( 'wp-content/plugins/quiteshield/gracewell-quietshield.php' ), 'The plugin must not quarantine its own engine.' );
    }

    public function test_guardian_lifecycle_install_status_uninstall() {
        // Clean slate.
        GWQSH_Guardian_Manager::uninstall();

        $installed = GWQSH_Guardian_Manager::install();
        $this->assertIsArray( $installed, 'Guardian install must succeed on a stock mu-plugins directory.' );
        $this->assertSame( 'active', GWQSH_Guardian_Manager::status() );
        $this->assertFileExists( WP_CONTENT_DIR . '/mu-plugins/000-gracewell-guardian.php' );
        $deployed = (string) file_get_contents( WP_CONTENT_DIR . '/mu-plugins/000-gracewell-guardian.php' );
        $this->assertStringContainsString( 'Gracewell Early Guardian', $deployed );

        // Tampering must be visible in status and must never be deleted blindly.
        $guardian_path = WP_CONTENT_DIR . '/mu-plugins/000-gracewell-guardian.php';
        file_put_contents( $guardian_path, $deployed . "\n/* tampered */" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Deliberate tamper for the test.
        $this->assertSame( 'modified', GWQSH_Guardian_Manager::status() );

        // Reinstall repairs the deployment (content-hash verified).
        $repaired = GWQSH_Guardian_Manager::install();
        $this->assertIsArray( $repaired );
        $this->assertSame( 'active', GWQSH_Guardian_Manager::status() );

        $overview = GWQSH_Guardian_Manager::overview();
        $this->assertSame( 'active', $overview['status'] );
        $this->assertArrayHasKey( 'mu_baseline', $overview );
        $this->assertArrayHasKey( 'root_baseline', $overview );
        $this->assertArrayHasKey( 'events', $overview );
        $this->assertArrayHasKey( 'quarantined', $overview );

        $removed = GWQSH_Guardian_Manager::uninstall();
        $this->assertIsArray( $removed );
        $this->assertSame( 'absent', GWQSH_Guardian_Manager::status() );
        $this->assertFileDoesNotExist( $guardian_path );

        // Final state for the runtime attack test: Guardian deployed.
        $final = GWQSH_Guardian_Manager::install();
        $this->assertIsArray( $final );
        $this->assertSame( 'active', GWQSH_Guardian_Manager::status() );
    }

    public function test_baseline_blocks_on_unresolved_critical_findings() {
        global $wpdb;
        $table = $wpdb->prefix . 'gwqsh_scan_results';

        // Ensure a clean slate for this scenario.
        GWQSH_Baseline_Manager::set( 'mu', array() );

        $this->assertFalse( GWQSH_Baseline_Manager::has_unresolved_critical() );
        $clean = GWQSH_Baseline_Manager::establish_mu_baseline( true );
        $this->assertIsArray( $clean, 'Baseline must succeed when no Critical findings are unresolved.' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Synthetic safety-gate row, removed in tearDownAfterClass.
        $wpdb->insert(
            $table,
            array(
				'file_path'   => '/wp-content/plugins/gwqsh-critical-fixture/gwqsh-critical-fixture.php',
				'file_type'   => 'Plugin',
				'status'      => 'Suspicious',
				'details'     => '[Critical] Synthetic gate row',
				'detected_at' => current_time( 'mysql' ),
				'is_resolved' => 0,
				'severity'    => 'critical',
				'rule_id'     => 'GQR-SHELL',
				'risk_score'  => 45,
            )
        );
        self::$inserted_rows++;

        try {
            $this->assertTrue( GWQSH_Baseline_Manager::has_unresolved_critical() );
            $blocked = GWQSH_Baseline_Manager::establish_mu_baseline( true );
            $this->assertWPError( $blocked );
            $this->assertSame( 'gwqsh_baseline_blocked', $blocked->get_error_code(), 'Baselines must never be established over Critical findings.' );
        } finally {
            // Remove the synthetic row immediately so later baseline work is unblocked.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Synthetic gate row from this test.
            $wpdb->delete( $table, array( 'rule_id' => 'GQR-SHELL', 'file_path' => '/wp-content/plugins/gwqsh-critical-fixture/gwqsh-critical-fixture.php' ) );
        }
    }

    public function test_baseline_established_for_runtime_attack_test() {
        // Final state required by the disposable-environment attack test:
        // Guardian deployed AND baselines present.
        $mu   = GWQSH_Baseline_Manager::establish_mu_baseline( true );
        $root = GWQSH_Baseline_Manager::establish_root_baseline();
        $this->assertIsArray( $mu );
        $this->assertIsArray( $root );
        $status = GWQSH_Baseline_Manager::status();
        $this->assertTrue( $status['mu']['established'] );
        $this->assertTrue( $status['root']['established'] );
    }

    private static function assertWPError( $value ) {
        // Minimal shim so PHPUnit 9/10 assertions both work in this suite.
        self::assertTrue( is_wp_error( $value ), 'Expected a WP_Error result.' );
    }
}
