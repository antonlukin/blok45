<?php
/**
 * The template for displaying archive pages
 *
 * @package blok45
 * @since 1.0
 */

get_header();
?>

<?php if ( have_posts() ) : ?>
	<main class="archive">
		<?php if ( have_posts() && get_the_archive_title() ) : ?>
			<header class="archive__header">
				<h1 class="archive__title">
					<?php the_archive_title(); ?>
				</h1>

				<?php if ( get_the_archive_description() ) : ?>
					<div class="archive__description">
						<?php the_archive_description(); ?>
					</div>
				<?php endif; ?>
			</header>
		<?php endif; ?>

		<section class="list">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/card' );
			endwhile;
			?>
		</section>
	</main>
<?php else : ?>
	<?php get_template_part( 'template-parts/message' ); ?>
<?php endif; ?>

<?php
get_footer();

