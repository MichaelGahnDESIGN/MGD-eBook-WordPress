<?php
/**
 * Plugin Name: MGD eBook
 * Plugin URI: https://github.com/MichaelGahnDESIGN/MGD-eBook-WordPress
 * Description: EPUB-Dateien hochladen, als Download anbieten und per Shortcode mit Cover und Vorschau-Fenster zeigen. Meldet sich höchstens einmal am Tag bei theme.michael-gahn.de (Plugin, Version, Shopsystem, Domain, zufällige ID). Abschaltbar in den Einstellungen.
 * Version: 0.6.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Michael Gahn DESIGN
 * Author URI: https://michael-gahn.de
 * License: GPL-2.0-or-later
 * Update URI: https://theme.michael-gahn.de/plugin-updates/mgd-ebook.json
 * Text Domain: mgd-ebook
 */

defined( 'ABSPATH' ) || exit;

define( 'MGD_EBOOK_VERSION', '0.6.0' );
define( 'MGD_EBOOK_FILE', __FILE__ );
define( 'MGD_EBOOK_DIR', plugin_dir_path( __FILE__ ) );
define( 'MGD_EBOOK_URL', plugin_dir_url( __FILE__ ) );

require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-epub.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-library.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-renderer.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-downloads.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-sample.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-schema.php';
require_once MGD_EBOOK_DIR . 'divi/class-mgd-ebook-divi.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-admin.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-plugin.php';

MGD_Ebook_Plugin::boot();
// Divi-5-Module, greift nur wenn Divi 5 läuft
MGD_Ebook_Divi::boot();

// Baukasten aus MichaThemeV5-Pro > includes/Kit (bin/build-plugin-kit.php), nicht von hand ändern
spl_autoload_register( static function ( string $class ): void {
	$prefix = 'Mgd\\Ebook\\Kit\\';
	if ( str_starts_with( $class, $prefix ) ) {
		$file = MGD_EBOOK_DIR . 'includes/Kit/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_file( $file ) ) {
			require $file;
		}
	}
} );

// Updater vom Baukasten, startet auch den Install-Melder - alter Updater, Install-Client und Admin-Tools sind raus
MGD_Ebook_Plugin::$updater = \Mgd\Ebook\Kit\Platform\WordPress\Setup::updater( __FILE__, 'mgd-ebook', 'wordpress', MGD_EBOOK_VERSION, 'MGD eBook', admin_url( 'admin.php?page=mgd-ebook-plugins' ) );
// Reste vom alten Updater wegräumen, 1x pro Version
\Mgd\Ebook\Kit\Platform\WordPress\Setup::cleanupLegacy( 'mgd-ebook', MGD_EBOOK_VERSION, array( 'mgd_admin_release_mgd-ebook', 'mgd_ebook_update_manifest' ) );

// Links Einstellungen + Info in der Plugin-Liste
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), static function ( array $links ): array {
	array_unshift(
		$links,
		'<a href="' . esc_url( admin_url( 'admin.php?page=mgd-ebook' ) ) . '">eBooks</a>',
		'<a href="' . esc_url( admin_url( 'admin.php?page=mgd-ebook-plugins' ) ) . '">Info und Updates</a>'
	);
	return $links;
} );
