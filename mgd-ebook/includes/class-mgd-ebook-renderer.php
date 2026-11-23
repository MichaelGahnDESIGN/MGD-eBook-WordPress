<?php
defined( 'ABSPATH' ) || exit;

/** Shortcodes [mgd_ebook] (ein Buch) und [mgd_ebook_regal] (Bücherregal). Klick aufs Cover öffnet das Vorschau-Fenster. */
final class MGD_Ebook_Renderer {
    private static int $counter = 0;
    private const MAX_SHELF = 60;

    public function register(): void {
        add_shortcode( 'mgd_ebook', array( $this, 'shortcode' ) );
        add_shortcode( 'mgd_ebook_regal', array( $this, 'shelf' ) );
    }

    // Dateien werden nur registriert, geladen wird erst wenn ein Shortcode auf der Seite ist
    public function assets(): void {
        wp_register_style( 'mgd-ebook', MGD_EBOOK_URL . 'assets/ebook.css', array(), MGD_EBOOK_VERSION );
        wp_register_script( 'mgd-ebook', MGD_EBOOK_URL . 'assets/ebook.js', array(), MGD_EBOOK_VERSION, true );
    }

    public function shortcode( $atts ): string {
        $atts = shortcode_atts( array( 'id' => 0, 'width' => 220, 'caption' => 'yes', 'button' => 'Download' ), is_array( $atts ) ? $atts : array(), 'mgd_ebook' );
        $id = absint( $atts['id'] );
        if ( ! $id || ! MGD_Ebook_Library::is_ebook( $id ) ) {
            // Besucher sehen nichts, Redakteure bekommen einen Hinweis
            return current_user_can( 'edit_posts' ) ? '<p class="mgd-ebook-hint">MGD eBook: Zu diesem Shortcode gibt es kein eBook. Bitte die ID prüfen.</p>' : '';
        }
        return $this->book( $id, $atts );
    }

    /** Bücherregal: ids="3,1,2" in genau der Reihenfolge oder reihe="..." sortiert nach Band. */
    public function shelf( $atts ): string {
        $atts = shortcode_atts( array( 'ids' => '', 'reihe' => '', 'width' => 180, 'caption' => 'yes', 'button' => 'Download' ), is_array( $atts ) ? $atts : array(), 'mgd_ebook_regal' );
        $ids = self::shelf_ids( (string) $atts['ids'], (string) $atts['reihe'] );
        if ( ! $ids ) {
            return current_user_can( 'edit_posts' ) ? '<p class="mgd-ebook-hint">MGD eBook: Für dieses Bücherregal wurde kein eBook gefunden. Bitte IDs oder Reihe prüfen.</p>' : '';
        }
        $width = min( 600, max( 80, absint( $atts['width'] ) ) );
        $html = '';
        foreach ( $ids as $id ) { $html .= $this->book( $id, $atts ); }
        return '<div class="mgd-ebook-regal" style="--mgd-ebook-width:' . (int) $width . 'px">' . $html . '</div>';
    }

    /** @return list<int> */
    public static function shelf_ids( string $ids, string $series ): array {
        $out = array();
        foreach ( preg_split( '/[\s,;]+/', $ids, -1, PREG_SPLIT_NO_EMPTY ) ?: array() as $raw ) {
            $id = absint( $raw );
            if ( $id && ! in_array( $id, $out, true ) && MGD_Ebook_Library::is_ebook( $id ) ) { $out[] = $id; }
        }
        $series = trim( sanitize_text_field( $series ) );
        if ( '' === $series ) { return array_slice( $out, 0, self::MAX_SHELF ); }
        $query = new WP_Query( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => MGD_Ebook_Library::MIME, 'posts_per_page' => 200, 'fields' => 'ids', 'no_found_rows' => true ) );
        $found = array();
        foreach ( $query->posts as $id ) {
            $book = MGD_Ebook_Library::book( (int) $id );
            if ( 0 === strcasecmp( trim( $book['series'] ), $series ) && ! in_array( (int) $id, $out, true ) ) { $found[ (int) $id ] = $book['series_index']; }
        }
        // nach Band, ohne Band ans Ende
        uksort( $found, static function ( int $a, int $b ) use ( $found ): int {
            $x = '' === $found[ $a ] ? PHP_FLOAT_MAX : (float) $found[ $a ];
            $y = '' === $found[ $b ] ? PHP_FLOAT_MAX : (float) $found[ $b ];
            return $x <=> $y ?: $a <=> $b;
        } );
        return array_slice( array_merge( $out, array_keys( $found ) ), 0, self::MAX_SHELF );
    }

    private function book( int $id, array $atts ): string {
        $url = MGD_Ebook_Library::file_url( $id );
        if ( '' === $url ) { return ''; }
        $book = MGD_Ebook_Library::book( $id );
        $show = MGD_Ebook_Library::show( $id );
        $free = MGD_Ebook_Library::is_free( $id );
        $sample = MGD_Ebook_Sample::settings( $id );
        $data = array(
            'uid' => 'mgd-ebook-' . $id . '-' . ( ++self::$counter ),
            'url' => $free ? MGD_Ebook_Downloads::link( $id ) : '',
            // ohne JavaScript: kostenlos = Download, Verkauf = Produktseite
            'cover_href' => $free ? MGD_Ebook_Downloads::link( $id ) : (string) apply_filters( 'mgd_ebook_cover_href', '', $id ),
            'title' => MGD_Ebook_Library::title( $id ),
            'book' => $book,
            'show' => $show,
            'details' => self::details( $book, $show ),
            'cover' => MGD_Ebook_Library::cover_url( $id ),
            'size' => $show['size'] ? MGD_Ebook_Library::file_size( $id ) : '',
            'sample' => $sample['on'] ? MGD_Ebook_Sample::pages( $id ) : array(),
            'width' => min( 800, max( 80, absint( $atts['width'] ) ) ),
            'caption' => ! in_array( strtolower( (string) $atts['caption'] ), array( 'no', 'false', '0' ), true ),
            'button' => '' !== trim( (string) $atts['button'] ) ? sanitize_text_field( (string) $atts['button'] ) : 'Download',
            'action' => (string) apply_filters( 'mgd_ebook_action_html', '', $id ),
        );
        wp_enqueue_style( 'mgd-ebook' );
        wp_enqueue_script( 'mgd-ebook' );
        MGD_Ebook_Schema::add( $id );
        ob_start();
        include MGD_EBOOK_DIR . 'templates/ebook.php';
        return (string) ob_get_clean();
    }

    /** Zusatzangaben fürs Fenster (nur gefüllte + eingeschaltete). @return list<array{0:string,1:string}> */
    public static function details( array $book, array $show ): array {
        $rows = array();
        if ( $show['series'] && '' !== $book['series'] ) { $rows[] = array( 'Reihe', $book['series'] . ( '' !== $book['series_index'] ? ' · Band ' . $book['series_index'] : '' ) ); }
        foreach ( array( 'publisher', 'year', 'isbn', 'language' ) as $key ) {
            if ( $show[ $key ] && '' !== $book[ $key ] ) { $rows[] = array( MGD_Ebook_Library::FIELDS[ $key ], $book[ $key ] ); }
        }
        return $rows;
    }
}
