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
		add_image_size( 'single', 1920, 1080, false );
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
}

/**
 * Load current module environment
 */
Blok45_Modules_Images::load_module();
