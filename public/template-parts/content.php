<?php
/**
 * Standart post format content
 *
 * @package blok45
 * @since 1.0
 */
?>

<article <?php post_class( 'post' ); ?> id="post-<?php the_ID(); ?>">
    <div class="entry-header">
        <?php if ( has_post_thumbnail() ) : ?>
            <figure class="entry-header__thumb">
                <?php
                the_post_thumbnail(
                    'single',
                    array(
                        'class'   => 'post__thumbnail',
                        'loading' => 'eager',
                        'decoding'=> 'async',
                    )
                );

                blok45_theme_info(
                    'caption',
                    '<figcaption class="post__caption">',
                    '</figcaption>'
                );
                ?>
            </figure>
        <?php endif; ?>
        <div class="entry-header__meta">
            <?php
            blok45_theme_info(
                'date',
                '<div class="entry-header__date meta">',
                '</div>'
            );

            blok45_theme_info(
                'tag',
                '<div class="entry-header__tags meta">',
                '</div>'
            );

            printf( '<hr>' );

            blok45_theme_info(
                'authors',
                '<div class="entry-header__authors meta">',
                '</div>'
            );
            ?>
        </div>

        <?php
        blok45_theme_info(
            'title',
            '<h1 class="entry-header__title">',
            '</h1>'
        );

        blok45_theme_info(
            'excerpt',
            '<div class="entry-header__excerpt">',
            '</div>'
        );

        blok45_theme_info(
            'lead',
            '<div class="entry-header__lead">',
            '</div>'
        );

        blok45_theme_info(
            'reactions',
            '<div class="entry-header__reactions">',
            '</div>'
        );
        ?>
    </div>

    <div class="entry-content">
        <?php the_content(); ?>
    </div>

    <section class="frame-sharing">
        <div class="frame-sharing__wrapper">
            <div class="frame-sharing__caption">
                <?php esc_html_e( 'Поделиться', 'blok45' ); ?>
            </div>

            <div class="frame-sharing__buttons sharing">
                <?php
                $url   = urlencode( get_permalink() );
                $title = urlencode( get_the_title() );

                printf(
                    '<a class="sharing__link" href="https://t.me/share/url?url=%1$s&text=%2$s" target="_blank" rel="noopener">Telegram</a>',
                    esc_attr( $url ),
                    esc_attr( $title )
                );

                printf(
                    '<a class="sharing__link" href="https://twitter.com/intent/tweet?url=%1$s&text=%2$s" target="_blank" rel="noopener">X/Twitter</a>',
                    esc_attr( $url ),
                    esc_attr( $title )
                );

                printf(
                    '<a class="sharing__link" href="%1$s">%2$s</a>',
                    esc_url( get_permalink() ),
                    esc_html__( 'Ссылка', 'blok45' )
                );
                ?>
            </div>
        </div>
    </section>
</article>
