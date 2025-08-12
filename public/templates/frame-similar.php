<?php
/**
 * Show similar related block for media with thumbnail
 *
 * @package blok45
 * @since 1.0
 */
?>

<div class="frame-similar__wrapper">
    <?php
    if ( ! empty( $args['title'] ) ) :
        printf( '<span class="frame-similar__caption">%s </span>', esc_html( $args['title'] ) );
    endif;
    ?>

    <div class="frame-similar__content">
        <?php if ( has_post_thumbnail() ) : ?>
            <figure class="frame-similar__image">
                <?php
                the_post_thumbnail(
                    'card',
                    array(
                        'class'   => 'frame-similar__image-thumbnail',
                        'loading' => 'lazy',
                    )
                );
                ?>
            </figure>
        <?php endif; ?>

        <div class="frame-similar__inner">
            <?php
            printf(
                '<a class="frame-similar__title" href="%s" target="_blank" rel="noopener">%s</a>',
                esc_url( get_permalink() ),
                esc_html( get_the_title() )
            );

            blok45_theme_info(
                'excerpt',
                '<div class="frame-similar__excerpt">',
                '</div>'
            );
            ?>
        </div>
    </div>
</div>
