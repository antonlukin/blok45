<?php
/**
 * Widget template for television post
 *
 * @package blok45
 * @since 1.0
 */
?>

<div class="frame-television__wrapper">
    <figure class="frame-television__image">
        <?php
        the_post_thumbnail(
            'full',
            array(
                'class'   => 'frame-television__image-thumbnail',
                'loading' => 'lazy',
            )
        );
        ?>
    </figure>

    <div class="frame-television__content">
        <?php
        printf(
            '<a class="frame-television__title" href="%s">%s</a>',
            esc_url( get_permalink() ),
            esc_html( get_the_title() )
        );

        blok45_theme_info(
            'excerpt',
            '<div class="frame-television__excerpt">',
            '</div>'
        );
        ?>
    </div>
</div>
