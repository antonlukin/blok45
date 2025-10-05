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
		add_filter( 'nav_menu_css_class', array( __CLASS__, 'update_css_classes' ), 10, 2 );
		add_filter( 'nav_menu_link_attributes', array( __CLASS__, 'update_link_attributes' ), 10, 2 );
		add_filter( 'nav_menu_item_id', '__return_empty_string' );
	}

	/**
	 * Register theme menus
	 */
	public static function register_menus() {
		register_nav_menus(
			array(
				'header-menu' => esc_html__( 'Header menu', 'blok45' ),
			)
		);
	}

	/**
	 * Add class to menu item link
	 */
	public static function update_link_attributes( $atts, $item ) {
		$is_current = false;

		if ( isset( $item->object ) && 'page' === $item->object ) {
			$is_current = (int) $item->object_id === (int) get_queried_object_id();
		}

		$atts['class'] = 'menu__item-link' . ( $is_current ? ' menu__item-link--current' : '' );

		return $atts;
	}

	/**
	 * Change default menu items classes
	 */
	public static function update_css_classes( $classes, $item ) {
		$classes = array();

		$classes[] = 'menu__item';

		// Add custom classes from interface
		$custom = (array) get_post_meta( $item->ID, '_menu_item_classes', true );

		foreach ( $custom as $class ) {
			if ( ! empty( $class ) ) {
				$classes[] = $class;
			}
		}

		if ( ! empty( in_array( 'current-menu-item', (array) $item->classes, true ) ) ) {
			$classes[] = 'menu__item--current';
		}

		return $classes;
	}
}

/**
 * Load current module environment
 */
Blok45_Modules_Menu::load_module();
