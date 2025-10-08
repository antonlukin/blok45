<?php
/**
 * Template for showing site front-page
 *
 * @package blok45
 * @since 1.0
 */

get_header();
?>

<?php if ( have_posts() ) : ?>
	<main class="archive">
		<section class="list">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/card' );
			endwhile;
			?>
		</section>

		<aside class="sidebar">
			<?php get_template_part( 'template-parts/filters' ); ?>
			<?php get_template_part( 'template-parts/map', null, blok45_get_map_args() ); ?>
		</aside>
	</main>
<?php else : ?>
	<?php get_template_part( 'template-parts/message' ); ?>
<?php endif; ?>

<?php
get_footer();
