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
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_public_scripts' ) );
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

		// Filters endpoint
		register_rest_route(
			'b45/v1',
			'/filter',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'args'                => array(
					'artist' => array(
						'required' => false,
						'type'     => 'string',
					),
					'years'  => array(
						'required' => false,
						'type'     => 'string',
					),
					'page'   => array(
						'required'          => false,
						'type'              => 'integer',
						'validate_callback' => function ( $value ) {
							return absint( $value ) >= 1;
						},
					),
					'coords' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'sort'   => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
				),
				'callback'            => array( __CLASS__, 'rest_filter_posts' ),
			)
		);
	}

	/**
	 * Enqueue public filters script (front page)
	 */
	public static function enqueue_public_scripts() {
		if ( ! is_front_page() ) {
			return;
		}

		$handle = 'blok45-filters';
		$src    = get_template_directory_uri() . '/assets/filters.min.js';
		$ver    = file_exists( get_template_directory() . '/assets/filters.min.js' ) ? filemtime( get_template_directory() . '/assets/filters.min.js' ) : null;

		$current_page = max( 1, absint( get_query_var( 'paged' ) ) );
		$next_page    = $current_page + 1;
		$has_more     = false;

		global $wp_query;

		if ( $wp_query instanceof WP_Query ) {
			$has_more = ( $current_page < (int) $wp_query->max_num_pages );
		}

		wp_enqueue_script( $handle, $src, array(), $ver, true );
		wp_localize_script(
			$handle,
			'B45Filters',
			array(
				'endpoint'  => esc_url_raw( rest_url( 'b45/v1/filter' ) ),
				'startPage' => max( 2, $next_page ),
				'hasMore'   => (bool) $has_more,
				'emptyMessage' => esc_html__( 'Nothing found for the selected filters.', 'blok45' ),
			)
		);
	}

	/**
	 * REST: Return posts filtered by taxonomies
	 *
	 * @param WP_REST_Request $request Current request object
	 *
	 * @return WP_REST_Response
	 */
	public static function rest_filter_posts( WP_REST_Request $request ) {
		$tax_query = array();
		$page      = max( 1, absint( $request->get_param( 'page' ) ) );

		usleep( 500000 );

		$artist_id = absint( $request->get_param( 'artist' ) );

		if ( $artist_id > 0 ) {
			$tax_query[] = array(
				'taxonomy' => 'artist',
				'field'    => 'term_id',
				'terms'    => array( $artist_id ),
				'operator' => 'IN',
			);
		}

		$year_token = trim( (string) $request->get_param( 'years' ) );

		if ( '' !== $year_token ) {
			$resolved_years = array_values( array_filter( array_map( 'absint', self::normalize_year_tokens_to_term_ids( array( $year_token ) ) ) ) );

			if ( ! empty( $resolved_years ) ) {
				$tax_query[] = array(
					'taxonomy' => 'years',
					'field'    => 'term_id',
					'terms'    => $resolved_years,
					'operator' => 'IN',
				);
			}
		}

		$coords_filter = self::normalize_coords_value( $request->get_param( 'coords' ) );

		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 24,
			'no_found_rows'  => false,
			'paged'          => $page,
		);

		if ( $coords_filter ) {
			if ( ! isset( $args['meta_query'] ) || ! is_array( $args['meta_query'] ) ) {
				$args['meta_query'] = array();
			}

			$args['meta_query'][] = array(
				'key'     => 'b45_coords',
				'value'   => $coords_filter,
				'compare' => '=',
			);
		}

		$sort = sanitize_key( $request->get_param( 'sort' ) );

		switch ( $sort ) {
			case 'newest':
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				break;

			case 'oldest':
				$args['orderby'] = 'date';
				$args['order']   = 'ASC';
				break;

			case 'rating':
				$meta_key = class_exists( 'Blok45_Modules_Rating' ) ? Blok45_Modules_Rating::META_KEY : 'b45_rating';

				$args['meta_key']   = $meta_key;
				$args['meta_type']  = 'NUMERIC';
				$args['orderby']    = array(
					'meta_value_num' => 'DESC',
					'date'           => 'DESC',
				);
				break;

			default:
				break;
		}

		if ( ! empty( $tax_query ) ) {
			if ( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'AND';
			}

			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$query = new WP_Query( $args );

		ob_start();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				get_template_part( 'template-parts/card' );
			}

			wp_reset_postdata();
		}

		$html     = ob_get_clean();
		$has_more = ( $page < (int) $query->max_num_pages );

		return rest_ensure_response(
			array(
				'html'     => $html,
				'has_more' => (bool) $has_more,
				'page'     => $page,
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
	 * Normalize coordinates string to "lat,lng" format or return empty string on failure.
	 *
	 * @param string $raw Raw incoming value.
	 *
	 * @return string
	 */
	protected static function normalize_coords_value( $raw ) {
		$coords = trim( (string) $raw );

		if ( '' === $coords ) {
			return '';
		}

		if ( ! preg_match( '/^-?\d{1,2}\.\d+,\s*-?\d{1,3}\.\d+$/', $coords ) ) {
			return '';
		}

		return str_replace( ' ', '', $coords );
	}

	/**
	 * Return predefined year ranges used by the filters UI and API.
	 *
	 * @return array
	 */
	public static function get_year_ranges() {
		return array(
			array(
				'slug'  => 'from-2005',
				'label' => '< 2005',
				'min'   => null,
				'max'   => 2004,
			),
			array(
				'slug'  => '2005-2009',
				'label' => '2005-2009',
				'min'   => 2005,
				'max'   => 2009,
			),
			array(
				'slug'  => '2010-2014',
				'label' => '2010-2014',
				'min'   => 2010,
				'max'   => 2014,
			),
			array(
				'slug'  => '2015-2019',
				'label' => '2015-2019',
				'min'   => 2015,
				'max'   => 2019,
			),
			array(
				'slug'  => 'after-2020',
				'label' => '> 2020',
				'min'   => 2020,
				'max'   => null,
			),
		);
	}

	/**
	 * REST: Return posts by exact coords match
	 */
	public static function rest_get_by_coords( $request ) {
		$coords = self::normalize_coords_value( $request->get_param( 'coords' ) );

		if ( '' === $coords ) {
			return new WP_Error( 'bad_coords', 'Bad coords format', array( 'status' => 400 ) );
		}

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

	/**
	 * Enqueue admin scripts for coordinates meta box
	 *
	 * @param string $hook Current admin page
	 */
	public static function enqueue_coords_scripts( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || $screen->post_type !== 'post' ) {
			return;
		}

		wp_enqueue_script(
			'blok45-filters-panel',
			get_stylesheet_directory_uri() . '/admin/coords-panel.js',
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

	/**
	 * Get filters meta for current entry
	 *
	 * @param array $links Existing links
	 *
	 * @return string
	 */
	public static function get_meta( $links = array() ) {
		$taxonomies = array( 'artist', 'years' );

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( get_the_ID(), $taxonomy );

			if ( is_array( $terms ) && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$links[] = sprintf(
						'<a href="%s">%s</a>',
						esc_url( get_term_link( $term ) ),
						esc_html( $term->name )
					);
				}
			}
		}

		return implode( '', $links );
	}

	/**
	 * Convert incoming year tokens (IDs or range slugs) to taxonomy term IDs.
	 *
	 * @param array $tokens Raw request values.
	 *
	 * @return array
	 */
	protected static function normalize_year_tokens_to_term_ids( $tokens ) {
		$tokens = array_unique( array_filter( array_map( 'trim', (array) $tokens ) ) );

		if ( empty( $tokens ) ) {
			return array();
		}

		$term_ids = array();
		$year_map = self::get_year_terms_map();

		if ( empty( $year_map ) ) {
			return array();
		}

		$range_map = null;

		foreach ( $tokens as $token ) {
			if ( is_numeric( $token ) ) {
				$term_ids[] = absint( $token );
				continue;
			}

			if ( null === $range_map ) {
				$range_map = array();

				foreach ( self::get_year_ranges() as $range ) {
					$slug = sanitize_title_with_dashes( $range['slug'] );

					$range_map[ $slug ] = $range;
				}
			}

			$slug = sanitize_title_with_dashes( $token );

			if ( ! isset( $range_map[ $slug ] ) ) {
				continue;
			}

			$range = $range_map[ $slug ];

			$min = isset( $range['min'] ) ? (int) $range['min'] : null;
			$max = isset( $range['max'] ) ? (int) $range['max'] : null;

			foreach ( $year_map as $year => $year_term_id ) {
				if ( ( null === $min || $year >= $min ) && ( null === $max || $year <= $max ) ) {
					$term_ids[] = $year_term_id;
				}
			}
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $term_ids ) ) ) );
	}

	/**
	 * Cached map of numeric year to taxonomy term ID.
	 *
	 * @return array
	 */
	protected static function get_year_terms_map() {
		static $cache = null;

		if ( null !== $cache ) {
			return $cache;
		}

		$cache = array();

		$terms = get_terms(
			array(
				'taxonomy'   => 'years',
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) ) {
			return $cache;
		}

		foreach ( $terms as $term ) {
			$year = (int) $term->name;

			if ( $year <= 0 ) {
				continue;
			}

			$cache[ $year ] = (int) $term->term_id;
		}

		return $cache;
	}
}

/**
 * Load current module environment
 */
Blok45_Modules_Filters::load_module();
