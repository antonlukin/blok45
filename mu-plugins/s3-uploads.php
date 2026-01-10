<?php
/**
 * Plugin Name: S3 Uploads tweaks
 * Description: Adjusts the S3 Uploads client to work with DigitalOcean Spaces settings.
 * Author: Anton Lukin
 * Author URI: https://lukin.me
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$autoload = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

final class Blok45_S3_Uploads_Tweaks {
	/**
	 * Bootstrap static hooks.
	 */
	public static function init(): void {
		add_filter( 's3_uploads_s3_client_params', array( __CLASS__, 'configure_s3_client' ) );
	}

	/**
	 * Ensure the DigitalOcean Spaces endpoint and other client params are set.
	 *
	 * @param array $params API client arguments.
	 *
	 * @return array
	 */
	public static function configure_s3_client( array $params ): array {
		$params['endpoint'] = 'https://ams3.digitaloceanspaces.com';
		$params['debug']    = false;

		return $params;
	}
}

Blok45_S3_Uploads_Tweaks::init();
