<?php
/**
 * Test Free-Lizenz Anbindung ohne WordPress: php tests/install.php
 * Prüft Client-Aufbau, Schalter-HTML und Schalter aus/an (ohne Netz).
 */
define( 'ABSPATH', __DIR__ );

$GLOBALS['opts']  = array();
$GLOBALS['hooks'] = array();
function get_option( $k, $d = false ) { return $GLOBALS['opts'][ $k ] ?? $d; }
function update_option( $k, $v, $a = null ) { $GLOBALS['opts'][ $k ] = $v; return true; }
function home_url() { return 'https://WooCommerce.Theme.Michael-Gahn.de/'; }
function wp_parse_url( $u, $c = -1 ) { return parse_url( $u, $c ); }
function wp_nonce_field( $a, $n, $r, $e ) { return '<input type="hidden" name="' . $n . '" value="x">'; }
function register_activation_hook( $f, $cb ) { $GLOBALS['hooks']['activate'] = $cb; }
function add_action( $h, $cb ) { $GLOBALS['hooks'][ $h ] = $cb; }

foreach ( array( 'Store', 'Transport', 'Response', 'CallbackStore', 'CurlTransport', 'InstallClient', 'Factory', 'Settings', 'Platform/WordPress' ) as $f ) {
	require __DIR__ . '/../mgd-ebook/includes/install-client/' . $f . '.php';
}
use Mgd\Ebook\Install\Platform\WordPress;
use Mgd\Ebook\Install\Settings;

$fails = 0;
function check( string $name, bool $ok ): void {
	global $fails;
	echo ( $ok ? 'OK    ' : 'FEHLER' ) . ' ' . $name . "\n";
	$fails += $ok ? 0 : 1;
}

$c = WordPress::client( 'mgd-ebook', 'wordpress', '0.3.0' );
check( 'Client wird gebaut (Produktname gültig)', null !== $c );
check( 'Falscher Produktname gibt null statt Fehler', null === WordPress::client( 'Kaputt Name!', 'wordpress', '0.3.0' ) );
check( 'Schalter ab Werk an', $c->isEnabled() );
$html = WordPress::settingsHtml( 'mgd-ebook', 'wordpress', '0.3.0' );
check( 'Einstellungen zeigen Schalter angehakt', str_contains( $html, 'mgd_install_report_mgd_ebook' ) && str_contains( $html, 'checked' ) );
check( 'Einstellungen zeigen freigegebenen Hinweistext', str_contains( $html, htmlspecialchars( Settings::HINT, ENT_QUOTES ) ) );
check( 'Einstellungen haben Nonce-Feld', str_contains( $html, '_mgd_install_nonce' ) );
$c->setEnabled( false );
check( 'Schalter aus wird gespeichert', ! WordPress::client( 'mgd-ebook', 'wordpress', '0.3.0' )->isEnabled() );
check( 'Schalter aus: ping sendet nichts', false === $c->ping( true ) );
$c->setEnabled( true );
check( 'Schalter wieder an', WordPress::client( 'mgd-ebook', 'wordpress', '0.3.0' )->isEnabled() );
check( 'Status ist eigene Option ohne Autoload', isset( $GLOBALS['opts']['mgd_install_mgd_ebook'] ) );
WordPress::boot( __FILE__, 'mgd-ebook', 'wordpress', '0.3.0', 'https://x/wp-admin/admin.php?page=mgd-ebook' );
check( 'boot hängt sich an admin_init und admin_notices', isset( $GLOBALS['hooks']['admin_init'], $GLOBALS['hooks']['admin_notices'], $GLOBALS['hooks']['activate'] ) );

// freigegebene Texte wörtlich (Michael, 18.11.2026)
$short = 'Meldet sich höchstens einmal am Tag bei theme.michael-gahn.de (Plugin, Version, Shopsystem, Domain, zufällige ID). Abschaltbar in den Einstellungen.';
check( 'Schalter-Text wörtlich freigegeben', 'Installation bei Michael Gahn DESIGN melden' === Settings::LABEL );
check( 'Kurztext im Plugin-Kopf', str_contains( file_get_contents( __DIR__ . '/../mgd-ebook/mgd-ebook.php' ), $short ) );
check( 'Kurztext in der README', str_contains( file_get_contents( __DIR__ . '/../README.md' ), $short ) );
check( 'Schalter kommt über das Baukasten-Dashboard', str_contains( file_get_contents( __DIR__ . '/../mgd-ebook/includes/class-mgd-ebook-admin.php' ), "Setup::dashboard( 'mgd-ebook'" ) );

echo $fails ? "\n$fails Fehler\n" : "\nAlles gut\n";
exit( $fails ? 1 : 0 );
