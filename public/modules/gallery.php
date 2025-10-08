<?php
/**
 * Single post gallery helpers.
 *
 * @package blok45
 * @since 1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Blok45_Modules_Gallery {
	const SWIPER_VERSION = '11.0.5';

	/**
	 * Cached gallery data keyed by post ID.
	 *
	 * @var array
	 */
	protected static $cache = array();

	/**
	 * Bootstrap module hooks.
	 */
	public static function load_module() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_single_assets' ) );
	}

	/**
	 * Enqueue single gallery assets when applicable.
	 */
	public static function enqueue_single_assets() {
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		$post_id = get_queried_object_id();

		if ( ! $post_id || empty( self::get_gallery_items( $post_id ) ) ) {
			return;
		}

		wp_enqueue_style(
			'blok45-swiper',
			sprintf( 'https://unpkg.com/swiper@%s/swiper-bundle.min.css', self::SWIPER_VERSION ),
			array(),
			self::SWIPER_VERSION
		);

		wp_enqueue_script(
			'blok45-swiper',
			sprintf( 'https://unpkg.com/swiper@%s/swiper-bundle.min.js', self::SWIPER_VERSION ),
			array(),
			self::SWIPER_VERSION,
			true
		);

		$script_path = get_template_directory() . '/assets/single-gallery.min.js';

		if ( file_exists( $script_path ) ) {
			wp_enqueue_script(
				'blok45-single-gallery',
				get_template_directory_uri() . '/assets/single-gallery.min.js',
				array( 'blok45-swiper' ),
				filemtime( $script_path ),
				true
			);
		}
	}

	/**
	 * Return the first gallery block items for a post.
	 *
	 * @param int|WP_Post|null $post Optional post reference.
	 */
	public static function get_gallery_items( $post = null ) {
		$post    = get_post( $post );
		$post_id = $post instanceof WP_Post ? (int) $post->ID : 0;

		if ( $post_id <= 0 ) {
			return array();
		}

		if ( array_key_exists( $post_id, self::$cache ) ) {
			return self::$cache[ $post_id ];
		}

		$blocks        = parse_blocks( (string) $post->post_content );
		$gallery_block = self::locate_gallery_block( $blocks );

		if ( ! $gallery_block ) {
			self::$cache[ $post_id ] = array();
			return self::$cache[ $post_id ];
		}

		$items = self::extract_items_from_block( $gallery_block );
		self::$cache[ $post_id ] = $items;

		return $items;
	}

	/**
	 * Recursively locate the first core/gallery block.
	 */
	protected static function locate_gallery_block( $blocks ) {
		foreach ( (array) $blocks as $block ) {
			if ( isset( $block['blockName'] ) && 'core/gallery' === $block['blockName'] ) {
				return $block;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$inner = self::locate_gallery_block( $block['innerBlocks'] );

				if ( $inner ) {
					return $inner;
				}
			}
		}

		return null;
	}

	/**
	 * Build gallery item data from the located block.
	 */
	protected static function extract_items_from_block( $block ) {
		$ids   = array();
		$items = array();

		if ( ! empty( $block['attrs']['ids'] ) && is_array( $block['attrs']['ids'] ) ) {
			$ids = array_map( 'absint', $block['attrs']['ids'] );
		}

		if ( ! empty( $ids ) ) {
			foreach ( $ids as $attachment_id ) {
				$item = self::create_item_from_attachment( $attachment_id );

				if ( $item ) {
					$items[] = $item;
				}
			}

			return $items;
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			foreach ( $block['innerBlocks'] as $inner ) {
				$attachment_id = isset( $inner['attrs']['id'] ) ? absint( $inner['attrs']['id'] ) : 0;

				if ( $attachment_id > 0 ) {
					$item = self::create_item_from_attachment( $attachment_id );

					if ( $item ) {
						$items[] = $item;
					}

					continue;
				}

				$url = isset( $inner['attrs']['url'] ) ? esc_url_raw( $inner['attrs']['url'] ) : '';

				if ( empty( $url ) ) {
					continue;
				}

				$alt     = isset( $inner['attrs']['alt'] ) ? sanitize_text_field( $inner['attrs']['alt'] ) : '';
				$caption = isset( $inner['attrs']['caption'] ) ? wp_kses_post( $inner['attrs']['caption'] ) : '';

				$items[] = array(
					'id'         => 0,
					'full'       => $url,
					'thumb'      => $url,
					'alt'        => $alt,
					'caption'    => $caption,
					'width'      => null,
					'height'     => null,
					'attachment' => 0,
				);
			}
		}

		return $items;
	}

	/**
	 * Create item payload from an attachment ID.
	 */
	protected static function create_item_from_attachment( $attachment_id ) {
		$full = wp_get_attachment_image_src( $attachment_id, 'full' );

		if ( ! $full ) {
			return null;
		}

		$thumb   = wp_get_attachment_image_src( $attachment_id, 'medium' );
		$alt     = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		$caption = wp_get_attachment_caption( $attachment_id );

		return array(
			'id'         => $attachment_id,
			'full'       => esc_url_raw( $full[0] ),
			'thumb'      => esc_url_raw( $thumb ? $thumb[0] : $full[0] ),
			'alt'        => sanitize_text_field( $alt ),
			'caption'    => $caption ? wp_kses_post( $caption ) : '',
			'width'      => isset( $full[1] ) ? (int) $full[1] : null,
			'height'     => isset( $full[2] ) ? (int) $full[2] : null,
			'attachment' => $attachment_id,
		);
	}

}

Blok45_Modules_Gallery::load_module();
