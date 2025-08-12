<?php
/**
 * Widget template list items
 *
 * @package blok45
 * @since 1.0
 */
?>

<div class="frame-list__item">
    <figure class="frame-list__image">
        <?php
        the_post_thumbnail(
            'card',
            array(
                'class'   => 'frame-list__image-thumbnail',
                'loading' => 'lazy',
            )
        );
        ?>
    </figure>

    <div class="frame-list__content">
        <div class="frame-list__head">
            <?php
            blok45_theme_info(
                'date',
                '<div class="frame-list__date meta">',
                '</div>'
            );

            blok45_theme_info(
                'tag',
                '<div class="frame-list__tags meta">',
                '</div>'
            );

            blok45_theme_info(
                'source',
                '<div class="frame-list__source meta">',
                '</div>'
            );
            ?>
        </div>

        <?php
        printf(
            '<a class="frame-list__title" href="%s">%s</a>',
            esc_url( get_permalink() ),
            esc_html( get_the_title() )
        );
        ?>
    </div>
</div>