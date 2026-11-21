<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Free-Lizenz Status (ID + Schlüssel) weg, eBooks selbst bleiben in der Mediathek
delete_option( 'mgd_install_mgd_ebook' );
delete_site_transient( 'mgd_ebook_update_manifest' );
