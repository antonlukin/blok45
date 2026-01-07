<?php
/**
 * Plugin Name: S3 Uploads tweaks
 * Description: 
 * Author: Anton Lukin
 * Author URI: https://lukin.ne
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Filter S3 Uploads params.
add_filter( 's3_uploads_s3_client_params', function ( $params ) {
	$params['endpoint'] = 'https://ams3.digitaloceanspaces.com';
	$params['use_path_style_endpoint'] = false;
	$params['debug'] = false; // Set to true if uploads are failing.
	return $params;
} );
