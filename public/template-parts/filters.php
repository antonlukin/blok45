<?php
/**
 * Filters template part
 * Renders taxonomy terms as filter links
 *
 * @package blok45
 * @since 1.0
 */

?>

<nav class="filters">
	<div class="filters__group filters__group--years">
		<?php
		printf(
			'<h4 class="filters__title">%s</h4>',
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
			'<h4 class="filters__title">%s</h4>',
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
			'<h4 class="filters__title">%s</h4>',
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
