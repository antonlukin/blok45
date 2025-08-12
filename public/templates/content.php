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
</article>
