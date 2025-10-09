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

		<?php get_template_part( 'template-parts/filters' ); ?>
	</main>
<?php else : ?>
	<?php get_template_part( 'template-parts/message' ); ?>
<?php endif; ?>

<?php
get_footer();
