<?php
/**
 * The template for displaying archive pages
 *
 * @package blok45
 * @since 1.0
 */

get_header(); ?>

<section class="archive">
    <?php if ( have_posts() && get_the_archive_title() ) : ?>
        <div class="caption caption--archive">
            <?php the_archive_title(); ?>

            <?php if ( get_the_archive_description() ) : ?>
                <div class="caption__description">
                    <?php the_archive_description(); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ( have_posts() ) : ?>
        <?php if ( get_next_posts_link() ) : ?>
            <nav class="navigate">
                <?php next_posts_link( esc_html__( 'Следующая страница', 'blok45' ) ); ?>
            </nav>
        <?php endif; ?>
    <?php else : ?>
        <?php get_template_part( 'templates/content', 'none' ); ?>
    <?php endif; ?>
</section>

<?php
get_footer();
