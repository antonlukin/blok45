<?php
/**
 * Plugin Name: Nginx Cache Clear
 * Description: Adds an admin-bar button to clear Nginx static cache via shell script.
 * Author: Anton Lukin
 * Author URI: https://lukin.ne
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Blok45_Nginx_Cache_Clear {

	/**
	 * Bootstrap static hooks.
	 */
	public static function init(): void {
		add_action( 'admin_bar_menu', array( __CLASS__, 'add_adminbar_button' ), 1000 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_clear_cache' ) );
	}

	/**
	 * Add "Clear Nginx Cache" admin bar link for admins.
	 */
	public static function add_adminbar_button( $admin_bar ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$admin_bar->add_menu(
			array(
				'id'    => 'blok45_clear_nginx_cache',
				'title' => esc_html__( 'Clear Nginx Cache', 'pchela-theme' ),
				'href'  => wp_nonce_url( admin_url( '?clear_nginx_cache=1' ), 'clear_nginx_cache_action' ),
				'meta'  => array(
					'title' => esc_html__( 'Clear Nginx Cache', 'pchela-theme' ),
				),
			)
		);
	}

	/**
	 * Execute clearing logic if requested.
	 */
	public static function maybe_clear_cache(): void {
		if ( ! isset( $_GET['clear_nginx_cache'] ) ) {
			return;
		}

		if ( current_user_can( 'manage_options' ) && check_admin_referer( 'clear_nginx_cache_action' ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_shell_exec
			shell_exec( 'sudo /srv/http/blok45.art/options/clear-nginx-cache.sh' );

			wp_safe_redirect( admin_url() );
			exit;
		}
	}
}

Blok45_Nginx_Cache_Clear::init();
