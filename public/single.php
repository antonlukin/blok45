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

		if ( empty( $items ) ) :
			printf(
				'<p class="single__empty">%s</p>',
				esc_html__( 'No gallery images were found for this post.', 'blok45' )
			);
			continue;
		endif;

		$count         = count( $items );
		$has_thumbs    = ( $count > 1 );
		$meta_labels   = array(
			'camera'        => __( 'Camera', 'blok45' ),
			'lens'          => __( 'Lens', 'blok45' ),
			'focal_length'  => __( 'Focal Length', 'blok45' ),
			'aperture'      => __( 'Aperture', 'blok45' ),
			'shutter_speed' => __( 'Shutter Speed', 'blok45' ),
			'iso'           => __( 'ISO', 'blok45' ),
			'dimensions'    => __( 'Dimensions', 'blok45' ),
			'created'       => __( 'Captured', 'blok45' ),
		);

		$raw_coords            = trim( (string) get_post_meta( get_the_ID(), 'b45_coords', true ) );
		$main_swiper_classes   = 'swiper swiper--main' . ( $has_thumbs ? ' swiper--with-thumbs' : '' );
		$thumbs_swiper_classes = 'swiper swiper--thumbs';
		?>
		<div class="single__layout">
			<div class="single__media" data-single-swiper>
				<div class="<?php echo esc_attr( $main_swiper_classes ); ?>" aria-live="polite" data-swiper="main">
					<div class="swiper__actions" data-swiper-actions>
						<button
							type="button"
							class="swiper__action swiper__action--exif"
							data-swiper-exif-trigger
							aria-expanded="false"
							aria-haspopup="dialog"
						>
							<?php esc_html_e( 'Show EXIF', 'blok45' ); ?>
						</button>
						<a
							href="#"
							class="swiper__action swiper__action--download"
							data-swiper-download
							rel="noopener"
						>
							<?php esc_html_e( 'Download original', 'blok45' ); ?>
						</a>
					</div>
					<div class="swiper-wrapper">
						<?php foreach ( $items as $index => $item ) :
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

					<div class="swiper__exif-overlay" data-swiper-exif-panel data-swiper-exif-placeholder="<?php esc_attr_e( '—', 'blok45' ); ?>" hidden aria-hidden="true" role="dialog" aria-modal="false">
						<div class="swiper__exif-overlay-content">
							<header class="swiper__exif-overlay-header">
								<h3 class="swiper__exif-overlay-title"><?php esc_html_e( 'Photo EXIF', 'blok45' ); ?></h3>
								<button type="button" class="swiper__exif-overlay-close" data-swiper-exif-close aria-label="<?php esc_attr_e( 'Close EXIF panel', 'blok45' ); ?>">
									<span aria-hidden="true">&times;</span>
								</button>
							</header>
							<dl class="swiper__exif-grid">
								<?php foreach ( $meta_labels as $meta_key => $meta_label ) : ?>
									<div class="swiper__exif-grid-row" data-swiper-exif-row>
										<dt><?php echo esc_html( $meta_label ); ?></dt>
										<dd data-swiper-exif-field="<?php echo esc_attr( $meta_key ); ?>"><?php esc_html_e( '—', 'blok45' ); ?></dd>
									</div>
								<?php endforeach; ?>
							</dl>
							<p class="swiper__exif-empty" data-swiper-exif-empty><?php esc_html_e( 'No EXIF data available for this image.', 'blok45' ); ?></p>
						</div>
					</div>

					<?php if ( $has_thumbs ) : ?>
						<div class="swiper-button-prev" aria-label="<?php esc_attr_e( 'Show previous image', 'blok45' ); ?>"></div>
						<div class="swiper-button-next" aria-label="<?php esc_attr_e( 'Show next image', 'blok45' ); ?>"></div>
					<?php endif; ?>
				</div>

				<?php if ( $has_thumbs ) : ?>
					<div class="<?php echo esc_attr( $thumbs_swiper_classes ); ?>" aria-label="<?php esc_attr_e( 'Gallery thumbnails', 'blok45' ); ?>" data-swiper="thumbs">
						<div class="swiper-wrapper">
							<?php foreach ( $items as $index => $item ) :
								$label = sprintf( /* translators: %d: slide number */ esc_html__( 'Show image %d', 'blok45' ), $index + 1 );
								$thumb = isset( $item['thumb'] ) && $item['thumb'] ? $item['thumb'] : $item['full'];
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

					<div class="single__post-meta">
						<?php blok45_display_meta(); ?>
					</div>
				</div>

				<?php if ( ! empty( $raw_coords ) ) : ?>
					<div class="single__map">
						<div class="map map--single" data-coords="<?php echo esc_attr( $raw_coords ); ?>" data-zoom="15" data-label="<?php echo esc_attr( get_the_title() ); ?>">
							<div class="map__canvas"></div>
							<div class="map__zoom">
								<button class="map__zoom-button map__zoom-in" type="button">+</button>
								<button class="map__zoom-button map__zoom-out" type="button">−</button>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</aside>
		</div>
	<?php endwhile; ?>
</main>

</div>

<?php
get_footer();
