<?php
defined( 'ABSPATH' ) || exit;

/**
 * Liest eine EPUB-Datei (ist technisch ein ZIP) ein.
 * Nichts wird entpackt - nur Container, Metadaten und Cover werden gezielt in den Speicher gelesen.
 */
final class MGD_Ebook_Epub {
    // Grenzen gegen kaputte oder boesartige Dateien (ZIP-Bomben)
    private const MAX_ENTRIES = 20000;
    private const MAX_XML_BYTES = 2097152;   // 2 MB pro XML
    private const MAX_COVER_BYTES = 12582912; // 12 MB fuers Cover
    // Leseprobe-Grenzen
    private const MAX_SAMPLE_BYTES = 62914560; // 60 MB Bilder insgesamt
    private const MAX_SAMPLE_IMAGES = 6;      // pro Seite
    private const MAX_SAMPLE_CHARS = 6000;    // Text pro Seite

    /**
     * Prueft die Datei und liest Titel, Autor, Beschreibung und Cover.
     *
     * @return array{title:string,author:string,description:string,language:string,cover:?string}|WP_Error
     */
    public static function read( string $path ) {
        if ( ! class_exists( 'ZipArchive' ) ) {
            return new WP_Error( 'mgd_ebook_no_zip', 'Auf diesem Server fehlt die PHP-Erweiterung „zip“. Bitte beim Hoster aktivieren lassen.' );
        }
        if ( ! is_readable( $path ) ) {
            return new WP_Error( 'mgd_ebook_unreadable', 'Die Datei kann nicht gelesen werden.' );
        }
        $zip = new ZipArchive();
        if ( true !== $zip->open( $path ) ) {
            return new WP_Error( 'mgd_ebook_not_zip', 'Das ist keine gültige EPUB-Datei (kein ZIP-Archiv).' );
        }
        try {
            if ( $zip->numFiles < 1 || $zip->numFiles > self::MAX_ENTRIES ) {
                return new WP_Error( 'mgd_ebook_entries', 'Die EPUB-Datei hat eine ungewöhnliche Struktur.' );
            }
            // mimetype-Datei muss genau application/epub+zip sagen
            $mimetype = self::entry( $zip, 'mimetype', 256 );
            if ( null === $mimetype || 'application/epub+zip' !== trim( $mimetype ) ) {
                return new WP_Error( 'mgd_ebook_mimetype', 'Das ist keine gültige EPUB-Datei (Kennung „application/epub+zip“ fehlt).' );
            }
            $container = self::entry( $zip, 'META-INF/container.xml', self::MAX_XML_BYTES );
            $opf_path = null === $container ? '' : self::rootfile( $container );
            if ( '' === $opf_path ) {
                return new WP_Error( 'mgd_ebook_container', 'Die EPUB-Datei ist unvollständig (container.xml fehlt oder ist defekt).' );
            }
            $opf = self::entry( $zip, $opf_path, self::MAX_XML_BYTES );
            if ( null === $opf ) {
                return new WP_Error( 'mgd_ebook_opf', 'Die EPUB-Datei ist unvollständig (Paketdatei fehlt).' );
            }
            $meta = self::parse_opf( $opf );
            if ( null === $meta ) {
                return new WP_Error( 'mgd_ebook_opf_xml', 'Die EPUB-Datei ist defekt (Paketdatei nicht lesbar).' );
            }
            $cover = null;
            if ( '' !== $meta['cover_href'] ) {
                $base = dirname( $opf_path );
                $cover_path = self::join( '.' === $base ? '' : $base, rawurldecode( $meta['cover_href'] ) );
                $cover = '' === $cover_path ? null : self::entry( $zip, $cover_path, self::MAX_COVER_BYTES );
            }
            return array(
                'title' => $meta['title'],
                'author' => $meta['author'],
                'description' => $meta['description'],
                'language' => $meta['language'],
                'publisher' => $meta['publisher'],
                'year' => $meta['year'],
                'isbn' => $meta['isbn'],
                'series' => $meta['series'],
                'series_index' => $meta['series_index'],
                'cover' => $cover,
            );
        } finally {
            $zip->close();
        }
    }

