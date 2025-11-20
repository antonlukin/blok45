<?php
/**
 * Filters template part
 * Renders taxonomy terms as filter links
 *
 * @package blok45
 * @since 1.0
 */

?>

<aside class="filters" aria-labelledby="filters-panel-heading">
	<button class="filters__toggle" type="button" aria-haspopup="dialog" aria-expanded="false" aria-controls="filters-panel-sheet">
		<?php
		printf(
			'<svg class="filters__toggle-icon" aria-hidden="true"><use xlink:href="%1$s" href="%1$s"></use></svg>',
			esc_url( blok45_get_icon( 'filters' ) )
		);
		?>
		<span><?php esc_html_e( 'Filters', 'blok45' ); ?></span>
	</button>

	<div class="filters__sheet" id="filters-panel-sheet" role="dialog" aria-modal="true" aria-labelledby="filters-panel-heading">
		<div class="filters__sheet-inner">
			<div class="filters__sheet-header">
				<?php
				printf(
					'<h3 class="filters__title" id="filters-panel-heading">%s</h3>',
					esc_html__( 'Filters', 'blok45' )
				);
				?>

				<button class="filters__close" type="button">
					<span class="screen-reader-text"><?php esc_html_e( 'Close', 'blok45' ); ?></span>
				</button>
			</div>

			<nav class="filters__body" aria-labelledby="filters-panel-heading">

				<div class="filters__group filters__group--artists">
					<?php
					printf(
						'<div class="filters__group-heading"><h4 class="filters__group-title">%s</h4>',
						esc_html__( 'Artist', 'blok45' )
					);

					printf(
						'<a class="filters__group-link" href="%1$s">%2$s<span class="filters__group-link-icon" aria-hidden="true">&rarr;</span></a></div>',
						esc_url( site_url( '/creators/' ) ),
						esc_html__( 'Show all', 'blok45' )
					);
					?>

					<div class="filters__list" role="list" data-tax="artist">
						<?php
						printf(
							'<button class="filters__item filters__item--active" data-value="" role="listitem" aria-pressed="true">%s</button>',
							esc_html__( 'Any', 'blok45' )
						);

						foreach ( blok45_get_artist_list( 4 ) as $artist ) {
							printf(
								'<button class="filters__item" data-value="%1$s" role="listitem" aria-pressed="false">%2$s<span class="filters__item-separator" aria-hidden="true"> | </span><span class="filters__item-count">%3$s</span></button>',
								esc_attr( $artist->term_id ),
								esc_html( $artist->name ),
								esc_html( number_format_i18n( $artist->count ) )
							);
						}
						?>
					</div>
				</div>

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
