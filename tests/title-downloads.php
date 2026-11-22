<?php
/**
 * Test Titel ändern + Download-Zähler ohne WordPress: php tests/title-downloads.php
 */
define( 'ABSPATH', __DIR__ . '/' );
class WP_Error { public function __construct( public $code = '', public $message = '' ) {} }
function is_wp_error( $x ) { return $x instanceof WP_Error; }
$GLOBALS['meta'] = array();
$GLOBALS['posts'] = array( 7 => array( 'type' => 'attachment', 'mime' => 'application/epub+zip', 'title' => 'datei-name' ), 8 => array( 'type' => 'attachment', 'mime' => 'image/png', 'title' => 'bild' ) );
function get_post_meta( $id, $k, $single = false ) { return $GLOBALS['meta'][ $id ][ $k ] ?? ''; }
function update_post_meta( $id, $k, $v ) { $GLOBALS['meta'][ $id ][ $k ] = $v; return true; }
function delete_post_meta( $id, $k ) { unset( $GLOBALS['meta'][ $id ][ $k ] ); return true; }
function get_post_type( $id ) { return $GLOBALS['posts'][ $id ]['type'] ?? false; }
function get_post_mime_type( $id ) { return $GLOBALS['posts'][ $id ]['mime'] ?? false; }
function get_the_title( $id ) { return $GLOBALS['posts'][ $id ]['title'] ?? ''; }
function wp_update_post( $a ) { $GLOBALS['posts'][ $a['ID'] ]['title'] = $a['post_title']; return $a['ID']; }
function sanitize_text_field( $s ) { return trim( preg_replace( '/\s+/', ' ', strip_tags( (string) $s ) ) ); }
function wp_date( $f, $t = null ) { return gmdate( $f, $t ?? time() ); }
function apply_filters( $h, $v, ...$a ) { return $v; }
function home_url( $p = '' ) { return 'https://example.test' . $p; }
function add_query_arg( $k, $v, $url ) { return $url . ( str_contains( $url, '?' ) ? '&' : '?' ) . $k . '=' . $v; }

require __DIR__ . '/../mgd-ebook/includes/class-mgd-ebook-library.php';
require __DIR__ . '/../mgd-ebook/includes/class-mgd-ebook-downloads.php';

$fails = 0;
function check( string $name, bool $ok ): void { global $fails; echo ( $ok ? 'OK    ' : 'FEHLER' ) . ' ' . $name . "\n"; $fails += $ok ? 0 : 1; }

// Metadaten
update_post_meta( 7, MGD_Ebook_Library::META_BOOK, array( 'title' => 'Spuren-Stimmen-Die Stimme im Wind', 'author' => 'Anna', 'description' => '', 'language' => 'de' ) );
check( 'Ohne eigene Angaben gilt der Titel aus der EPUB', 'Spuren-Stimmen-Die Stimme im Wind' === MGD_Ebook_Library::title( 7 ) );
check( 'Metadaten speichern klappt', true === MGD_Ebook_Library::set_meta( 7, array( 'title' => '  Ein Kinderreim  aus <b>Morgenhall</b> ', 'series' => 'Spuren & Stimmen aus Allverund', 'series_index' => '2', 'year' => '2026', 'isbn' => '978-3-16-148410-0', 'description' => "Zeile eins\r\n\n\n\nZeile <i>zwei</i>" ) ) );
$b = MGD_Ebook_Library::book( 7 );
check( 'Eigener Titel gewinnt, bereinigt', 'Ein Kinderreim aus Morgenhall' === MGD_Ebook_Library::title( 7 ) );
check( 'Autor aus der EPUB bleibt, wenn nicht überschrieben', 'Anna' === $b['author'] );
check( 'Reihe + Band + Jahr gespeichert', 'Spuren & Stimmen aus Allverund' === $b['series'] && '2' === $b['series_index'] && '2026' === $b['year'] );
check( 'ISBN ohne Striche', '9783161484100' === $b['isbn'] );
check( 'Beschreibung ohne HTML, Absätze bleiben', "Zeile eins\n\nZeile zwei" === $b['description'] );
check( 'Mediathek-Titel wird mitgezogen', 'Ein Kinderreim aus Morgenhall' === $GLOBALS['posts'][7]['title'] );
check( 'EPUB-Daten bleiben unangetastet', 'Spuren-Stimmen-Die Stimme im Wind' === MGD_Ebook_Library::epub_book( 7 )['title'] );
check( 'Ungültiges Jahr wird verworfen', '' === MGD_Ebook_Library::clean_field( 'year', '20a6' ) );
check( 'Titel wird auf 300 Zeichen gekürzt', 300 === mb_strlen( MGD_Ebook_Library::clean_field( 'title', str_repeat( 'a', 400 ) ) ) );
check( 'Alles leer = zurück zu den EPUB-Werten', true === MGD_Ebook_Library::set_meta( 7, array() ) && 'Spuren-Stimmen-Die Stimme im Wind' === MGD_Ebook_Library::title( 7 ) && '' === MGD_Ebook_Library::book( 7 )['series'] );
check( 'Kein eBook: wird abgelehnt', false === MGD_Ebook_Library::set_meta( 8, array( 'title' => 'x' ) ) );
$show = MGD_Ebook_Library::show( 7 );
check( 'Anzeige ab Werk: Autor an, Sprache aus', true === $show['author'] && false === $show['language'] );
MGD_Ebook_Library::set_show( 7, array( 'author' => '1', 'language' => '1' ) );
$show = MGD_Ebook_Library::show( 7 );
check( 'Anzeige gespeichert: Beschreibung aus, Sprache an', false === $show['description'] && true === $show['language'] && true === $show['author'] );

