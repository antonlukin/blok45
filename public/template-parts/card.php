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

	<?php
	$rating_value = 0;

	if ( class_exists( 'Blok45_Modules_Rating' ) ) {
		$rating_value = Blok45_Modules_Rating::get_post_rating_value( get_the_ID() );
	}

	$rating_display = number_format_i18n( $rating_value );
	?>

	<button
		class="card__like"
		type="button"
		data-post="<?php echo esc_attr( get_the_ID() ); ?>"
		data-rating="<?php echo esc_attr( $rating_value ); ?>"
		aria-label="<?php echo esc_attr__( 'Toggle rating', 'blok45' ); ?>"
		aria-pressed="false"
	>
		<?php
		printf(
			'<svg class="card__like-icon card__like-icon--default" aria-hidden="true"><use xlink:href="%1$s" href="%1$s"></use></svg>',
			esc_url( blok45_get_icon( 'like' ) )
		);

		printf(
			'<svg class="card__like-icon card__like-icon--active" aria-hidden="true"><use xlink:href="%1$s" href="%1$s"></use></svg>',
			esc_url( blok45_get_icon( 'liked' ) )
		);
		?>
		<span class="card__like-count" aria-live="polite"><?php echo esc_html( $rating_display ); ?></span>
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
