<?php
/**
 * Load more (infinite scroll) for front page
 * - Enqueues a standalone JS bundle for the front page
 * - Exposes REST endpoint returning rendered cards HTML by page
 *
 * @package blok45
 * @since 1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Blok45_Modules_LoadMore {
	/**
	 * Init hooks
	 */
	public static function load_module() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
	}

	/**
	 * Enqueue standalone bundle on front page only
	 */
	public static function enqueue_assets() {
		if ( ! is_front_page() ) {
			return;
		}

		wp_enqueue_script(
			'blok45-loadmore',
			get_template_directory_uri() . '/assets/loadmore.min.js',
			array(),
			filemtime( get_template_directory() . '/assets/loadmore.min.js' ),
			true
		);

		$settings = array(
			'endpoint'  => esc_url_raw( rest_url( 'b45/v1/more' ) ),
			'startPage' => max( 2, (int) get_query_var( 'paged' ) + 1 ),
		);

		wp_localize_script( 'blok45-loadmore', 'blok45_more', $settings );
	}

	/**
	 * Register REST API route for fetching more posts (paged)
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'b45/v1',
			'/more',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'args'                => array(
					'page' => array(
						'required' => true,
						'type'     => 'integer',
						'minimum'  => 1,
					),
				),
				'callback'            => array( __CLASS__, 'rest_get_more' ),
			)
		);
	}

	/**
	 * Callback: returns rendered cards HTML for requested page
	 */
	public static function rest_get_more( WP_REST_Request $req ) {
		$page = max( 1, absint( $req->get_param( 'page' ) ) );

		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'paged'          => $page,
			'posts_per_page' => (int) get_option( 'posts_per_page', 10 ),
			'no_found_rows'  => false,
		);

		$query = new WP_Query( $args );

		sleep( 1 ); // Simulate loading delay

		if ( ! $query->have_posts() ) {
			return new WP_REST_Response(
				array(
					'html'     => '',
					'has_more' => false,
				),
				200
			);
		}

		ob_start();
		while ( $query->have_posts() ) {
			$query->the_post();
			get_template_part( 'template-parts/card' );
		}
		wp_reset_postdata();

		$output = ob_get_clean();

		// Check if there are more pages
		$has_more = ( $page < (int) $query->max_num_pages );

		return rest_ensure_response(
			array(
				'html'     => $output,
				'has_more' => (bool) $has_more,
				'page'     => $page,
			),
			200
		);
	}
}

/**
 * Load current module environment
 */
Blok45_Modules_LoadMore::load_module();
