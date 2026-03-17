<?php
/**
 * QR redirect helpers for short object URLs.
 *
 * @package blok45
 * @since 1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Blok45_Modules_QR {
	const QUERY_VAR = 'blok45_qr_slug';

	/**
	 * Bootstrap QR redirects.
	 */
	public static function load_module() {
		add_action( 'init', array( __CLASS__, 'register_rewrite_rule' ) );
		add_filter( 'query_vars', array( __CLASS__, 'register_query_var' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect' ), 1 );
	}

	/**
	 * Register /qr/{slug}/ rewrite rule.
	 */
	public static function register_rewrite_rule() {
		add_rewrite_rule(
			'^qr/([^/]+)/?$',
			sprintf( 'index.php?%s=$matches[1]', self::QUERY_VAR ),
			'top'
		);
	}

	/**
	 * Register public query var for QR redirects.
	 *
	 * @param array $vars Existing public query vars.
	 *
	 * @return array
	 */
	public static function register_query_var( $vars ) {
		$vars[] = self::QUERY_VAR;

		return array_unique( $vars );
	}

	/**
	 * Redirect QR short URLs to canonical object URLs.
	 */
	public static function maybe_redirect() {
		$post_slug = sanitize_title_for_query( (string) get_query_var( self::QUERY_VAR ) );

		if ( '' === $post_slug ) {
			return;
		}

		$post = get_page_by_path( $post_slug, OBJECT, 'post' );

		if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status ) {
			self::set_not_found();
			return;
		}

		$target_url = get_permalink( $post );

		if ( empty( $target_url ) ) {
			self::set_not_found();
			return;
		}

		$utm_query = '';

		if ( class_exists( 'Blok45_Modules_Settings' ) && method_exists( 'Blok45_Modules_Settings', 'get_qr_utm_query' ) ) {
			$utm_query = Blok45_Modules_Settings::get_qr_utm_query();
		}

		if ( '' !== $utm_query ) {
			$target_url = self::append_query_string( $target_url, $utm_query );
		}

		wp_safe_redirect( $target_url, 301 );
		exit;
	}

	/**
	 * Append a pre-sanitized query string to a URL.
	 *
	 * @param string $url          Base URL.
	 * @param string $query_string Query string without leading question mark.
	 *
	 * @return string
	 */
	protected static function append_query_string( $url, $query_string ) {
		$query_string = ltrim( trim( (string) $query_string ), '?' );

		if ( '' === $query_string ) {
			return $url;
		}

		$query_args = array();
		wp_parse_str( $query_string, $query_args );

		if ( empty( $query_args ) ) {
			return $url;
		}

		return add_query_arg( $query_args, $url );
	}

	/**
	 * Convert the current request into a 404 response.
	 */
	protected static function set_not_found() {
		global $wp_query;

		if ( $wp_query instanceof WP_Query ) {
			$wp_query->set_404();
		}

		status_header( 404 );
		nocache_headers();
	}
}

Blok45_Modules_QR::load_module();
