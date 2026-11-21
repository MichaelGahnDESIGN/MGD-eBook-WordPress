<?php
/**
 * Plugin Name: MGD eBook
 * Plugin URI: https://github.com/MichaelGahnDESIGN/MGD-eBook-WordPress
 * Description: EPUB-Dateien hochladen, als Download anbieten und per Shortcode mit Cover und Vorschau-Fenster zeigen. Meldet sich höchstens einmal am Tag bei theme.michael-gahn.de (Plugin, Version, Shopsystem, Domain, zufällige ID). Abschaltbar in den Einstellungen.
 * Version: 0.2.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Michael Gahn DESIGN
 * Author URI: https://michael-gahn.de
 * License: GPL-2.0-or-later
 * Update URI: https://theme.michael-gahn.de/plugin-updates/mgd-ebook.json
 * Text Domain: mgd-ebook
 */

defined( 'ABSPATH' ) || exit;

define( 'MGD_EBOOK_VERSION', '0.2.0' );
define( 'MGD_EBOOK_FILE', __FILE__ );
define( 'MGD_EBOOK_DIR', plugin_dir_path( __FILE__ ) );
define( 'MGD_EBOOK_URL', plugin_dir_url( __FILE__ ) );

require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-epub.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-library.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-renderer.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-wordpress-plugin-catalog.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-admin.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-plugin.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-updater.php';

MGD_Ebook_Plugin::boot();

// Updates nur noch über theme.michael-gahn.de mit SHA-256 Prüfung, wie MGD Login
MGD_Ebook_Updater::register( __FILE__ );

// Free-Lizenz Meldung > Install-Client aus MichaThemeV5-Pro (bin/build-install-client.php), nicht von hand ändern
foreach ( array( 'Store', 'Transport', 'Response', 'CallbackStore', 'CurlTransport', 'InstallClient', 'Factory', 'Settings', 'Platform/WordPress' ) as $mgd_ebook_ic ) {
	require_once MGD_EBOOK_DIR . 'includes/install-client/' . $mgd_ebook_ic . '.php';
}
unset( $mgd_ebook_ic );
try {
	\Mgd\Ebook\Install\Platform\WordPress::boot( __FILE__, 'mgd-ebook', 'wordpress', MGD_EBOOK_VERSION, admin_url( 'admin.php?page=mgd-ebook#mgd-ebook-install' ) );
} catch ( \Throwable $e ) {
	// Plugin läuft auch ohne Meldung weiter
	unset( $e );
}

// Plugin-Links + Details wie bei den anderen MGD Plugins, ohne 'package' > keine GitHub-Updates mehr
require_once __DIR__ . '/includes/class-mgd-plugin-admin-tools.php';
\MGD_Plugin_Admin_Tools_20261023::register([
    'file' => __FILE__,
    'repo' => 'MGD-eBook-WordPress',
    'slug' => 'mgd-ebook',
    'name' => 'MGD eBook',
    'description' => 'EPUB-Dateien hochladen, als Download anbieten und per Shortcode mit Cover und Vorschau-Fenster zeigen.',
    'version' => '0.2.0',
    'settings' => 'admin.php?page=mgd-ebook',
    'requires' => '6.5',
    'php' => '8.1',
    'caches' => [MGD_Ebook_Updater::CACHE],
]);
