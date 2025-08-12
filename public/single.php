<?php
/**
 * Template for display single post
 *
 * @package blok45
 * @since 1.0
 */

get_header(); ?>

<section class="content">
    <?php
    while ( have_posts() ) :
        the_post();
        get_template_part( 'templates/content', get_post_format() );
    endwhile;
    ?>
</section>

<?php
get_footer();