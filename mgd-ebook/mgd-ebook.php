<?php
/**
 * Plugin Name: MGD eBook
 * Plugin URI: https://theme.michael-gahn.de/plugins/mgd-ebook
 * Description: EPUB-Dateien hochladen, als Download oder zum Kauf anbieten und mit Cover, Vorschau und Leseprobe zeigen.
 * Version: 2.1.1
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Michael Gahn DESIGN
 * Author URI: https://michael-gahn.de
 * License: GPL-2.0-or-later
 * Update URI: https://theme.michael-gahn.de/plugin-updates/mgd-ebook.json
 * Text Domain: mgd-ebook
 */

defined( 'ABSPATH' ) || exit;

define( 'MGD_EBOOK_VERSION', '2.1.1' );
define( 'MGD_EBOOK_FILE', __FILE__ );
define( 'MGD_EBOOK_DIR', plugin_dir_path( __FILE__ ) );
define( 'MGD_EBOOK_URL', plugin_dir_url( __FILE__ ) );

require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-epub.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-library.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-renderer.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-downloads.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-sample.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-schema.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-shop.php';
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

// Updater + MGD Tools Menü vom Baukasten
use Mgd\Ebook\Kit\Platform\WordPress\Setup;
use Mgd\Ebook\Kit\Platform\WordPress\Tools;

MGD_Ebook_Plugin::$updater = Setup::updater( __FILE__, 'mgd-ebook', 'wordpress', MGD_EBOOK_VERSION, 'MGD eBook', Tools::url() );
// Reste vom alten Updater wegräumen, 1x pro Version
Setup::cleanupLegacy( 'mgd-ebook', MGD_EBOOK_VERSION, array( 'mgd_admin_release_mgd-ebook', 'mgd_ebook_update_manifest' ) );

// Seite eBook unter MGD Tools - Seiten-Kennung bleibt mgd-ebook, Recht bleibt upload_files
Setup::tools( __FILE__, 'mgd-ebook', 'wordpress', MGD_EBOOK_VERSION, 'MGD eBook', MGD_Ebook_Plugin::$updater, array(
	'slug'     => 'mgd-ebook',
	'repo'     => 'MGD-eBook-WordPress',
	'settings' => array( 'page' => 'mgd-ebook', 'title' => 'eBook', 'cap' => 'upload_files', 'legacy' => array( 'mgd-ebook-plugins' ), 'render' => static function (): void { MGD_Ebook_Plugin::$admin?->page(); } ),
	'license'  => false,
) );
