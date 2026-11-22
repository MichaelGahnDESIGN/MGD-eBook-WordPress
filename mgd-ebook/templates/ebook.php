<?php
defined( 'ABSPATH' ) || exit;
/** @var array $data */
$label = 'Vorschau öffnen: ' . $data['title'];
$book = $data['book'];
$show = $data['show'];
$author = $show['author'] ? $book['author'] : '';
// Reihe als kleine Zeile über dem Titel, Rest in die Angaben-Liste
$series = $show['series'] && '' !== $book['series'] ? $book['series'] . ( '' !== $book['series_index'] ? ' · Band ' . $book['series_index'] : '' ) : '';
$details = array_values( array_filter( $data['details'], static fn( array $row ): bool => 'Reihe' !== $row[0] ) );
if ( '' !== $data['size'] ) { $details[] = array( 'Format', 'EPUB · ' . $data['size'] ); }
?>
<div class="mgd-ebook" style="--mgd-ebook-width:<?php echo (int) $data['width']; ?>px">
    <div class="mgd-ebook__stage">
        <?php // Ohne JavaScript ist das Cover einfach ein Download-Link ?>
        <a class="mgd-ebook__cover" href="<?php echo esc_url( '' !== $data['url'] ? $data['url'] : '#' . $data['uid'] ); ?>" data-mgd-ebook-open="<?php echo esc_attr( $data['uid'] ); ?>" aria-haspopup="dialog" aria-label="<?php echo esc_attr( $label ); ?>">
            <?php if ( $data['cover'] ) : ?>
                <img src="<?php echo esc_url( $data['cover'] ); ?>" alt="Cover: <?php echo esc_attr( $data['title'] ); ?>" loading="lazy" decoding="async">
            <?php else : ?>
                <span class="mgd-ebook__placeholder" aria-hidden="true"><span><?php echo esc_html( $data['title'] ); ?></span></span>
            <?php endif; ?>
        </a>
    </div>
    <?php if ( $data['caption'] ) : ?>
        <p class="mgd-ebook__caption"><strong><?php echo esc_html( $data['title'] ); ?></strong><?php if ( '' !== $author ) : ?><span><?php echo esc_html( $author ); ?></span><?php endif; ?></p>
    <?php endif; ?>

    <dialog class="mgd-ebook__dialog" id="<?php echo esc_attr( $data['uid'] ); ?>" aria-labelledby="<?php echo esc_attr( $data['uid'] ); ?>-title">
        <button type="button" class="mgd-ebook__close" data-mgd-ebook-close aria-label="Fenster schließen"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
        <div class="mgd-ebook__panel">
            <div class="mgd-ebook__preview">
                <?php if ( $data['cover'] ) : ?>
                    <img src="<?php echo esc_url( $data['cover'] ); ?>" alt="Cover: <?php echo esc_attr( $data['title'] ); ?>" loading="lazy" decoding="async">
                <?php else : ?>
                    <span class="mgd-ebook__placeholder" aria-hidden="true"><span><?php echo esc_html( $data['title'] ); ?></span></span>
                <?php endif; ?>
            </div>
            <div class="mgd-ebook__info">
                <?php if ( '' !== $series ) : ?><p class="mgd-ebook__series"><?php echo esc_html( $series ); ?></p><?php endif; ?>
                <h2 id="<?php echo esc_attr( $data['uid'] ); ?>-title"><?php echo esc_html( $data['title'] ); ?></h2>
                <?php if ( '' !== $author ) : ?><p class="mgd-ebook__author"><?php echo esc_html( $author ); ?></p><?php endif; ?>
                <?php if ( $show['description'] && '' !== $book['description'] ) : ?><p class="mgd-ebook__description"><?php echo nl2br( esc_html( $book['description'] ) ); ?></p><?php endif; ?>
                <?php if ( $details ) : ?>
                    <dl class="mgd-ebook__details">
                        <?php foreach ( $details as $row ) : ?>
                            <div><dt><?php echo esc_html( $row[0] ); ?></dt><dd><?php echo esc_html( $row[1] ); ?></dd></div>
                        <?php endforeach; ?>
                    </dl>
                <?php endif; ?>
                <div class="mgd-ebook__action">
                    <?php if ( '' !== $data['action'] ) : ?>
                        <?php echo wp_kses_post( $data['action'] ); ?>
                    <?php elseif ( '' !== $data['url'] ) : ?>
                        <a class="mgd-ebook__download" href="<?php echo esc_url( $data['url'] ); ?>" download><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M12 4v11m0 0l-4.5-4.5M12 15l4.5-4.5M5 19h14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><span><?php echo esc_html( $data['button'] ); ?></span></a>
                    <?php endif; ?>
                    <?php if ( $data['sample'] ) : ?>
                        <button type="button" class="mgd-ebook__sample-toggle" data-mgd-ebook-sample aria-expanded="false" aria-controls="<?php echo esc_attr( $data['uid'] ); ?>-sample"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M12 6.5C10.2 5 7.6 4.5 4 4.5v13c3.6 0 6.2.5 8 2 1.8-1.5 4.4-2 8-2v-13c-3.6 0-6.2.5-8 2zm0 0v13" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg><span>Leseprobe lesen</span></button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php // Leseprobe: Text + Bilder aus den ersten Seiten, öffnet per Knopf ?>
        <?php if ( $data['sample'] ) : ?>
            <div class="mgd-ebook__sample" id="<?php echo esc_attr( $data['uid'] ); ?>-sample" hidden>
                <p class="mgd-ebook__sample-head">Leseprobe · <?php echo esc_html( $data['title'] ); ?></p>
                <?php foreach ( $data['sample'] as $i => $page ) : ?>
                    <section class="mgd-ebook__page" aria-label="<?php echo esc_attr( 'Seite ' . ( $i + 1 ) . ' der Leseprobe' ); ?>">
                        <?php foreach ( $page as $block ) :
                            if ( 'img' === ( $block['type'] ?? '' ) ) :
                                $src = MGD_Ebook_Sample::url( (string) ( $block['src'] ?? '' ) );
                                if ( '' !== $src ) : ?>
                                    <img src="<?php echo esc_url( $src ); ?>" alt="" loading="lazy" decoding="async">
                                <?php endif;
                            elseif ( 'h' === ( $block['type'] ?? '' ) ) : ?>
                                <h3><?php echo esc_html( (string) ( $block['text'] ?? '' ) ); ?></h3>
                            <?php else : ?>
                                <p><?php echo esc_html( (string) ( $block['text'] ?? '' ) ); ?></p>
                            <?php endif;
                        endforeach; ?>
                        <p class="mgd-ebook__page-no" aria-hidden="true"><?php echo (int) ( $i + 1 ); ?></p>
                    </section>
                <?php endforeach; ?>
                <p class="mgd-ebook__sample-end">Ende der Leseprobe<?php echo '' !== $data['url'] ? ' · ' : ''; ?><?php if ( '' !== $data['url'] ) : ?><a href="<?php echo esc_url( $data['url'] ); ?>" download><?php echo esc_html( $data['button'] ); ?></a><?php endif; ?></p>
            </div>
        <?php endif; ?>
    </dialog>
</div>
