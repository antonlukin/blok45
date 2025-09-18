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
	<?php foreach ( array( 'years', 'artist' ) as $filter ) : ?>
		<?php
		if ( ! taxonomy_exists( $filter ) ) {
			continue;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $filter,
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			continue;
		}
		?>

		<div class="filters__group filters__group--<?php echo esc_attr( $filter ); ?>">
			<?php
			printf(
				'<h4 class="filters__title">%s</h4>',
				esc_html( ucfirst( $filter ) )
			);
			?>

			<div class="filters__list" role="list">
				<?php
				foreach ( $terms as $item ) {
					printf(
						'<button class="filters__item">%s</button>',
						esc_html( $item->name )
					);
				}
				?>
			</div>
		</div>
		<?php endforeach; ?>

		<div class="filters__group filters__group--sort">
			<?php
			printf(
				'<h4 class="filters__title">%s</h4>',
				esc_html__( 'Sort by', 'blok45' )
			);
			?>

			<div class="filters__list" role="list">
				<?php
				printf(
					'<button class="filters__item filters__item--active" role="listitem">%s</button>',
					esc_html__( 'Highest Rated', 'blok45' )
				);

				printf(
					'<button class="filters__item" role="listitem">%s</button>',
					esc_html__( 'Oldest First', 'blok45' )
				);

				printf(
					'<button class="filters__item" role="listitem">%s</button>',
					esc_html__( 'Newest First', 'blok45' )
				);
				?>
			</div>
		</div>
	</nav>
