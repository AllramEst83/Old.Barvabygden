<?php

/**
 * adds one.com Shortcuts to WordPress admin
 */

if ( ! class_exists( 'Onecom_Vcache_Shortcuts' ) ) {
	class Onecom_Vcache_Shortcuts {



		public $url;
		const SETTINGS_CHANGE  = 'get_err';
		const OC_PLUGIN_DOMAIN = 'vcaching';


		public function __construct() {
			$this->url = menu_page_url( 'onecom-vcache-plugin', false );

			add_action( 'tool_box', array( $this, 'tools_page_vcache_box' ) );
			add_action( 'admin_head', array( $this, 'oc_button_css' ) );
			add_action( 'update_option_show_on_front', array( $this, self::SETTINGS_CHANGE ), 10, 3 );
			add_action( 'update_option_rss_use_excerpt', array( $this, self::SETTINGS_CHANGE ), 10, 3 );
			add_action( 'update_option_posts_per_page', array( $this, self::SETTINGS_CHANGE ), 10, 3 );
			add_action( 'permalink_structure_changed', array( $this, 'permalinks_updated' ), 11, 2 );
		}


		/**
		 * adds performance cache box to tools screen
		 */

		public function tools_page_vcache_box() {
			$title = __( 'Performance Cache', 'vcaching' );
			$desc  = __( 'With one.com Performance Cache enabled your website loads a lot faster. We save a cached copy of your website on a Varnish server, that will then be served to your next visitors.', 'vcaching' );
			$label = __( 'Performance Cache', 'vcaching' );
			$this->tools_page_content_render_html( $title, $desc, $label, $this->url );
		}


		/**
		 * returns html for the tools box
		 */

		public function tools_page_content_render_html( $title, $desc, $label, $url ) {
			echo '<div class="card">
                <h2 class="title">' . $title . '</h2>
                <p>' . $desc . '</p>
                <p><a class="button" href="' . $url . '">' . $label . '</a></p>
            </div>';
		}

		/**
		 * adds css for the shortcuts
		 */

		public function oc_button_css() {

			echo '<style>
                    .oc-span-alert{
                    font-weight:400
                    }
                    </style>';
		}

		/**
		 * adds notice when reading settings change
		 */

		public function get_err( $oldvalue, $newvalue ) {

			if ( $oldvalue !== $newvalue && ! get_settings_errors() ) {

				$message = __( 'Settings saved.' ) . '<br/><p><span class="oc-span-alert">' . __( 'If you are using a caching plugin, remember to empty your cache', 'vcaching' ) . '&nbsp;<a href="' . wp_nonce_url( add_query_arg( 'purge_varnish_cache', 1, admin_url( 'options-reading.php' ) ), 'vcaching' ) . '">' . __( 'Purge Performance Cache', 'vcaching' ) . '</a></span></p>';

				add_settings_error( 'options-reading', 'settings_updated', $message, 'success' );

			}
		}

		/**
		 * adds notice when permalink structure changes
		 */

		public function permalinks_updated( $old_permalink_structure, $permalink_structure ) {

			$permalink_structure = get_option( 'permalink_structure' );
			$message             = __( 'Permalink structure updated.' ) . '<br/>
                       <p><span class="oc-span-alert">' . __( 'If you are using a caching plugin, remember to empty your cache', 'vcaching' ) . '&nbsp;<a href="' . wp_nonce_url( add_query_arg( 'purge_varnish_cache', 1 ), 'vcaching' ) . '">' . __( 'Purge Performance Cache', 'vcaching' ) . '</a></span></p>';

			if ( $permalink_structure !== $old_permalink_structure && ! get_settings_errors() ) {

				add_settings_error( 'general', 'permalink_updated', $message, 'success' );
			}
		}
	}
}
