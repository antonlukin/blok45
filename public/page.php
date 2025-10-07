<?php
/**
 * Page templates
 *
 * @package blok45
 * @since 1.0
 */

get_header(); ?>

<section class="content">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
</section>

<?php
get_footer();