    /**
     * Leseprobe: die ersten Seiten als Blöcke (Überschrift, Absatz, Bild). Nur Text + Rasterbilder, kein HTML.
     * Eine reine Cover-Seite am Anfang (nur 1 Bild, kein Text) wird übersprungen.
     *
     * @return list<list<array{type:string,text?:string,bytes?:string}>>|WP_Error
     */
    public static function sample_pages( string $path, int $pages ) {
        $pages = max( 1, min( 10, $pages ) );
        if ( ! class_exists( 'ZipArchive' ) ) { return new WP_Error( 'mgd_ebook_no_zip', 'Auf diesem Server fehlt die PHP-Erweiterung „zip“.' ); }
        $zip = new ZipArchive();
        if ( true !== $zip->open( $path ) ) { return new WP_Error( 'mgd_ebook_not_zip', 'Die EPUB-Datei kann nicht gelesen werden.' ); }
        try {
            $container = self::entry( $zip, 'META-INF/container.xml', self::MAX_XML_BYTES );
            $opf_path = null === $container ? '' : self::rootfile( $container );
            $opf = '' === $opf_path ? null : self::entry( $zip, $opf_path, self::MAX_XML_BYTES );
            $dom = null === $opf ? null : self::load_xml( $opf );
            if ( null === $dom ) { return new WP_Error( 'mgd_ebook_opf', 'Die EPUB-Datei ist unvollständig.' ); }
            $base = dirname( $opf_path );
            $base = '.' === $base ? '' : $base;
            $xpath = new DOMXPath( $dom );
            $manifest = array();
            foreach ( $xpath->query( "//*[local-name()='manifest']/*[local-name()='item']" ) as $item ) {
                if ( $item instanceof DOMElement ) { $manifest[ $item->getAttribute( 'id' ) ] = $item; }
            }
            $out = array();
            $budget = self::MAX_SAMPLE_BYTES;
            $first = true;
            foreach ( $xpath->query( "//*[local-name()='spine']/*[local-name()='itemref']" ) as $ref ) {
                if ( count( $out ) >= $pages ) { break; }
                if ( ! $ref instanceof DOMElement || 'no' === $ref->getAttribute( 'linear' ) ) { continue; }
                $item = $manifest[ $ref->getAttribute( 'idref' ) ] ?? null;
                if ( ! $item instanceof DOMElement || ! preg_match( '~html~i', $item->getAttribute( 'media-type' ) ) ) { continue; }
                $doc_path = self::join( $base, rawurldecode( $item->getAttribute( 'href' ) ) );
                $html = '' === $doc_path ? null : self::entry( $zip, $doc_path, self::MAX_XML_BYTES );
                if ( null === $html ) { continue; }
                $blocks = self::page_blocks( $zip, $html, dirname( $doc_path ), $budget );
                $was_first = $first;
                $first = false;
                if ( ! $blocks ) { continue; }
                // Cover-Seite vorne weglassen, das Cover steht eh schon im Fenster
                if ( $was_first && 1 === count( $blocks ) && 'img' === $blocks[0]['type'] ) { continue; }
                $out[] = $blocks;
            }
            return $out;
        } finally {
            $zip->close();
        }
    }

    /** Eine Seite in Blöcke zerlegen, in Lesereihenfolge. */
    private static function page_blocks( ZipArchive $zip, string $html, string $dir, int &$budget ): array {
        if ( false !== stripos( $html, '<!ENTITY' ) ) { return array(); }
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors( true );
        // HTML-Parser kennt &nbsp; usw., XML-Parser nicht
        $ok = $dom->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
        libxml_clear_errors();
        libxml_use_internal_errors( $previous );
        if ( ! $ok ) { return array(); }
        $dir = '.' === $dir ? '' : $dir;
        $blocks = array();
        $chars = 0;
        $images = 0;
        $xpath = new DOMXPath( $dom );
        foreach ( $xpath->query( '//body//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6 or self::p or self::li or self::img or self::image]' ) as $node ) {
            if ( ! $node instanceof DOMElement ) { continue; }
            $tag = strtolower( $node->localName );
            if ( 'img' === $tag || 'image' === $tag ) {
                if ( $images >= self::MAX_SAMPLE_IMAGES ) { continue; }
                $src = $node->getAttribute( 'img' === $tag ? 'src' : 'xlink:href' ) ?: $node->getAttribute( 'href' );
                if ( '' === $src || preg_match( '~^[a-z][a-z0-9+.-]*:~i', $src ) ) { continue; }
                $path = self::join( $dir, rawurldecode( (string) strtok( $src, '#?' ) ) );
                $bytes = '' === $path ? null : self::entry( $zip, $path, min( self::MAX_COVER_BYTES, max( 0, $budget ) ) );
                $info = null === $bytes ? false : @getimagesizefromstring( $bytes );
                if ( ! is_array( $info ) || ! in_array( $info[2], array( IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP ), true ) ) { continue; }
                $budget -= strlen( $bytes );
                $images++;
                $blocks[] = array( 'type' => 'img', 'bytes' => $bytes );
                continue;
            }
            // verschachtelte Absätze nicht doppelt
            if ( 'li' === $tag && $xpath->query( './/p', $node )->length ) { continue; }
            $text = trim( preg_replace( '/\s+/u', ' ', $node->textContent ) ?? '' );
            if ( '' === $text || $chars >= self::MAX_SAMPLE_CHARS ) { continue; }
            $text = mb_substr( $text, 0, self::MAX_SAMPLE_CHARS - $chars );
            $chars += mb_strlen( $text );
            $blocks[] = array( 'type' => preg_match( '/^h[1-6]$/', $tag ) ? 'h' : 'p', 'text' => $text );
        }
        return $blocks;
    }

