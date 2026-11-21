<?php
/**
 * Test Cover speichern ohne WordPress: php tests/cover-save.php
 * Nachgestellt: Bild-Plugin (Modern Image Formats, EWWW) macht aus PNG/JPEG still AVIF/WebP mit anderer Endung.
 */
define( 'ABSPATH', __DIR__ . '/' );
class WP_Error { public function __construct( public $code = '', public $message = '' ) {} }
function is_wp_error( $x ) { return $x instanceof WP_Error; }

// Mini-Filter wie WordPress
$GLOBALS['filters'] = array();
function add_filter( $h, $cb, $p = 10, $a = 1 ) { $GLOBALS['filters'][ $h ][ $p ][] = $cb; return true; }
function remove_filter( $h, $cb, $p = 10 ) {
	foreach ( $GLOBALS['filters'][ $h ][ $p ] ?? array() as $i => $f ) { if ( $f === $cb ) { unset( $GLOBALS['filters'][ $h ][ $p ][ $i ] ); } }
	return true;
}
function apply_filters( $h, $v, ...$args ) {
	$all = $GLOBALS['filters'][ $h ] ?? array();
	ksort( $all );
	foreach ( $all as $cbs ) { foreach ( $cbs as $cb ) { $v = $cb( $v, ...$args ); } }
	return $v;
}
$GLOBALS['up'] = sys_get_temp_dir() . '/mgd-ebook-covertest-' . getmypid();
function wp_upload_dir() { return array( 'basedir' => $GLOBALS['up'], 'baseurl' => 'https://x/up', 'error' => false ); }
function wp_mkdir_p( $d ) { return is_dir( $d ) || mkdir( $d, 0777, true ); }
function trailingslashit( $s ) { return rtrim( $s, '/' ) . '/'; }
function wp_generate_password( $n = 8 ) { return 'abcdefgh'; }

// Fake-Editor wie WP: fragt image_editor_output_format und speichert dann mit passender Endung
abstract class WP_Image_Editor {}
final class Fake_Editor extends WP_Image_Editor {
	public function resize( $w, $h, $c ) { return true; }
	public function save( $file, $mime ) {
		$map = apply_filters( 'image_editor_output_format', array(), $file, $mime );
		if ( isset( $map[ $mime ] ) ) {
			$mime = $map[ $mime ];
			$file = preg_replace( '/\.\w+$/', '.' . explode( '/', $mime )[1], $file );
		}
		file_put_contents( $file, 'bild' );
		return array( 'path' => $file, 'file' => basename( $file ), 'mime-type' => $mime );
	}
}

require __DIR__ . '/../mgd-ebook/includes/class-mgd-ebook-library.php';
$lib   = new MGD_Ebook_Library();
$write = new ReflectionMethod( $lib, 'write_cover' );

$fails = 0;
function check( string $name, bool $ok ): void { global $fails; echo ( $ok ? 'OK    ' : 'FEHLER' ) . ' ' . $name . "\n"; $fails += $ok ? 0 : 1; }

// Normalfall ohne Bild-Plugin
$rel = $write->invoke( $lib, new Fake_Editor(), 12, true );
check( 'Ohne Bild-Plugin: PNG-Cover gespeichert', '' !== $rel && is_file( $GLOBALS['up'] . '/' . $rel ) && str_ends_with( $rel, '.png' ) );

// Bild-Plugin macht aus PNG und JPEG AVIF
add_filter( 'image_editor_output_format', static fn( $m ) => array( 'image/png' => 'image/avif', 'image/jpeg' => 'image/avif' ) + $m );
$rel = $write->invoke( $lib, new Fake_Editor(), 13, true );
check( 'Mit Bild-Plugin: gespeicherte Datei existiert wirklich', '' !== $rel && is_file( $GLOBALS['up'] . '/' . $rel ) );
check( 'Mit Bild-Plugin: Cover bleibt PNG', str_ends_with( $rel, '.png' ) );
$rel = $write->invoke( $lib, new Fake_Editor(), 14, false );
check( 'Mit Bild-Plugin: JPEG-Cover bleibt JPEG', '' !== $rel && is_file( $GLOBALS['up'] . '/' . $rel ) && str_ends_with( $rel, '.jpg' ) );
check( 'Filter der anderen Plugins danach wieder wie vorher', 'image/avif' === ( apply_filters( 'image_editor_output_format', array(), 'x.png', 'image/png' )['image/png'] ?? '' ) );

array_map( 'unlink', glob( $GLOBALS['up'] . '/mgd-ebook-covers/*' ) ?: array() );
@rmdir( $GLOBALS['up'] . '/mgd-ebook-covers' ); @rmdir( $GLOBALS['up'] );
echo $fails ? "\n$fails Fehler\n" : "\nAlles gut\n";
exit( $fails ? 1 : 0 );
