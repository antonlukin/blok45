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

if ( ! function_exists( 'blok45_theme_info' ) ) :
    /**
     * Public template function to show post info
     */
    function blok45_theme_info( $option, $before = '', $after = '' ) {
        $output = null;
        $method = 'get_' . $option;

        if ( method_exists( 'Blok45_Modules_Postinfo', $method ) ) {
            $output = Blok45_Modules_Postinfo::$method();
        }

        if ( ! empty( $output ) ) {
            $output = $before . $output . $after;

            echo $output; // phpcs:ignore WordPress.Security.EscapeOutput
        }
    }
endif;

if ( ! function_exists( 'blok45_get_icon' ) ) :
    /**
     * Public template function to show icon
     */
    function blok45_get_icon( $name ) {
        $version = filemtime( get_template_directory() . '/assets/images/symbol-defs.svg' );

        return get_template_directory_uri() . "/assets/images/symbol-defs.svg?v={$version}#blok45-icon-{$name}";
    }
endif;

/**
 * Include theme core modules
 */
require_once get_template_directory() . '/modules/global.php';
require_once get_template_directory() . '/modules/blocks.php';
require_once get_template_directory() . '/modules/comments.php';
require_once get_template_directory() . '/modules/embeds.php';
require_once get_template_directory() . '/modules/images.php';
require_once get_template_directory() . '/modules/loadmore.php';
require_once get_template_directory() . '/modules/login.php';
require_once get_template_directory() . '/modules/menu.php';
require_once get_template_directory() . '/modules/postinfo.php';
require_once get_template_directory() . '/modules/random.php';
require_once get_template_directory() . '/modules/reactions.php';
require_once get_template_directory() . '/modules/search.php';
require_once get_template_directory() . '/modules/sitemeta.php';
require_once get_template_directory() . '/modules/translit.php';
