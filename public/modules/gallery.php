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
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
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
	 * Register REST API routes.
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'blok45/v1',
			'/exif/(?P<attachment>\d+)',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'rest_get_attachment_exif' ),
				'args'                => array(
					'attachment' => array(
						'required' => true,
						'type'     => 'integer',
					),
				),
			)
		);
	}

	/**
	 * REST: Return EXIF data for attachment.
	 *
	 * @param WP_REST_Request $request Request instance.
	 */
	public static function rest_get_attachment_exif( WP_REST_Request $request ) {
		$attachment_id = absint( $request->get_param( 'attachment' ) );

		if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
			return new WP_REST_Response( array( 'message' => esc_html__( 'Attachment not found.', 'blok45' ) ), 404 );
		}

		$full = wp_get_attachment_image_src( $attachment_id, 'full' );

		if ( ! $full ) {
			return new WP_REST_Response( array( 'message' => esc_html__( 'Image source not available.', 'blok45' ) ), 404 );
		}

		$meta = self::get_attachment_exif( $attachment_id, $full );

		return rest_ensure_response(
			array(
				'attachment' => $attachment_id,
				'meta'       => $meta,
			)
		);
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

	/**
	 * Extract EXIF data from attachment metadata.
	 */
	protected static function get_attachment_exif( $attachment_id, $full ) {
		$meta = wp_get_attachment_metadata( $attachment_id );
		$exif = array();

		if ( isset( $meta['image_meta'] ) && is_array( $meta['image_meta'] ) ) {
			$image_meta = $meta['image_meta'];

			if ( ! empty( $image_meta['camera'] ) ) {
				$exif['camera'] = sanitize_text_field( $image_meta['camera'] );
			}

			if ( ! empty( $image_meta['lens'] ) ) {
				$exif['lens'] = sanitize_text_field( $image_meta['lens'] );
			}

			if ( ! empty( $image_meta['focal_length'] ) ) {
				$focal_length = self::format_fractional_number( $image_meta['focal_length'] );
				if ( $focal_length ) {
					$exif['focal_length'] = self::format_numeric_label( $focal_length, __( 'mm', 'blok45' ) );
				}
			}

			if ( ! empty( $image_meta['aperture'] ) ) {
				$aperture = self::format_fractional_number( $image_meta['aperture'], 2 );
				if ( $aperture ) {
					$exif['aperture'] = 'f/' . $aperture;
				}
			}

			if ( ! empty( $image_meta['shutter_speed'] ) ) {
				$shutter = self::format_shutter_speed( $image_meta['shutter_speed'] );
				if ( $shutter ) {
					$exif['shutter_speed'] = $shutter;
				}
			}

			if ( ! empty( $image_meta['iso'] ) ) {
				$exif['iso'] = 'ISO ' . absint( $image_meta['iso'] );
			}

			if ( ! empty( $image_meta['created_timestamp'] ) ) {
				$timestamp = absint( $image_meta['created_timestamp'] );
				if ( $timestamp > 0 ) {
					$exif['created'] = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
				}
			}
		}

		$width  = isset( $meta['width'] ) ? (int) $meta['width'] : ( isset( $full[1] ) ? (int) $full[1] : 0 );
		$height = isset( $meta['height'] ) ? (int) $meta['height'] : ( isset( $full[2] ) ? (int) $full[2] : 0 );

		if ( $width > 0 && $height > 0 ) {
			$exif['dimensions'] = sprintf( '%1$s × %2$s px', number_format_i18n( $width ), number_format_i18n( $height ) );
		}

		return array_filter(
			$exif,
			static function ( $value ) {
				return $value !== null && $value !== '';
			}
		);
	}

	protected static function format_fractional_number( $value, $precision = 1 ) {
		if ( '' === $value || null === $value ) {
			return '';
		}

		if ( strpos( (string) $value, '/' ) !== false ) {
			list( $numerator, $denominator ) = array_map( 'floatval', explode( '/', $value ) );
			if ( 0.0 === $denominator ) {
				return '';
			}
			$value = $numerator / $denominator;
		}

		$value = floatval( $value );

		if ( $value <= 0 ) {
			return '';
		}

		return self::trim_numeric( $value, $precision );
	}

	protected static function format_shutter_speed( $value ) {
		$numeric = self::format_fractional_number( $value, 4 );

		if ( '' === $numeric ) {
			return '';
		}

		$numeric = (float) $numeric;

		if ( $numeric >= 1 ) {
			return self::trim_numeric( $numeric, 2 ) . 's';
		}

		$denominator = (int) round( 1 / $numeric );

		if ( $denominator <= 0 ) {
			return '';
		}

		return '1/' . $denominator . 's';
	}

	protected static function format_numeric_label( $value, $unit ) {
		return self::trim_numeric( $value, 1 ) . ' ' . $unit;
	}

	protected static function trim_numeric( $value, $precision = 1 ) {
		$formatted = number_format( (float) $value, $precision, '.', '' );
		return rtrim( rtrim( $formatted, '0' ), '.' );
	}
}

Blok45_Modules_Gallery::load_module();
