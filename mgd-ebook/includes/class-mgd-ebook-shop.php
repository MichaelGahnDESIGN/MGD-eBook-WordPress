<?php
defined( 'ABSPATH' ) || exit;

/**
 * Verkauf über WooCommerce. Pro Buch: kostenlos (wie immer) oder verkaufen.
 * Beim Verkaufen liegt die EPUB im geschützten WooCommerce-Ordner, WooCommerce prüft den Kauf beim Download.
 * Ohne WooCommerce bleibt jedes Buch kostenlos und dieser Teil macht nichts.
 */
final class MGD_Ebook_Shop {
    public const META = '_mgd_ebook_sale';           // array{mode:free|paid, product:int}
    public const META_ORIGIN = '_mgd_ebook_origin';   // ursprünglicher Pfad relativ zu uploads
    public const PRODUCT_META = '_mgd_ebook_book';    // am Produkt: ID des eBooks
    private const PROTECTED = 'woocommerce_uploads/mgd-ebook';

    public static function register(): void {
        add_filter( 'mgd_ebook_is_free', array( self::class, 'filter_free' ), 10, 2 );
        add_filter( 'mgd_ebook_action_html', array( self::class, 'action_html' ), 10, 2 );
        add_filter( 'mgd_ebook_schema', array( self::class, 'schema' ), 10, 2 );
        add_filter( 'mgd_ebook_cover_href', array( self::class, 'cover_href' ), 10, 2 );
        // Käufer-Downloads zählen auch im eBook-Zähler
        add_action( 'woocommerce_download_product', array( self::class, 'count_download' ), 10, 6 );
    }

    public static function active(): bool {
        return class_exists( 'WooCommerce' ) && function_exists( 'wc_get_product' );
    }

    /** @return array{mode:string,product:int} */
    public static function sale( int $attachment_id ): array {
        $raw = get_post_meta( $attachment_id, self::META, true );
        $raw = is_array( $raw ) ? $raw : array();
        return array(
            'mode' => 'paid' === ( $raw['mode'] ?? '' ) ? 'paid' : 'free',
            'product' => absint( $raw['product'] ?? 0 ),
        );
    }

    /** Zum Verkauf eingestellt (egal ob WooCommerce gerade an ist). */
    public static function is_paid( int $attachment_id ): bool {
        return 'paid' === self::sale( $attachment_id )['mode'];
    }

    public static function filter_free( $free, $attachment_id ): bool {
        return self::is_paid( (int) $attachment_id ) ? false : (bool) $free;
    }

    /** Verkaufbares Produkt zum Buch oder null. */
    public static function product( int $attachment_id ) {
        if ( ! self::active() || ! self::is_paid( $attachment_id ) ) { return null; }
        $product = wc_get_product( self::sale( $attachment_id )['product'] );
        return $product && 'publish' === $product->get_status() ? $product : null;
    }

    /**
     * Einstellung speichern. $price leer = Preis vom Produkt lassen. Gibt eine Fehlermeldung zurück, leer = ok.
     * $product_id 0 = neues Produkt anlegen.
     */
    public static function update( int $attachment_id, string $mode, string $price, int $product_id ): string {
        if ( 'paid' !== $mode ) { return self::make_free( $attachment_id ); }
        if ( ! self::active() ) { return 'Zum Verkaufen muss WooCommerce aktiv sein.'; }
        $price = self::clean_price( $price );
        $product = $product_id ? wc_get_product( $product_id ) : null;
        if ( $product_id && ( ! $product || ! current_user_can( 'edit_post', $product_id ) ) ) { return 'Das gewählte Produkt wurde nicht gefunden.'; }
        if ( ! $product && '' === $price ) { return 'Bitte einen Preis eintragen, damit das Produkt angelegt werden kann.'; }
        // erst schützen, dann verkaufen - nie eine offen liegende Datei verkaufen
        $error = self::protect( $attachment_id );
        if ( '' !== $error ) { return $error; }
        if ( ! $product ) {
            $product = new WC_Product_Simple();
            $product->set_name( MGD_Ebook_Library::title( $attachment_id ) );
            $product->set_status( 'publish' );
        }
        if ( ! $product instanceof WC_Product_Simple ) { return 'Bitte ein einfaches Produkt wählen (kein variables Produkt).'; }
        if ( '' !== $price ) { $product->set_regular_price( $price ); }
        $product->set_virtual( true );
        $product->set_downloadable( true );
        $product->set_status( 'publish' );
        $download = new WC_Product_Download();
        $download->set_id( 'mgd-ebook-' . $attachment_id );
        $download->set_name( MGD_Ebook_Library::title( $attachment_id ) . '.epub' );
        $download->set_file( (string) wp_get_attachment_url( $attachment_id ) );
        $product->set_downloads( array( $download ) );
        $description = MGD_Ebook_Library::book( $attachment_id )['description'];
        if ( '' !== $description && ! $product->get_short_description() ) { $product->set_short_description( $description ); }
        $product->update_meta_data( self::PRODUCT_META, $attachment_id );
        $saved = $product->save();
        if ( ! $saved ) { return 'Das Produkt konnte nicht gespeichert werden.'; }
        update_post_meta( $attachment_id, self::META, array( 'mode' => 'paid', 'product' => (int) $saved ) );
        return '';
    }

