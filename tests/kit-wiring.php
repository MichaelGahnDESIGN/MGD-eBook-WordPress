<?php
/**
 * Test Baukasten-Einbau ohne WordPress: php tests/kit-wiring.php
 * Prüft, dass der Baukasten angeschlossen ist und die alten Updater nicht mehr laufen.
 */
$fails = 0;
function check( string $name, bool $ok ): void {
	global $fails;
	echo ( $ok ? 'OK    ' : 'FEHLER' ) . ' ' . $name . "\n";
	$fails += $ok ? 0 : 1;
}
$root  = __DIR__ . '/../mgd-ebook/';
$main  = file_get_contents( $root . 'mgd-ebook.php' );
$admin = file_get_contents( $root . 'includes/class-mgd-ebook-admin.php' );
$unin  = file_get_contents( $root . 'uninstall.php' );
preg_match( '/^ \* Version: (\S+)/m', $main, $m );
$version = $m[1] ?? '';

check( 'Version im Kopf und in der Konstante gleich', str_contains( $main, "define( 'MGD_EBOOK_VERSION', '" . $version . "' )" ) );
check( 'Plugin-Kopf genormt: Name MGD eBook, ein Autor, eine Beschreibung', str_contains( $main, 'Plugin Name: MGD eBook' ) && 1 === substr_count( $main, ' * Author: ' ) && 1 === substr_count( $main, ' * Description: ' ) );
check( 'Update URI zeigt auf theme.michael-gahn.de', str_contains( $main, 'Update URI: https://theme.michael-gahn.de/plugin-updates/mgd-ebook.json' ) );
check( 'Baukasten-Updater mit Produkt mgd-ebook', str_contains( $main, "Setup::updater( __FILE__, 'mgd-ebook', 'wordpress', MGD_EBOOK_VERSION, 'MGD eBook'" ) );
check( 'Seite unter MGD Tools: Kennung mgd-ebook, Recht upload_files', str_contains( $main, "Setup::tools( __FILE__, 'mgd-ebook'" ) && str_contains( $main, "'page' => 'mgd-ebook'" ) && str_contains( $main, "'cap' => 'upload_files'" ) );
check( 'Lizenz auf der eBook-Seite aus (kein Lizenzfeld)', str_contains( $main, "'license'  => false" ) );
check( 'Kein eigenes Menü mehr', ! str_contains( $admin, 'add_menu_page' ) && ! str_contains( $admin, 'add_submenu_page' ) );
check( 'Seiten-Skript lädt auch unter MGD Tools (Hook endet auf _page_mgd-ebook)', str_contains( $admin, "'_page_' . self::PAGE" ) );
check( 'Alter Updater, Install-Client, Admin-Tools und alte Katalogseite sind gelöscht', ! is_file( $root . 'includes/class-mgd-ebook-updater.php' ) && ! is_file( $root . 'includes/class-mgd-plugin-admin-tools.php' ) && ! is_dir( $root . 'includes/install-client' ) && ! is_dir( $root . 'includes/plugin-catalog' ) && ! is_file( $root . 'includes/class-mgd-wordpress-plugin-catalog.php' ) );
check( 'Kein eigener catalogActions-Aufruf mehr (macht der Baukasten)', ! str_contains( $admin, 'catalogActions' ) );
check( 'uninstall.php meldet über den Baukasten ab', str_contains( $unin, "Setup::uninstall( 'mgd-ebook', 'wordpress' )" ) );
check( 'Icons 128 und 256 da', is_file( $root . 'assets/icon-128x128.png' ) && is_file( $root . 'assets/icon-256x256.png' ) );
check( 'CHANGELOG.md da', is_file( __DIR__ . '/../CHANGELOG.md' ) );
$kit = $root . 'includes/Kit/Platform/WordPress/Setup.php';
check( 'Baukasten liegt in includes/Kit, eigener Namensraum', is_file( $kit ) && str_contains( (string) file_get_contents( $kit ), 'namespace Mgd\\Ebook\\Kit\\Platform\\WordPress;' ) && is_file( $root . 'includes/Kit/Platform/WordPress/Tools.php' ) );

echo $fails ? "\n$fails Fehler\n" : "\nAlles gut\n";
exit( $fails ? 1 : 0 );
