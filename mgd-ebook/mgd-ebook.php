<?php
/**
 * Plugin Name: MGD eBook
 * Plugin URI: https://github.com/MichaelGahnDESIGN/MGD-eBook-WordPress
 * Description: EPUB-Dateien hochladen, als Download anbieten und per Shortcode mit Cover und Vorschau-Fenster zeigen.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Michael Gahn DESIGN
 * License: GPL-2.0-or-later
 * Update URI: https://github.com/MichaelGahnDESIGN/MGD-eBook-WordPress
 * Text Domain: mgd-ebook
 */

defined( 'ABSPATH' ) || exit;

define( 'MGD_EBOOK_VERSION', '0.1.0' );
define( 'MGD_EBOOK_FILE', __FILE__ );
define( 'MGD_EBOOK_DIR', plugin_dir_path( __FILE__ ) );
define( 'MGD_EBOOK_URL', plugin_dir_url( __FILE__ ) );

require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-epub.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-library.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-renderer.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-wordpress-plugin-catalog.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-admin.php';
require_once MGD_EBOOK_DIR . 'includes/class-mgd-ebook-plugin.php';

MGD_Ebook_Plugin::boot();

// Updater + Plugin-Links - gleiche Komponente wie in den anderen MGD Plugins
require_once __DIR__ . '/includes/class-mgd-plugin-admin-tools.php';
\MGD_Plugin_Admin_Tools_20261023::register([
    'file' => __FILE__,
    'repo' => 'MGD-eBook-WordPress',
    'slug' => 'mgd-ebook',
    'name' => 'MGD eBook',
    'description' => 'EPUB-Dateien hochladen, als Download anbieten und per Shortcode mit Cover und Vorschau-Fenster zeigen.',
    'version' => '0.1.0',
    'settings' => 'admin.php?page=mgd-ebook',
    'requires' => '6.5',
    'php' => '8.1',
    'caches' => ['mgd_admin_release_mgd-ebook'],
    'package' => 'mgd-ebook-{version}.zip',
]);
