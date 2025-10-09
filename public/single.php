<?php
/**
 * Template for display single post
 *
 * @package blok45
 * @since 1.0
 */

$context = blok45_get_single_context( get_the_ID() );
$gallery = $context['gallery'];

get_header(); ?>

<main class="single">
	<?php while ( have_posts() ) : ?>
		<?php the_post(); ?>

		<div class="single__media" data-single-swiper>
			<div class="<?php echo esc_attr( $gallery['main_classes'] ); ?>" aria-live="polite" data-swiper="main">
				<div class="swiper__actions" data-swiper-actions>
					<button type="button" class="like like--inline" data-post="<?php echo esc_attr( get_the_ID() ); ?>" aria-pressed="false">
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
						<span class="like__count" aria-live="polite" data-rating="<?php echo esc_attr( $gallery['rating']['value'] ); ?>"><?php echo esc_html( $gallery['rating']['display'] ); ?></span>
					</button>
				</div>

				<div class="swiper-wrapper">
					<?php
					foreach ( $gallery['items'] as $item ) {
						printf(
							'<div class="swiper-slide" data-index="%1$s" data-full="%2$s"><figure class="swiper__figure">%3$s</figure></div>',
							esc_attr( $item['index'] ),
							esc_url( $item['full'] ),
							wp_get_attachment_image(
								(int) $item['attachment'],
								'full',
								false,
								array(
									'class'    => 'swiper__image',
									'alt'      => $item['alt'],
									'loading'  => $item['loading'],
									'decoding' => 'async',
								)
							)
						);
					}
					?>
				</div>

				<?php if ( $gallery['has_thumbs'] ) : ?>
					<div class="swiper__nav">
						<button type="button" class="swiper__nav-button swiper__nav-button--prev" data-swiper-nav="prev">
							<?php
							printf(
								'<svg class="swiper__nav-icon" aria-hidden="true"><use xlink:href="%1$s" href="%1$s"></use></svg>',
								esc_url( blok45_get_icon( 'left' ) )
							);
							?>
						</button>

						<button type="button" class="swiper__nav-button swiper__nav-button--next" data-swiper-nav="next">
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

			<?php if ( $gallery['has_thumbs'] ) : ?>
				<div class="<?php echo esc_attr( $gallery['thumbs_classes'] ); ?>" aria-label="<?php esc_attr_e( 'Gallery thumbnails', 'blok45' ); ?>" data-swiper="thumbs">
					<div class="swiper-wrapper">
						<?php foreach ( $gallery['items'] as $item ) : ?>
							<?php
							printf(
								'<div class="swiper-slide" role="button" aria-label="%s" tabindex="0" data-index="%s"><img src="%s" alt="" aria-hidden="true" loading="lazy"></div>',
								esc_attr( $item['label'] ),
								esc_attr( $item['index'] ),
								esc_url( $item['thumb'] )
							);
							?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<aside class="single__content">
			<div class="single__post">
				<h2 class="single__post-title">
					<?php the_title(); ?>
				</h2>

				<div class="single__post-excerpt">
					<?php the_content(); ?>
				</div>

				<?php if ( ! empty( $context['meta'] ) ) : ?>
					<div class="single__post-meta">
						<?php foreach ( $context['meta'] as $group ) : ?>
							<div class="single__meta-group">
								<?php
								printf(
									'<span class="single__meta-label">%s</span>',
									esc_html( $group['label'] )
								);
								?>

								<div class="single__meta-links">
									<?php foreach ( $group['items'] as $meta_link ) : ?>
										<?php
										printf(
											'<a class="single__meta-link" href="%1$s">%2$s</a>',
											esc_url( $meta_link['url'] ),
											esc_html( $meta_link['name'] )
										);
										?>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $context['map'] ) ) : ?>
				<div class="single__map">
					<?php get_template_part( 'template-parts/map', null, $context['map'] ); ?>
				</div>
			<?php endif; ?>
		</aside>
	<?php endwhile; ?>
</main>

</div>

<?php
get_footer();
