<?php
/**
 * Plugin Name: Cloudflare Cache Clear
 * Description: Adds an admin-bar button to clear the Cloudflare cache using the Cloudflare API.
 * Author: Anton Lukin
 * Author URI: https://lukin.me
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Blok45_Cloudflare_Cache_Clear {
	private const ACTION_PARAM = 'clear_cloudflare_cache';
	private const NOTICE_PARAM = 'cf_cache_cleared';
	private const NONCE_ACTION = 'clear_cloudflare_cache_action';
	private const NOTICE_KEY   = 'blok45_cf_cache_notice_';

	/**
	 * Bootstrap hooks.
	 */
	public static function init(): void {
		add_action( 'admin_bar_menu', array( __CLASS__, 'add_adminbar_button' ), 1000 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_clear_cache' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_notice' ) );
	}

	/**
	 * Append the clear cache link to the admin bar.
	 *
	 * @param WP_Admin_Bar $admin_bar Admin bar instance.
	 */
	public static function add_adminbar_button( $admin_bar ): void {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! is_object( $admin_bar ) || ! method_exists( $admin_bar, 'add_menu' ) ) {
			return;
		}

		$url = add_query_arg( self::ACTION_PARAM, '1', admin_url( 'index.php' ) );
		$url = wp_nonce_url( $url, self::NONCE_ACTION );

		$admin_bar->add_menu(
			array(
				'id'    => 'blok45_clear_cloudflare_cache',
				'title' => esc_html__( 'Clear Cloudflare Cache', 'blok45' ),
				'href'  => $url,
				'meta'  => array(
					'title' => esc_html__( 'Clear Cloudflare Cache', 'blok45' ),
				),
			)
		);
	}

	/**
	 * Trigger the Cloudflare cache purge when the query arg is present and the nonce passes.
	 */
	public static function maybe_clear_cache(): void {
		if ( ! isset( $_GET[ self::ACTION_PARAM ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! check_admin_referer( self::NONCE_ACTION ) ) {
			return;
		}

		$result = self::clear_cloudflare_cache();

		$notice_value = '1';

		if ( is_wp_error( $result ) ) {
			$notice_value = '0';

			$message = sprintf( '[%s] %s', $result->get_error_code(), $result->get_error_message() );

			self::store_notice_message( $message );

			error_log( 'Cloudflare cache purge failed: ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		$redirect_url = wp_get_referer();

		if ( ! is_string( $redirect_url ) || $redirect_url === '' ) {
			$redirect_url = admin_url( 'index.php' );
		}

		$redirect_url = remove_query_arg( array( self::ACTION_PARAM, '_wpnonce' ), $redirect_url );
		$redirect_url = add_query_arg( self::NOTICE_PARAM, $notice_value, $redirect_url );

		wp_safe_redirect( esc_url_raw( $redirect_url ) );
		exit;
	}

	/**
	 * Render a notice after a cache purge attempt.
	 */
	public static function render_notice(): void {
		if ( ! isset( $_GET[ self::NOTICE_PARAM ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$notice = sanitize_key( wp_unslash( $_GET[ self::NOTICE_PARAM ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $notice === '1' ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html__( 'Cloudflare cache successfully purged.', 'blok45' )
			);

			return;
		}

		$details = self::pull_notice_message();

		printf(
			'<div class="notice notice-error is-dismissible"><p><strong>%s</strong>%s</p></div>',
			esc_html__( 'Cloudflare cache flush failed.', 'blok45' ),
			$details !== '' ? ' <code>' . esc_html( $details ) . '</code>' : ''
		);
	}

	/**
	 * Call the Cloudflare API to purge the entire zone cache.
	 *
	 * @return true|WP_Error True on success, WP_Error describing the failure otherwise.
	 */
	private static function clear_cloudflare_cache() {
		$zone_id = self::get_zone_id();

		if ( $zone_id === '' ) {
			return new WP_Error( 'cf_no_zone_id', 'CLOUDFLARE_ZONE_ID constant is not defined or empty.' );
		}

		$token = self::get_api_token();

		if ( $token === '' ) {
			return new WP_Error( 'cf_no_token', 'CLOUDFLARE_API_TOKEN constant is not defined or empty.' );
		}

		$response = wp_remote_post(
			sprintf( 'https://api.cloudflare.com/client/v4/zones/%s/purge_cache', rawurlencode( $zone_id ) ),
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $token,
				),
				'body'    => wp_json_encode( array( 'purge_everything' => true ) ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'cf_http_error',
				sprintf( 'wp_remote_post failed: %s (%s)', $response->get_error_message(), $response->get_error_code() )
			);
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body        = (string) wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'cf_bad_response',
				sprintf( 'HTTP %d, non-JSON body: %s', $status_code, self::truncate( $body ) )
			);
		}

		if ( ( $data['success'] ?? false ) === true && $status_code >= 200 && $status_code < 300 ) {
			return true;
		}

		$errors = array();

		foreach ( (array) ( $data['errors'] ?? array() ) as $error ) {
			$code    = isset( $error['code'] ) ? (string) $error['code'] : '?';
			$message = isset( $error['message'] ) ? (string) $error['message'] : '';

			$errors[] = sprintf( '%s: %s', $code, $message );
		}

		if ( $errors === array() ) {
			$errors[] = self::truncate( $body );
		}

		return new WP_Error(
			'cf_api_error',
			sprintf( 'HTTP %d, Cloudflare errors: %s', $status_code, implode( '; ', $errors ) )
		);
	}

	/**
	 * Store the failure details for the current user until the notice is rendered.
	 */
	private static function store_notice_message( string $message ): void {
		set_transient( self::NOTICE_KEY . get_current_user_id(), $message, MINUTE_IN_SECONDS );
	}

	/**
	 * Read and delete the stored failure details for the current user.
	 */
	private static function pull_notice_message(): string {
		$key     = self::NOTICE_KEY . get_current_user_id();
		$message = get_transient( $key );

		delete_transient( $key );

		return is_string( $message ) ? $message : '';
	}

	/**
	 * Keep raw bodies short enough for a notice.
	 */
	private static function truncate( string $text, int $limit = 300 ): string {
		$text = trim( $text );

		if ( $text === '' ) {
			return '(empty body)';
		}

		if ( strlen( $text ) <= $limit ) {
			return $text;
		}

		return substr( $text, 0, $limit ) . '…';
	}

	/**
	 * Retrieve the Cloudflare zone ID from the constant.
	 */
	private static function get_zone_id(): string {
		if ( ! defined( 'CLOUDFLARE_ZONE_ID' ) ) {
			return '';
		}

		return trim( (string) CLOUDFLARE_ZONE_ID );
	}

	/**
	 * Retrieve the Cloudflare API token from the constant.
	 */
	private static function get_api_token(): string {
		if ( ! defined( 'CLOUDFLARE_API_TOKEN' ) ) {
			return '';
		}

		return trim( (string) CLOUDFLARE_API_TOKEN );
	}
}

Blok45_Cloudflare_Cache_Clear::init();
