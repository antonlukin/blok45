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
        <?php
        blok45_theme_info(
            'title',
            '<h1 class="entry-header__title">',
            '</h1>'
        );
        ?>
    </div>

    <div class="entry-content">
        <?php the_content(); ?>
    </div>
</article>
