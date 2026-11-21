<?php
defined( 'ABSPATH' ) || exit;

/** Alles rund um die hochgeladenen EPUB-Dateien: Erlaubnis, Pruefung, Cover, Liste. */
final class MGD_Ebook_Library {
    public const MIME = 'application/epub+zip';
    public const META_BOOK = '_mgd_ebook';          // Titel, Autor, Beschreibung
    public const META_COVER = '_mgd_ebook_cover';   // Pfad zum Cover (relativ zu uploads)
    private const COVER_DIR = 'mgd-ebook-covers';
    private const COVER_MAX_WIDTH = 900;
    private const COVER_MAX_HEIGHT = 1350;

    public function register(): void {
        // epub in der Mediathek erlauben
        add_filter( 'upload_mimes', array( $this, 'allow_epub' ) );
        // Datei pruefen bevor sie gespeichert wird
        add_filter( 'wp_handle_upload_prefilter', array( $this, 'prefilter' ) );
        // nach dem Speichern Daten + Cover einlesen
        add_action( 'add_attachment', array( $this, 'after_upload' ) );
        add_action( 'delete_attachment', array( $this, 'cleanup' ) );
    }

    public function allow_epub( array $mimes ): array {
        $mimes['epub'] = self::MIME;
        return $mimes;
    }

    /** Falsche .epub-Dateien schon vor dem Speichern abweisen. */
    public function prefilter( array $file ): array {
        $name = (string) ( $file['name'] ?? '' );
        if ( 'epub' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) { return $file; }
        $parsed = MGD_Ebook_Epub::read( (string) ( $file['tmp_name'] ?? '' ) );
        if ( is_wp_error( $parsed ) ) { $file['error'] = $parsed->get_error_message(); }
        return $file;
    }

    public function after_upload( int $attachment_id ): void {
        if ( self::MIME !== get_post_mime_type( $attachment_id ) ) { return; }
        $this->index( $attachment_id );
    }

    /** Daten + Cover (neu) einlesen. Gibt true zurueck wenn die Datei lesbar war. */
    public function index( int $attachment_id ): bool {
        $path = get_attached_file( $attachment_id );
        $parsed = $path ? MGD_Ebook_Epub::read( $path ) : new WP_Error( 'mgd_ebook_missing', 'Datei fehlt.' );
        if ( is_wp_error( $parsed ) ) { return false; }
        update_post_meta( $attachment_id, self::META_BOOK, array(
            'title' => $parsed['title'],
            'author' => $parsed['author'],
            'description' => $parsed['description'],
            'language' => $parsed['language'],
        ) );
        $this->remove_cover( $attachment_id );
        if ( null !== $parsed['cover'] ) {
            $stored = $this->store_cover( $attachment_id, $parsed['cover'] );
            if ( '' !== $stored ) { update_post_meta( $attachment_id, self::META_COVER, $stored ); }
        }
        // Titel der Mediathek mit dem Buchtitel ersetzen, wenn noch der Dateiname drinsteht
        $post = get_post( $attachment_id );
        if ( $post && '' !== $parsed['title'] && sanitize_title( $post->post_title ) === sanitize_title( pathinfo( (string) $path, PATHINFO_FILENAME ) ) ) {
            wp_update_post( array( 'ID' => $attachment_id, 'post_title' => $parsed['title'] ) );
        }
        return true;
    }

    /** Cover neu aufbereiten (verkleinern, neu kodieren) und ablegen. Leer = kein Cover. */
    private function store_cover( int $attachment_id, string $bytes ): string {
        $info = @getimagesizefromstring( $bytes );
        if ( ! is_array( $info ) || ! in_array( $info[2], array( IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP ), true ) ) { return ''; }
        $uploads = wp_upload_dir();
        if ( ! empty( $uploads['error'] ) ) { return ''; }
        $dir = trailingslashit( $uploads['basedir'] ) . self::COVER_DIR;
        if ( ! wp_mkdir_p( $dir ) ) { return ''; }
        // Temp-Datei braucht die richtige Endung, sonst kann ImageMagick sie nicht lesen
        $base = wp_tempnam( 'mgd-ebook-cover' );
        $tmp = $base ? $base . '.' . image_type_to_extension( $info[2], false ) : '';
        if ( '' !== $base ) { wp_delete_file( $base ); }
        if ( '' === $tmp || false === file_put_contents( $tmp, $bytes ) ) { return ''; }
        $editor = wp_get_image_editor( $tmp );
        if ( is_wp_error( $editor ) ) { wp_delete_file( $tmp ); return ''; }
        $stored = $this->write_cover( $editor, $attachment_id, IMAGETYPE_PNG === $info[2] );
        wp_delete_file( $tmp );
        return $stored;
    }

