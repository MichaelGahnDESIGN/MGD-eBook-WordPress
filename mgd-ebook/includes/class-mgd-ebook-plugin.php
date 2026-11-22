<?php
defined( 'ABSPATH' ) || exit;

/** Startet alle Teile des Plugins. */
final class MGD_Ebook_Plugin {
    public static ?\Mgd\Ebook\Kit\Platform\WordPress\Updater $updater = null;

    public static function boot(): void {
        $library = new MGD_Ebook_Library();
        $library->register();
        ( new MGD_Ebook_Downloads() )->register();
        $renderer = new MGD_Ebook_Renderer();
        add_action( 'init', array( $renderer, 'register' ) );
        add_action( 'wp_enqueue_scripts', array( $renderer, 'assets' ) );
        if ( is_admin() ) {
            ( new MGD_Ebook_Admin( $library ) )->register();
        }
    }
}
