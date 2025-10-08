<?php
/**
 * The main template file
 *
 * Most likely this template will never be shown.
 * It is used to display a page when nothing more specific matches a query.
 *
 * @package blok45
 * @since 1.0
 */

get_header(); ?>

<section class="archive">
	<?php get_template_part( 'templates/content', 'none' ); ?>
</section>

<?php
get_footer();
