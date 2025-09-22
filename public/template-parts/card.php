<?php
/**
 * Card template part
 *
 * @package blok45
 * @since 1.0
 */
?>

<div class="card">
	<figure class="card__image">
		<?php
		the_post_thumbnail(
			'full',
			array(
				'class'   => 'card__thumbnail',
				'loading' => 'lazy',
			)
		);
		?>
	</figure>

	<button class="card__like">
		<?php
		printf(
			'<svg class="card__like-icon"><use xlink:href="%s"></use></svg>',
			esc_url( blok45_get_icon( 'like' ) )
		);
		?>
		<span class="card__like-count">128</span>
	</button>

	<div class="card__content">
			<?php
			blok45_display_meta(
				'<div class="card__meta">',
				'</div>'
			);

			printf(
				'<a class="card__link" href="%s">%s</a>',
				esc_url( get_permalink() ),
				esc_html( get_the_title() )
			);
			?>
	</div>
</div>
