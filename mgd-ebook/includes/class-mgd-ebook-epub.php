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
                'cover' => $cover,
            );
        } finally {
            $zip->close();
        }
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

    /** @return array{title:string,author:string,description:string,language:string,cover_href:string}|null */
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
        return array(
            'title' => mb_substr( $first( 'title' ), 0, 300 ),
            'author' => mb_substr( implode( ', ', array_slice( $authors, 0, 5 ) ), 0, 300 ),
            'description' => mb_substr( $description, 0, 1500 ),
            'language' => mb_substr( $first( 'language' ), 0, 20 ),
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
