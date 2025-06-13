<?php

/**
 * Plugin name: one.com Maintenance Mode
 * Plugin uri: https://one.com
 * Author: one.com
 * Version: 3.0.10
 * Plugin URI: https://www.one.com/en/wordpress-hosting
 * Description: Make your website private when editing it. Maintenance Mode tells the visitors that your website is under construction. With the Pro version, you get more customization options.
 * Text Domain: onecom-uc
 * Domain Path: /languages
 * License: GPL v2 or later
 *
 *  Copyright 2025 one.com
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 */

// Exit if file accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define essential constants.
define( 'ONECOM_UC_PLUGIN_MAIN_FILE', __FILE__ );
define( 'ONECOM_UC_PLUGIN_URL', plugin_dir_path( __FILE__ ) );
define( 'ONECOM_UC_DIR_URL', plugin_dir_url( __FILE__ ) );

// The code that runs during plugin activation.
function activate_under_construction() {
	include_once ONECOM_UC_PLUGIN_URL . 'inc/classes/class-ocuc-activator.php';
	$activate_obj = new OCUC_Activator();
	$activate_obj->uc_activate_actions();
}

// The code that runs during plugin de-activation.
function deactivate_under_construction() {
	include_once ONECOM_UC_PLUGIN_URL . 'inc/classes/class-ocuc-deactivator.php';
	$deactivate_obj = new OCUC_Deactivator();
	$deactivate_obj->uc_deactivate_actions();
}

// Activation and deactivation hook
register_activation_hook( __FILE__, 'activate_under_construction' );
register_deactivation_hook( __FILE__, 'deactivate_under_construction' );

// Because ajax handles works well outside class
add_action( 'wp_ajax_oc_newsleter_sub', array( 'OCUC_Newsletter', 'newsletter_cb' ) );
add_action( 'wp_ajax_nopriv_oc_newsleter_sub', array( 'OCUC_Newsletter', 'newsletter_cb' ) );
add_action( 'wp_ajax_ocuc_wp_time', 'ocuc_wp_time' );
add_action( 'wp_ajax_nopriv_ocuc_wp_time', 'ocuc_wp_time' );
function ocuc_wp_time() {
	echo (int) strtotime( current_time( 'Y-m-d H:i:s' ) );
	wp_die();
}

/**
 * The main plugin class that is used to define:
 * * admin-specific hooks,
 * * and public-facing site hooks.
 */
require_once plugin_dir_path( __FILE__ ) . 'inc/classes/class-ocuc-loader.php';

// Load plugin's essential files
$loader = new OCUC_Loader();
$loader->init_loader();

// Load assets
$assets = new OCUC_Assets();
$assets->init_assets();

// Initialize admin settings
if ( is_admin() ) {
	$admin_settings_obj = new OCUC_Admin_Settings();
	$admin_settings_obj->init_admin_settings();
}

// Hook under-construction frontend page
$themes = new OCUC_Themes();
$themes->init_theme();
