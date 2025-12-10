<?php
/**
 * Image filters
 *
 * @package blok45
 * @since 1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Blok45_Modules_Images {
	private static $imgproxy_url = null;

	/**
	 * Use this method instead of constructor to avoid multiple hook setting
	 */
	public static function load_module() {
		add_action( 'after_setup_theme', array( __CLASS__, 'add_image_sizes' ) );
		add_action( 'template_redirect', array( __CLASS__, 'redirect_attachments' ) );
		add_filter( 'max_srcset_image_width', array( __CLASS__, 'set_srcset_width' ) );
		add_filter( 'jpeg_quality', array( __CLASS__, 'improve_jpeg' ) );

		add_filter( 'wp_generate_attachment_metadata', array( __CLASS__, 'compress_original_image' ), 10, 2 );
		add_filter( 'wp_image_editors', array( __CLASS__, 'change_image_editor' ) );

		if ( defined( 'BLOK45_IMGPROXY_URL' ) ) {
			add_filter( 'wp_get_attachment_image', array( __CLASS__, 'add_imgproxy_attachments' ), 10, 5 );
			add_filter( 'wp_content_img_tag', array( __CLASS__, 'add_imgproxy_content' ), 10, 3 );
		}
	}

	/**
	 * Compress images with GD instead of Imagick
	 * Try to fix 504 error on image uploading
	 */
	public static function change_image_editor() {
		return array( 'WP_Image_Editor_GD', 'WP_Image_Editor_Imagick' );
	}

	/**
	 * Compress original jpg image
	 */
	public static function compress_original_image( $metadata, $attachment_id ) {
		$file = get_attached_file( $attachment_id );
		$type = get_post_mime_type( $attachment_id );

		if ( in_array( $type, array( 'image/jpg', 'image/jpeg' ), true ) ) {
			$editor = wp_get_image_editor( $file );

			if ( ! is_wp_error( $editor ) ) {
				$result = $editor->set_quality( 90 );

				if ( ! is_wp_error( $result ) ) {
					$editor->save( $file );
				}
			}
		}

		return $metadata;
	}

	/**
	 * Add custom image sizes
	 */
	public static function add_image_sizes() {
		add_theme_support( 'post-thumbnails' );
		set_post_thumbnail_size( 300, 300, true );

		add_image_size( 'card', 640, 480, false );
		add_image_size( 'single', 1200, 800, false );
	}

	/**
	 * Little bit increase jpeg quality
	 */
	public static function improve_jpeg() {
		return 80;
	}

	/**
	 * Filters the maximum image width to be included in a 'srcset' attribute
	 */
	public static function set_srcset_width() {
		return 1280;
	}

	/**
	 * Disable post attachment pages
	 */
	public static function redirect_attachments() {
		if ( ! is_attachment() ) {
			return;
		}

		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
	}

	/**
	 * Update attachments html to use picture with imgproxied urls
	 */
	public static function add_imgproxy_attachments( $html, $attachment_id, $size, $icon, $attr ) {
		if ( is_admin() || ! $html || $icon ) {
			return $html;
		}

		$srcset = isset( $attr['srcset'] ) ? $attr['srcset'] : '';

		$webp_srcset = self::convert_srcset( $srcset, 'webp' );
		$avif_srcset = self::convert_srcset( $srcset, 'avif' );

		if ( empty( $srcset ) && $attr['src'] ) {
			$webp_srcset = self::append_format( $attr['src'], 'webp' );
			$avif_srcset = self::append_format( $attr['src'], 'avif' );
		}

		$picture = sprintf(
			'<picture>
                <source type="image/avif" srcset="%s"%s />
                <source type="image/webp" srcset="%s"%s />
                %s
            </picture>',
			esc_attr( $avif_srcset ),
			isset( $attr['sizes'] ) ? sprintf( ' sizes="%s"', esc_attr( $attr['sizes'] ) ) : '',
			esc_attr( $webp_srcset ),
			isset( $attr['sizes'] ) ? sprintf( ' sizes="%s"', esc_attr( $attr['sizes'] ) ) : '',
			$html
		);

		return $picture;
	}

	/**
	 * Update images from content with imgproxied urls
	 */
	public static function add_imgproxy_content( $img, $context, $attachment_id ) {
		if ( is_admin() || ! $attachment_id ) {
			return $img;
		}

		$size = $context['size'] ?? 'full';

		$src    = wp_get_attachment_image_url( $attachment_id, $size );
		$srcset = wp_get_attachment_image_srcset( $attachment_id, $size );
		$sizes  = wp_get_attachment_image_sizes( $attachment_id, $size );

		$webp_srcset = self::convert_srcset( $srcset, 'webp' );
		$avif_srcset = self::convert_srcset( $srcset, 'avif' );

		if ( empty( $srcset ) && $src ) {
			$webp_srcset = self::append_format( $src, 'webp' );
			$avif_srcset = self::append_format( $src, 'avif' );
		}

		$picture = sprintf(
			'<picture>
                <source type="image/avif" srcset="%s"%s />
                <source type="image/webp" srcset="%s"%s />
                %s
            </picture>',
			esc_attr( $avif_srcset ),
			$sizes ? sprintf( ' sizes="%s"', esc_attr( $sizes ) ) : '',
			esc_attr( $webp_srcset ),
			$sizes ? sprintf( ' sizes="%s"', esc_attr( $sizes ) ) : '',
			$img
		);

		return $picture;
	}

	/**
	 * Add format imgproxy url
	 */
	protected static function append_format( $url, $format ) {
		$parsed_url = wp_parse_url( $url );

		if ( empty( $parsed_url['path'] ) ) {
			return $url;
		}

		$uploads_pos = strpos( $parsed_url['path'], '/uploads/' );

		if ( $uploads_pos === false ) {
			return $url;
		}

		$relative_path = substr( $parsed_url['path'], $uploads_pos + strlen( '/uploads/' ) );
		$encoded_parts = array_map( 'rawurlencode', explode( '/', $relative_path ) );

		// Compose imgproxy url
		$imgproxy_url = BLOK45_IMGPROXY_URL . '/format:%s/plain/%s';

		return sprintf( $imgproxy_url, $format, implode( '/', $encoded_parts ) );
	}

	/**
	 * Smart method to convert srcset attribute
	 */
	protected static function convert_srcset( $srcset_str, $format ) {
		$converted = array();

		if ( empty( $srcset_str ) ) {
			return '';
		}

		$sources = array_map( 'trim', explode( ',', $srcset_str ) );

		foreach ( $sources as $source ) {
			$parts      = preg_split( '/\s+/', $source, 2 );
			$url        = $parts[0];
			$descriptor = $parts[1] ?? '';
			$updated    = self::append_format( $url, $format );

			$converted[] = $descriptor ? ( $updated . ' ' . $descriptor ) : $new_url;
		}

		return implode( ', ', $converted );
	}
}

/**
 * Load current module environment
 */
Blok45_Modules_Images::load_module();
