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
            ?>
            <article class="mgd-ebook-admin__card">
                <div class="mgd-ebook-admin__card-head">
                    <?php if ( $cover ) : ?>
                        <img class="mgd-ebook-admin__cover" src="<?php echo esc_url( $cover ); ?>" alt="" width="72" loading="lazy">
                    <?php else : ?>
                        <span class="mgd-ebook-admin__cover mgd-ebook-admin__cover--none" aria-hidden="true"><?php echo esc_html( mb_substr( $title, 0, 1 ) ); ?></span>
                    <?php endif; ?>
                    <div>
                        <h3><?php echo esc_html( $title ); ?></h3>
                        <?php if ( '' !== $book['author'] ) : ?><p class="mgd-ebook-admin__author"><?php echo esc_html( $book['author'] ); ?></p><?php endif; ?>
                        <p class="mgd-ebook-admin__meta"><?php echo esc_html( MGD_Ebook_Library::file_size( $id ) ); ?> · <?php echo esc_html( get_the_date( '', $post ) ); ?><?php echo $cover ? '' : ' · kein Cover in der Datei'; ?></p>
                    </div>
                </div>

                <div class="mgd-ebook-admin__row">
                    <label for="mgd-ebook-url-<?php echo $id; ?>">Link zur Datei <small>(zum Verlinken, z. B. im Divi Editor)</small></label>
                    <div class="mgd-ebook-admin__copy">
                        <input id="mgd-ebook-url-<?php echo $id; ?>" type="text" readonly value="<?php echo esc_attr( $url ); ?>">
                        <button type="button" class="button" data-mgd-copy="mgd-ebook-url-<?php echo $id; ?>">Kopieren</button>
                    </div>
                </div>
                <div class="mgd-ebook-admin__row">
                    <label for="mgd-ebook-sc-<?php echo $id; ?>">Shortcode <small>(Cover mit Vorschau-Fenster)</small></label>
                    <div class="mgd-ebook-admin__copy">
                        <input id="mgd-ebook-sc-<?php echo $id; ?>" type="text" readonly value="<?php echo esc_attr( $shortcode ); ?>">
                        <button type="button" class="button button-primary" data-mgd-copy="mgd-ebook-sc-<?php echo $id; ?>">Kopieren</button>
                    </div>
                </div>

                <div class="mgd-ebook-admin__actions">
                    <a href="<?php echo esc_url( $url ); ?>" download>Datei laden</a>
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

    <?php // Hilfe einklappbar, damit die Seite ruhig bleibt ?>
    <details class="mgd-ebook-admin__help">
        <summary>So benutzt du es</summary>
        <ol>
            <li><strong>Nur verlinken:</strong> „Link zur Datei“ kopieren und im Divi Editor bei einem Button oder Text als Link eintragen.</li>
            <li><strong>Mit Cover und Vorschau:</strong> Shortcode kopieren und in ein Text-Modul einfügen. Besucher sehen das Cover, ein Klick öffnet ein Fenster mit Infos und Download-Button.</li>
            <li><strong>Feinschliff:</strong> <code>width="260"</code> ändert die Cover-Breite, <code>caption="no"</code> blendet den Titel aus, <code>button="Jetzt laden"</code> ändert den Button-Text. Beispiel: <code>[mgd_ebook id="12" width="260" button="Jetzt laden"]</code></li>
        </ol>
    </details>
</div>
