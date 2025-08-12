<?php
/**
 * Widget template with 3 cards in a row
 *
 * @package blok45
 * @since 1.0
 */
?>

<div class="frame-triple__item">
    <div class="frame-triple__head">
        <?php
        blok45_theme_info(
            'tag',
            '<div class="frame-triple__tags meta">',
            '</div>'
        );

        printf( '<hr>' );

        blok45_theme_info(
            'authors',
            '<div class="frame-triple__authors meta">',
            '</div>'
        );
        ?>
    </div>

    <figure class="frame-triple__image">
        <?php
        the_post_thumbnail(
            'card',
            array(
                'class'   => 'frame-triple__image-thumbnail',
                'loading' => 'lazy',
            )
        );
        ?>
    </figure>

    <div class="frame-triple__content">
        <?php
        printf(
            '<a class="frame-triple__title" href="%s">%s</a>',
            esc_url( get_permalink() ),
            esc_html( get_the_title() )
        );

        blok45_theme_info(
            'excerpt',
            '<div class="frame-triple__excerpt">',
            '</div>'
        );
        ?>
    </div>
</div>