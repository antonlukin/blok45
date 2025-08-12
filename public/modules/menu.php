<?php
/**
 * Menu manager
 * Filters that upgrade theme menus
 *
 * @package blok45
 * @since 1.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class Blok45_Modules_Menu {
    /**
     * Use this method instead of constructor to avoid setting multiple hooks
     */
    public static function load_module() {
        add_action( 'after_setup_theme', array( __CLASS__, 'register_menus' ) );
        add_filter( 'nav_menu_css_class', array( __CLASS__, 'update_css_classes' ), 10, 3 );
        add_filter( 'nav_menu_link_attributes', array( __CLASS__, 'update_link_attributes' ), 10, 3 );
        add_filter( 'nav_menu_item_id', '__return_empty_string' );
    }

    /**
     * Register theme menus
     */
    public static function register_menus() {
        register_nav_menus(
            array(
                'footer' => esc_html__( 'Меню в подвале', 'blok45' ),
            )
        );
    }

    /**
     * Add class to menu item link
     */
    public static function update_link_attributes( $atts, $item, $args ) {
        $atts['class'] = 'menu__item-link';

        return $atts;
    }

    /**
     * Change default menu items classes
     */
    public static function update_css_classes( $classes, $item, $args ) {
        $classes = array();

        $classes[] = 'menu__item';

        // Add custom classes from interface
        $custom = (array) get_post_meta( $item->ID, '_menu_item_classes', true );

        foreach ( $custom as $class ) {
            if ( ! empty( $class ) ) {
                $classes[] = $class;
            }
        }

        return $classes;
    }
}

/**
 * Load current module environment
 */
Blok45_Modules_Menu::load_module();