    /** Verkleinern und im Cover-Ordner speichern. Gibt den relativen Pfad zurueck, leer = Fehler. */
    private function write_cover( WP_Image_Editor $editor, int $attachment_id, bool $keep_png ): string {
        $uploads = wp_upload_dir();
        $dir = trailingslashit( $uploads['basedir'] ) . self::COVER_DIR;
        if ( ! empty( $uploads['error'] ) || ! wp_mkdir_p( $dir ) ) { return ''; }
        $editor->resize( self::COVER_MAX_WIDTH, self::COVER_MAX_HEIGHT, false );
        $name = $attachment_id . '-' . wp_generate_password( 8, false, false ) . ( $keep_png ? '.png' : '.jpg' );
        // Bild-Plugins (Modern Image Formats, EWWW...) machen sonst still AVIF/WebP draus > Datei heißt anders, Cover weg
        $keep_format = static fn(): array => array();
        add_filter( 'image_editor_output_format', $keep_format, PHP_INT_MAX );
        try {
            $saved = $editor->save( $dir . '/' . $name, $keep_png ? 'image/png' : 'image/jpeg' );
        } finally {
            remove_filter( 'image_editor_output_format', $keep_format, PHP_INT_MAX );
        }
        return is_wp_error( $saved ) ? '' : self::saved_relative( $saved, $dir );
    }

    /** Echten Speicherort aus dem Editor nehmen, nur im Cover-Ordner und als Bild. Leer = nix brauchbares. */
    private static function saved_relative( $saved, string $dir ): string {
        $path = is_array( $saved ) ? (string) ( $saved['path'] ?? '' ) : '';
        $file = basename( $path );
        if ( '' === $path || dirname( $path ) !== rtrim( $dir, '/' ) || ! preg_match( '/^[A-Za-z0-9_-]+\.(png|jpe?g|webp|avif|gif)$/D', $file ) || ! is_file( $path ) ) { return ''; }
        return self::COVER_DIR . '/' . $file;
    }

    /**
     * Eigenes Cover: Ausschnitt aus einem Bild (Werte 0 bis 1, relativ zur Bildgroesse).
     *
     * @param array{x:float,y:float,w:float,h:float} $crop
     */
    public function set_cover_from_image( int $book_id, string $source_path, array $crop ): bool|WP_Error {
        $editor = wp_get_image_editor( $source_path );
        if ( is_wp_error( $editor ) ) { return new WP_Error( 'mgd_ebook_cover_image', 'Dieses Bild kann nicht verarbeitet werden.' ); }
        $size = $editor->get_size();
        $width = (int) $size['width'];
        $height = (int) $size['height'];
        $x = max( 0.0, min( 1.0, (float) $crop['x'] ) );
        $y = max( 0.0, min( 1.0, (float) $crop['y'] ) );
        $sx = (int) round( $x * $width );
        $sy = (int) round( $y * $height );
        $sw = min( $width - $sx, (int) round( max( 0.0, min( 1.0, (float) $crop['w'] ) ) * $width ) );
        $sh = min( $height - $sy, (int) round( max( 0.0, min( 1.0, (float) $crop['h'] ) ) * $height ) );
        if ( $sw < 20 || $sh < 20 ) { return new WP_Error( 'mgd_ebook_cover_crop', 'Der gewählte Ausschnitt ist zu klein.' ); }
        $cropped = $editor->crop( $sx, $sy, $sw, $sh );
        if ( is_wp_error( $cropped ) ) { return new WP_Error( 'mgd_ebook_cover_crop', 'Der Ausschnitt konnte nicht berechnet werden.' ); }
        $stored = $this->write_cover( $editor, $book_id, 'png' === strtolower( pathinfo( $source_path, PATHINFO_EXTENSION ) ) );
        if ( '' === $stored ) { return new WP_Error( 'mgd_ebook_cover_save', 'Das Cover konnte nicht gespeichert werden.' ); }
        // erst jetzt das alte Cover entfernen
        $this->remove_cover( $book_id );
        update_post_meta( $book_id, self::META_COVER, $stored );
        return true;
    }

