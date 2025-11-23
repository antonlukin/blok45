<?php
/**
 * Template Name: Artists Catalog
 * Description: Lists all artists with their works.
 *
 * @package blok45
 * @since 1.0
 */

get_header();

?>

<section class="archive">
	<header class="archive__header">
		<?php the_title( '<h1 class="archive__title">', '</h1>' ); ?>

		<div class="archive__description">
			<?php the_content(); ?>
		</div>
	</header>

	<div class="directory">
		<?php foreach ( blok45_get_artist_list() as $artist ) : ?>
			<a class="directory__link" href="<?php echo esc_url( get_term_link( $artist ) ); ?>">
				<span class="directory__header">
					<?php
					printf(
						'<span class="directory__name">%s</span>',
						esc_html( $artist->name )
					);

					printf(
						'<svg class="directory__icon" aria-hidden="true"><use xlink:href="%1$s" href="%1$s"></use></svg>',
						esc_url( blok45_get_icon( 'right' ) )
					);
					?>
				</span>

				<span class="directory__count">
					<?php
					printf(
						/* translators: %s: Number of posts */
						esc_html( _n( '%s work', '%s works', (int) $artist->count, 'blok45' ) ),
						absint( number_format_i18n( $artist->count ) )
					);
					?>
				</span>

				<?php $previews = blok45_get_artist_preview_query( $artist ); ?>

				<?php if ( ! empty( $previews ) ) : ?>
					<span class="directory__preview" aria-label="<?php echo esc_attr( sprintf( __( 'Last works by %s', 'blok45' ), $artist->name ) ); ?>">
						<?php
						foreach ( $previews as $thumbnail ) :
							printf(
								'<figure class="directory__preview-item">%s</figure>',
								$thumbnail // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							);
						endforeach;
						?>
					</span>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</div>
</section>

<?php
get_footer();
