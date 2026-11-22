<?php
/**
 * Test Google-Infos (schema.org) + Divi-Modul-Beschreibung ohne WordPress: php tests/schema.php
 */
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['meta'] = array();
function get_post_meta( $id, $k, $s = false ) { return $GLOBALS['meta'][ $id ][ $k ] ?? ''; }
function get_post_type( $id ) { return 'attachment'; }
function get_post_mime_type( $id ) { return 'application/epub+zip'; }
function get_the_title( $id ) { return 'datei'; }
function apply_filters( $h, $v, ...$a ) { return $v; }
function wp_upload_dir() { return array( 'basedir' => '/nix', 'baseurl' => 'https://x/up' ); }
function trailingslashit( $s ) { return rtrim( $s, '/' ) . '/'; }
function home_url( $p = '' ) { return 'https://example.test' . $p; }
function add_query_arg( $k, $v, $u ) { return $u . '?' . $k . '=' . $v; }
function wp_json_encode( $d, $f = 0 ) { return json_encode( $d, $f ); }
function is_admin() { return false; }
function add_action( ...$a ) {}
require __DIR__ . '/../mgd-ebook/includes/class-mgd-ebook-library.php';
require __DIR__ . '/../mgd-ebook/includes/class-mgd-ebook-downloads.php';
require __DIR__ . '/../mgd-ebook/includes/class-mgd-ebook-schema.php';

$fails = 0;
function check( string $name, bool $ok ): void { global $fails; echo ( $ok ? 'OK    ' : 'FEHLER' ) . ' ' . $name . "\n"; $fails += $ok ? 0 : 1; }

$GLOBALS['meta'][7][ MGD_Ebook_Library::META_BOOK ] = array( 'title' => 'Die Stimme im Wind', 'author' => 'Anna Müller, Zweite Person', 'description' => 'Text </script><b>', 'isbn' => '9783161484100', 'series' => 'Allverund', 'series_index' => '1', 'language' => 'de' );
$d = MGD_Ebook_Schema::data( 7 );
check( 'Typ Book als E-Book', 'Book' === $d['@type'] && 'https://schema.org/EBook' === $d['bookFormat'] );
check( 'Zwei Autoren als Personen', 2 === count( $d['author'] ) && 'Zweite Person' === $d['author'][1]['name'] );
check( 'Reihe + Band', 'Allverund' === $d['isPartOf']['name'] && '1' === $d['position'] );
check( 'Sprache ab Werk nicht drin (Schalter aus)', ! isset( $d['inLanguage'] ) );
check( 'Kostenlos: Angebot mit Preis 0 und Zähl-Link', '0' === $d['offers']['price'] && str_contains( $d['offers']['url'], 'mgd_ebook_download=7' ) );
$GLOBALS['meta'][7][ MGD_Ebook_Library::META_SHOW ] = array( 'isbn' => false, 'author' => true, 'description' => true, 'series' => true, 'publisher' => true, 'year' => true, 'language' => true, 'size' => true );
$d = MGD_Ebook_Schema::data( 7 );
check( 'Abgeschaltete ISBN fehlt, eingeschaltete Sprache da', ! isset( $d['isbn'] ) && 'de' === $d['inLanguage'] );
MGD_Ebook_Schema::add( 7 ); MGD_Ebook_Schema::add( 7 );
ob_start(); MGD_Ebook_Schema::print(); $out = ob_get_clean();
check( 'Ausgabe einmal pro Buch', 1 === substr_count( $out, '"Die Stimme im Wind"' ) );
check( '</script> im Text kann nicht ausbrechen', 1 === substr_count( $out, '</script>' ) );

// Divi: module.json gültig, Namen + Felder wie erwartet
foreach ( array( 'ebook' => 'mgd/ebook', 'regal' => 'mgd/ebook-regal' ) as $dir => $name ) {
	$j = json_decode( (string) file_get_contents( __DIR__ . '/../mgd-ebook/divi/modules-json/' . $dir . '/module.json' ), true );
	$items = $j['attributes']['ebook']['settings']['innerContent']['items'] ?? array();
	check( "Divi $dir: module.json gültig, Name $name", is_array( $j ) && $name === $j['name'] );
	check( "Divi $dir: Felder Breite, Bildunterschrift, Knopftext", isset( $items['width'], $items['caption'], $items['button'] ) );
}

echo $fails ? "\n$fails Fehler\n" : "\nAlles gut\n";
exit( $fails ? 1 : 0 );