    /** Original-Cover aus der EPUB-Datei wieder einlesen. */
    public function restore_cover( int $book_id ): bool {
        return $this->index( $book_id );
    }

    /** Voller Dateipfad des aktuellen Covers (leer = keins). */
    public static function cover_path( int $attachment_id ): string {
        $relative = (string) get_post_meta( $attachment_id, self::META_COVER, true );
        if ( '' === $relative || 0 !== strpos( $relative, self::COVER_DIR . '/' ) || false !== strpos( $relative, '..' ) ) { return ''; }
        $uploads = wp_upload_dir();
        $path = trailingslashit( $uploads['basedir'] ) . $relative;
        return is_file( $path ) ? $path : '';
    }

    public function cleanup( int $attachment_id ): void {
        $this->remove_cover( $attachment_id );
    }

    private function remove_cover( int $attachment_id ): void {
        $relative = (string) get_post_meta( $attachment_id, self::META_COVER, true );
        delete_post_meta( $attachment_id, self::META_COVER );
        // nur Dateien im eigenen Cover-Ordner anfassen
        if ( '' === $relative || 0 !== strpos( $relative, self::COVER_DIR . '/' ) || false !== strpos( $relative, '..' ) ) { return; }
        $uploads = wp_upload_dir();
        wp_delete_file( trailingslashit( $uploads['basedir'] ) . $relative );
    }

    /** Daten eines Buchs (auch wenn nichts gelesen werden konnte). */
    public static function book( int $attachment_id ): array {
        $meta = get_post_meta( $attachment_id, self::META_BOOK, true );
        $meta = is_array( $meta ) ? $meta : array();
        return array(
            'title' => (string) ( $meta['title'] ?? '' ),
            'author' => (string) ( $meta['author'] ?? '' ),
            'description' => (string) ( $meta['description'] ?? '' ),
            'language' => (string) ( $meta['language'] ?? '' ),
        );
    }

    public static function title( int $attachment_id ): string {
        $data = self::book( $attachment_id );
        return '' !== $data['title'] ? $data['title'] : get_the_title( $attachment_id );
    }

    public static function cover_url( int $attachment_id ): string {
        $relative = (string) get_post_meta( $attachment_id, self::META_COVER, true );
        if ( '' === $relative || 0 !== strpos( $relative, self::COVER_DIR . '/' ) ) { return ''; }
        $uploads = wp_upload_dir();
        return is_file( trailingslashit( $uploads['basedir'] ) . $relative ) ? trailingslashit( $uploads['baseurl'] ) . $relative : '';
    }

    public static function is_ebook( int $attachment_id ): bool {
        return 'attachment' === get_post_type( $attachment_id ) && self::MIME === get_post_mime_type( $attachment_id );
    }

    public static function file_url( int $attachment_id ): string {
        return (string) wp_get_attachment_url( $attachment_id );
    }

    public static function file_size( int $attachment_id ): string {
        $path = get_attached_file( $attachment_id );
        return $path && is_file( $path ) ? size_format( (int) filesize( $path ), 1 ) : '';
    }

    /** Liste fuer die Verwaltung. */
    public static function query( int $page, int $per_page ): WP_Query {
        return new WP_Query( array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => self::MIME,
            'posts_per_page' => $per_page,
            'paged' => max( 1, $page ),
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => false,
        ) );
    }
}
