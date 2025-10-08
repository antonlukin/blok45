<?php
/**
 * Map module: assets and settings for the map block
 *
 * @package blok45
 * @since 1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Blok45_Modules_Map {
	/**
	 * Use this method instead of constructor to avoid multiple hook setting
	 */
	public static function load_module() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Prepare sanitized template arguments for the map markup.
	 *
	 * @param array $args Raw arguments passed from template calls.
	 *
	 * @return array
	 */
	public static function prepare_template_args( array $args = array() ) {
		$defaults = array(
			'class'  => '',
			'coords' => '',
		);

		// Merge defaults and sanitize
		$args = wp_parse_args( $args, $defaults );

		// Prepare classes and coords
		$classes = array( 'map' );

		if ( $args['class'] ) {
			$classes[] = $args['class'];
		}

		$coords = '';

		$raw_coords = isset( $args['coords'] ) ? trim( (string) $args['coords'] ) : '';

		if ( ! empty( $raw_coords ) ) {
			$classes[] = 'map--single';

			$coords = sanitize_text_field( $raw_coords );
		}

		$classes = array_unique( array_filter( array_map( 'sanitize_html_class', $classes ) ) );

		return array(
			'class'  => implode( ' ', $classes ),
			'coords' => $coords,
		);
	}

	/**
	 * Enqueue Mapbox GL CSS/JS and expose settings for frontend
	 */
	public static function enqueue_assets() {
		// Load only on front page and single posts
		if ( ! ( is_front_page() || is_singular( 'post' ) ) ) {
			return;
		}

		// Mapbox assets
		wp_enqueue_style(
			'mapbox-gl',
			'https://api.mapbox.com/mapbox-gl-js/v3.6.0/mapbox-gl.css',
			array(),
			'3.6.0'
		);

		wp_enqueue_script(
			'mapbox-gl',
			'https://api.mapbox.com/mapbox-gl-js/v3.6.0/mapbox-gl.js',
			array(),
			'3.6.0',
			true
		);

		// Provide settings for the map bundle
		$settings = array(
			'accessToken' => 'pk.eyJ1IjoiYW50b25sdWtpbiIsImEiOiJjbWVmdzk5Zm8weGV0MnFzYWJ4ZW5rdGw2In0.us3fPF4AplcaDwVeL3kbDQ',
			'style'       => 'mapbox://styles/antonlukin/cmefwqf3g00vv01qw9klb4m5b',
			'center'      => array( 20.379262, 44.794558 ),
			'zoom'        => 15,
			'endpoints'   => array(
				'coords'   => esc_url_raw( rest_url( 'blok45/v1/coords' ) ),
				'byCoords' => esc_url_raw( rest_url( 'blok45/v1/by-coords' ) ),
			),
		);

		wp_enqueue_script(
			'blok45-map',
			get_template_directory_uri() . '/assets/map.min.js',
			array( 'mapbox-gl' ),
			filemtime( get_template_directory() . '/assets/map.min.js' ),
			true
		);

		// Use wp_localize_script to expose settings object
		wp_localize_script( 'blok45-map', 'blok45_map', $settings );
	}
}

/**
 * Load current module environment
 */
Blok45_Modules_Map::load_module();
