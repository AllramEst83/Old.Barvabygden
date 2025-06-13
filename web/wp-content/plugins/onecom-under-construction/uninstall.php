<?php
// Action to perform on uninstallation of plugin

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_event_themes_and_plugins( 'delete', 'plugin', dirname( plugin_basename( __FILE__ ) ), 'plugins_page' ) : '' );

// delete under-construction settings data
delete_option( 'onecom_under_construction_info' );
