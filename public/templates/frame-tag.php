<?php
/**
 * Widget template for posts from single tag
 *
 * @package blok45
 * @since 1.0
 */
?>

<div class="frame-tag__wrapper">
    <div class="frame-tag__heading">
        <?php
        if ( ! empty( $args['term']->name ) ) :
            printf(
                '<p class="frame-tag__caption">%s %s</p>',
                esc_html( $args['term']->name ),
                esc_html( $args['emoji'] )
            );
        endif;
        ?>

        <div class="frame-tag__hero">
            <?php
            if ( ! empty( $args['term']->description ) ) :
                printf(
                    '<span class="frame-tag__description">%s</span>',
                    esc_html( $args['term']->description )
                );
            endif;

            printf(
                '<a class="frame-tag__hero-transfer" href="%s">%s</a>',
                esc_url( get_tag_link( $args['term']->term_id ) ),
                esc_html__( 'Все статьи по теме', 'blok45' )
            )
            ?>
        </div>
    </div>

    <div class="frame-tag__grid">
        <?php while ( $args['query']->have_posts() ) : ?>
            <?php $args['query']->the_post(); ?>

            <div class="frame-tag__item">
                <div class="frame-tag__head">
                    <?php
                    blok45_theme_info(
                        'tag',
                        '<div class="frame-tag__tags meta">',
                        '</div>'
                    );

                    printf( '<hr>' );

                    blok45_theme_info(
                        'authors',
                        '<div class="frame-tag__authors meta">',
                        '</div>'
                    );
                    ?>
                </div>

                <div class="frame-tag__content">
                    <?php
                    printf(
                        '<a class="frame-tag__title" href="%s">%s</a>',
                        esc_url( get_permalink() ),
                        esc_html( get_the_title() )
                    );
                    ?>
                </div>
            </div>
        <?php endwhile; ?>
        <?php wp_reset_postdata(); ?>
    </div>

    <?php if ( ! empty( $args['more'] ) ) : ?>
        <div class="frame-tag__more">
            <?php
            printf(
                '<span class="frame-tag__more-title">%s</span>',
                esc_html__( 'А также: ', 'blok45' )
            );
            ?>

            <?php foreach ( $args['more'] as $entry ) : ?>
                <span class="frame-tag__reference">
                    <?php
                    printf(
                        '<a class="frame-tag__reference-link" href="%s">%s</a>',
                        esc_url( get_permalink( $entry ) ),
                        esc_html( get_the_title( $entry ) )
                    );
                    ?>
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
