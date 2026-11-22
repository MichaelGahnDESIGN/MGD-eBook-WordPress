<?php
defined( 'ABSPATH' ) || exit;

/**
 * Leseprobe pro Buch: an/aus + Seitenzahl. Seiten werden einmal aus der EPUB gelesen
 * und als Text + verkleinerte Bilder unter uploads/mgd-ebook-samples/ abgelegt.
 */
final class MGD_Ebook_Sample {
    public const META_SETTINGS = '_mgd_ebook_sample';
    public const META_DATA = '_mgd_ebook_sample_data';
    public const MAX_PAGES = 10;
    public const DEFAULT_PAGES = 3;
    private const DIR = 'mgd-ebook-samples';
    private const IMG_W = 1000;
    private const IMG_H = 1500;

    /** @return array{on:bool,pages:int} */
    public static function settings( int $attachment_id ): array {
        $raw = get_post_meta( $attachment_id, self::META_SETTINGS, true );
        $raw = is_array( $raw ) ? $raw : array();
        return array(
            'on' => ! empty( $raw['on'] ),
            'pages' => self::clamp_pages( $raw['pages'] ?? self::DEFAULT_PAGES ),
        );
    }

    public static function clamp_pages( $value ): int {
        return max( 1, min( self::MAX_PAGES, (int) $value ) );
    }

    /** Gespeicherte Seiten: list<list<{type:h|p|img, text?, src?}>> */
    public static function pages( int $attachment_id ): array {
        $data = get_post_meta( $attachment_id, self::META_DATA, true );
        return is_array( $data ) && isset( $data['pages'] ) && is_array( $data['pages'] ) ? $data['pages'] : array();
    }

    /** Einstellungen speichern und Leseprobe neu bauen, wenn nötig. true = alles ok */
    public static function update( int $attachment_id, bool $on, int $pages ): bool {
        $pages = self::clamp_pages( $pages );
        $before = self::settings( $attachment_id );
        update_post_meta( $attachment_id, self::META_SETTINGS, array( 'on' => $on, 'pages' => $pages ) );
        if ( ! $on ) {
            self::remove( $attachment_id );
            return true;
        }
        if ( $before['on'] && $before['pages'] === $pages && self::pages( $attachment_id ) ) { return true; }
        return self::build( $attachment_id );
    }

    /** Seiten aus der EPUB holen und ablegen. Alte Leseprobe erst nach Erfolg weg. */
    public static function build( int $attachment_id ): bool {
        $settings = self::settings( $attachment_id );
        if ( ! $settings['on'] ) { return true; }
        $path = (string) get_attached_file( $attachment_id );
        $raw = '' !== $path && is_file( $path ) ? MGD_Ebook_Epub::sample_pages( $path, $settings['pages'] ) : new WP_Error( 'mgd_ebook_missing', 'Datei fehlt.' );
        if ( is_wp_error( $raw ) || ! $raw ) { return false; }
        $folder = self::DIR . '/' . $attachment_id . '-' . wp_generate_password( 8, false, false );
        $pages = array();
        $n = 0;
        foreach ( $raw as $blocks ) {
            $page = array();
            foreach ( $blocks as $block ) {
                if ( 'img' === $block['type'] ) {
                    $rel = MGD_Ebook_Library::save_image_bytes( (string) $block['bytes'], $folder, 'bild-' . ( ++$n ), self::IMG_W, self::IMG_H );
                    if ( '' !== $rel ) { $page[] = array( 'type' => 'img', 'src' => $rel ); }
                } else {
                    $page[] = array( 'type' => 'h' === $block['type'] ? 'h' : 'p', 'text' => (string) $block['text'] );
                }
            }
            if ( $page ) { $pages[] = $page; }
        }
        if ( ! $pages ) { return false; }
        $old = get_post_meta( $attachment_id, self::META_DATA, true );
        update_post_meta( $attachment_id, self::META_DATA, array( 'folder' => $folder, 'pages' => $pages ) );
        self::delete_folder( is_array( $old ) ? (string) ( $old['folder'] ?? '' ) : '' );
        return true;
    }

    public static function remove( int $attachment_id ): void {
        $data = get_post_meta( $attachment_id, self::META_DATA, true );
        delete_post_meta( $attachment_id, self::META_DATA );
        self::delete_folder( is_array( $data ) ? (string) ( $data['folder'] ?? '' ) : '' );
    }

    /** Bild-URL nur aus dem eigenen Ordner. */
    public static function url( string $relative ): string {
        if ( ! preg_match( '~^' . self::DIR . '/\d+-[A-Za-z0-9]{8}/[A-Za-z0-9_-]+\.(png|jpe?g|webp|avif|gif)$~D', $relative ) ) { return ''; }
        $uploads = wp_upload_dir();
        return trailingslashit( $uploads['baseurl'] ) . $relative;
    }

    // nur eigene Ordner unter uploads/mgd-ebook-samples/<id>-<zufall> löschen
    private static function delete_folder( string $folder ): void {
        if ( ! preg_match( '~^' . self::DIR . '/\d+-[A-Za-z0-9]{8}$~D', $folder ) ) { return; }
        $uploads = wp_upload_dir();
        $dir = trailingslashit( $uploads['basedir'] ) . $folder;
        if ( ! is_dir( $dir ) ) { return; }
        foreach ( (array) glob( $dir . '/*' ) as $file ) {
            if ( is_file( $file ) ) { wp_delete_file( $file ); }
        }
        @rmdir( $dir );
    }
}