    /** Eintrag lesen, aber nur wenn er nicht zu gross ist. null = nicht da oder zu gross. */
    private static function entry( ZipArchive $zip, string $name, int $max ): ?string {
        $stat = $zip->statName( $name );
        if ( false === $stat ) {
            // zur Not ohne Beachtung der Gross-/Kleinschreibung suchen
            $stat = $zip->statName( $name, ZipArchive::FL_NOCASE );
        }
        if ( false === $stat || $stat['size'] > $max || $stat['size'] < 0 ) {
            return null;
        }
        $data = $zip->getFromIndex( (int) $stat['index'], $max + 1 );
        return ( false === $data || strlen( $data ) > $max ) ? null : $data;
    }

    /** Pfad zur OPF-Datei aus container.xml holen. */
    private static function rootfile( string $xml ): string {
        $dom = self::load_xml( $xml );
        if ( null === $dom ) { return ''; }
        $xpath = new DOMXPath( $dom );
        $node = $xpath->query( "//*[local-name()='rootfile'][@full-path]" )->item( 0 );
        if ( ! $node instanceof DOMElement ) { return ''; }
        return self::join( '', rawurldecode( $node->getAttribute( 'full-path' ) ) );
    }

    /** @return array{title:string,author:string,description:string,language:string,publisher:string,year:string,isbn:string,series:string,series_index:string,cover_href:string}|null */
    private static function parse_opf( string $xml ): ?array {
        $dom = self::load_xml( $xml );
        if ( null === $dom ) { return null; }
        $xpath = new DOMXPath( $dom );
        $first = static function ( string $name ) use ( $xpath ): string {
            $node = $xpath->query( "//*[local-name()='metadata']/*[local-name()='$name']" )->item( 0 );
            return $node ? trim( preg_replace( '/\s+/u', ' ', $node->textContent ) ?? '' ) : '';
        };
        $authors = array();
        foreach ( $xpath->query( "//*[local-name()='metadata']/*[local-name()='creator']" ) as $node ) {
            $name = trim( preg_replace( '/\s+/u', ' ', $node->textContent ) ?? '' );
            if ( '' !== $name ) { $authors[] = $name; }
        }
        $description = $xpath->query( "//*[local-name()='metadata']/*[local-name()='description']" )->item( 0 );
        $description = $description ? trim( preg_replace( '/[ \t]+/u', ' ', wp_strip_all_tags( $description->textContent ) ) ?? '' ) : '';

        // Cover-Eintrag suchen: EPUB 3 (properties), EPUB 2 (meta name=cover), dann Notloesung
        $items = array();
        foreach ( $xpath->query( "//*[local-name()='manifest']/*[local-name()='item']" ) as $node ) {
            if ( $node instanceof DOMElement ) { $items[] = $node; }
        }
        $is_image = static fn( DOMElement $i ): bool => 0 === strpos( strtolower( $i->getAttribute( 'media-type' ) ), 'image/' );
        $href = '';
        foreach ( $items as $item ) {
            if ( $is_image( $item ) && in_array( 'cover-image', preg_split( '/\s+/', trim( $item->getAttribute( 'properties' ) ) ) ?: array(), true ) ) { $href = $item->getAttribute( 'href' ); break; }
        }
        if ( '' === $href ) {
            $meta = $xpath->query( "//*[local-name()='metadata']/*[local-name()='meta'][@name='cover']" )->item( 0 );
            $id = $meta instanceof DOMElement ? $meta->getAttribute( 'content' ) : '';
            foreach ( $items as $item ) {
                if ( '' !== $id && $item->getAttribute( 'id' ) === $id && $is_image( $item ) ) { $href = $item->getAttribute( 'href' ); break; }
            }
        }
        if ( '' === $href ) {
            foreach ( $items as $item ) {
                if ( $is_image( $item ) && preg_match( '/cover/i', $item->getAttribute( 'id' ) . ' ' . $item->getAttribute( 'href' ) ) ) { $href = $item->getAttribute( 'href' ); break; }
            }
        }
        // Zusatzdaten: Verlag, Jahr, ISBN, Reihe (EPUB 3 belongs-to-collection oder calibre)
        $year = preg_match( '/\b(1[5-9]\d\d|2\d\d\d)\b/', $first( 'date' ), $y ) ? $y[1] : '';
        $isbn = '';
        foreach ( $xpath->query( "//*[local-name()='metadata']/*[local-name()='identifier']" ) as $node ) {
            $raw = strtoupper( preg_replace( '/[^0-9Xx]/', '', $node->textContent ) ?? '' );
            if ( preg_match( '/^(97[89]\d{10}|\d{9}[\dX])$/', $raw ) ) { $isbn = $raw; break; }
        }
        $meta_value = static function ( string $query ) use ( $xpath ): string {
            $node = $xpath->query( $query )->item( 0 );
            if ( ! $node instanceof DOMElement ) { return ''; }
            $value = $node->hasAttribute( 'content' ) ? $node->getAttribute( 'content' ) : $node->textContent;
            return trim( preg_replace( '/\s+/u', ' ', $value ) ?? '' );
        };
        $series = $meta_value( "//*[local-name()='metadata']/*[local-name()='meta'][@property='belongs-to-collection']" );
        $series = '' !== $series ? $series : $meta_value( "//*[local-name()='metadata']/*[local-name()='meta'][@name='calibre:series']" );
        $index = $meta_value( "//*[local-name()='metadata']/*[local-name()='meta'][@property='group-position']" );
        $index = '' !== $index ? $index : $meta_value( "//*[local-name()='metadata']/*[local-name()='meta'][@name='calibre:series_index']" );
        $index = preg_match( '/^\d{1,4}(?:[.,]\d{1,2})?$/', $index ) ? rtrim( rtrim( str_replace( ',', '.', $index ), '0' ), '.' ) : '';
        return array(
            'title' => mb_substr( $first( 'title' ), 0, 300 ),
            'author' => mb_substr( implode( ', ', array_slice( $authors, 0, 5 ) ), 0, 300 ),
            'description' => mb_substr( $description, 0, 1500 ),
            'language' => mb_substr( $first( 'language' ), 0, 20 ),
            'publisher' => mb_substr( $first( 'publisher' ), 0, 200 ),
            'year' => $year,
            'isbn' => $isbn,
            'series' => mb_substr( $series, 0, 200 ),
            'series_index' => $index,
            'cover_href' => $href,
        );
    }

    /** XML sicher laden: kein Netzwerk, keine Entitaeten. */
    private static function load_xml( string $xml ): ?DOMDocument {
        if ( false !== stripos( $xml, '<!ENTITY' ) ) { return null; }
        $previous = libxml_use_internal_errors( true );
        $dom = new DOMDocument();
        $ok = $dom->loadXML( $xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING );
        libxml_clear_errors();
        libxml_use_internal_errors( $previous );
        return $ok ? $dom : null;
    }

    /** Pfad zusammensetzen und aufraeumen. Leer = unzulaessig (z. B. mit ".."). */
    private static function join( string $base, string $relative ): string {
        $relative = str_replace( '\\', '/', $relative );
        if ( '' === $relative || str_starts_with( $relative, '/' ) || false !== strpos( $relative, "\0" ) ) { return ''; }
        $parts = array();
        foreach ( explode( '/', ( '' === $base ? '' : $base . '/' ) . $relative ) as $part ) {
            if ( '' === $part || '.' === $part ) { continue; }
            if ( '..' === $part ) {
                if ( ! $parts ) { return ''; }
                array_pop( $parts );
                continue;
            }
            $parts[] = $part;
        }
        return implode( '/', $parts );
    }
}
