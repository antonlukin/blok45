<?php
/**
 * Template for display single post
 *
 * @package blok45
 * @since 1.0
 */

get_header(); ?>

<main class="content">
    <?php
    while ( have_posts() ) :
        the_post();
        get_template_part( 'template-parts/content' );
    endwhile;
    ?>
</main>

<?php
get_footer();
