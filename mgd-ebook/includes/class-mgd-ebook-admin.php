<?php
defined( 'ABSPATH' ) || exit;

/** Backend: Seite "eBook" (unter MGD Tools) mit Uploader und Liste. */
final class MGD_Ebook_Admin {
    private const PAGE = 'mgd-ebook';
    private const PER_PAGE = 20;
    private const MAX_FILES = 20;
    private const BIG_FILE = 20971520; // ab 20 MB Hinweis

    public function __construct( private MGD_Ebook_Library $library ) {}

    public function register(): void {
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
        add_action( 'admin_post_mgd_ebook_upload', array( $this, 'upload' ) );
        add_action( 'admin_post_mgd_ebook_delete', array( $this, 'delete' ) );
        add_action( 'admin_post_mgd_ebook_settings', array( $this, 'save_settings' ) );
        add_action( 'admin_post_mgd_ebook_replace', array( $this, 'replace' ) );
        // Cover aendern (Editor schickt per AJAX)
        add_action( 'wp_ajax_mgd_ebook_cover', array( $this, 'ajax_cover' ) );
        add_action( 'wp_ajax_mgd_ebook_cover_reset', array( $this, 'ajax_cover_reset' ) );
    }

    public function assets( string $hook ): void {
        if ( ! str_ends_with( $hook, '_page_' . self::PAGE ) ) { return; }
        wp_enqueue_style( 'mgd-ebook-admin', MGD_EBOOK_URL . 'assets/ebook-admin.css', array(), MGD_EBOOK_VERSION );
        // Mediathek-Fenster fuer die Cover-Auswahl
        wp_enqueue_media();
        wp_enqueue_script( 'mgd-ebook-admin', MGD_EBOOK_URL . 'assets/ebook-admin.js', array(), MGD_EBOOK_VERSION, true );
        wp_localize_script( 'mgd-ebook-admin', 'mgdEbook', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'mgd_ebook_cover' ),
        ) );
    }

    // Seite: Uploader oben, Liste darunter
    public function page(): void {
        if ( ! current_user_can( 'upload_files' ) ) { wp_die( 'Nicht berechtigt.', '', array( 'response' => 403 ) ); }
        $paged = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
        $query = MGD_Ebook_Library::query( $paged, self::PER_PAGE );
        $messages = $this->take_messages();
        $max_upload = size_format( wp_max_upload_size() );
        require MGD_EBOOK_DIR . 'templates/admin-page.php';
    }

    public function upload(): void {
        $this->guard( 'mgd_ebook_upload', 'upload_files' );
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $messages = array();
        $files = $this->normalize_files( $_FILES['mgd_ebook_files'] ?? array() );
        if ( ! $files ) {
            $messages[] = array( 'error', 'Es wurde keine Datei ausgewählt.' );
        }
        foreach ( array_slice( $files, 0, self::MAX_FILES ) as $file ) {
            $name = sanitize_file_name( (string) $file['name'] );
            if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
                $messages[] = array( 'error', $name . ': ' . $this->upload_error( (int) $file['error'] ) );
                continue;
            }
            if ( 'epub' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
                $messages[] = array( 'error', $name . ': Nur Dateien mit der Endung .epub sind erlaubt.' );
                continue;
            }
            // media_handle_upload holt sich die Datei aus $_FILES
            $_FILES['mgd_ebook_single'] = $file;
            $id = media_handle_upload( 'mgd_ebook_single', 0 );
            if ( is_wp_error( $id ) ) {
                $messages[] = array( 'error', $name . ': ' . $id->get_error_message() );
                continue;
            }
            $messages[] = array( 'success', MGD_Ebook_Library::title( (int) $id ) . ' wurde hochgeladen.' . ( '' === MGD_Ebook_Library::cover_url( (int) $id ) ? ' Es wurde kein Cover gefunden, die Anzeige nutzt dann eine Platzhalter-Karte.' : '' ) );
            $big = $this->size_hint( (int) $id );
            if ( '' !== $big ) { $messages[] = array( 'warning', $big ); }
        }
        if ( count( $files ) > self::MAX_FILES ) {
            $messages[] = array( 'warning', 'Pro Vorgang werden höchstens ' . self::MAX_FILES . ' Dateien verarbeitet. Der Rest wurde ausgelassen.' );
        }
        $this->store_messages( $messages );
        $this->back();
    }

    public function delete(): void {
        $id = (int) ( $_POST['attachment_id'] ?? 0 );
        $this->guard( 'mgd_ebook_delete_' . $id, 'upload_files' );
        if ( ! MGD_Ebook_Library::is_ebook( $id ) || ! current_user_can( 'delete_post', $id ) ) { wp_die( 'Nicht berechtigt.', '', array( 'response' => 403 ) ); }
        $title = MGD_Ebook_Library::title( $id );
        $deleted = wp_delete_attachment( $id, true );
        $this->store_messages( array( $deleted ? array( 'success', $title . ' wurde gelöscht.' ) : array( 'error', $title . ' konnte nicht gelöscht werden.' ) ) );
        $this->back();
    }

    // Metadaten, Anzeige im Vorschau-Fenster + Leseprobe speichern
    public function save_settings(): void {
        $id = (int) ( $_POST['attachment_id'] ?? 0 );
        $this->guard( 'mgd_ebook_settings_' . $id, 'upload_files' );
        if ( ! MGD_Ebook_Library::is_ebook( $id ) || ! current_user_can( 'edit_post', $id ) ) { wp_die( 'Nicht berechtigt.', '', array( 'response' => 403 ) ); }
        $meta = wp_unslash( $_POST['meta'] ?? array() ); // phpcs:ignore -- clean_field prüft jedes Feld
        $show = wp_unslash( $_POST['show'] ?? array() ); // phpcs:ignore -- nur Schlüssel aus SHOW zählen
        MGD_Ebook_Library::set_meta( $id, is_array( $meta ) ? $meta : array() );
        MGD_Ebook_Library::set_show( $id, is_array( $show ) ? $show : array() );
        $sample_on = ! empty( $_POST['sample_on'] );
        $sample_ok = MGD_Ebook_Sample::update( $id, $sample_on, (int) ( $_POST['sample_pages'] ?? MGD_Ebook_Sample::DEFAULT_PAGES ) );
        $title = MGD_Ebook_Library::title( $id );
        $messages = array( array( 'success', 'Einstellungen für „' . $title . '“ gespeichert.' ) );
        // Verkauf nur anfassen, wenn das Formular den Bereich hatte (WooCommerce aktiv)
        if ( isset( $_POST['sale_mode'] ) ) {
            $sale_error = MGD_Ebook_Shop::update( $id, sanitize_key( wp_unslash( $_POST['sale_mode'] ) ), sanitize_text_field( wp_unslash( $_POST['sale_price'] ?? '' ) ), absint( $_POST['sale_product'] ?? 0 ) );
            if ( '' !== $sale_error ) { $messages[] = array( 'error', $sale_error ); }
        }
        if ( $sample_on && ! $sample_ok ) { $messages[] = array( 'error', 'Die Leseprobe konnte nicht erstellt werden. In dieser EPUB-Datei wurden keine lesbaren Seiten gefunden.' ); }
        $this->store_messages( $messages );
        $this->back();
    }

    // Neue EPUB-Datei für ein vorhandenes eBook: Link, Shortcode, Zähler + Einstellungen bleiben
    public function replace(): void {
        $id = (int) ( $_POST['attachment_id'] ?? 0 );
        $this->guard( 'mgd_ebook_replace_' . $id, 'upload_files' );
        if ( ! MGD_Ebook_Library::is_ebook( $id ) || ! current_user_can( 'edit_post', $id ) ) { wp_die( 'Nicht berechtigt.', '', array( 'response' => 403 ) ); }
        $file = $_FILES['mgd_ebook_file'] ?? array(); // phpcs:ignore -- wird unten geprüft
        $error = $this->replace_file( $id, is_array( $file ) ? $file : array() );
        $title = MGD_Ebook_Library::title( $id );
        $messages = array( '' === $error ? array( 'success', 'Die Datei von „' . $title . '“ wurde ersetzt. Link, Shortcode und Zähler bleiben gleich.' ) : array( 'error', $error ) );
        $big = '' === $error ? $this->size_hint( $id ) : '';
        if ( '' !== $big ) { $messages[] = array( 'warning', $big ); }
        $this->store_messages( $messages );
        $this->back();
    }

    /** Datei tauschen. Leer = ok, sonst Fehlermeldung. Bei Fehler bleibt die alte Datei. */
    private function replace_file( int $id, array $file, bool $from_upload = true ): string {
        $error = (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE );
        if ( UPLOAD_ERR_NO_FILE === $error ) { return 'Es wurde keine Datei ausgewählt.'; }
        if ( UPLOAD_ERR_OK !== $error ) { return $this->upload_error( $error ); }
        $tmp = (string) ( $file['tmp_name'] ?? '' );
        if ( 'epub' !== strtolower( pathinfo( (string) ( $file['name'] ?? '' ), PATHINFO_EXTENSION ) ) ) { return 'Nur Dateien mit der Endung .epub sind erlaubt.'; }
        // $from_upload false nur für den Demo-Test per WP-CLI (kein Browser-Upload möglich)
        if ( '' === $tmp || ( $from_upload ? ! is_uploaded_file( $tmp ) : ! is_file( $tmp ) ) ) { return 'Der Upload hat nicht geklappt.'; }
        $parsed = MGD_Ebook_Epub::read( $tmp );
        if ( is_wp_error( $parsed ) ) { return $parsed->get_error_message(); }
        $target = (string) get_attached_file( $id );
        if ( '' === $target || ! is_file( $target ) ) { return 'Die bisherige Datei wurde nicht gefunden.'; }
        // alte Datei erst weg, wenn die neue sicher liegt
        $backup = $target . '.mgd-alt';
        if ( ! @copy( $target, $backup ) ) { return 'Die bisherige Datei konnte nicht gesichert werden. Es wurde nichts geändert.'; }
        $moved = $from_upload ? @move_uploaded_file( $tmp, $target ) : @rename( $tmp, $target );
        if ( ! $moved ) {
            @rename( $backup, $target );
            return 'Die neue Datei konnte nicht gespeichert werden. Die alte Datei ist noch da.';
        }
        @chmod( $target, 0644 );
        wp_delete_file( $backup );
        $meta = wp_get_attachment_metadata( $id );
        $meta = is_array( $meta ) ? $meta : array();
        $meta['filesize'] = (int) filesize( $target );
        wp_update_attachment_metadata( $id, $meta );
        $this->library->index( $id );
        MGD_Ebook_Sample::build( $id );
        return '';
    }

    /** Hinweis bei großen Dateien, leer = alles gut. */
    private function size_hint( int $id ): string {
        $path = (string) get_attached_file( $id );
        $bytes = '' !== $path && is_file( $path ) ? (int) filesize( $path ) : 0;
        if ( $bytes < self::BIG_FILE ) { return ''; }
        return '„' . MGD_Ebook_Library::title( $id ) . '“ ist ' . size_format( $bytes, 1 ) . ' groß. Auf dem Handy dauert der Download dann länger. Tipp: Bilder beim Export kleiner speichern, zum Beispiel mit 150 dpi oder als JPEG statt PNG.';
    }

    // Cover aus Mediathek-Bild oder aktuellem Cover neu zuschneiden
    public function ajax_cover(): void {
        $book_id = $this->ajax_book();
        $source = sanitize_text_field( wp_unslash( $_POST['source'] ?? '' ) );
        if ( 'current' === $source ) {
            $path = MGD_Ebook_Library::cover_path( $book_id );
        } else {
            $image_id = absint( $source );
            $path = '';
            // nur echte Bilder (kein SVG), die der Benutzer sehen darf
            if ( $image_id && wp_attachment_is_image( $image_id ) && current_user_can( 'read_post', $image_id )
                && in_array( get_post_mime_type( $image_id ), array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ), true ) ) {
                $path = (string) get_attached_file( $image_id );
            }
        }
        if ( '' === $path || ! is_file( $path ) ) { wp_send_json_error( array( 'message' => 'Das Bild wurde nicht gefunden.' ), 400 ); }
        $crop = array();
        foreach ( array( 'x', 'y', 'w', 'h' ) as $key ) { $crop[ $key ] = (float) ( $_POST[ $key ] ?? 0 ); }
        $result = $this->library->set_cover_from_image( $book_id, $path, $crop );
        if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 ); }
        wp_send_json_success( array( 'url' => MGD_Ebook_Library::cover_url( $book_id ) ) );
    }

    public function ajax_cover_reset(): void {
        $book_id = $this->ajax_book();
        if ( ! $this->library->restore_cover( $book_id ) ) { wp_send_json_error( array( 'message' => 'Die EPUB-Datei konnte nicht gelesen werden.' ), 400 ); }
        wp_send_json_success( array( 'url' => MGD_Ebook_Library::cover_url( $book_id ) ) );
    }

    /** Gemeinsame Pruefung der AJAX-Aufrufe. Gibt die ID des eBooks zurueck. */
    private function ajax_book(): int {
        check_ajax_referer( 'mgd_ebook_cover', 'nonce' );
        $book_id = absint( $_POST['book_id'] ?? 0 );
        if ( ! MGD_Ebook_Library::is_ebook( $book_id ) || ! current_user_can( 'edit_post', $book_id ) ) { wp_send_json_error( array( 'message' => 'Nicht berechtigt.' ), 403 ); }
        return $book_id;
    }

    private function guard( string $nonce, string $capability ): void {
        if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! current_user_can( $capability ) ) { wp_die( 'Nicht berechtigt.', '', array( 'response' => 403 ) ); }
        check_admin_referer( $nonce );
    }

    /** $_FILES mit mehreren Dateien in eine einfache Liste umbauen. */
    private function normalize_files( $raw ): array {
        if ( ! is_array( $raw ) || ! isset( $raw['name'] ) || ! is_array( $raw['name'] ) ) { return array(); }
        $files = array();
        foreach ( array_keys( $raw['name'] ) as $i ) {
            // leeres Feld (nichts ausgewaehlt) ueberspringen
            if ( UPLOAD_ERR_NO_FILE === (int) $raw['error'][ $i ] ) { continue; }
            $files[] = array( 'name' => $raw['name'][ $i ], 'type' => $raw['type'][ $i ], 'tmp_name' => $raw['tmp_name'][ $i ], 'error' => $raw['error'][ $i ], 'size' => $raw['size'][ $i ] );
        }
        return $files;
    }

    private function upload_error( int $code ): string {
        return match ( $code ) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Die Datei ist größer als das Upload-Limit des Servers (' . size_format( wp_max_upload_size() ) . ').',
            UPLOAD_ERR_PARTIAL => 'Der Upload wurde unterbrochen. Bitte nochmal versuchen.',
            default => 'Der Upload hat nicht geklappt (Fehlercode ' . $code . ').',
        };
    }

    // Meldungen ueber den Redirect hinweg merken (pro Benutzer)
    private function store_messages( array $messages ): void {
        set_transient( 'mgd_ebook_msg_' . get_current_user_id(), $messages, 5 * MINUTE_IN_SECONDS );
    }

    private function take_messages(): array {
        $key = 'mgd_ebook_msg_' . get_current_user_id();
        $messages = get_transient( $key );
        if ( false !== $messages ) { delete_transient( $key ); }
        return is_array( $messages ) ? $messages : array();
    }

    private function back(): void {
        wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE ) );
        exit;
    }
}
