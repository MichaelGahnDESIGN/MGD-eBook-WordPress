<?php
defined( 'ABSPATH' ) || exit;

/**
 * Google-Infos: schema.org "Book" als JSON-LD für jedes angezeigte eBook, einmal pro Seite.
 * Nur Angaben, die auch sichtbar sein dürfen (Schalter "Im Vorschau-Fenster anzeigen" gilt).
 */
final class MGD_Ebook_Schema {
    /** @var array<int,true> */
    private static array $books = array();

    public static function register(): void {
        add_action( 'wp_footer', array( self::class, 'print' ), 99 );
    }

    public static function add( int $attachment_id ): void {
        self::$books[ $attachment_id ] = true;
    }

    /** schema.org-Daten für ein Buch. */
    public static function data( int $attachment_id ): array {
        $book = MGD_Ebook_Library::book( $attachment_id );
        $show = MGD_Ebook_Library::show( $attachment_id );
        $data = array(
            '@type' => 'Book',
            'name' => MGD_Ebook_Library::title( $attachment_id ),
            'bookFormat' => 'https://schema.org/EBook',
        );
        if ( $show['author'] && '' !== $book['author'] ) {
            $data['author'] = array_map( static fn( string $name ): array => array( '@type' => 'Person', 'name' => $name ), array_values( array_filter( array_map( 'trim', explode( ',', $book['author'] ) ) ) ) );
        }
        if ( $show['description'] && '' !== $book['description'] ) { $data['description'] = $book['description']; }
        $cover = MGD_Ebook_Library::cover_url( $attachment_id );
        if ( '' !== $cover ) { $data['image'] = $cover; }
        if ( $show['isbn'] && '' !== $book['isbn'] ) { $data['isbn'] = $book['isbn']; }
        if ( $show['publisher'] && '' !== $book['publisher'] ) { $data['publisher'] = array( '@type' => 'Organization', 'name' => $book['publisher'] ); }
        if ( $show['year'] && '' !== $book['year'] ) { $data['datePublished'] = $book['year']; }
        if ( $show['language'] && '' !== $book['language'] ) { $data['inLanguage'] = $book['language']; }
        if ( $show['series'] && '' !== $book['series'] ) {
            $data['isPartOf'] = array( '@type' => 'BookSeries', 'name' => $book['series'] );
            if ( '' !== $book['series_index'] ) { $data['position'] = $book['series_index']; }
        }
        if ( MGD_Ebook_Library::is_free( $attachment_id ) ) {
            $data['offers'] = array( '@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'EUR', 'availability' => 'https://schema.org/InStock', 'url' => MGD_Ebook_Downloads::link( $attachment_id ) );
        }
        return (array) apply_filters( 'mgd_ebook_schema', $data, $attachment_id );
    }

    public static function print(): void {
        if ( ! self::$books || is_admin() ) { return; }
        $graph = array();
        foreach ( array_keys( self::$books ) as $id ) {
            if ( MGD_Ebook_Library::is_ebook( $id ) ) { $graph[] = self::data( $id ); }
        }
        if ( ! $graph ) { return; }
        // JSON_HEX_TAG: </script> im Text kann nicht ausbrechen
        echo "\n<script type=\"application/ld+json\">" . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . "</script>\n";
    }
}
