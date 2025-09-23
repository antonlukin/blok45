<?php
/**
 * Template for display single post
 *
 * @package blok45
 * @since 1.0
 */

get_header(); ?>

<main class="single-gallery">
	<?php
	while ( have_posts() ) :
		the_post();

		$items = blok45_get_gallery_items( get_the_ID() );

		if ( empty( $items ) ) :
			printf(
				'<p class="single-gallery__empty">%s</p>',
				esc_html__( 'No gallery images were found for this post.', 'blok45' )
			);
			continue;
		endif;

		$count        = count( $items );
		$has_thumbs   = ( $count > 1 );
		$gallery_class = 'gallery' . ( $has_thumbs ? ' gallery--with-thumbs' : '' );
		$meta_labels  = array(
			'camera'        => __( 'Camera', 'blok45' ),
			'lens'          => __( 'Lens', 'blok45' ),
			'focal_length'  => __( 'Focal Length', 'blok45' ),
			'aperture'      => __( 'Aperture', 'blok45' ),
			'shutter_speed' => __( 'Shutter Speed', 'blok45' ),
			'iso'           => __( 'ISO', 'blok45' ),
			'dimensions'    => __( 'Dimensions', 'blok45' ),
			'created'       => __( 'Captured', 'blok45' ),
		);

		$raw_coords = trim( (string) get_post_meta( get_the_ID(), 'b45_coords', true ) );
		?>
		<div class="single-gallery__layout">
			<div class="<?php echo esc_attr( $gallery_class ); ?>">
				<div class="swiper gallery-top" aria-live="polite" data-gallery="main">
					<div class="swiper-wrapper">
						<?php foreach ( $items as $index => $item ) :
							$caption    = isset( $item['caption'] ) ? $item['caption'] : '';
							$alt        = isset( $item['alt'] ) && '' !== $item['alt'] ? $item['alt'] : get_the_title();
							$width      = isset( $item['width'] ) ? (int) $item['width'] : null;
							$height     = isset( $item['height'] ) ? (int) $item['height'] : null;
							$attachment = isset( $item['id'] ) ? (int) $item['id'] : 0;
							?>
							<div class="swiper-slide" data-index="<?php echo esc_attr( $index ); ?>" data-attachment="<?php echo esc_attr( $attachment ); ?>">
								<figure class="gallery__slide">
									<img
										src="<?php echo esc_url( $item['full'] ); ?>"
										alt="<?php echo esc_attr( $alt ); ?>"
										<?php echo $width ? ' width="' . esc_attr( $width ) . '"' : ''; ?>
										<?php echo $height ? ' height="' . esc_attr( $height ) . '"' : ''; ?>
										class="gallery__image"
										decoding="async"
										loading="<?php echo 0 === $index ? 'eager' : 'lazy'; ?>"
								/>
							</figure>
							</div>
						<?php endforeach; ?>
					</div>

					<?php if ( $has_thumbs ) : ?>
						<div class="swiper-button-prev" aria-label="<?php esc_attr_e( 'Show previous image', 'blok45' ); ?>"></div>
						<div class="swiper-button-next" aria-label="<?php esc_attr_e( 'Show next image', 'blok45' ); ?>"></div>
					<?php endif; ?>
				</div>

				<?php if ( $has_thumbs ) : ?>
					<div class="swiper gallery-thumbs" aria-label="<?php esc_attr_e( 'Gallery thumbnails', 'blok45' ); ?>" data-gallery="thumbs">
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

			<aside class="single-gallery__sidebar" data-gallery-sidebar data-exif-placeholder="<?php esc_attr_e( '—', 'blok45' ); ?>">
				<div class="single-gallery__post">
					<h2 class="single-gallery__post-title"><?php the_title(); ?></h2>

						<div class="single-gallery__post-excerpt"><?php the_content(); ?></div>

					<div class="single-gallery__post-meta">
						<?php blok45_display_meta(); ?>
					</div>
				</div>

				<h3 class="single-gallery__sidebar-title"><?php esc_html_e( 'Photo details', 'blok45' ); ?></h3>
				<dl class="single-gallery__exif" data-gallery-exif>
					<?php foreach ( $meta_labels as $meta_key => $meta_label ) : ?>
						<div class="single-gallery__exif-row" data-exif-row>
							<dt><?php echo esc_html( $meta_label ); ?></dt>
							<dd data-exif-field="<?php echo esc_attr( $meta_key ); ?>"><?php esc_html_e( '—', 'blok45' ); ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>
				<p class="single-gallery__exif-empty" data-exif-empty><?php esc_html_e( 'No EXIF data available for this image.', 'blok45' ); ?></p>

				<?php if ( ! empty( $raw_coords ) ) : ?>
					<div class="single-gallery__map">
						<h3 class="single-gallery__map-title"><?php esc_html_e( 'Location', 'blok45' ); ?></h3>
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
