<?php
defined( 'ABSPATH' ) || exit;
/** @var WP_Query $query */
/** @var array $messages */
/** @var string $max_upload */
/** @var int $paged */
$total_pages = (int) $query->max_num_pages;
?>
<div class="wrap mgd-ebook-admin">
    <h1>eBook</h1>
    <p class="mgd-ebook-admin__intro">Hier lädst du EPUB-Dateien hoch. Zu jeder Datei bekommst du einen Download-Link (zum Verlinken, z. B. im Divi Editor) und einen Shortcode (zeigt das Cover mit Vorschau-Fenster).</p>

    <?php foreach ( $messages as $message ) : ?>
        <div class="notice notice-<?php echo esc_attr( $message[0] ); ?> is-dismissible"><p><?php echo esc_html( $message[1] ); ?></p></div>
    <?php endforeach; ?>

    <section class="mgd-ebook-admin__box" aria-labelledby="mgd-ebook-upload-title">
        <h2 id="mgd-ebook-upload-title">EPUB hochladen</h2>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
            <?php wp_nonce_field( 'mgd_ebook_upload' ); ?>
            <input type="hidden" name="action" value="mgd_ebook_upload">
            <p>
                <label for="mgd-ebook-files" class="screen-reader-text">EPUB-Dateien auswählen</label>
                <input id="mgd-ebook-files" type="file" name="mgd_ebook_files[]" accept=".epub,application/epub+zip" multiple required>
            </p>
            <p class="description">Du kannst mehrere Dateien auf einmal auswählen. Erlaubt sind nur .epub-Dateien, höchstens <?php echo esc_html( $max_upload ); ?> pro Datei.</p>
            <?php submit_button( 'Hochladen', 'primary', 'submit', false ); ?>
        </form>
    </section>

    <h2>Hochgeladene eBooks</h2>
    <?php if ( ! $query->have_posts() ) : ?>
        <p>Noch kein eBook vorhanden. Lade oben die erste EPUB-Datei hoch.</p>
    <?php else : ?>
        <table class="widefat striped mgd-ebook-admin__table">
            <thead>
                <tr>
                    <th scope="col">Cover</th>
                    <th scope="col">Titel</th>
                    <th scope="col">Link zur Datei</th>
                    <th scope="col">Shortcode</th>
                    <th scope="col">Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $query->posts as $post ) :
                $id = (int) $post->ID;
                $book = MGD_Ebook_Library::book( $id );
                $title = MGD_Ebook_Library::title( $id );
                $cover = MGD_Ebook_Library::cover_url( $id );
                $url = MGD_Ebook_Library::file_url( $id );
                $shortcode = '[mgd_ebook id="' . $id . '"]';
                ?>
                <tr>
                    <td class="mgd-ebook-admin__cover">
                        <?php if ( $cover ) : ?>
                            <img src="<?php echo esc_url( $cover ); ?>" alt="" width="56" loading="lazy">
                        <?php else : ?>
                            <span class="mgd-ebook-admin__nocover">Kein Cover</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo esc_html( $title ); ?></strong>
                        <?php if ( '' !== $book['author'] ) : ?><br><?php echo esc_html( $book['author'] ); ?><?php endif; ?>
                        <br><span class="description"><?php echo esc_html( basename( (string) get_attached_file( $id ) ) ); ?> · <?php echo esc_html( MGD_Ebook_Library::file_size( $id ) ); ?> · <?php echo esc_html( get_the_date( '', $post ) ); ?></span>
                    </td>
                    <td>
                        <label class="screen-reader-text" for="mgd-ebook-url-<?php echo $id; ?>">Link zu <?php echo esc_attr( $title ); ?></label>
                        <input id="mgd-ebook-url-<?php echo $id; ?>" class="mgd-ebook-admin__field" type="text" readonly value="<?php echo esc_attr( $url ); ?>">
                        <button type="button" class="button" data-mgd-copy="mgd-ebook-url-<?php echo $id; ?>">Link kopieren</button>
                    </td>
                    <td>
                        <label class="screen-reader-text" for="mgd-ebook-sc-<?php echo $id; ?>">Shortcode für <?php echo esc_attr( $title ); ?></label>
                        <input id="mgd-ebook-sc-<?php echo $id; ?>" class="mgd-ebook-admin__field" type="text" readonly value="<?php echo esc_attr( $shortcode ); ?>">
                        <button type="button" class="button" data-mgd-copy="mgd-ebook-sc-<?php echo $id; ?>">Shortcode kopieren</button>
                    </td>
                    <td>
                        <a class="button" href="<?php echo esc_url( $url ); ?>" download>Datei laden</a>
                        <?php if ( current_user_can( 'delete_post', $id ) ) : ?>
                            <form class="mgd-ebook-admin__delete" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-mgd-confirm="<?php echo esc_attr( '„' . $title . '“ endgültig löschen? Das kann nicht rückgängig gemacht werden. Links und Shortcodes dazu funktionieren danach nicht mehr.' ); ?>">
                                <?php wp_nonce_field( 'mgd_ebook_delete_' . $id ); ?>
                                <input type="hidden" name="action" value="mgd_ebook_delete">
                                <input type="hidden" name="attachment_id" value="<?php echo $id; ?>">
                                <button type="submit" class="button button-link-delete">Löschen</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ( $total_pages > 1 ) : ?>
            <div class="tablenav"><div class="tablenav-pages">
                <?php echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%', admin_url( 'admin.php?page=mgd-ebook' ) ), 'format' => '', 'current' => $paged, 'total' => $total_pages ) ) ); ?>
            </div></div>
        <?php endif; ?>
    <?php endif; ?>

    <h2>So benutzt du es</h2>
    <ol class="mgd-ebook-admin__help">
        <li><strong>Nur verlinken:</strong> Kopiere den „Link zur Datei“ und trage ihn im Divi Editor bei einem Button oder Text als Link ein.</li>
        <li><strong>Mit Cover und Vorschau:</strong> Kopiere den Shortcode und füge ihn in ein Text-Modul (oder Code-Modul) ein. Besucher sehen das Cover, ein Klick öffnet ein Fenster mit Infos und Download-Button.</li>
        <li>Optional für den Shortcode: <code>width="260"</code> ändert die Cover-Breite in Pixel, <code>caption="no"</code> blendet den Titel unter dem Cover aus, <code>button="Jetzt laden"</code> ändert den Text des Download-Buttons.</li>
    </ol>
</div>
