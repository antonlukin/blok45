<?php
/**
 * Important functions and definitions
 *
 * Setups the theme and provides some helper functions, which are used in the
 * theme as custom template tags. Others are attached to action and filter
 * hooks in WordPress to change core functionality.
 *
 * @package blok45
 * @since 1.0
 */

/**
 * We have to install this value for image sizes
 */
if ( ! isset( $content_width ) ) {
	$content_width = 1040;
}

/**
 * Include theme core modules
 */
require_once get_template_directory() . '/modules/global.php';
require_once get_template_directory() . '/modules/blocks.php';
require_once get_template_directory() . '/modules/comments.php';
require_once get_template_directory() . '/modules/images.php';
require_once get_template_directory() . '/modules/rating.php';
require_once get_template_directory() . '/modules/sitemeta.php';
require_once get_template_directory() . '/modules/translit.php';
require_once get_template_directory() . '/modules/filters.php';
require_once get_template_directory() . '/modules/map.php';
require_once get_template_directory() . '/modules/gallery.php';
require_once get_template_directory() . '/modules/single.php';
require_once get_template_directory() . '/modules/menu.php';

/**
 * Include template tags
 */
require_once get_template_directory() . '/template-tags.php';
