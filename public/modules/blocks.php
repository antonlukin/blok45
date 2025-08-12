<?php
/**
 * Blocks filters
 * All filters to replace core blocks default behavior
 *
 * @package blok45
 * @since 1.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class Blok45_Modules_Blocks {
    /**
     * Use this method instead of constructor to avoid multiple hook setting
     */
    public static function load_module() {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'remove_block_styles' ), 20 );
        add_action( 'wp_footer', array( __CLASS__, 'remove_footer_styles' ), 5 );

        add_filter( 'allowed_block_types_all', array( __CLASS__, 'disable_core_blocks' ), 20 );
        add_filter( 'block_type_metadata_settings', array( __CLASS__, 'remove_gallery_gaps' ) );
        add_filter( 'register_block_type_args', array( __CLASS__, 'modify_image_block_support' ), 10, 2 );
    }

    /**
     * Remove useless supports for blocks.
     */
    public static function modify_image_block_support( $args, $name ) {
        if ( $name === 'core/image' ) {
            $args['supports']['align'] = array( 'center', 'wide', 'full' );
        }

        if ( $name === 'core/gallery' ) {
            $args['supports']['align'] = array( 'center', 'wide', 'full' );
        }

        if ( $name === 'core/audio' ) {
            $args['supports']['align'] = false;
        }

        if ( $name === 'core/video' ) {
            $args['supports']['align'] = array( 'center', 'wide', 'full' );
        }

        if ( $name === 'core/button' ) {
            $args['supports']['align'] = false;
        }

        if ( $name === 'core/buttons' ) {
            $args['supports']['align'] = false;
        }

        return $args;
    }

    /**
     * Remove default inline core/gallery block gap styles
     */
    public static function remove_gallery_gaps( $args ) {
        $callback = 'block_core_gallery_render';

        if ( isset( $args['render_callback'] ) && $args['render_callback'] === $callback ) {
            $args['render_callback'] = null;
        }

        return $args;
    }

    /**
     * Remove default Gutenberg styles and fonts
     */
    public static function remove_block_styles() {
        wp_dequeue_style( 'global-styles' );
        wp_dequeue_style( 'wp-webfonts' );
        wp_dequeue_style( 'wp-block-library' );
        wp_dequeue_style( 'wp-block-library' );
        wp_dequeue_style( 'wp-block-library-theme' );
        wp_dequeue_style( 'block-style-variation-styles' );
        wp_dequeue_style( 'core-block-supports' );
    }


    /**
     * Remove footer default styles
     */
    public static function remove_footer_styles() {
        wp_dequeue_style( 'core-block-supports' );
    }

    /**
     * Disable some core blocks
     */
    public static function disable_core_blocks( $allowed ) {
        $blocks = array_keys( WP_Block_Type_Registry::get_instance()->get_all_registered() );

        // Allowed core blocks
        $allowed = array(
            'core/paragraph',
            'core/image',
            'core/embed',
            'core/separator',
            'core/spacer',
            'core/html',
            'core/code',
            'core/video',
            'core/audio',
            'core/details',
            'core/list',
            'core/list-item',
            'core/gallery',
            'core/heading',
            'core/block',
            'core/buttons',
            'core/button',
        );

        $whitelist = array();

        foreach ( $blocks as $block ) {
            list( $prefix, ) = explode( '/', $block );

            if ( $prefix !== 'core' || in_array( $block, $allowed, true ) ) {
                $whitelist[] = $block;
            }
        }

        return $whitelist;
    }
}

/**
 * Load current module environment
 */
Blok45_Modules_Blocks::load_module();
