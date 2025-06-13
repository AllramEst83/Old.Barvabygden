<?php
// Exit if file accessed directly.
defined( 'WPINC' ) or die(); // No Direct Access

/**
 * The core plugin class that is used to define functions,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path( __FILE__ ) . 'classes/class-wpr.php';

// Load WP-Rocket
if ( class_exists( 'Onecom_Wp_Rocket' ) ) {
	$wpr_object = new Onecom_Wp_Rocket();
	$wpr_object->init();
}