// Zähler
check( 'Zähl-Link zeigt auf die Startseite mit ID', 'https://example.test/?mgd_ebook_download=7' === MGD_Ebook_Downloads::link( 7 ) );
check( 'Start bei 0', 0 === MGD_Ebook_Downloads::count( 7 ) );
check( 'Normaler Browser zählt', MGD_Ebook_Downloads::should_count( 'GET', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Safari/604.1', array() ) );
check( 'Googlebot zählt nicht', ! MGD_Ebook_Downloads::should_count( 'GET', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', array() ) );
check( 'Link-Vorschau (WhatsApp/Facebook) zählt nicht', ! MGD_Ebook_Downloads::should_count( 'GET', 'WhatsApp/2.23.20.0', array() ) && ! MGD_Ebook_Downloads::should_count( 'GET', 'facebookexternalhit/1.1', array() ) );
check( 'Leerer User-Agent zählt nicht', ! MGD_Ebook_Downloads::should_count( 'GET', '', array() ) );
check( 'HEAD-Anfrage zählt nicht', ! MGD_Ebook_Downloads::should_count( 'HEAD', 'Mozilla/5.0 Firefox/140.0', array() ) );
check( 'Vorab-Laden (prefetch) zählt nicht', ! MGD_Ebook_Downloads::should_count( 'GET', 'Mozilla/5.0 Chrome/140.0', array( 'HTTP_SEC_PURPOSE' => 'prefetch' ) ) && ! MGD_Ebook_Downloads::should_count( 'GET', 'Mozilla/5.0 Chrome/140.0', array( 'HTTP_PURPOSE' => 'prefetch' ) ) );
MGD_Ebook_Downloads::add( 7 ); MGD_Ebook_Downloads::add( 7 );
check( 'Zähler zählt hoch', 2 === MGD_Ebook_Downloads::count( 7 ) );
$months = MGD_Ebook_Downloads::months( 7 );
check( 'Monats-Statistik zählt mit', 2 === ( $months[ gmdate( 'Y-m' ) ] ?? 0 ) );
update_post_meta( 7, MGD_Ebook_Downloads::META_MONTHS, array_fill_keys( array_map( fn( $i ) => sprintf( '20%02d-01', $i ), range( 1, 40 ) ), 1 ) );
MGD_Ebook_Downloads::add( 7 );
check( 'Statistik behält höchstens 24 Monate', 24 >= count( MGD_Ebook_Downloads::months( 7 ) ) && 1 === ( MGD_Ebook_Downloads::months( 7 )[ gmdate( 'Y-m' ) ] ?? 0 ) );
check( 'Kaputter Zählerwert wird als 0 behandelt', ( update_post_meta( 9, MGD_Ebook_Downloads::META, 'abc' ) && 0 === MGD_Ebook_Downloads::count( 9 ) ) );

echo $fails ? "\n$fails Fehler\n" : "\nAlles gut\n";
exit( $fails ? 1 : 0 );
