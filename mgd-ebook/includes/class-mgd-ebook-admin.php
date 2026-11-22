<?php
defined( 'ABSPATH' ) || exit;

/** Backend: Menüpunkt "eBook" mit Uploader und Liste. */
final class MGD_Ebook_Admin {
    private const PAGE = 'mgd-ebook';
    private const PER_PAGE = 20;
    private const MAX_FILES = 20;

    public function __construct( private MGD_Ebook_Library $library ) {}

    public function register(): void {
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
        add_action( 'admin_post_mgd_ebook_upload', array( $this, 'upload' ) );
        add_action( 'admin_post_mgd_ebook_delete', array( $this, 'delete' ) );
        // Knopf Installieren bei Weitere Plugins, muss auch in admin-post.php angemeldet sein
        add_action( 'admin_init', array( $this, 'catalog_actions' ) );
        // Cover aendern (Editor schickt per AJAX)
        add_action( 'wp_ajax_mgd_ebook_cover', array( $this, 'ajax_cover' ) );
        add_action( 'wp_ajax_mgd_ebook_cover_reset', array( $this, 'ajax_cover_reset' ) );
    }

    public function menu(): void {
        add_menu_page( 'eBook', 'eBook', 'upload_files', self::PAGE, array( $this, 'page' ), 'dashicons-book-alt', 25 );
        add_submenu_page( self::PAGE, 'eBooks', 'eBooks', 'upload_files', self::PAGE, array( $this, 'page' ) );
        // Dashboard vom Baukasten: Info, Updates, Weitere Plugins, Installation melden
        add_submenu_page( self::PAGE, 'Info und Updates', 'Info und Updates', 'manage_options', 'mgd-ebook-plugins', array( $this, 'plugins_page' ) );
    }

    public function assets( string $hook ): void {
        if ( 'toplevel_page_' . self::PAGE !== $hook ) { return; }
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

    public function plugins_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Nicht berechtigt.', '', array( 'response' => 403 ) ); }
        $list = $this->recommendations();
        // parent leer = kein eigener Menüpunkt, Lizenz bleibt aus
        $dash = \Mgd\Ebook\Kit\Platform\WordPress\Setup::dashboard( 'mgd-ebook', 'wordpress', MGD_EBOOK_VERSION, 'MGD eBook', MGD_Ebook_Plugin::$updater, $list, false, '' );
        if ( null === $dash ) { echo '<div class="wrap"><p>Die Seite konnte gerade nicht geladen werden.</p></div>'; return; }
        $dash->page();
    }

    public function catalog_actions(): void {
        \Mgd\Ebook\Kit\Platform\WordPress\Setup::catalogActions( 'mgd-ebook', $this->recommendations(), admin_url( 'admin.php?page=mgd-ebook-plugins' ) );
    }

    private function recommendations(): ?\Mgd\Ebook\Kit\Catalog\Recommendations {
        return \Mgd\Ebook\Kit\Platform\WordPress\Setup::recommendations( 'mgd-ebook', 'wordpress', 'mgd-ebook', $this->fallback_plugins(), 'MGD-eBook-WordPress' );
    }

    // Ersatzliste aus plugin-catalog/plugins.json, falls theme.michael-gahn.de nicht antwortet
    private function fallback_plugins(): array {
        $raw = file_get_contents( MGD_EBOOK_DIR . 'includes/plugin-catalog/plugins.json' );
        $data = is_string( $raw ) ? json_decode( $raw, true ) : null;
        $out = array();
        foreach ( is_array( $data ) ? $data : array() as $row ) {
            if ( is_array( $row ) && ! empty( $row['slug'] ) ) {
                // Kategorie, Häkchen + Basenames für die neue Kollektion-Ansicht, Rest prüft der Baukasten
                $out[] = array(
                    'slug' => (string) $row['slug'],
                    'name' => (string) ( $row['name'] ?? '' ),
                    'description' => (string) ( $row['description'] ?? '' ),
                    'version' => '',
                    'category' => (string) ( $row['category'] ?? '' ),
                    'features' => (array) ( $row['features'] ?? array() ),
                    'basenames' => (array) ( $row['basenames'] ?? array() ),
                    'tier' => (string) ( $row['tier'] ?? 'free' ),
                );
            }
        }
        return $out;
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
