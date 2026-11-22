<?php
// Test fuer den EPUB-Parser - laeuft ohne WordPress: php tests/epub-parser.php
define( 'ABSPATH', __DIR__ . '/' );
if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error { public function __construct( public $code = '', public $message = '' ) {} public function get_error_message() { return $this->message; } }
}
function is_wp_error( $x ) { return $x instanceof WP_Error; }
function wp_strip_all_tags( $s ) { return trim( strip_tags( $s ) ); }
require __DIR__ . '/../mgd-ebook/includes/class-mgd-ebook-epub.php';

$failed = 0;
function check( string $name, bool $ok ): void { global $failed; echo ( $ok ? 'OK    ' : 'FEHLER' ) . ' ' . $name . "\n"; if ( ! $ok ) { $failed++; } }

// kleines PNG (1x1) fuers Cover
$png = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' );

function make_epub( array $over = array() ): string {
    $files = array_merge( array(
        'mimetype' => 'application/epub+zip',
        'META-INF/container.xml' => '<?xml version="1.0"?><container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container"><rootfiles><rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/></rootfiles></container>',
        'OEBPS/content.opf' => '<?xml version="1.0"?><package xmlns="http://www.idpf.org/2007/opf" version="3.0"><metadata xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:title>Das  Testbuch</dc:title><dc:creator>Anna Müller</dc:creator><dc:creator>Zweite Person</dc:creator><dc:description>&lt;p&gt;Eine &lt;b&gt;kurze&lt;/b&gt; Beschreibung.&lt;/p&gt;</dc:description><dc:language>de</dc:language></metadata><manifest><item id="c" href="images/cover%20bild.png" media-type="image/png" properties="cover-image"/></manifest></package>',
        'OEBPS/images/cover bild.png' => $GLOBALS['png'],
    ), $over );
    $path = tempnam( sys_get_temp_dir(), 'epubtest' );
    $zip = new ZipArchive();
    $zip->open( $path, ZipArchive::OVERWRITE );
    foreach ( $files as $name => $content ) { if ( null !== $content ) { $zip->addFromString( $name, $content ); } }
    $zip->close();
    return $path;
}

// 1 gueltiges EPUB 3
$r = MGD_Ebook_Epub::read( make_epub() );
check( 'EPUB3: gueltig gelesen', is_array( $r ) );
check( 'EPUB3: Titel (Leerzeichen bereinigt)', ( $r['title'] ?? '' ) === 'Das Testbuch' );
check( 'EPUB3: zwei Autoren', ( $r['author'] ?? '' ) === 'Anna Müller, Zweite Person' );
check( 'EPUB3: Beschreibung ohne HTML', ( $r['description'] ?? '' ) === 'Eine kurze Beschreibung.' );
check( 'EPUB3: Cover gefunden (URL-kodierter Pfad)', ( $r['cover'] ?? null ) === $png );
check( 'EPUB3: Sprache', ( $r['language'] ?? '' ) === 'de' );

// Zusatzfelder: Verlag, Jahr, ISBN, Reihe (calibre) + Band
$opf_extra = '<?xml version="1.0"?><package xmlns="http://www.idpf.org/2007/opf" version="2.0"><metadata xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:title>Band</dc:title><dc:publisher>Selbstverlag</dc:publisher><dc:date>2026-11-19T21:16:14Z</dc:date><dc:identifier>urn:uuid:123</dc:identifier><dc:identifier>urn:isbn:978-3-16-148410-0</dc:identifier><meta name="calibre:series" content="Spuren &amp; Stimmen"/><meta name="calibre:series_index" content="2.0"/></metadata><manifest/></package>';
$r = MGD_Ebook_Epub::read( make_epub( array( 'OEBPS/content.opf' => $opf_extra ) ) );
check( 'Zusatz: Verlag', ( $r['publisher'] ?? '' ) === 'Selbstverlag' );
check( 'Zusatz: Jahr aus Datum', ( $r['year'] ?? '' ) === '2026' );
check( 'Zusatz: ISBN gefunden, UUID ignoriert', ( $r['isbn'] ?? '' ) === '9783161484100' );
check( 'Zusatz: Reihe + Band (2.0 > 2)', ( $r['series'] ?? '' ) === 'Spuren & Stimmen' && ( $r['series_index'] ?? '' ) === '2' );
$opf_epub3 = '<?xml version="1.0"?><package xmlns="http://www.idpf.org/2007/opf" version="3.0"><metadata xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:title>B</dc:title><meta property="belongs-to-collection" id="c1">Allverund</meta><meta refines="#c1" property="group-position">4</meta></metadata><manifest/></package>';
$r = MGD_Ebook_Epub::read( make_epub( array( 'OEBPS/content.opf' => $opf_epub3 ) ) );
check( 'Zusatz: EPUB-3-Reihe', ( $r['series'] ?? '' ) === 'Allverund' && ( $r['series_index'] ?? '' ) === '4' );

