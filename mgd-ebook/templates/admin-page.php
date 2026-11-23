<?php
defined( 'ABSPATH' ) || exit;
/** @var WP_Query $query */
/** @var array $messages */
/** @var string $max_upload */
/** @var int $paged */
$total_pages = (int) $query->max_num_pages;
$total = (int) $query->found_posts;
?>
<div class="wrap mgd-ebook-admin">
    <header class="mgd-ebook-admin__hero">
        <div>
            <p class="mgd-ebook-admin__eyebrow"><span>MGD</span> eBook</p>
            <h1>Deine eBooks</h1>
            <p>EPUB hochladen, Link kopieren oder Shortcode einfügen. Fertig.</p>
        </div>
        <p class="mgd-ebook-admin__count"><strong><?php echo (int) $total; ?></strong> <?php echo 1 === $total ? 'eBook' : 'eBooks'; ?></p>
    </header>

    <?php foreach ( $messages as $message ) : ?>
        <div class="notice notice-<?php echo esc_attr( $message[0] ); ?> is-dismissible"><p><?php echo esc_html( $message[1] ); ?></p></div>
    <?php endforeach; ?>

    <?php // Schritt 1: Uploader ?>
    <section class="mgd-ebook-admin__panel" aria-labelledby="mgd-ebook-upload-title">
        <h2 id="mgd-ebook-upload-title"><span class="mgd-ebook-admin__step">1</span> EPUB hochladen</h2>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" data-mgd-upload>
            <?php wp_nonce_field( 'mgd_ebook_upload' ); ?>
            <input type="hidden" name="action" value="mgd_ebook_upload">
            <label class="mgd-ebook-admin__drop" for="mgd-ebook-files" data-mgd-drop>
                <span class="mgd-ebook-admin__drop-icon" aria-hidden="true">⬆</span>
                <span class="mgd-ebook-admin__drop-title">EPUB-Dateien hierher ziehen</span>
                <span class="mgd-ebook-admin__drop-sub">oder klicken zum Auswählen · mehrere Dateien möglich · höchstens <?php echo esc_html( $max_upload ); ?> pro Datei</span>
                <input id="mgd-ebook-files" type="file" name="mgd_ebook_files[]" accept=".epub,application/epub+zip" multiple required>
            </label>
            <ul class="mgd-ebook-admin__chosen" data-mgd-chosen aria-live="polite" hidden></ul>
            <p><?php submit_button( 'Hochladen', 'primary large', 'submit', false, array( 'data-mgd-submit' => '1' ) ); ?></p>
        </form>
    </section>

    <?php // Schritt 2: Liste als Karten ?>
    <h2 class="mgd-ebook-admin__heading"><span class="mgd-ebook-admin__step">2</span> Link oder Shortcode kopieren</h2>
    <?php if ( ! $query->have_posts() ) : ?>
        <p class="mgd-ebook-admin__empty">Noch kein eBook vorhanden. Lade oben die erste EPUB-Datei hoch.</p>
    <?php else : ?>
        <div class="mgd-ebook-admin__grid">
        <?php foreach ( $query->posts as $post ) :
            $id = (int) $post->ID;
            $book = MGD_Ebook_Library::book( $id );
            $title = MGD_Ebook_Library::title( $id );
            $cover = MGD_Ebook_Library::cover_url( $id );
            $url = MGD_Ebook_Library::file_url( $id );
            $shortcode = '[mgd_ebook id="' . $id . '"]';
            $counted = MGD_Ebook_Downloads::link( $id );
            $downloads = MGD_Ebook_Downloads::count( $id );
            $epub = MGD_Ebook_Library::epub_book( $id );
            $custom = MGD_Ebook_Library::custom_book( $id );
            $show = MGD_Ebook_Library::show( $id );
            $sample = MGD_Ebook_Sample::settings( $id );
            $can_edit = current_user_can( 'edit_post', $id );
            $sale = MGD_Ebook_Shop::sale( $id );
            $paid = 'paid' === $sale['mode'];
            $shop_product = $paid ? MGD_Ebook_Shop::product( $id ) : null;
            ?>
            <article class="mgd-ebook-admin__card" data-mgd-card="<?php echo $id; ?>">
                <div class="mgd-ebook-admin__card-head">
                    <?php if ( $cover ) : ?>
                        <img class="mgd-ebook-admin__cover" data-mgd-cover-img src="<?php echo esc_url( $cover ); ?>" alt="" width="72" loading="lazy">
                    <?php else : ?>
                        <span class="mgd-ebook-admin__cover mgd-ebook-admin__cover--none" data-mgd-cover-img aria-hidden="true"><?php echo esc_html( mb_substr( $title, 0, 1 ) ); ?></span>
                    <?php endif; ?>
                    <div>
                        <h3><?php echo esc_html( $title ); ?></h3>
                        <?php if ( '' !== $book['author'] ) : ?><p class="mgd-ebook-admin__author"><?php echo esc_html( $book['author'] ); ?></p><?php endif; ?>
                        <p class="mgd-ebook-admin__meta"><?php echo esc_html( MGD_Ebook_Library::file_size( $id ) ); ?> · <?php echo esc_html( get_the_date( '', $post ) ); ?><?php echo $cover ? '' : ' · kein Cover in der Datei'; ?></p>
                        <p class="mgd-ebook-admin__downloads"><strong><?php echo (int) $downloads; ?>×</strong> heruntergeladen<?php echo $sample['on'] ? ' · Leseprobe an' : ''; ?><?php echo $paid ? ' · im Verkauf' : ''; ?></p>
                    </div>
                </div>

                <?php if ( $paid ) : ?>
                <p class="mgd-ebook-admin__sale-note">
                    Dieses eBook wird verkauft. Die Datei ist geschützt, nur Käufer können sie laden.
                    <?php if ( $shop_product ) : ?><a href="<?php echo esc_url( (string) get_edit_post_link( $shop_product->get_id() ) ); ?>">Produkt bearbeiten</a> · <a href="<?php echo esc_url( $shop_product->get_permalink() ); ?>" target="_blank" rel="noopener">Produkt ansehen</a><?php else : ?><strong>Das Produkt ist nicht veröffentlicht oder WooCommerce ist aus.</strong><?php endif; ?>
                </p>
                <?php else : ?>
                <div class="mgd-ebook-admin__row">
                    <label for="mgd-ebook-count-<?php echo $id; ?>">Download-Link mit Zähler <small>(empfohlen, z. B. für Bilder und Buttons im Divi Editor)</small></label>
                    <div class="mgd-ebook-admin__copy">
                        <input id="mgd-ebook-count-<?php echo $id; ?>" type="text" readonly value="<?php echo esc_attr( $counted ); ?>">
                        <button type="button" class="button button-primary" data-mgd-copy="mgd-ebook-count-<?php echo $id; ?>">Kopieren</button>
                    </div>
                </div>
                <div class="mgd-ebook-admin__row">
                    <label for="mgd-ebook-url-<?php echo $id; ?>">Direkter Link zur Datei <small>(zählt nicht mit)</small></label>
                    <div class="mgd-ebook-admin__copy">
                        <input id="mgd-ebook-url-<?php echo $id; ?>" type="text" readonly value="<?php echo esc_attr( $url ); ?>">
                        <button type="button" class="button" data-mgd-copy="mgd-ebook-url-<?php echo $id; ?>">Kopieren</button>
                    </div>
                </div>
                <?php endif; ?>
                <div class="mgd-ebook-admin__row">
                    <label for="mgd-ebook-sc-<?php echo $id; ?>">Shortcode <small>(Cover mit Vorschau-Fenster)</small></label>
                    <div class="mgd-ebook-admin__copy">
                        <input id="mgd-ebook-sc-<?php echo $id; ?>" type="text" readonly value="<?php echo esc_attr( $shortcode ); ?>">
                        <button type="button" class="button" data-mgd-copy="mgd-ebook-sc-<?php echo $id; ?>">Kopieren</button>
                    </div>
                </div>

                <?php if ( $can_edit ) : ?>
                <?php // Metadaten, Anzeige + Leseprobe - einklappbar, damit die Karte ruhig bleibt ?>
                <details class="mgd-ebook-admin__more">
                    <summary>Angaben, Anzeige und Leseprobe</summary>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <?php wp_nonce_field( 'mgd_ebook_settings_' . $id ); ?>
                        <input type="hidden" name="action" value="mgd_ebook_settings">
                        <input type="hidden" name="attachment_id" value="<?php echo $id; ?>">
                        <p class="description">Leere Felder nehmen den Wert aus der EPUB-Datei (grau angezeigt).</p>
                        <div class="mgd-ebook-admin__fields">
                            <?php foreach ( MGD_Ebook_Library::FIELDS as $key => $label ) :
                                $field_id = 'mgd-ebook-' . $key . '-' . $id; ?>
                                <p class="mgd-ebook-admin__field mgd-ebook-admin__field--<?php echo esc_attr( $key ); ?>">
                                    <label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
                                    <?php if ( 'description' === $key ) : ?>
                                        <textarea id="<?php echo esc_attr( $field_id ); ?>" name="meta[description]" rows="4" maxlength="1500" placeholder="<?php echo esc_attr( $epub['description'] ); ?>"><?php echo esc_textarea( $custom['description'] ?? '' ); ?></textarea>
                                    <?php else : ?>
                                        <input id="<?php echo esc_attr( $field_id ); ?>" type="text" name="meta[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $custom[ $key ] ?? '' ); ?>" placeholder="<?php echo esc_attr( $epub[ $key ] ); ?>"<?php echo in_array( $key, array( 'year', 'series_index' ), true ) ? ' inputmode="numeric"' : ''; ?>>
                                    <?php endif; ?>
                                </p>
                            <?php endforeach; ?>
                        </div>
                        <fieldset class="mgd-ebook-admin__show">
                            <legend>Im Vorschau-Fenster anzeigen</legend>
                            <?php foreach ( MGD_Ebook_Library::SHOW as $key => $default ) :
                                $label = 'size' === $key ? 'Dateigröße' : MGD_Ebook_Library::FIELDS[ $key ]; ?>
                                <label><input type="checkbox" name="show[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $show[ $key ] ); ?>> <?php echo esc_html( $label ); ?></label>
                            <?php endforeach; ?>
                        </fieldset>
                        <fieldset class="mgd-ebook-admin__sample">
                            <legend>Leseprobe</legend>
                            <label><input type="checkbox" name="sample_on" value="1" <?php checked( $sample['on'] ); ?>> Leseprobe im Vorschau-Fenster zeigen</label>
                            <label>Seiten <input type="number" name="sample_pages" min="1" max="<?php echo (int) MGD_Ebook_Sample::MAX_PAGES; ?>" value="<?php echo (int) $sample['pages']; ?>"></label>
                            <span class="description">Eine reine Cover-Seite am Anfang wird übersprungen.</span>
                        </fieldset>
                        <?php if ( MGD_Ebook_Shop::active() ) : $products = MGD_Ebook_Shop::product_options(); ?>
                        <fieldset class="mgd-ebook-admin__sale">
                            <legend>Verkauf (WooCommerce)</legend>
                            <label><input type="radio" name="sale_mode" value="free" <?php checked( ! $paid ); ?>> Kostenlos zum Herunterladen</label>
                            <label><input type="radio" name="sale_mode" value="paid" <?php checked( $paid ); ?>> Verkaufen</label>
                            <div class="mgd-ebook-admin__sale-fields">
                                <label>Preis in <?php echo esc_html( get_woocommerce_currency_symbol() ); ?> <input type="text" name="sale_price" inputmode="decimal" placeholder="<?php echo esc_attr( $shop_product ? wc_format_localized_price( $shop_product->get_regular_price() ) : '4,99' ); ?>"></label>
                                <label>Produkt
                                    <select name="sale_product">
                                        <option value="0"><?php echo $sale['product'] ? 'Neues Produkt anlegen' : '– neues Produkt anlegen –'; ?></option>
                                        <?php foreach ( $products as $pid => $plabel ) : ?>
                                            <option value="<?php echo (int) $pid; ?>" <?php selected( $sale['product'], $pid ); ?>><?php echo esc_html( $plabel ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            <span class="description">Beim Verkaufen wird die Datei geschützt abgelegt und nur nach dem Kauf über WooCommerce ausgeliefert. Zurück auf „Kostenlos“ macht die Datei wieder öffentlich und setzt das Produkt auf Entwurf. Gelöscht wird nichts.</span>
                        </fieldset>
                        <?php endif; ?>
                        <p><?php submit_button( 'Speichern', 'primary', 'submit', false ); ?></p>
                    </form>
                </details>

                <?php // Neue Version der Datei hochladen, alles andere bleibt ?>
                <details class="mgd-ebook-admin__more">
                    <summary>Datei ersetzen</summary>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
                        <?php wp_nonce_field( 'mgd_ebook_replace_' . $id ); ?>
                        <input type="hidden" name="action" value="mgd_ebook_replace">
                        <input type="hidden" name="attachment_id" value="<?php echo $id; ?>">
                        <p class="description">Lade eine neue Version dieses Buchs hoch. Links, Shortcode, Zähler und deine Angaben bleiben gleich. Das Cover wird aus der neuen Datei gelesen.</p>
                        <p><input type="file" name="mgd_ebook_file" accept=".epub,application/epub+zip" required aria-label="Neue EPUB-Datei für <?php echo esc_attr( $title ); ?>"></p>
                        <p><?php submit_button( 'Datei ersetzen', 'secondary', 'submit', false ); ?></p>
                    </form>
                </details>
                <?php endif; ?>

                <?php $months = MGD_Ebook_Downloads::months( $id ); if ( $months ) : ?>
                <details class="mgd-ebook-admin__more">
                    <summary>Downloads pro Monat</summary>
                    <table class="mgd-ebook-admin__stats">
                        <thead><tr><th scope="col">Monat</th><th scope="col">Downloads</th></tr></thead>
                        <tbody>
                        <?php foreach ( array_slice( $months, 0, 12, true ) as $month => $n ) : ?>
                            <tr><td><?php echo esc_html( wp_date( 'F Y', (int) strtotime( $month . '-01 12:00:00' ) ) ); ?></td><td><?php echo (int) $n; ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </details>
                <?php endif; ?>

                <div class="mgd-ebook-admin__actions">
                    <?php if ( ! $paid ) : ?><a href="<?php echo esc_url( $url ); ?>" download>Datei laden</a><?php else : ?><span></span><?php endif; ?>
                    <button type="button" class="mgd-ebook-admin__link" data-mgd-cover-edit data-book="<?php echo $id; ?>" data-title="<?php echo esc_attr( $title ); ?>" data-cover="<?php echo esc_url( $cover ); ?>">Cover ändern</button>
                    <?php if ( current_user_can( 'delete_post', $id ) ) : ?>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-mgd-confirm="<?php echo esc_attr( '„' . $title . '“ endgültig löschen? Das kann nicht rückgängig gemacht werden. Links und Shortcodes dazu funktionieren danach nicht mehr.' ); ?>">
                            <?php wp_nonce_field( 'mgd_ebook_delete_' . $id ); ?>
                            <input type="hidden" name="action" value="mgd_ebook_delete">
                            <input type="hidden" name="attachment_id" value="<?php echo $id; ?>">
                            <button type="submit" class="mgd-ebook-admin__delete">Löschen</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
        </div>
        <?php if ( $total_pages > 1 ) : ?>
            <div class="tablenav"><div class="tablenav-pages">
                <?php echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%', admin_url( 'admin.php?page=mgd-ebook' ) ), 'format' => '', 'current' => $paged, 'total' => $total_pages ) ) ); ?>
            </div></div>
        <?php endif; ?>
    <?php endif; ?>


    <?php // Mini-Editor fuer das Cover (wird per JavaScript geoeffnet) ?>
    <div class="mgd-ebook-editor" data-mgd-editor role="dialog" aria-modal="true" aria-labelledby="mgd-ebook-editor-title" hidden>
        <div class="mgd-ebook-editor__box">
            <h2 id="mgd-ebook-editor-title">Cover ändern</h2>
            <p class="mgd-ebook-editor__book" data-mgd-editor-book></p>
            <p class="description">Wähle ein Bild aus der Mediathek (oder lade dort ein neues hoch). Verschiebe das Bild mit der Maus oder den Pfeiltasten und stelle mit dem Regler die Größe ein. Der Rahmen zeigt den späteren Ausschnitt.</p>
            <div class="mgd-ebook-editor__stage" data-mgd-stage tabindex="0" aria-label="Ausschnitt. Pfeiltasten verschieben das Bild, Plus und Minus ändern die Größe.">
                <img alt="" data-mgd-editor-img draggable="false" hidden>
                <p class="mgd-ebook-editor__empty" data-mgd-editor-empty>Noch kein Bild gewählt</p>
            </div>
            <p class="mgd-ebook-editor__zoom"><label for="mgd-ebook-zoom">Größe</label> <input id="mgd-ebook-zoom" type="range" min="1" max="4" step="0.01" value="1" data-mgd-zoom disabled></p>
            <p class="mgd-ebook-editor__tools">
                <button type="button" class="button" data-mgd-pick>Bild aus Mediathek wählen …</button>
                <button type="button" class="button-link" data-mgd-restore>Original aus der EPUB wiederherstellen</button>
            </p>
            <p class="mgd-ebook-editor__status" role="status" data-mgd-status></p>
            <p class="mgd-ebook-editor__footer">
                <button type="button" class="button button-primary" data-mgd-save disabled>Cover speichern</button>
                <button type="button" class="button" data-mgd-cancel>Abbrechen</button>
            </p>
        </div>
    </div>

    <?php // Hilfe einklappbar, damit die Seite ruhig bleibt ?>
    <details class="mgd-ebook-admin__help">
        <summary>So benutzt du es</summary>
        <ol>
            <li><strong>Nur verlinken:</strong> „Download-Link mit Zähler“ kopieren und im Divi Editor bei einem Bild, Button oder Text als Link eintragen. Jeder Download wird gezählt (ohne IP-Adressen, ohne Cookies).</li>
            <li><strong>Mit Cover und Vorschau:</strong> Shortcode kopieren und in ein Text-Modul einfügen. Besucher sehen das Cover, ein Klick öffnet ein Fenster mit Infos und Download-Button.</li>
            <li><strong>Bücherregal:</strong> Mehrere Bücher nebeneinander mit <code>[mgd_ebook_regal ids="12,15,9"]</code> (in dieser Reihenfolge) oder alle Bücher einer Reihe mit <code>[mgd_ebook_regal reihe="Name der Reihe"]</code> (sortiert nach Band). Die Reihe trägst du unter „Angaben, Anzeige und Leseprobe“ ein.</li>
            <li><strong>Feinschliff:</strong> <code>width="260"</code> ändert die Cover-Breite, <code>caption="no"</code> blendet den Titel aus, <code>button="Jetzt laden"</code> ändert den Button-Text. Beispiel: <code>[mgd_ebook id="12" width="260" button="Jetzt laden"]</code></li>
        </ol>
    </details>
</div>
