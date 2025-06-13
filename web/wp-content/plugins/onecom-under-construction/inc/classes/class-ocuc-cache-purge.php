<?php

/**
 * This class handles cache functions
 *
 * @since      0.3.0
 * @package    Under_Construction
 * @subpackage OCUC_Cache_Purge
 */

// Exit if file accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
#[\AllowDynamicProperties]
class OCUC_Cache_Purge {


	// Constructor
	public function __construct() {
		$this->blog_url = get_option( 'home' );

		// update_option_name fires when options actully updated with new value
		add_action( 'update_option_onecom_under_construction_info', array( $this, 'uc_purge_cache' ), 10, 2 );
	}

	// Clear cache for popular caching plugins
	public function uc_purge_cache() {

		// WordPress purge cache function
		wp_cache_flush();

		// one.com performance cache
		$this->purge_onecom_vcache();

		// W3 total cache
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}

		// wp super cache
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}

		// WP Fastest Cache
		if ( isset( $GLOBALS['wp_fastest_cache'] ) && method_exists( $GLOBALS['wp_fastest_cache'], 'deleteCache' ) ) {
			$GLOBALS['wp_fastest_cache']->deleteCache( true );
		}

		// WP Rocket
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}

		// wp-optimize
		if ( class_exists( 'WP_Optimize' ) ) {
			if ( ! class_exists( 'WP_Optimize_Cache_Commands' ) ) {
				include_once WPO_PLUGIN_MAIN_PATH . 'cache/class-cache-commands.php';
			}
			$cache_commands = new WP_Optimize_Cache_Commands();
			$cache_commands->purge_page_cache();
		}

		// Clear Litespeed cache
		if ( class_exists( 'LiteSpeed_Cache_API' ) && method_exists( 'LiteSpeed_Cache_API', 'purge_all' ) ) {
			LiteSpeed_Cache_API::purge_all();
		}
	}

	// purge one.com performance (vanish) cache
	public function purge_onecom_vcache() {
		wp_remote_request( $this->blog_url, array( 'method' => 'PURGE' ) );
	}
}

$cache_info = new OCUC_Cache_Purge();
