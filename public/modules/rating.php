<?php
/**
 * Simple post rating stored in post meta.
 *
 * @package blok45
 * @since 1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Blok45_Modules_Rating {
	const META_KEY      = 'b45_rating';
	const STORAGE_KEY   = 'b45_rating_posts';
	const SCRIPT_HANDLE = 'blok45-rating';

	/**
	 * Bootstrap rating module.
	 */
	public static function load_module() {
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_public_assets' ) );
	}

	/**
	 * Ensure rating meta is registered.
	 */
	public static function register_meta() {
		register_post_meta(
			'post',
			self::META_KEY,
			array(
				'type'              => 'integer',
				'default'           => 0,
				'single'            => true,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Register REST routes for rating interactions.
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'b45/v1',
			'/rating',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'rest_update_rating' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'post'  => array(
							'required' => true,
							'type'     => 'integer',
						),
						'liked' => array(
							'required' => true,
							'type'     => 'boolean',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'rest_get_rating' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'post' => array(
							'required' => true,
							'type'     => 'integer',
						),
					),
				),
			)
		);
	}

	/**
	 * Expose rating data to the rating script.
	 */
	public static function enqueue_public_assets() {
		$asset_path = get_template_directory() . '/assets/rating.min.js';

		if ( ! file_exists( $asset_path ) ) {
			return;
		}

		$src = get_template_directory_uri() . '/assets/rating.min.js';
		$ver = filemtime( $asset_path );

		wp_enqueue_script( self::SCRIPT_HANDLE, $src, array(), $ver, true );
		wp_localize_script(
			self::SCRIPT_HANDLE,
			'B45Rating',
			array(
				'endpoint'   => esc_url_raw( rest_url( 'b45/v1/rating' ) ),
				'storageKey' => self::STORAGE_KEY,
			)
		);
	}

	/**
	 * Return rating for requested post.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 *
	 * @return WP_REST_Response|
	 */
	public static function rest_get_rating( WP_REST_Request $request ) {
		$post_id = absint( $request->get_param( 'post' ) );

		if ( ! self::is_valid_post( $post_id ) ) {
			return rest_ensure_response(
				array(
					'post'    => $post_id,
					'rating'  => 0,
					'success' => false,
				)
			);
		}

		return rest_ensure_response(
			array(
				'post'    => $post_id,
				'rating'  => self::get_post_rating_value( $post_id ),
				'success' => true,
			)
		);
	}

	/**
	 * Increment rating for requested post.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 *
	 * @return WP_REST_Response
	 */
	public static function rest_update_rating( WP_REST_Request $request ) {
		$post_id = absint( $request->get_param( 'post' ) );
		$liked   = rest_sanitize_boolean( $request->get_param( 'liked' ) );

		if ( ! self::is_valid_post( $post_id ) ) {
			return new WP_REST_Response( array( 'message' => esc_html__( 'Invalid post.', 'blok45' ) ), 400 );
		}

		self::apply_rating_delta( $post_id, $liked ? 1 : -1 );
		$rating = self::get_post_rating_value( $post_id );

		return rest_ensure_response(
			array(
				'post'    => $post_id,
				'rating'  => $rating,
				'liked'   => (bool) $liked,
				'success' => true,
			)
		);
	}

	/**
	 * Apply atomic rating delta.
	 *
	 * @param int $post_id Post identifier.
	 * @param int $delta   Change to apply (positive or negative).
	 */
	protected static function apply_rating_delta( $post_id, $delta ) {
		$delta = (int) $delta;

		if ( 0 === $delta ) {
			return;
		}

		global $wpdb;

		$meta_key = self::META_KEY;

		$updated = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta}
				SET meta_value = GREATEST( 0, CAST(meta_value AS SIGNED) + %d )
				WHERE post_id = %d AND meta_key = %s",
				$delta,
				$post_id,
				$meta_key
			)
		);

		if ( $updated === 0 && $delta > 0 ) {
			add_post_meta( $post_id, $meta_key, $delta, true );
		}
	}

	/**
	 * Helper: verify that post exists and can be rated.
	 *
	 * @param int $post_id Post identifier.
	 */
	protected static function is_valid_post( $post_id ) {
		if ( $post_id <= 0 ) {
			return false;
		}

		$post = get_post( $post_id );

		if ( ! $post || 'publish' !== $post->post_status ) {
			return false;
		}

		return true;
	}

	/**
	 * Helper: get rating integer for a post.
	 *
	 * @param int $post_id Post identifier.
	 */
	public static function get_post_rating_value( $post_id ) {
		$value = get_post_meta( $post_id, self::META_KEY, true );

		if ( '' === $value ) {
			return 0;
		}

		return max( 0, (int) $value );
	}
}

/**
 * Load rating module.
 */
Blok45_Modules_Rating::load_module();
