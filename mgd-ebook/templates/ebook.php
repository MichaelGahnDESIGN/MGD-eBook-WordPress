<?php
defined( 'ABSPATH' ) || exit;
/** @var array $data */
$label = 'Vorschau öffnen: ' . $data['title'];
$book = $data['book'];
$show = $data['show'];
$author = $show['author'] ? $book['author'] : '';
?>
<div class="mgd-ebook" style="--mgd-ebook-width:<?php echo (int) $data['width']; ?>px">
    <?php // Ohne JavaScript ist das Cover einfach ein Download-Link ?>
    <a class="mgd-ebook__cover" href="<?php echo esc_url( '' !== $data['url'] ? $data['url'] : '#' . $data['uid'] ); ?>" data-mgd-ebook-open="<?php echo esc_attr( $data['uid'] ); ?>" aria-haspopup="dialog" aria-label="<?php echo esc_attr( $label ); ?>">
        <?php if ( $data['cover'] ) : ?>
            <img src="<?php echo esc_url( $data['cover'] ); ?>" alt="Cover: <?php echo esc_attr( $data['title'] ); ?>" loading="lazy" decoding="async">
        <?php else : ?>
            <span class="mgd-ebook__placeholder" aria-hidden="true"><span><?php echo esc_html( $data['title'] ); ?></span></span>
        <?php endif; ?>
    </a>
    <?php if ( $data['caption'] ) : ?>
        <p class="mgd-ebook__caption"><strong><?php echo esc_html( $data['title'] ); ?></strong><?php if ( '' !== $author ) : ?><span><?php echo esc_html( $author ); ?></span><?php endif; ?></p>
    <?php endif; ?>

    <dialog class="mgd-ebook__dialog" id="<?php echo esc_attr( $data['uid'] ); ?>" aria-labelledby="<?php echo esc_attr( $data['uid'] ); ?>-title">
        <button type="button" class="mgd-ebook__close" data-mgd-ebook-close aria-label="Fenster schließen">&times;</button>
        <div class="mgd-ebook__panel">
            <div class="mgd-ebook__preview">
                <?php if ( $data['cover'] ) : ?>
                    <img src="<?php echo esc_url( $data['cover'] ); ?>" alt="Cover: <?php echo esc_attr( $data['title'] ); ?>" loading="lazy" decoding="async">
                <?php else : ?>
                    <span class="mgd-ebook__placeholder" aria-hidden="true"><span><?php echo esc_html( $data['title'] ); ?></span></span>
                <?php endif; ?>
            </div>
            <div class="mgd-ebook__info">
                <h2 id="<?php echo esc_attr( $data['uid'] ); ?>-title"><?php echo esc_html( $data['title'] ); ?></h2>
                <?php if ( '' !== $author ) : ?><p class="mgd-ebook__author"><?php echo esc_html( $author ); ?></p><?php endif; ?>
                <?php if ( $show['description'] && '' !== $book['description'] ) : ?><p class="mgd-ebook__description"><?php echo nl2br( esc_html( $book['description'] ) ); ?></p><?php endif; ?>
                <?php if ( $data['details'] ) : ?>
                    <dl class="mgd-ebook__details">
                        <?php foreach ( $data['details'] as $row ) : ?>
                            <div><dt><?php echo esc_html( $row[0] ); ?></dt><dd><?php echo esc_html( $row[1] ); ?></dd></div>
                        <?php endforeach; ?>
                    </dl>
                <?php endif; ?>
                <p class="mgd-ebook__action">
                    <?php if ( '' !== $data['action'] ) : ?>
                        <?php echo wp_kses_post( $data['action'] ); ?>
                    <?php elseif ( '' !== $data['url'] ) : ?>
                        <a class="mgd-ebook__download" href="<?php echo esc_url( $data['url'] ); ?>" download><?php echo esc_html( $data['button'] ); ?></a>
                    <?php endif; ?>
                    <span class="mgd-ebook__meta">EPUB<?php echo '' !== $data['size'] ? ' · ' . esc_html( $data['size'] ) : ''; ?></span>
                </p>
            </div>
        </div>
        <?php // Leseprobe: Text + Bilder aus den ersten Seiten, einklappbar ?>
        <?php if ( $data['sample'] ) : ?>
            <details class="mgd-ebook__sample">
                <summary>Leseprobe lesen</summary>
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
                    </section>
                <?php endforeach; ?>
            </details>
        <?php endif; ?>
    </dialog>
</div>
