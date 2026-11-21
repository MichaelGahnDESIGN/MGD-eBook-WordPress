<?php
defined( 'ABSPATH' ) || exit;
/** @var array $data */
$label = 'Vorschau öffnen: ' . $data['title'];
?>
<div class="mgd-ebook" style="--mgd-ebook-width:<?php echo (int) $data['width']; ?>px">
    <?php // Ohne JavaScript ist das Cover einfach ein Download-Link ?>
    <a class="mgd-ebook__cover" href="<?php echo esc_url( $data['url'] ); ?>" data-mgd-ebook-open="<?php echo esc_attr( $data['uid'] ); ?>" aria-haspopup="dialog" aria-label="<?php echo esc_attr( $label ); ?>">
        <?php if ( $data['cover'] ) : ?>
            <img src="<?php echo esc_url( $data['cover'] ); ?>" alt="Cover: <?php echo esc_attr( $data['title'] ); ?>" loading="lazy" decoding="async">
        <?php else : ?>
            <span class="mgd-ebook__placeholder" aria-hidden="true"><span><?php echo esc_html( $data['title'] ); ?></span></span>
        <?php endif; ?>
    </a>
    <?php if ( $data['caption'] ) : ?>
        <p class="mgd-ebook__caption"><strong><?php echo esc_html( $data['title'] ); ?></strong><?php if ( '' !== $data['author'] ) : ?><span><?php echo esc_html( $data['author'] ); ?></span><?php endif; ?></p>
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
                <?php if ( '' !== $data['author'] ) : ?><p class="mgd-ebook__author"><?php echo esc_html( $data['author'] ); ?></p><?php endif; ?>
                <?php if ( '' !== $data['description'] ) : ?><p class="mgd-ebook__description"><?php echo esc_html( $data['description'] ); ?></p><?php endif; ?>
                <p class="mgd-ebook__action">
                    <a class="mgd-ebook__download" href="<?php echo esc_url( $data['url'] ); ?>" download><?php echo esc_html( $data['button'] ); ?></a>
                    <span class="mgd-ebook__meta">EPUB<?php echo '' !== $data['size'] ? ' · ' . esc_html( $data['size'] ) : ''; ?></span>
                </p>
            </div>
        </div>
    </dialog>
</div>
