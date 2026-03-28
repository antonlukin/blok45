<?php
/**
 * QR redirect helpers for short marketing URLs.
 *
 * @package blok45
 * @since 1.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Blok45_Modules_QR {
	const QUERY_FLAG        = 'blok45_qr';
	const QUERY_CONTENT_VAR = 'blok45_qr_content';

	/**
	 * Bootstrap QR redirects.
	 */
	public static function load_module() {
		add_action( 'init', array( __CLASS__, 'register_rewrite_rules' ) );
		add_filter( 'query_vars', array( __CLASS__, 'register_query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect' ), 1 );
	}

	/**
	 * Register QR rewrite rules.
	 */
	public static function register_rewrite_rules() {
		add_rewrite_rule(
			'^qr/?$',
			sprintf( 'index.php?%s=1', self::QUERY_FLAG ),
			'top'
		);

		add_rewrite_rule(
			'^qr/([^/]+)/?$',
			sprintf( 'index.php?%1$s=1&%2$s=$matches[1]', self::QUERY_FLAG, self::QUERY_CONTENT_VAR ),
			'top'
		);
	}

	/**
	 * Register public query vars for QR redirects.
	 *
	 * @param array $vars Existing public query vars.
	 *
	 * @return array
	 */
	public static function register_query_vars( $vars ) {
		$vars[] = self::QUERY_FLAG;
		$vars[] = self::QUERY_CONTENT_VAR;

		return array_unique( $vars );
	}

	/**
	 * Redirect QR short URLs to the homepage with UTM parameters.
	 */
	public static function maybe_redirect() {
		if ( '1' !== (string) get_query_var( self::QUERY_FLAG ) ) {
			return;
		}

		$query_args = array(
			'utm_source' => 'qr',
		);

		$content = sanitize_text_field( (string) get_query_var( self::QUERY_CONTENT_VAR ) );

		if ( '' !== $content ) {
			$query_args['utm_content'] = $content;
		}

		$target_url = add_query_arg( $query_args, home_url( '/' ) );

		wp_safe_redirect( $target_url, 301 );
		exit;
	}
}

Blok45_Modules_QR::load_module();