    /** Zurück auf kostenlos: Datei wieder öffentlich, Produkt auf Entwurf (nicht löschen). */
    private static function make_free( int $attachment_id ): string {
        $sale = self::sale( $attachment_id );
        $error = self::unprotect( $attachment_id );
        if ( '' !== $error ) { return $error; }
        if ( self::active() && $sale['product'] && ( $product = wc_get_product( $sale['product'] ) ) ) {
            $product->set_status( 'draft' );
            $product->save();
        }
        update_post_meta( $attachment_id, self::META, array( 'mode' => 'free', 'product' => $sale['product'] ) );
        return '';
    }

    public static function clean_price( string $price ): string {
        $price = str_replace( array( ' ', '€' ), '', trim( $price ) );
        if ( preg_match( '/^\d{1,3}(\.\d{3})*,\d{1,2}$/', $price ) ) { $price = str_replace( array( '.', ',' ), array( '', '.' ), $price ); }
        $price = str_replace( ',', '.', $price );
        return preg_match( '/^\d{1,6}(\.\d{1,2})?$/', $price ) && (float) $price > 0 ? number_format( (float) $price, 2, '.', '' ) : '';
    }

    /** Datei in den gesperrten WooCommerce-Ordner, mit zufälligem Unterordner. */
    public static function protect( int $attachment_id ): string {
        $path = (string) get_attached_file( $attachment_id );
        if ( '' === $path || ! is_file( $path ) ) { return 'Die EPUB-Datei wurde nicht gefunden.'; }
        $uploads = wp_upload_dir();
        $base = trailingslashit( $uploads['basedir'] );
        if ( str_starts_with( wp_normalize_path( $path ), wp_normalize_path( $base . self::PROTECTED . '/' ) ) ) { return ''; }
        $dir = $base . self::PROTECTED . '/' . $attachment_id . '-' . strtolower( wp_generate_password( 12, false, false ) );
        if ( ! wp_mkdir_p( $dir ) ) { return 'Der geschützte Ordner konnte nicht angelegt werden.'; }
        self::guard( $base . 'woocommerce_uploads' );
        self::guard( $base . self::PROTECTED );
        self::guard( $dir );
        $target = $dir . '/' . basename( $path );
        if ( ! @rename( $path, $target ) ) { return 'Die Datei konnte nicht in den geschützten Ordner verschoben werden.'; }
        update_post_meta( $attachment_id, self::META_ORIGIN, _wp_relative_upload_path( $path ) );
        update_attached_file( $attachment_id, $target );
        return '';
    }

