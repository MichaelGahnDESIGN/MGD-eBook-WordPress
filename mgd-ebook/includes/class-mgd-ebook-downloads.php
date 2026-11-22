<?php
defined( 'ABSPATH' ) || exit;

/**
 * Zähl-Link: ?mgd_ebook_download=<ID> zählt +1 und leitet auf die Datei weiter.
 * Gespeichert wird nur eine Zahl (gesamt + pro Monat), keine IP, kein Cookie.
 */
final class MGD_Ebook_Downloads {
    public const QUERY = 'mgd_ebook_download';
    public const META = '_mgd_ebook_downloads';
    public const META_MONTHS = '_mgd_ebook_downloads_months';
    private const KEEP_MONTHS = 24;
    // Roboter + Link-Vorschauen (Messenger, Social) zählen nicht
    private const BOT_RE = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|discord|slack|skype|embedly|curl|wget|python|httpclient|java\\/|okhttp|headless|lighthouse|pingdom|uptime|monitor|scan/i';

    public function register(): void {
        // ganz früh, damit Wartungs- und Cache-Plugins nicht dazwischenfunken
        add_action( 'init', array( $this, 'serve' ), 0 );
    }

    public static function link( int $attachment_id ): string {
        return add_query_arg( self::QUERY, $attachment_id, home_url( '/' ) );
    }

    public static function count( int $attachment_id ): int {
        $value = get_post_meta( $attachment_id, self::META, true );
        return is_numeric( $value ) ? max( 0, (int) $value ) : 0;
    }

    /** @return array<string,int> Monat (JJJJ-MM) => Anzahl, neuester zuerst */
    public static function months( int $attachment_id ): array {
        $raw = get_post_meta( $attachment_id, self::META_MONTHS, true );
        $out = array();
        foreach ( is_array( $raw ) ? $raw : array() as $month => $n ) {
            if ( is_string( $month ) && preg_match( '/^\\d{4}-\\d{2}$/', $month ) && is_numeric( $n ) ) { $out[ $month ] = max( 0, (int) $n ); }
        }
        krsort( $out );
        return array_slice( $out, 0, self::KEEP_MONTHS, true );
    }

    public static function add( int $attachment_id ): void {
        update_post_meta( $attachment_id, self::META, self::count( $attachment_id ) + 1 );
        $months = self::months( $attachment_id );
        $now = wp_date( 'Y-m' );
        $months[ $now ] = ( $months[ $now ] ?? 0 ) + 1;
        krsort( $months );
        update_post_meta( $attachment_id, self::META_MONTHS, array_slice( $months, 0, self::KEEP_MONTHS, true ) );
    }

    /** Zählt nur echte Downloads im Browser. */
    public static function should_count( string $method, string $agent, array $server ): bool {
        if ( 'GET' !== strtoupper( $method ) || '' === trim( $agent ) || preg_match( self::BOT_RE, $agent ) ) { return false; }
        // Browser lädt Links manchmal vorab
        foreach ( array( 'HTTP_SEC_PURPOSE', 'HTTP_PURPOSE', 'HTTP_X_PURPOSE', 'HTTP_X_MOZ' ) as $header ) {
            if ( isset( $server[ $header ] ) && preg_match( '/prefetch|preview|prerender/i', (string) $server[ $header ] ) ) { return false; }
        }
        return true;
    }

    public function serve(): void {
        if ( ! isset( $_GET[ self::QUERY ] ) ) { return; }
        $id = absint( wp_unslash( $_GET[ self::QUERY ] ) );
        nocache_headers();
        header( 'X-Robots-Tag: noindex, nofollow', true );
        $url = $id && MGD_Ebook_Library::is_ebook( $id ) && MGD_Ebook_Library::is_free( $id ) ? MGD_Ebook_Library::file_url( $id ) : '';
        if ( '' === $url ) {
            wp_safe_redirect( home_url( '/' ), 302 );
            exit;
        }
        if ( self::should_count( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ), (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ), $_SERVER ) ) {
            self::add( $id );
        }
        // eigene Datei aus der Mediathek, kein Fremd-Input > wp_redirect (CDN-Host erlaubt)
        wp_redirect( $url, 302 );
        exit;
    }
}
