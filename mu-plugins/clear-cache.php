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

		$cleared = self::clear_cloudflare_cache();

		$notice_value   = '0';
		$notice_message = '';

		if ( $cleared === true ) {
			$notice_value = '1';
		} elseif ( is_wp_error( $cleared ) ) {
			$notice_message = $cleared->get_error_message();
		}

		if ( $notice_message !== '' ) {
			self::store_notice_message( $notice_message );
		}

		$redirect_url = wp_get_referer();

		if ( ! is_string( $redirect_url ) || $redirect_url === '' ) {
			$redirect_url = admin_url( 'index.php' );
		}

		$redirect_url = remove_query_arg( self::ACTION_PARAM, $redirect_url );
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
				'<div class="notice notice-sucess is-dismissible"><p>%s</p></div>',
				esc_html__( 'Cloudflare cache successfuly purged.', 'blok45' )
			);

			return;
		}

		printf(
			'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
			esc_html__( 'Cloudflare cache flush failed. Check configuration.', 'blok45' )
		);
	}

	/**
	 * Call the Cloudflare API to purge the entire zone cache.
	 */
	private static function clear_cloudflare_cache(): bool {
		$zone_id = self::get_zone_id();

		if ( empty( $zone_id ) ) {
			return false;
		}

		$token = self::get_api_token();

		if ( empty( $token ) ) {
			return false;
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
			return false;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( $status_code < 200 || $status_code >= 300 ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( ! is_string( $body ) || $body === '' ) {
			return false;
		}

		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return false;
		}

		if ( ! array_key_exists( 'success', $data ) ) {
			return false;
		}

		return $data['success'] === true;
	}

	/**
	 * Retrieve the Cloudflare zone ID from the constant.
	 */
	private static function get_zone_id(): string {
		if ( ! defined( 'CLOUDFLARE_ZONE_ID' ) ) {
			return '';
		}

		$zone_id = trim( (string) CLOUDFLARE_ZONE_ID );

		if ( empty( $zone_id ) ) {
			return '';
		}

		return $zone_id;
	}

	/**
	 * Retrieve the Cloudflare API token from the constant.
	 */
	private static function get_api_token(): string {
		if ( ! defined( 'CLOUDFLARE_API_TOKEN' ) ) {
			return '';
		}

		$token = trim( (string) CLOUDFLARE_API_TOKEN );

		if ( empty( $token ) ) {
			return '';
		}

		return $token;
	}
}

Blok45_Cloudflare_Cache_Clear::init();
