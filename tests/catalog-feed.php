<?php
/**
 * Test Empfehlungs-Feed ohne WordPress: php tests/catalog-feed.php
 * Neue Adresse /api/v1/recommendations, 15 Min Cache, Ersatzliste wenn Feed weg.
 */
define( 'ABSPATH', __DIR__ );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );

$GLOBALS['transients'] = array();
$GLOBALS['ttl']        = array();
$GLOBALS['calls']      = array();
$GLOBALS['http']       = null;
class WP_Error {}
function is_wp_error( $x ) { return $x instanceof WP_Error; }
function get_transient( $k ) { return $GLOBALS['transients'][ $k ] ?? false; }
function set_transient( $k, $v, $t = 0 ) { $GLOBALS['transients'][ $k ] = $v; $GLOBALS['ttl'][ $k ] = $t; return true; }
function wp_remote_get( $url, $args = array() ) { $GLOBALS['calls'][] = $url; return ( $GLOBALS['http'] )( $url ); }
function wp_remote_retrieve_response_code( $r ) { return $r['code'] ?? 0; }
function wp_remote_retrieve_body( $r ) { return $r['body'] ?? ''; }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function esc_url_raw( $s ) { return $s; }
function wp_upload_dir( $a = null, $b = true ) { return array( 'error' => 'kein Upload-Ordner im Test' ); }

require __DIR__ . '/../mgd-ebook/includes/plugin-catalog/class-provider.php';

$fails = 0;
function check( string $name, bool $ok ): void {
	global $fails;
	echo ( $ok ? 'OK    ' : 'FEHLER' ) . ' ' . $name . "\n";
	$fails += $ok ? 0 : 1;
}

const FEED = 'https://theme.michael-gahn.de/api/v1/recommendations/wordpress.json';
$feed = array( 'schemaVersion' => 1, 'items' => array(
	array( 'id' => 'mgd-wordpress-login', 'name' => 'MGD WordPress Login', 'description' => 'Schöner Login', 'platform' => 'wordpress', 'tier' => 'free', 'status' => 'live', 'version' => 'v1.5.0', 'repositoryUrl' => 'https://github.com/MichaelGahnDESIGN/MGD-WordPress-Login', 'detailsUrl' => 'https://theme.michael-gahn.de/plugins/mgd-wordpress-login' ),
	array( 'id' => 'x-shopware', 'name' => 'Nur Shopware', 'platform' => 'shopware', 'tier' => 'free', 'status' => 'live', 'repositoryUrl' => 'https://github.com/MichaelGahnDESIGN/MGD-Shopware-X' ),
	array( 'id' => 'fremd', 'name' => 'Fremdes Repo', 'platform' => 'wordpress', 'tier' => 'free', 'status' => 'live', 'repositoryUrl' => 'https://github.com/evil/MGD-Fake' ),
) );
// GitHub-Release-Abfrage still abweisen, nur der Feed zählt hier
$GLOBALS['http'] = fn( $url ) => FEED === $url ? array( 'code' => 200, 'body' => json_encode( $feed ) ) : array( 'code' => 404 );

$cat = ( new MGD_Catalog_V4_Provider() )->catalog();
check( 'Fragt die neue Adresse /api/v1/recommendations/wordpress.json', FEED === ( $GLOBALS['calls'][0] ?? '' ) );
check( 'Alte Adresse api/catalog/plugins.json wird nicht mehr gefragt', ! in_array( 'https://theme.michael-gahn.de/api/catalog/plugins.json', $GLOBALS['calls'], true ) );
check( 'Quelle ist der zentrale Feed', 'central' === $cat['source'] );
check( 'Nur WordPress-Plugins aus dem eigenen Account', 1 === count( $cat['items'] ) && 'MGD-WordPress-Login' === $cat['items'][0]['repository'] );
check( 'Version ohne v', '1.5.0' === $cat['items'][0]['version'] );
check( 'Cache 15 Minuten (900 s)', 900 === ( $GLOBALS['ttl']['mgd_wordpress_catalog_v4'] ?? null ) );

$GLOBALS['calls'] = array();
( new MGD_Catalog_V4_Provider() )->catalog();
check( 'Zweiter Aufruf kommt aus dem Cache', array() === $GLOBALS['calls'] );

$GLOBALS['transients'] = array();
$GLOBALS['http']       = fn( $url ) => new WP_Error();
$cat = ( new MGD_Catalog_V4_Provider() )->catalog();
check( 'Feed weg: mitgelieferte Ersatzliste, kein Fehler', 'fallback' === $cat['source'] && count( $cat['items'] ) > 0 );

echo $fails ? "\n$fails Fehler\n" : "\nAlles gut\n";
exit( $fails ? 1 : 0 );
