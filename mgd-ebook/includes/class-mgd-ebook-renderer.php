<?php
defined( 'ABSPATH' ) || exit;

/** Shortcode [mgd_ebook]: Cover, Klick oeffnet Vorschau-Fenster mit Download. */
final class MGD_Ebook_Renderer {
    private static int $counter = 0;

    public function register(): void {
        add_shortcode( 'mgd_ebook', array( $this, 'shortcode' ) );
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
        $url = MGD_Ebook_Library::file_url( $id );
        if ( '' === $url ) { return ''; }
        $book = MGD_Ebook_Library::book( $id );
        $data = array(
            'uid' => 'mgd-ebook-' . $id . '-' . ( ++self::$counter ),
            'url' => $url,
            'title' => MGD_Ebook_Library::title( $id ),
            'author' => $book['author'],
            'description' => $book['description'],
            'cover' => MGD_Ebook_Library::cover_url( $id ),
            'size' => MGD_Ebook_Library::file_size( $id ),
            'width' => min( 800, max( 80, absint( $atts['width'] ) ) ),
            'caption' => ! in_array( strtolower( (string) $atts['caption'] ), array( 'no', 'false', '0' ), true ),
            'button' => '' !== trim( (string) $atts['button'] ) ? sanitize_text_field( (string) $atts['button'] ) : 'Download',
        );
        wp_enqueue_style( 'mgd-ebook' );
        wp_enqueue_script( 'mgd-ebook' );
        ob_start();
        include MGD_EBOOK_DIR . 'templates/ebook.php';
        return (string) ob_get_clean();
    }
}
