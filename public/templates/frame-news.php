<?php
/**
 * Widget template for kiosk posts
 *
 * @package blok45
 * @since 1.0
 */
?>

<div class="frame-news__wrapper">
    <div class="frame-news__heading">
        <?php
        if ( ! empty( $args['title'] ) ) :
            printf(
                '<p class="frame-news__caption">%s</p>',
                esc_html( $args['title'] )
            );
        endif;

        if ( ! empty( $args['archive'] ) ) :
            printf(
                '<a class="frame-news__more" href="%s">%s</a>',
                esc_url( $args['archive'] ),
                esc_html__( 'Читать все новости улья', 'blok45' )
            );
        endif;
        ?>
    </div>

    <div class="frame-news__items">
        <?php while ( $args['query']->have_posts() ) : ?>
            <?php $args['query']->the_post(); ?>

            <div class="frame-news__content">
                <?php
                blok45_theme_info(
                    'date',
                    '<div class="frame-news__date">',
                    '</div>'
                );
                ?>

                <div class="frame-news__transfer">
                    <?php
                    printf(
                        '<a class="frame-news__link" href="%s">%s</a>',
                        esc_url( get_permalink() ),
                        esc_html( get_the_title() )
                    );

                    blok45_theme_info(
                        'source',
                        '<div class="frame-news__source meta">',
                        '</div>'
                    );
                    ?>
                </div>
            </div>
        <?php endwhile; ?>
        <?php wp_reset_postdata(); ?>
    </div>
</div>
