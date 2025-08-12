<?php
/**
 * Widget template with 4 cards in a row
 *
 * @package blok45
 * @since 1.0
 */
?>

<div class="frame-quatro__item">
    <div class="frame-quatro__head">
        <?php
        blok45_theme_info(
            'tag',
            '<div class="frame-quatro__tags meta">',
            '</div>'
        );

        printf( '<hr>' );

        blok45_theme_info(
            'authors',
            '<div class="frame-quatro__authors meta">',
            '</div>'
        );
        ?>
    </div>

    <div class="frame-quatro__content">
        <?php
        printf(
            '<a class="frame-quatro__title" href="%s">%s</a>',
            esc_url( get_permalink() ),
            esc_html( get_the_title() )
        );

        blok45_theme_info(
            'excerpt',
            '<div class="frame-quatro__excerpt">',
            '</div>'
        );
        ?>
    </div>
</div>
