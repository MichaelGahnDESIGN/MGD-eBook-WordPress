<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Baukasten laden > includes/Kit
spl_autoload_register( static function ( string $class ): void {
	$prefix = 'Mgd\\Ebook\\Kit\\';
	if ( str_starts_with( $class, $prefix ) ) {
		$file = __DIR__ . '/includes/Kit/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_file( $file ) ) {
			require $file;
		}
	}
} );

// Abmelden bei theme.michael-gahn.de + Baukasten-Daten weg, eBooks selbst bleiben in der Mediathek
try {
	\Mgd\Ebook\Kit\Platform\WordPress\Setup::uninstall( 'mgd-ebook', 'wordpress' );
} catch ( \Throwable $e ) {
	unset( $e );
}
// alter Updater-Cache aus 0.3.0
delete_site_transient( 'mgd_ebook_update_manifest' );