// Leseprobe: Cover-Seite weg, Text + Bild der nächsten Seiten
$sample_files = array(
    'OEBPS/content.opf' => '<?xml version="1.0"?><package xmlns="http://www.idpf.org/2007/opf" version="3.0"><metadata xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:title>S</dc:title></metadata><manifest><item id="p1" href="p1.xhtml" media-type="application/xhtml+xml"/><item id="p2" href="p2.xhtml" media-type="application/xhtml+xml"/><item id="p3" href="p3.xhtml" media-type="application/xhtml+xml"/><item id="i" href="images/cover%20bild.png" media-type="image/png"/></manifest><spine><itemref idref="p1"/><itemref idref="p2"/><itemref idref="p3"/></spine></package>',
    'OEBPS/p1.xhtml' => '<html xmlns="http://www.w3.org/1999/xhtml"><body><img src="images/cover%20bild.png"/></body></html>',
    'OEBPS/p2.xhtml' => '<html xmlns="http://www.w3.org/1999/xhtml"><body><h2>Kapitel&nbsp;1</h2><p>Es war <b>einmal</b>.</p><img src="images/cover%20bild.png"/><img src="https://evil.example/x.png"/><img src="../../etc/passwd"/></body></html>',
    'OEBPS/p3.xhtml' => '<html xmlns="http://www.w3.org/1999/xhtml"><body><p>Seite drei</p></body></html>',
);
$pages = MGD_Ebook_Epub::sample_pages( make_epub( $sample_files ), 1 );
check( 'Leseprobe: Cover-Seite übersprungen, 1 Seite', is_array( $pages ) && 1 === count( $pages ) );
check( 'Leseprobe: Überschrift + Absatz als Text (ohne HTML)', ( $pages[0][0]['type'] ?? '' ) === 'h' && str_contains( $pages[0][0]['text'] ?? '', 'Kapitel' ) && ( $pages[0][1]['text'] ?? '' ) === 'Es war einmal.' );
check( 'Leseprobe: nur das eigene Bild, keine fremden oder ../-Pfade', 3 === count( $pages[0] ) && 'img' === $pages[0][2]['type'] && $pages[0][2]['bytes'] === $png );
$pages = MGD_Ebook_Epub::sample_pages( make_epub( $sample_files ), 5 );
check( 'Leseprobe: mehr Seiten gewünscht als da sind', 2 === count( $pages ) );

// 2 EPUB 2 mit meta name=cover
$opf2 = '<?xml version="1.0"?><package xmlns="http://www.idpf.org/2007/opf" version="2.0"><metadata xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:opf="http://www.idpf.org/2007/opf"><dc:title>Alt</dc:title><meta name="cover" content="cov"/></metadata><manifest><item id="cov" href="img/x.png" media-type="image/png"/></manifest></package>';
$r = MGD_Ebook_Epub::read( make_epub( array( 'OEBPS/content.opf' => $opf2, 'OEBPS/img/x.png' => $png, 'OEBPS/images/cover bild.png' => null ) ) );
check( 'EPUB2: Cover ueber meta name=cover', is_array( $r ) && $r['cover'] === $png );

// 3 ohne Cover
$opf3 = '<?xml version="1.0"?><package xmlns="http://www.idpf.org/2007/opf" version="3.0"><metadata xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:title>Ohne</dc:title></metadata><manifest/></package>';
$r = MGD_Ebook_Epub::read( make_epub( array( 'OEBPS/content.opf' => $opf3 ) ) );
check( 'ohne Cover: gueltig, cover = null', is_array( $r ) && null === $r['cover'] && 'Ohne' === $r['title'] );

// 4 falsche mimetype
check( 'falsche mimetype wird abgelehnt', is_wp_error( MGD_Ebook_Epub::read( make_epub( array( 'mimetype' => 'text/plain' ) ) ) ) );
// 5 kein ZIP
$txt = tempnam( sys_get_temp_dir(), 'epubtest' ); file_put_contents( $txt, 'kein zip' );
check( 'Text-Datei wird abgelehnt', is_wp_error( MGD_Ebook_Epub::read( $txt ) ) );
// 6 container fehlt
check( 'container.xml fehlt wird abgelehnt', is_wp_error( MGD_Ebook_Epub::read( make_epub( array( 'META-INF/container.xml' => null ) ) ) ) );
// 7 Pfad mit .. im Cover
$evil = str_replace( 'images/cover%20bild.png', '../../META-INF/../../etc/passwd', make_epub_opf() );
function make_epub_opf(): string { return '<?xml version="1.0"?><package xmlns="http://www.idpf.org/2007/opf" version="3.0"><metadata xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:title>X</dc:title></metadata><manifest><item id="c" href="images/cover%20bild.png" media-type="image/png" properties="cover-image"/></manifest></package>'; }
$r = MGD_Ebook_Epub::read( make_epub( array( 'OEBPS/content.opf' => $evil ) ) );
check( 'Pfad mit ".." liefert kein Cover', is_array( $r ) && null === $r['cover'] );
// 8 XML-Entity-Angriff
$bomb = '<?xml version="1.0"?><!DOCTYPE x [<!ENTITY a "aaaa">]><package xmlns="http://www.idpf.org/2007/opf"><metadata xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:title>&a;</dc:title></metadata><manifest/></package>';
check( 'XML mit ENTITY wird abgelehnt', is_wp_error( MGD_Ebook_Epub::read( make_epub( array( 'OEBPS/content.opf' => $bomb ) ) ) ) );
// 9 riesiges Cover wird ignoriert
$big = str_repeat( 'A', 13 * 1024 * 1024 );
$r = MGD_Ebook_Epub::read( make_epub( array( 'OEBPS/images/cover bild.png' => $big ) ) );
check( 'zu grosses Cover wird ignoriert, Buch bleibt gueltig', is_array( $r ) && null === $r['cover'] );

echo $failed ? "\n$failed Test(s) fehlgeschlagen\n" : "\nAlle Tests bestanden\n";
exit( $failed ? 1 : 0 );
