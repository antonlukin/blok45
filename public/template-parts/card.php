<?php
/**
 * Card template part
 *
 * @package blok45
 * @since 1.0
 */

$is_archived  = (bool) get_post_meta( get_the_ID(), 'blok45_graffiti_archived', true );
$card_classes = array( 'card' );

if ( $is_archived ) {
	$card_classes[] = 'card--archived';
}
?>

<div <?php post_class( $card_classes ); ?>>
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

	<?php if ( $is_archived ) : ?>
		<span class="card__state" aria-label="<?php esc_attr_e( 'This graffiti has been removed.', 'blok45' ); ?>">
			<?php esc_html_e( 'Removed', 'blok45' ); ?>
		</span>
	<?php endif; ?>

	<button class="card__like like" type="button" data-post="<?php echo esc_attr( get_the_ID() ); ?>" aria-pressed="false">
		<?php
		printf(
			'<svg class="like__icon like__icon--default" aria-hidden="true"><use xlink:href="%1$s" href="%1$s"></use></svg>',
			esc_url( blok45_get_icon( 'like' ) )
		);

		printf(
			'<svg class="like__icon like__icon--active" aria-hidden="true"><use xlink:href="%1$s" href="%1$s"></use></svg>',
			esc_url( blok45_get_icon( 'liked' ) )
		);
		?>
		<span class="like__count" aria-live="polite">0</span>
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
