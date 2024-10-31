<?php

/**
 * Plugin Name: Noor Starter Templates
 * Description: Choose the prebuilt website and click to import.
 * Version: 1.0.5
 * Author: PixelDima
 * Author URI: https://pixeldima.com/
 * License: GPLv2 or later
 * Text Domain: noor-starter-templates
 *
 * @package Noor Starter Templates
 */

// Block direct access to the main plugin file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! version_compare( PHP_VERSION, '7.0', '>=' ) ) {
	add_action( 'admin_notices', 'noor_starter_old_php_admin_error_notice' );
} else {
	if ( is_admin() ) {
		require_once 'class-noor-starter-templates.php';
	}
}
/**
 * Display an admin error notice when PHP is older the version 5.3.2.
 * Hook it to the 'admin_notices' action.
 */
function noor_starter_old_php_admin_error_notice() {
	$message = __( 'The Noor Starter templates plugin requires at least PHP 7.0 to run properly. Please contact your hosting company and ask them to update the PHP version of your site to at least PHP 7.0. We strongly encourage you to update to 7.3+', 'noor-starter-templates' );

	printf( '<div class="notice notice-error"><p>%1$s</p></div>', wp_kses_post( $message ) );
}
