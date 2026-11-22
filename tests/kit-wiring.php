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
$tpl   = file_get_contents( $root . 'templates/admin-page.php' );
$unin  = file_get_contents( $root . 'uninstall.php' );
preg_match( '/^ \* Version: (\S+)/m', $main, $m );
$version = $m[1] ?? '';

check( 'Version im Kopf und in der Konstante gleich', str_contains( $main, "define( 'MGD_EBOOK_VERSION', '" . $version . "' )" ) );
check( 'Update URI zeigt auf theme.michael-gahn.de', str_contains( $main, 'Update URI: https://theme.michael-gahn.de/plugin-updates/mgd-ebook.json' ) );
check( 'Baukasten-Updater mit Produkt mgd-ebook', str_contains( $main, "Setup::updater( __FILE__, 'mgd-ebook', 'wordpress', MGD_EBOOK_VERSION, 'MGD eBook'" ) );
check( 'Alte Reste werden aufgeräumt', str_contains( $main, "Setup::cleanupLegacy( 'mgd-ebook'" ) );
check( 'Alter Updater wird nicht mehr registriert', ! str_contains( $main, 'MGD_Ebook_Updater::register' ) );
check( 'Alter Install-Client wird nicht mehr gestartet', ! str_contains( $main, 'Install\\Platform\\WordPress::boot' ) );
check( 'Alte Admin-Tools (GitHub-Updater) sind aus', ! str_contains( $main, 'MGD_Plugin_Admin_Tools_20261023::register' ) );
check( 'Alte Seite Weitere Plugins ist aus', ! str_contains( $admin, 'MGD_WordPress_Plugin_Catalog_V4' ) );
check( 'Alte Katalog-Datei ist ganz entfernt', ! is_file( $root . 'includes/class-mgd-wordpress-plugin-catalog.php' ) && ! str_contains( $main, 'class-mgd-wordpress-plugin-catalog.php' ) );
check( 'Kein eigener catalogActions-Aufruf mehr (macht der Baukasten ab 6a64577)', ! str_contains( $admin, 'catalogActions' ) && str_contains( (string) file_get_contents( $root . 'includes/Kit/Platform/WordPress/Setup.php' ), 'catalogAuto' ) );
check( 'Neue Kollektion-Ansicht im Baukasten', is_file( $root . 'includes/Kit/Platform/WordPress/assets/kit-catalog.css' ) && is_file( $root . 'includes/Kit/Platform/WordPress/CatalogInstaller.php' ) );
check( 'Seite Info und Updates (Dashboard) ist da', str_contains( $admin, "'mgd-ebook-plugins'" ) && str_contains( $admin, "Setup::dashboard( 'mgd-ebook', 'wordpress', MGD_EBOOK_VERSION, 'MGD eBook', MGD_Ebook_Plugin::\$updater, \$list, false, '' )" ) );
check( 'Empfehlungen blenden eBook selbst aus', str_contains( $admin, "Setup::recommendations( 'mgd-ebook', 'wordpress', 'mgd-ebook', \$this->fallback_plugins(), 'MGD-eBook-WordPress' )" ) );
check( 'Alter Schalter nicht mehr doppelt auf der eBook-Seite', ! str_contains( $tpl, 'settingsHtml' ) );
check( 'uninstall.php meldet über den Baukasten ab', str_contains( $unin, "Setup::uninstall( 'mgd-ebook', 'wordpress' )" ) );

// Kit-Dateien vorhanden + Namensraum passt
$kit = $root . 'includes/Kit/Platform/WordPress/Setup.php';
check( 'Baukasten liegt in includes/Kit', is_file( $kit ) && str_contains( (string) file_get_contents( $kit ), 'namespace Mgd\\Ebook\\Kit\\Platform\\WordPress;' ) );

// Ersatzliste: jeder Eintrag hat eine Katalog-Kennung, eBook selbst ist nicht drin
$list = json_decode( (string) file_get_contents( $root . 'includes/plugin-catalog/plugins.json' ), true );
$ok = is_array( $list ) && $list;
foreach ( (array) $list as $row ) { $ok = $ok && ! empty( $row['slug'] ) && 'mgd-ebook' !== $row['slug']; }
check( 'Ersatzliste mit Kennungen, ohne eBook', $ok );

echo $fails ? "\n$fails Fehler\n" : "\nAlles gut\n";
exit( $fails ? 1 : 0 );
