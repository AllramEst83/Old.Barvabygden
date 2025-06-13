<?php
if ( ! function_exists( 'oc_page_source_vars' ) ) {
	function oc_page_source_vars() {

		// exit if wp-admin
		if ( is_admin() ) {
			return '';
		}

		// skip if the plugin is not from one.com
		// a note for future, update the below line to include non-one.com plugins
		$onecom_plugins = array_filter( get_plugins(), fn( $v ) => $v['Author'] === 'one.com' );

		// exit if no plugins installed
		if ( empty( $onecom_plugins ) ) {
			return '';
		}

		// iterate over installed one.com plugins to mark active status
		$onecom_plugins_status = array();
		array_walk(
			$onecom_plugins,
			function ( &$v, $k ) use ( &$onecom_plugins_status ) {

				// set value as "1" or "0" if the plugin is active
				$v = is_plugin_active( $k ) ? 1 : 0;

				// set the key as the first part of the plugin slug
				$newKey = current( explode( '/', $k ) );

				// assign new key
				$onecom_plugins_status[ md5( $newKey ) ] = $v;
			}
		);

		// prepare JS object properties for plugins
		$js_plugin_props = array();
		foreach ( $onecom_plugins_status as $key => $value ) {
			$js_plugin_props[] = '"' . $key . '": ' . $value;
		}

		// <script> tag for adding in HTML page source
		$template = '<script id="ocvars">var ocSiteMeta = {plugins: {__plugins__}}</script>';

		// replace placeholders with actual values
		echo str_replace( '__plugins__', join( ',', $js_plugin_props ), $template );
		return '';
	}

	add_action( 'wp_footer', 'oc_page_source_vars', 999 );
}
