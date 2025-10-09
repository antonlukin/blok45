<?php
/**
 * Filters template part
 * Renders taxonomy terms as filter links
 *
 * @package blok45
 * @since 1.0
 */

?>

<aside class="filters" data-filters-container aria-labelledby="filters-panel-heading">
	<button class="filters__toggle" type="button" data-filters-toggle aria-haspopup="dialog" aria-expanded="false" aria-controls="filters-panel-sheet">
		<span><?php esc_html_e( 'Filters', 'blok45' ); ?></span>
	</button>

	<div class="filters__sheet" id="filters-panel-sheet" data-filters-panel role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="filters-panel-heading">
		<div class="filters__sheet-inner">
			<div class="filters__sheet-header">
				<h3 class="filters__title" id="filters-panel-heading"><?php esc_html_e( 'Filters', 'blok45' ); ?></h3>
				<button class="filters__close" type="button" data-filters-close>
					<span><?php esc_html_e( 'Close', 'blok45' ); ?></span>
				</button>
			</div>

			<nav class="filters__body" aria-labelledby="filters-panel-heading">
				<div class="filters__group filters__group--years">
					<?php
					printf(
						'<h4 class="filters__group-title">%s</h4>',
						esc_html__( 'Year', 'blok45' )
					);
					?>

					<div class="filters__list" role="list" data-tax="years">
						<?php
						printf(
							'<button class="filters__item filters__item--active" data-value="" role="listitem" aria-pressed="true">%s</button>',
							esc_html__( 'Any', 'blok45' )
						);

						foreach ( blok45_year_ranges() as $range ) {
							printf(
								'<button class="filters__item" data-value="%1$s" role="listitem" aria-pressed="false">%2$s</button>',
								esc_attr( $range['slug'] ),
								esc_html( $range['label'] )
							);
						}
						?>
					</div>
				</div>
	<?php
	$artists = get_terms(
		array(
			'taxonomy'   => 'artist',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);
	?>

				<div class="filters__group filters__group--artists">
					<?php
					printf(
						'<h4 class="filters__group-title">%s</h4>',
						esc_html__( 'Artist', 'blok45' )
					);
					?>

					<div class="filters__list" role="list" data-tax="artist">
						<?php
						printf(
							'<button class="filters__item filters__item--active" data-value="" role="listitem" aria-pressed="true">%s</button>',
							esc_html__( 'Any', 'blok45' )
						);

						foreach ( $artists as $artist ) {
							printf(
								'<button class="filters__item" data-value="%1$s" role="listitem" aria-pressed="false">%2$s</button>',
								esc_attr( $artist->term_id ),
								esc_html( $artist->name )
							);
						}
						?>
					</div>
				</div>

				<div class="filters__group filters__group--sort">
					<?php
					printf(
						'<h4 class="filters__group-title">%s</h4>',
						esc_html__( 'Sort by', 'blok45' )
					);
					?>

					<div class="filters__list" role="list" data-role="sort">
						<?php
						printf(
							'<button class="filters__item filters__item--active" role="listitem" aria-pressed="true">%s</button>',
							esc_html__( 'By default', 'blok45' )
						);

						printf(
							'<button class="filters__item" role="listitem" data-sort="rating" aria-pressed="false">%s</button>',
							esc_html__( 'Highest Rated', 'blok45' )
						);
						?>
					</div>
				</div>
			</nav>

			<div class="filters__map">
				<?php
				get_template_part(
					'template-parts/map',
					null,
					array(
						'class' => 'filters__map-block',
					)
				);
				?>
			</div>
		</div>
	</div>
</aside>
