<?php
/**
 * Custom taxonomies and post meta for filters
 *
 * @package blok45
 * @since 1.0
 */

class Blok45_Modules_Filters {
	/**
	 * Use this method instead of constructor to avoid multiple hook setting
	 */
	public static function load_module() {
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ) );
		add_action( 'init', array( __CLASS__, 'register_coordinates' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_coords_scripts' ) );
	}

	/**
	 * Register REST API routes for coordinates filters
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'b45/v1',
			'/coords',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'rest_get_coords' ),
			)
		);

		register_rest_route(
			'b45/v1',
			'/by-coords',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'args'                => array(
					'coords' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
				'callback'            => array( __CLASS__, 'rest_get_by_coords' ),
			)
		);
	}

	/**
	 * REST: Return posts with existing non-empty coords
	 */
	public static function rest_get_coords() {
		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array(
					'key'     => 'b45_coords',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'b45_coords',
					'value'   => '',
					'compare' => '!=',
				),
			),
		);

		$posts = array();
		$query = new WP_Query( $args );

		foreach ( $query->posts as $post_id ) {
			$coords = (string) get_post_meta( $post_id, 'b45_coords', true );

			if ( empty( $coords ) ) {
				continue;
			}

			$coords = str_replace( ' ', '', $coords );

			$posts[] = array(
				'id'     => $post_id,
				'title'  => get_the_title( $post_id ),
				'coords' => $coords,
			);
		}

		return rest_ensure_response( $posts );
	}

	/**
	 * REST: Return posts by exact coords match
	 */
	public static function rest_get_by_coords( $request ) {
		$coords = trim( (string) $request->get_param( 'coords' ) );

		// Minimal validation: "lat, lng" with decimals and optional minus
		if ( ! preg_match( '/^-?\d{1,2}\.\d+,\s*-?\d{1,3}\.\d+$/', $coords ) ) {
			return new WP_Error( 'bad_coords', 'Bad coords format', array( 'status' => 400 ) );
		}

		$coords = str_replace( ' ', '', $coords );

		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'b45_coords',
					'value'   => $coords,
					'compare' => '=',
				),
			),
		);

		$posts = array();
		$query = new WP_Query( $args );

		foreach ( $query->posts as $post_id ) {
			$posts[] = array(
				'id'     => $post_id,
				'title'  => get_the_title( $post_id ),
				'coords' => (string) get_post_meta( $post_id, 'b45_coords', true ),
				'link'   => get_permalink( $post_id ),
			);
		}

		return rest_ensure_response( $posts );
	}

	/**
	 * Register custom taxonomies
	 */
	public static function register_taxonomies() {
		register_taxonomy(
			'artist',
			array( 'post' ),
			array(
				'label'             => esc_html__( 'Artists', 'blok45' ),
				'labels'            => array(
					'name'          => esc_html__( 'Artists', 'blok45' ),
					'singular_name' => esc_html__( 'Artist', 'blok45' ),
				),
				'public'            => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'hierarchical'      => false,
				'show_admin_column' => true,
			)
		);

		register_taxonomy(
			'years',
			array( 'post' ),
			array(
				'label'             => esc_html__( 'Years', 'blok45' ),
				'labels'            => array(
					'name'          => esc_html__( 'Years', 'blok45' ),
					'singular_name' => esc_html__( 'Year', 'blok45' ),
				),
				'public'            => true,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'hierarchical'      => false,
				'show_admin_column' => true,
			)
		);
	}

	/*
	 * Register custom post meta for coordinates
	 */
	public static function register_coordinates() {
		register_post_meta(
			'post',
			'b45_coords',
			array(
				'type'          => 'string',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' ); },
			)
		);
	}

	public static function enqueue_coords_scripts( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || $screen->post_type !== 'post' ) {
			return;
		}

		$src = get_stylesheet_directory_uri() . '/admin/coords-panel.js';
		wp_enqueue_script(
			'blok45-filters-panel',
			$src,
			array(
				'wp-plugins',
				'wp-edit-post',
				'wp-components',
				'wp-data',
				'wp-i18n',
			),
			filemtime( get_stylesheet_directory() . '/admin/coords-panel.js' ),
			true
		);
	}
}

/**
 * Load current module environment
 */
Blok45_Modules_Filters::load_module();
