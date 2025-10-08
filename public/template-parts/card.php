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

	<?php $rating = blok45_get_post_rating( get_the_ID() ); ?>

	<button class="card__like" type="button" data-post="<?php echo esc_attr( get_the_ID() ); ?>" aria-pressed="false">
		<?php
		printf(
			'<svg class="card__like-icon card__like-icon--default" aria-hidden="true"><use xlink:href="%1$s" href="%1$s"></use></svg>',
			esc_url( blok45_get_icon( 'like' ) )
		);

		printf(
			'<svg class="card__like-icon card__like-icon--active" aria-hidden="true"><use xlink:href="%1$s" href="%1$s"></use></svg>',
			esc_url( blok45_get_icon( 'liked' ) )
		);

		printf(
			'<span class="card__like-count" aria-live="polite" data-rating="%s">%s</span>',
			esc_attr( $rating ),
			esc_html( $rating )
		);
		?>
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
