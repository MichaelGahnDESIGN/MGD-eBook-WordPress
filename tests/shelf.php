<?php
/**
 * Test Bücherregal + Angaben im Fenster ohne WordPress: php tests/shelf.php
 */
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['meta'] = array();
$GLOBALS['books'] = array( 11, 12, 13, 14 );
function get_post_meta( $id, $k, $s = false ) { return $GLOBALS['meta'][ $id ][ $k ] ?? ''; }
function get_post_type( $id ) { return in_array( $id, $GLOBALS['books'], true ) || 20 === $id ? 'attachment' : false; }
function get_post_mime_type( $id ) { return in_array( $id, $GLOBALS['books'], true ) ? 'application/epub+zip' : 'image/png'; }
function absint( $v ) { return abs( (int) $v ); }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
class WP_Query { public array $posts; public function __construct( $a ) { $this->posts = $GLOBALS['books']; } }
require __DIR__ . '/../mgd-ebook/includes/class-mgd-ebook-library.php';
require __DIR__ . '/../mgd-ebook/includes/class-mgd-ebook-renderer.php';

$fails = 0;
function check( string $name, bool $ok ): void { global $fails; echo ( $ok ? 'OK    ' : 'FEHLER' ) . ' ' . $name . "\n"; $fails += $ok ? 0 : 1; }
$set = function ( int $id, array $epub, array $custom = array() ) {
	$GLOBALS['meta'][ $id ][ MGD_Ebook_Library::META_BOOK ] = $epub;
	$GLOBALS['meta'][ $id ][ MGD_Ebook_Library::META_CUSTOM ] = $custom;
};
$set( 11, array( 'title' => 'Band drei' ), array( 'series' => 'Spuren & Stimmen', 'series_index' => '3' ) );
$set( 12, array( 'title' => 'Band eins', 'series' => 'Spuren & Stimmen', 'series_index' => '1' ) );
$set( 13, array( 'title' => 'Ohne Band' ), array( 'series' => 'spuren & stimmen' ) );
$set( 14, array( 'title' => 'Andere Reihe', 'series' => 'Anderes' ) );

check( 'IDs in genau der angegebenen Reihenfolge', array( 13, 11, 12 ) === MGD_Ebook_Renderer::shelf_ids( '13, 11,12', '' ) );
check( 'Doppelte, falsche und Nicht-eBook-IDs fliegen raus', array( 12 ) === MGD_Ebook_Renderer::shelf_ids( '12,12,99,abc,20', '' ) );
check( 'Reihe: sortiert nach Band, ohne Band am Ende, Groß/klein egal', array( 12, 11, 13 ) === MGD_Ebook_Renderer::shelf_ids( '', 'Spuren & Stimmen' ) );
check( 'IDs + Reihe: IDs zuerst, keine Doppelten', array( 13, 12, 11 ) === MGD_Ebook_Renderer::shelf_ids( '13', 'Spuren & Stimmen' ) );
check( 'Unbekannte Reihe: leer', array() === MGD_Ebook_Renderer::shelf_ids( '', 'Gibt es nicht' ) );

$book = array( 'series' => 'Spuren & Stimmen', 'series_index' => '2', 'publisher' => 'Selbstverlag', 'year' => '2026', 'isbn' => '', 'language' => 'de' );
$rows = MGD_Ebook_Renderer::details( $book, MGD_Ebook_Library::SHOW );
check( 'Angaben: Reihe mit Band, Verlag, Jahr; leere ISBN + abgeschaltete Sprache fehlen', array( array( 'Reihe', 'Spuren & Stimmen · Band 2' ), array( 'Verlag', 'Selbstverlag' ), array( 'Erscheinungsjahr', '2026' ) ) === $rows );
$rows = MGD_Ebook_Renderer::details( $book, array( 'series' => false, 'language' => true ) + MGD_Ebook_Library::SHOW );
check( 'Angaben: Schalter wirken (Reihe aus, Sprache an)', 'Verlag' === $rows[0][0] && 'Sprache' === end( $rows )[0] );

echo $fails ? "\n$fails Fehler\n" : "\nAlles gut\n";
exit( $fails ? 1 : 0 );
