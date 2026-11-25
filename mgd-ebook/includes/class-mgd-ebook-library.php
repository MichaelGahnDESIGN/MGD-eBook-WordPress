<?php
defined( 'ABSPATH' ) || exit;

/** Alles rund um die hochgeladenen EPUB-Dateien: Erlaubnis, Pruefung, Cover, Liste. */
final class MGD_Ebook_Library {
    public const MIME = 'application/epub+zip';
    public const META_BOOK = '_mgd_ebook';          // Titel, Autor, Beschreibung
    public const META_COVER = '_mgd_ebook_cover';   // Pfad zum Cover (relativ zu uploads)
    public const META_CUSTOM = '_mgd_ebook_custom'; // eigene Metadaten, überschreiben die aus der EPUB
    public const META_SHOW = '_mgd_ebook_show';     // was im Vorschau-Fenster angezeigt wird
    // Felder zum Bearbeiten, Reihenfolge = Formular
    public const FIELDS = array(
        'title' => 'Titel',
        'author' => 'Autor',
        'description' => 'Beschreibung',
        'series' => 'Reihe',
        'series_index' => 'Band',
        'publisher' => 'Verlag',
        'year' => 'Erscheinungsjahr',
        'isbn' => 'ISBN',
        'language' => 'Sprache',
    );
    // Schalter fürs Vorschau-Fenster, true = ab Werk an
    public const SHOW = array(
        'author' => true,
        'description' => true,
        'series' => true,
        'publisher' => true,
        'year' => true,
        'isbn' => true,
        'language' => false,
        'size' => true,
    );
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
        $book = array();
        foreach ( array_keys( self::FIELDS ) as $key ) { $book[ $key ] = (string) ( $parsed[ $key ] ?? '' ); }
        update_post_meta( $attachment_id, self::META_BOOK, $book );
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
        return self::save_image_bytes( $bytes, self::COVER_DIR, $attachment_id . '-' . wp_generate_password( 8, false, false ), self::COVER_MAX_WIDTH, self::COVER_MAX_HEIGHT );
    }

    /** Verkleinern und im Cover-Ordner speichern. Gibt den relativen Pfad zurueck, leer = Fehler. */
    private function write_cover( WP_Image_Editor $editor, int $attachment_id, bool $keep_png ): string {
        return self::save_image( $editor, self::COVER_DIR, $attachment_id . '-' . wp_generate_password( 8, false, false ), $keep_png, self::COVER_MAX_WIDTH, self::COVER_MAX_HEIGHT );
    }

    /** Bild verkleinern und unter uploads/<ordner> speichern. Relativer Pfad, leer = Fehler. */
    public static function save_image( WP_Image_Editor $editor, string $subdir, string $basename, bool $keep_png, int $max_w, int $max_h ): string {
        $uploads = wp_upload_dir();
        $dir = trailingslashit( $uploads['basedir'] ) . $subdir;
        if ( ! empty( $uploads['error'] ) || ! wp_mkdir_p( $dir ) ) { return ''; }
        $editor->resize( $max_w, $max_h, false );
        $name = $basename . ( $keep_png ? '.png' : '.jpg' );
        // Bild-Plugins (Modern Image Formats, EWWW...) machen sonst still AVIF/WebP draus > Datei heißt anders, Cover weg
        $keep_format = static fn(): array => array();
        add_filter( 'image_editor_output_format', $keep_format, PHP_INT_MAX );
        try {
            $saved = $editor->save( $dir . '/' . $name, $keep_png ? 'image/png' : 'image/jpeg' );
        } finally {
            remove_filter( 'image_editor_output_format', $keep_format, PHP_INT_MAX );
        }
        $file = is_wp_error( $saved ) ? '' : self::saved_file( $saved, $dir );
        return '' === $file ? '' : $subdir . '/' . $file;
    }

    /** Bild aus Bytes speichern (Temp-Datei mit richtiger Endung für ImageMagick). */
    public static function save_image_bytes( string $bytes, string $subdir, string $basename, int $max_w, int $max_h ): string {
        $info = @getimagesizefromstring( $bytes );
        if ( ! is_array( $info ) || ! in_array( $info[2], array( IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP ), true ) ) { return ''; }
        $base = wp_tempnam( 'mgd-ebook-img' );
        $tmp = $base ? $base . '.' . image_type_to_extension( $info[2], false ) : '';
        if ( '' !== $base ) { wp_delete_file( $base ); }
        if ( '' === $tmp || false === file_put_contents( $tmp, $bytes ) ) { return ''; }
        $editor = wp_get_image_editor( $tmp );
        $stored = is_wp_error( $editor ) ? '' : self::save_image( $editor, $subdir, $basename, IMAGETYPE_PNG === $info[2], $max_w, $max_h );
        wp_delete_file( $tmp );
        return $stored;
    }

    /** Echten Dateinamen aus dem Editor nehmen, nur im Zielordner und als Bild. Leer = nix brauchbares. */
    private static function saved_file( $saved, string $dir ): string {
        $path = is_array( $saved ) ? (string) ( $saved['path'] ?? '' ) : '';
        $file = basename( $path );
        if ( '' === $path || dirname( $path ) !== rtrim( $dir, '/' ) || ! preg_match( '/^[A-Za-z0-9_-]+\.(png|jpe?g|webp|avif|gif)$/D', $file ) || ! is_file( $path ) ) { return ''; }
        return $file;
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
        if ( ! self::is_ebook( $attachment_id ) ) { return; }
        $this->remove_cover( $attachment_id );
        MGD_Ebook_Sample::remove( $attachment_id );
    }

    private function remove_cover( int $attachment_id ): void {
        $relative = (string) get_post_meta( $attachment_id, self::META_COVER, true );
        delete_post_meta( $attachment_id, self::META_COVER );
        // nur Dateien im eigenen Cover-Ordner anfassen
        if ( '' === $relative || 0 !== strpos( $relative, self::COVER_DIR . '/' ) || false !== strpos( $relative, '..' ) ) { return; }
        $uploads = wp_upload_dir();
        wp_delete_file( trailingslashit( $uploads['basedir'] ) . $relative );
    }

    /** Daten aus der EPUB-Datei, ohne eigene Änderungen. */
    public static function epub_book( int $attachment_id ): array {
        $meta = get_post_meta( $attachment_id, self::META_BOOK, true );
        $meta = is_array( $meta ) ? $meta : array();
        $out = array();
        foreach ( array_keys( self::FIELDS ) as $key ) { $out[ $key ] = (string) ( $meta[ $key ] ?? '' ); }
        return $out;
    }

    /** Eigene Metadaten (nur die gesetzten Felder). */
    public static function custom_book( int $attachment_id ): array {
        $custom = get_post_meta( $attachment_id, self::META_CUSTOM, true );
        $out = array();
        foreach ( is_array( $custom ) ? $custom : array() as $key => $value ) {
            if ( isset( self::FIELDS[ $key ] ) && is_string( $value ) && '' !== $value ) { $out[ $key ] = $value; }
        }
        return $out;
    }

    /** Daten eines Buchs: eigene Angaben vor denen aus der EPUB. */
    public static function book( int $attachment_id ): array {
        return array_merge( self::epub_book( $attachment_id ), self::custom_book( $attachment_id ) );
    }

    public static function title( int $attachment_id ): string {
        $data = self::book( $attachment_id );
        return '' !== $data['title'] ? $data['title'] : get_the_title( $attachment_id );
    }

    /** Eigene Metadaten speichern. Leeres Feld = wieder der Wert aus der EPUB. false = kein eBook. */
    public static function set_meta( int $attachment_id, array $input ): bool {
        if ( ! self::is_ebook( $attachment_id ) ) { return false; }
        $custom = array();
        foreach ( array_keys( self::FIELDS ) as $key ) {
            $value = self::clean_field( $key, (string) ( $input[ $key ] ?? '' ) );
            if ( '' !== $value ) { $custom[ $key ] = $value; }
        }
        if ( $custom ) { update_post_meta( $attachment_id, self::META_CUSTOM, $custom ); } else { delete_post_meta( $attachment_id, self::META_CUSTOM ); }
        // Mediathek-Titel mitziehen, damit Divi + Mediathek denselben Namen zeigen
        wp_update_post( array( 'ID' => $attachment_id, 'post_title' => self::title( $attachment_id ) ) );
        return true;
    }

    /** Ein Feld bereinigen. Ungültiges wird leer (= EPUB-Wert gilt). */
    public static function clean_field( string $key, string $value ): string {
        if ( 'description' === $key ) {
            $value = trim( preg_replace( "/[ \t]+/u", ' ', strip_tags( str_replace( "\r", '', $value ) ) ) ?? '' );
            return mb_substr( preg_replace( "/\n{3,}/", "\n\n", $value ) ?? '', 0, 1500 );
        }
        $value = sanitize_text_field( $value );
        return match ( $key ) {
            'year' => preg_match( '/^(1[5-9]\d\d|2\d\d\d)$/', $value ) ? $value : '',
            'isbn' => preg_match( '/^[0-9Xx -]{10,17}$/', $value ) ? strtoupper( str_replace( array( ' ', '-' ), '', $value ) ) : '',
            'series_index' => preg_match( '/^\d{1,4}(?:[.,]\d{1,2})?$/', $value ) ? str_replace( ',', '.', $value ) : '',
            'language' => mb_substr( $value, 0, 20 ),
            'title', 'author' => mb_substr( $value, 0, 300 ),
            default => mb_substr( $value, 0, 200 ),
        };
    }

    /** Was im Vorschau-Fenster gezeigt wird (Schalter pro Buch). */
    public static function show( int $attachment_id ): array {
        $saved = get_post_meta( $attachment_id, self::META_SHOW, true );
        $out = array();
        foreach ( self::SHOW as $key => $default ) { $out[ $key ] = is_array( $saved ) && array_key_exists( $key, $saved ) ? (bool) $saved[ $key ] : $default; }
        return $out;
    }

    public static function set_show( int $attachment_id, array $input ): void {
        $out = array();
        foreach ( array_keys( self::SHOW ) as $key ) { $out[ $key ] = ! empty( $input[ $key ] ); }
        update_post_meta( $attachment_id, self::META_SHOW, $out );
    }

    public static function cover_url( int $attachment_id ): string {
        $relative = (string) get_post_meta( $attachment_id, self::META_COVER, true );
        if ( '' === $relative || 0 !== strpos( $relative, self::COVER_DIR . '/' ) ) { return ''; }
        $uploads = wp_upload_dir();
        return is_file( trailingslashit( $uploads['basedir'] ) . $relative ) ? trailingslashit( $uploads['baseurl'] ) . $relative : '';
    }

    /** Kostenlos herunterladbar? Verkauf über WooCommerce (Klasse MGD_Ebook_Shop) macht ein Buch zum Kaufbuch. */
    public static function is_free( int $attachment_id ): bool {
        return (bool) apply_filters( 'mgd_ebook_is_free', true, $attachment_id );
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
