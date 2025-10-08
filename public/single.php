<?php
/**
 * Template for display single post
 *
 * @package blok45
 * @since 1.0
 */

get_header(); ?>

<main class="single">
	<?php
	while ( have_posts() ) :
		the_post();

		$items = blok45_get_gallery_items( get_the_ID() );

		$has_thumbs = ( count( $items ) > 1 );

		$rating_value = 0;

		if ( class_exists( 'Blok45_Modules_Rating' ) ) {
			$rating_value = Blok45_Modules_Rating::get_post_rating_value( get_the_ID() );
		}

		$rating_display = number_format_i18n( $rating_value );
		$raw_coords     = trim( (string) get_post_meta( get_the_ID(), 'blok45_coords', true ) );
		if ( '' === $raw_coords ) {
			$raw_coords = trim( (string) get_post_meta( get_the_ID(), 'b45_coords', true ) );
		}
		$main_swiper_classes   = 'swiper swiper--main' . ( $has_thumbs ? ' swiper--with-thumbs' : '' );
		$thumbs_swiper_classes = 'swiper swiper--thumbs';
		?>
		<div class="single__media" data-single-swiper>
			<div class="<?php echo esc_attr( $main_swiper_classes ); ?>" aria-live="polite" data-swiper="main">
				<div class="swiper__actions" data-swiper-actions>
					<button
						type="button"
						class="card__like card__like--inline"
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
				</div>
				<div class="swiper-wrapper">
					<?php
					foreach ( $items as $index => $item ) :
						$caption    = isset( $item['caption'] ) ? $item['caption'] : '';
						$alt        = isset( $item['alt'] ) && '' !== $item['alt'] ? $item['alt'] : get_the_title();
						$width      = isset( $item['width'] ) ? (int) $item['width'] : null;
						$height     = isset( $item['height'] ) ? (int) $item['height'] : null;
						$attachment = isset( $item['id'] ) ? (int) $item['id'] : 0;
						$full       = isset( $item['full'] ) ? $item['full'] : '';
						?>
						<div
							class="swiper-slide"
							data-index="<?php echo esc_attr( $index ); ?>"
							data-attachment="<?php echo esc_attr( $attachment ); ?>"
							data-full="<?php echo esc_url( $full ); ?>"
						>
							<figure class="swiper__figure">
								<img
									src="<?php echo esc_url( $item['full'] ); ?>"
									alt="<?php echo esc_attr( $alt ); ?>"
									<?php echo $width ? ' width="' . esc_attr( $width ) . '"' : ''; ?>
									<?php echo $height ? ' height="' . esc_attr( $height ) . '"' : ''; ?>
									class="swiper__image"
									decoding="async"
									loading="<?php echo 0 === $index ? 'eager' : 'lazy'; ?>"
							/>
						</figure>
						</div>
					<?php endforeach; ?>
				</div>

				<?php if ( $has_thumbs ) : ?>
					<div class="swiper__nav">
						<button
							type="button"
							class="swiper__nav-button swiper__nav-button--prev"
							data-swiper-nav="prev"
							aria-label="<?php esc_attr_e( 'Show previous image', 'blok45' ); ?>"
						>
							<?php
							printf(
								'<svg class="swiper__nav-icon" aria-hidden="true"><use xlink:href="%1$s" href="%1$s"></use></svg>',
								esc_url( blok45_get_icon( 'left' ) )
							);
							?>
						</button>
						<button
							type="button"
							class="swiper__nav-button swiper__nav-button--next"
							data-swiper-nav="next"
							aria-label="<?php esc_attr_e( 'Show next image', 'blok45' ); ?>"
						>
							<?php
							printf(
								'<svg class="swiper__nav-icon" aria-hidden="true"><use xlink:href="%1$s" href="%1$s"></use></svg>',
								esc_url( blok45_get_icon( 'left' ) )
							);
							?>
						</button>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $has_thumbs ) : ?>
				<div class="<?php echo esc_attr( $thumbs_swiper_classes ); ?>" aria-label="<?php esc_attr_e( 'Gallery thumbnails', 'blok45' ); ?>" data-swiper="thumbs">
					<div class="swiper-wrapper">
						<?php
						foreach ( $items as $index => $item ) :
							$label      = sprintf( /* translators: %d: slide number */ esc_html__( 'Show image %d', 'blok45' ), $index + 1 );
							$thumb      = isset( $item['thumb'] ) && $item['thumb'] ? $item['thumb'] : $item['full'];
							$attachment = isset( $item['id'] ) ? (int) $item['id'] : 0;
							?>
							<div class="swiper-slide" role="button" aria-label="<?php echo esc_attr( $label ); ?>" tabindex="0" data-index="<?php echo esc_attr( $index ); ?>" data-attachment="<?php echo esc_attr( $attachment ); ?>">
								<img src="<?php echo esc_url( $thumb ); ?>" alt="" aria-hidden="true" loading="lazy" />
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<aside class="single__sidebar">
			<div class="single__post">
				<h2 class="single__post-title"><?php the_title(); ?></h2>
				<div class="single__post-excerpt"><?php the_content(); ?></div>

				<?php
				$artists = get_the_terms( get_the_ID(), 'artist' );
				$years   = get_the_terms( get_the_ID(), 'years' );

				$has_artists = is_array( $artists ) && ! is_wp_error( $artists ) && ! empty( $artists );
				$has_years   = is_array( $years ) && ! is_wp_error( $years ) && ! empty( $years );

				if ( $has_artists || $has_years ) :
					?>
				<div class="single__post-meta">
					<?php if ( $has_artists ) : ?>
						<div class="single__meta-group">
							<span class="single__meta-label"><?php echo esc_html( _n( 'Artist', 'Artists', count( $artists ), 'blok45' ) ); ?></span>
							<div class="single__meta-links">
								<?php
								foreach ( $artists as $artist ) :
									$link = get_term_link( $artist );

									if ( is_wp_error( $link ) ) {
										continue;
									}
									?>
									<a class="single__meta-link" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $artist->name ); ?></a>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( $has_years ) : ?>
						<div class="single__meta-group">
							<span class="single__meta-label"><?php echo esc_html( _n( 'Year', 'Years', count( $years ), 'blok45' ) ); ?></span>
							<div class="single__meta-links">
								<?php
								foreach ( $years as $year ) :
									$link = get_term_link( $year );

									if ( is_wp_error( $link ) ) {
										continue;
									}
									?>
									<a class="single__meta-link" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $year->name ); ?></a>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
				</div>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $raw_coords ) ) : ?>
				<div class="single__map">
					<?php get_template_part( 'template-parts/map', null, blok45_get_map_args( array( 'coords' => $raw_coords ) ) ); ?>
				</div>
			<?php endif; ?>
		</aside>
	<?php endwhile; ?>
</main>

</div>

<?php
get_footer();
