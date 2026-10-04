<?php
// phpcs:ignoreFile -- Development test suite; excluded from release package.
use PHPUnit\Framework\TestCase;

/**
 * Filesystem path security: traversal, null bytes, stream wrappers, symlinks.
 */
final class PathGuardTest extends TestCase {

    private $scratch = array();

    protected function tearDown(): void {
        foreach ( $this->scratch as $path ) {
            if ( is_link( $path ) ) {
                @unlink( $path ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- Test cleanup.
            } elseif ( file_exists( $path ) ) {
                @unlink( $path ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- Test cleanup.
            }
        }
        $this->scratch = array();
        GWQSH_Path_Guard::reset_cache();
    }

    private function scratch_in_uploads( $content = "<?php echo 'fixture';\n" ) {
        $upload = wp_upload_dir();
        $path   = $upload['basedir'] . '/gwqsh-pg-' . wp_generate_password( 8, false ) . '.php';
        file_put_contents( $path, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test scratch file.
        $this->scratch[] = $path;
        return $path;
    }

    public function test_traversal_is_rejected() {
        $this->assertNull( GWQSH_Path_Guard::validate( '../wp-config.php' ) );
        $this->assertNull( GWQSH_Path_Guard::validate( 'wp-content/../../wp-config.php' ) );
        $this->assertNull( GWQSH_Path_Guard::validate( '/wordpress/wp-includes/../../../etc/passwd' ) );
        $this->assertNull( GWQSH_Path_Guard::validate( 'uploads/..%2f..%2fsecret.php' ) );
    }

    public function test_null_bytes_are_rejected() {
        $this->assertNull( GWQSH_Path_Guard::validate( "wp-content/uploads/a.php\0.jpg" ) );
        $this->assertNull( GWQSH_Path_Guard::regular_file( "index.php\0" ) );
    }

    public function test_stream_wrappers_are_rejected() {
        foreach ( array( 'php://filter/convert.base64-encode/resource=index.php', 'data://text/plain;base64,x', 'phar://wp-content/plugins/x.phar/stub.php', 'expect://id', 'file:///etc/passwd' ) as $wrapper ) {
            $this->assertNull( GWQSH_Path_Guard::validate( $wrapper ), "Wrapper must be rejected: {$wrapper}" );
        }
    }

    public function test_windows_drive_and_backslash_paths_are_rejected() {
        $this->assertNull( GWQSH_Path_Guard::validate( 'C:\\xampp\\wordpress\\wp-config.php' ) );
        $this->assertNull( GWQSH_Path_Guard::validate( 'C:/xampp/wordpress/wp-config.php' ) );
        $this->assertNull( GWQSH_Path_Guard::validate( 'wp-content\\..\\wp-config.php' ) );
    }

    public function test_absolute_paths_outside_roots_are_rejected() {
        $this->assertNull( GWQSH_Path_Guard::validate( '/etc/passwd' ) );
        $this->assertNull( GWQSH_Path_Guard::validate( '/internal/shared/php.ini' ) );
        $this->assertNull( GWQSH_Path_Guard::validate( '/wordpress-outside-root/wp-config.php' ) );
    }

    public function test_valid_file_passes_with_canonical_and_relative() {
        $path   = $this->scratch_in_uploads();
        $result = GWQSH_Path_Guard::validate( 'wp-content/uploads/' . basename( $path ) );
        $this->assertIsArray( $result );
        $this->assertSame( wp_normalize_path( realpath( $path ) ), $result['canonical'] );
        $this->assertSame( 'wp-content/uploads/' . basename( $path ), $result['relative'] );
    }

    public function test_nonexistent_file_rejected_only_when_must_exist() {
        $this->assertNull( GWQSH_Path_Guard::validate( 'wp-content/uploads/gwqsh-missing-' . wp_generate_password( 6, false ) . '.php', true ) );
        $allow = GWQSH_Path_Guard::validate( 'wp-content/uploads/gwqsh-missing-' . wp_generate_password( 6, false ) . '.php', false );
        $this->assertIsArray( $allow, 'Non-existent targets inside the root must validate for creation flows.' );
    }

    public function test_regular_file_and_directory_type_checks() {
        $file = $this->scratch_in_uploads();
        $this->assertSame( wp_normalize_path( realpath( $file ) ), GWQSH_Path_Guard::regular_file( $file ) );
        $this->assertEmpty( GWQSH_Path_Guard::regular_file( dirname( $file ) ), 'Directories are not regular files.' );
        $this->assertEmpty( GWQSH_Path_Guard::directory( $file ), 'Files are not directories.' );
    }

    public function test_symlink_escape_is_rejected() {
        // Point OUTSIDE every allowed root (the temp dir is itself allowed).
        $outside = '/internal/shared/php.ini';
        if ( ! file_exists( $outside ) ) {
            $this->markTestSkipped( 'No out-of-root target available for the symlink probe.' );
        }

        $upload = wp_upload_dir();
        $link   = $upload['basedir'] . '/gwqsh-link-' . wp_generate_password( 6, false ) . '.php';
        if ( ! @symlink( $outside, $link ) ) { // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- Optional coverage when symlinks are available.
            $this->markTestSkipped( 'symlink() is unavailable in this environment.' );
        }
        $this->scratch[] = $link;
        $this->assertNull( GWQSH_Path_Guard::validate( 'wp-content/uploads/' . basename( $link ) ), 'A symlink escaping the allowed roots must be rejected.' );
        $this->assertNull( GWQSH_Path_Guard::regular_file( $link ) );
    }

    public function test_allowed_roots_cover_wordpress_surfaces() {
        $roots = GWQSH_Path_Guard::allowed_roots();
        $this->assertNotEmpty( $roots );
        $normalized_roots = array_map( static function ( $r ) { return rtrim( wp_normalize_path( (string) $r ), '/' ); }, $roots );
        foreach ( array( ABSPATH, WP_PLUGIN_DIR, get_theme_root(), wp_upload_dir()['basedir'] ) as $expected ) {
            $this->assertContains( rtrim( wp_normalize_path( (string) $expected ), '/' ), $normalized_roots );
        }
    }
}
