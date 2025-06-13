<?php
/**
 * Plugin name:     one.com Spam Protection
 * Plugin uri:      https://one.com
 * Author:          one.com
 * Author URI:        https://one.com/
 * Version:         1.0.32
 * Plugin URI:      https://www.one.com/en/wordpress-hosting
 * Description:     Protect your website from spambots commenting or registering on it.
 * Text Domain:     onecom-sp
 * Domain Path:     /languages
 * License:         GPL v2 or later
 *
 *    Copyright 2025 one.com
 *
 *    This program is free software; you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation; either version 2 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 */

// Exit if file accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'ONECOM_SP_VERSION' ) ) {
	define( 'ONECOM_SP_VERSION', '1.0.32' );
}
if ( ! defined( 'ONECOM_PLUGIN_PATH' ) ) {
	define( 'ONECOM_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'ONECOM_SP_WP_URL' ) ) {
	define( 'ONECOM_SP_WP_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'ONECOM_SP_PLUGIN_SLUG' ) ) {
	define( 'ONECOM_SP_PLUGIN_SLUG', dirname( plugin_basename( __FILE__ ) ) );
}

if ( ! defined( 'OC_SP_TEXTDOMAIN' ) ) {
	define( 'OC_SP_TEXTDOMAIN', 'onecom-sp' );
}

if ( ! defined( 'MIDDLEWARE_URL' ) ) {
	$api_version = 'v1.0';
	if ( isset( $_SERVER['ONECOM_WP_ADDONS_API'] ) && '' != $_SERVER['ONECOM_WP_ADDONS_API'] ) {
		$ONECOM_WP_ADDONS_API = $_SERVER['ONECOM_WP_ADDONS_API'];
	} elseif ( defined( 'ONECOM_WP_ADDONS_API' ) && ONECOM_WP_ADDONS_API != '' && ONECOM_WP_ADDONS_API ) {
		$ONECOM_WP_ADDONS_API = ONECOM_WP_ADDONS_API;
	} else {
		$ONECOM_WP_ADDONS_API = 'https://wpapi.one.com/';
	}
	$ONECOM_WP_ADDONS_API = rtrim( $ONECOM_WP_ADDONS_API, '/' );
	define( 'MIDDLEWARE_URL', $ONECOM_WP_ADDONS_API . '/api/' . $api_version );
}

require_once 'inc' . DIRECTORY_SEPARATOR . 'functions.php';
spl_autoload_register(
	function ( $class ) {
		$class_file = ONECOM_PLUGIN_PATH . 'inc' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'class-' .
					strtolower(
						preg_replace(
							array( '/([A-Z]+)/', '/_([A-Z]+)([A-Z][a-z])/' ),
							array( '-$1', '-$1-$2' ),
							lcfirst( $class )
						)
					) . '.php';
		if ( file_exists( $class_file ) ) {
			require_once $class_file;
		}
	}
);
if ( ! class_exists( 'ONECOMUPDATER' ) ) {
	require_once ONECOM_PLUGIN_PATH . '/inc/update.php';
}
if ( ! ( class_exists( 'OTPHP\TOTP' ) && class_exists( 'ParagonIE\ConstantTime\Base32' ) ) ) {
	require_once ONECOM_PLUGIN_PATH . 'inc' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'validator.php';
}

/**
 * Include stats script file
 **/
if ( ! class_exists( 'OCPushStats' ) ) {
	include_once ONECOM_PLUGIN_PATH . '/inc/lib/OCPushStats.php';
}

if ( ! class_exists( 'Onecom_Nested_Menu' ) ) {
	require_once plugin_dir_path( __FILE__ ) . '/inc/lib/onecom-nested-menu.php';
	$onecom_menu = new Onecom_Nested_Menu();
	$onecom_menu->init();
}

$onecom_sp_init     = new OnecomSp();
$onecom_sp_settings = new OnecomSpSettings();
if ( onecomsp_is_premium() ) {
	$onecom_sp_api_check  = new OnecomSpApiCheck();
	$onecom_sp_init_check = new OnecomSpWebsiteCheck();
}

register_activation_hook( __FILE__, 'oc_spam_protection_activate' );
