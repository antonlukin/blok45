<?php
/**
 * Plugin Name: Hide Login
 * Description: Forces WPS Hide Login to use predefined slugs and blocks default admin/login endpoints when the plugin is absent.
 * Author: Anton Lukin
 * Author URI: https://lukin.me
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Blok45_Hide_Login {
	/**
	 * Bootstrap the static hooks, no instances required.
	 */
	public static function init(): void {
		// If constants are not defined, nothing to do to be able to manage slugs via admin settings.
		if ( ! defined( 'BLOK45_LOGIN_SLUG' ) || ! defined( 'BLOK45_LOGIN_REDIRECT_SLUG' ) ) {
			return;
		}

		add_action( 'muplugins_loaded', array( __CLASS__, 'register_whl_slugs' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'detach_whl_settings' ), 20 );
	}

	/**
	 * Register option overrides if constants are available.
	 */
	public static function register_whl_slugs(): void {
		if ( ! empty( BLOK45_LOGIN_SLUG ) ) {
			add_filter(
				'pre_option_whl_page',
				static function (): string {
					return BLOK45_LOGIN_SLUG;
				}
			);
		}

		if ( ! empty( BLOK45_LOGIN_REDIRECT_SLUG ) ) {
			add_filter(
				'pre_option_whl_redirect_admin',
				static function (): string {
					return BLOK45_LOGIN_REDIRECT_SLUG;
				}
			);
		}
	}

	/**
	 * Remove WPS Hide Login settings elements via admin hooks.
	 */
	public static function detach_whl_settings(): void {
		if ( ! class_exists( '\WPS\WPS_Hide_Login\Plugin' ) ) {
			return;
		}

		$instance = \WPS\WPS_Hide_Login\Plugin::get_instance();

		remove_action( 'admin_init', array( $instance, 'admin_init' ) );
		remove_action( 'admin_menu', array( $instance, 'wps_hide_login_menu_page' ) );
	}
}

Blok45_Hide_Login::init();