    /** Datei zurück an den alten Platz (oder in den Monatsordner). */
    public static function unprotect( int $attachment_id ): string {
        $path = (string) get_attached_file( $attachment_id );
        $uploads = wp_upload_dir();
        $base = trailingslashit( $uploads['basedir'] );
        if ( '' === $path || ! str_starts_with( wp_normalize_path( $path ), wp_normalize_path( $base . self::PROTECTED . '/' ) ) ) { return ''; }
        $origin = (string) get_post_meta( $attachment_id, self::META_ORIGIN, true );
        $target = '' !== $origin && ! str_contains( $origin, '..' ) ? $base . ltrim( $origin, '/' ) : trailingslashit( $uploads['path'] ) . basename( $path );
        if ( file_exists( $target ) ) { $target = trailingslashit( dirname( $target ) ) . wp_unique_filename( dirname( $target ), basename( $target ) ); }
        if ( ! wp_mkdir_p( dirname( $target ) ) || ! @rename( $path, $target ) ) { return 'Die Datei konnte nicht zurückverschoben werden.'; }
        // leeren Zufallsordner wegräumen
        wp_delete_file( dirname( $path ) . '/index.php' );
        wp_delete_file( dirname( $path ) . '/.htaccess' );
        @rmdir( dirname( $path ) );
        update_attached_file( $attachment_id, $target );
        delete_post_meta( $attachment_id, self::META_ORIGIN );
        return '';
    }

    // Verzeichnis sperren wie WooCommerce selbst (Apache) + leere index.php
    private static function guard( string $dir ): void {
        if ( ! is_dir( $dir ) ) { return; }
        if ( ! is_file( $dir . '/.htaccess' ) ) { @file_put_contents( $dir . '/.htaccess', "deny from all\n" ); }
        if ( ! is_file( $dir . '/index.php' ) ) { @file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ); }
    }

    /** Knopf im Vorschau-Fenster: Preis + In den Warenkorb */
    public static function action_html( $html, $attachment_id ): string {
        $attachment_id = (int) $attachment_id;
        if ( ! self::is_paid( $attachment_id ) ) { return (string) $html; }
        $product = self::product( $attachment_id );
        if ( ! $product ) { return '<p class="mgd-ebook__unavailable">Dieses eBook ist gerade nicht erhältlich.</p>'; }
        $icon = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M3 4h2l2.2 10.2a1.5 1.5 0 0 0 1.5 1.2h8.6a1.5 1.5 0 0 0 1.5-1.1L20.5 8H6.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9.5" cy="19.5" r="1.3" fill="currentColor"/><circle cx="17" cy="19.5" r="1.3" fill="currentColor"/></svg>';
        $out = '<span class="mgd-ebook__price">' . wp_kses_post( $product->get_price_html() ) . '</span>';
        if ( $product->is_purchasable() ) {
            $out .= '<a class="mgd-ebook__download mgd-ebook__buy" href="' . esc_url( $product->add_to_cart_url() ) . '" rel="nofollow">' . $icon . '<span>In den Warenkorb</span></a>';
        }
        $out .= '<a class="mgd-ebook__more" href="' . esc_url( $product->get_permalink() ) . '">Zum Produkt</a>';
        return $out;
    }

    public static function cover_href( $href, $attachment_id ): string {
        $product = self::product( (int) $attachment_id );
        return $product ? (string) $product->get_permalink() : (string) $href;
    }

    /** Google-Infos: echter Preis statt 0 */
    public static function schema( $data, $attachment_id ): array {
        $data = (array) $data;
        $product = self::product( (int) $attachment_id );
        if ( ! $product ) { unset( $data['offers'] ); return $data; }
        $data['offers'] = array(
            '@type' => 'Offer',
            'price' => (string) wc_get_price_to_display( $product ),
            'priceCurrency' => get_woocommerce_currency(),
            'availability' => $product->is_purchasable() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url' => $product->get_permalink(),
        );
        return $data;
    }

    public static function count_download( $email, $order_key, $product_id, $user_id, $download_id, $order_id ): void {
        $book = absint( get_post_meta( (int) $product_id, self::PRODUCT_META, true ) );
        if ( $book && MGD_Ebook_Library::is_ebook( $book ) && 'mgd-ebook-' . $book === (string) $download_id ) {
            MGD_Ebook_Downloads::add( $book );
        }
    }

    /** Produkte zur Auswahl im Admin (einfache, virtuelle oder ohne Versand) */
    public static function product_options(): array {
        if ( ! self::active() ) { return array(); }
        $out = array();
        foreach ( wc_get_products( array( 'type' => 'simple', 'limit' => 200, 'status' => array( 'publish', 'draft' ), 'orderby' => 'title', 'order' => 'ASC' ) ) as $product ) {
            $out[ $product->get_id() ] = $product->get_name() . ' (' . wp_strip_all_tags( wc_price( (float) $product->get_regular_price() ) ) . ')';
        }
        return $out;
    }
}
