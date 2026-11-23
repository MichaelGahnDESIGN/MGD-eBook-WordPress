<?php
/**
 * Test Verkauf (ohne WooCommerce-Lauf): php tests/shop.php
 */
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['meta'] = array();
function get_post_meta( $id, $k, $s = false ) { return $GLOBALS['meta'][ $id ][ $k ] ?? ''; }
function update_post_meta( $id, $k, $v ) { $GLOBALS['meta'][ $id ][ $k ] = $v; return true; }
function absint( $v ) { return abs( (int) $v ); }
function add_filter( ...$a ) {}
function add_action( ...$a ) {}
require __DIR__ . '/../mgd-ebook/includes/class-mgd-ebook-shop.php';

$fails = 0;
function check( string $name, bool $ok ): void { global $fails; echo ( $ok ? 'OK    ' : 'FEHLER' ) . ' ' . $name . "\n"; $fails += $ok ? 0 : 1; }

check( 'Preis 4,99 > 4.99', '4.99' === MGD_Ebook_Shop::clean_price( '4,99' ) );
check( 'Preis 4.5 € > 4.50', '4.50' === MGD_Ebook_Shop::clean_price( '4.5 €' ) );
check( 'Preis 1.299,00 > 1299.00', '1299.00' === MGD_Ebook_Shop::clean_price( '1.299,00' ) );
check( 'Preis 0 / Text / negativ abgelehnt', '' === MGD_Ebook_Shop::clean_price( '0' ) && '' === MGD_Ebook_Shop::clean_price( 'gratis' ) && '' === MGD_Ebook_Shop::clean_price( '-3' ) );
check( 'Ab Werk kostenlos', 'free' === MGD_Ebook_Shop::sale( 7 )['mode'] && true === MGD_Ebook_Shop::filter_free( true, 7 ) );
update_post_meta( 7, MGD_Ebook_Shop::META, array( 'mode' => 'paid', 'product' => 12 ) );
check( 'Verkauf: nicht mehr kostenlos (Zähl-Link + Download aus)', false === MGD_Ebook_Shop::filter_free( true, 7 ) );
check( 'Verkauf ohne WooCommerce: kein Produkt, Schema ohne Angebot', null === MGD_Ebook_Shop::product( 7 ) && ! isset( MGD_Ebook_Shop::schema( array( 'offers' => array( 'price' => '0' ) ), 7 )['offers'] ) );
update_post_meta( 8, MGD_Ebook_Shop::META, array( 'mode' => 'quatsch', 'product' => 'x' ) );
check( 'Kaputte Werte: gilt als kostenlos', 'free' === MGD_Ebook_Shop::sale( 8 )['mode'] && 0 === MGD_Ebook_Shop::sale( 8 )['product'] );

echo $fails ? "\n$fails Fehler\n" : "\nAlles gut\n";
exit( $fails ? 1 : 0 );
