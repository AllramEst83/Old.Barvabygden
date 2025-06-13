<?php
declare( strict_types=1 );

/**
 * Class OnecomCheckPlugins
 */
class OnecomCheckPlugins extends OnecomHealthMonitor {
	use OnecomLite;

	public $pcache_plugin = 'onecom-vcache/vcaching.php';

	/**
	 * Check is Performance cache is installed and all its components are active
	 * @return array
	 */
	public function check_performance_cache(): array {
		$result = $this->format_result( $this->flag_open );
		if ( ! $this->is_plugin_active( $this->pcache_plugin ) ) {
			$result                    = $this->format_result( $this->flag_open );
			$result['activate_plugin'] = true;
			return $result;
		}
		$vc_state = get_option( 'varnish_caching_enable' );
		if ( $vc_state !== 'true' ) {
			$result = $this->format_result( $this->flag_open, '', );
		}

		if ( $vc_state === 'true' ) {
			$result = $this->format_result( $this->flag_resolved, '' );
		}

		return $result;
	}

	public function check_cdn(): array {
		$result = array(
			$this->status_key => $this->flag_open,
		);
		if ( ! $this->is_plugin_active( $this->pcache_plugin ) ) {
			return array(
				$this->status_key => $this->flag_open,
				'activate_plugin' => true,
			);
		}
		$cdn_state = get_option( 'oc_cdn_enabled' );
		if ( $cdn_state !== 'true' ) {
			$result = $this->format_result( $this->flag_open, '' );
		}

		if ( $cdn_state === 'true' ) {
			$result = $this->format_result( $this->flag_resolved, '' );
		}

		return $result;
	}

	/**
	 * Get a list of all the plugins that are not tested with last 2 major version of WP
	 * @return array
	 */
	public function check_plugins_last_update(): array {
		$outdated    = array();
		$plugin_list = get_plugins();
		global $wp_version;
		foreach ( $plugin_list as $key => $p ) {
			$slug        = $this->plugin_slug( $key );
			$tested_upto = $this->get_tested_upto( $slug );
			$diff        = $this->version_compare( $tested_upto, $wp_version );
			/**
			 * If outdated (version difference is >= 2) or tested_upto info is missing, add into outdated/to-do list
			 */
			if ( $tested_upto === 'missing' || $diff >= 2 ) {
				$outdated[] = $p['Name'];
			}
		}
		if ( empty( $outdated ) ) {
			$title  = __( 'All the plugins are tested with last 2 major releases of WordPress', 'onecom-wp' );
			$result = $this->format_result( $this->flag_resolved, $title );
		} else {
			$title = __( 'Some of the plugins are not tested with last 2 major releases of WordPress', 'onecom-wp' );
			$desc  = __( 'Following plugins are not tested with the last 2 major versions of WordPress. You should consider using their alternatives' );

			$result         = $this->format_result( $this->flag_open, $title, $desc );
			$result['list'] = $outdated;
		}

		return $result;
	}

	/**
	 * Try to get plugin slug from provided string
	 *
	 * @param string $plugin the name of plugin
	 *
	 * @return string
	 */
	public function plugin_slug( string $plugin ): string {
		if ( ! $plugin || $plugin === '' ) {
			return '';
		}
		if ( strpos( $plugin, '/' ) === false ) {
			return $plugin;
		}

		return explode( '/', $plugin )[0];
	}

	/**m
	 * Get plugin info from WordPress plugin API,
	 * and return the version of WP upto which plugin is tested. Returns empty string if
	 * no info is found.
	 *
	 * @param string $slug
	 *
	 * @return string
	 */
	private function get_tested_upto( string $slug ): string {
		$url      = "https://api.wordpress.org/plugins/info/1.0/{$slug}.json";
		$response = wp_remote_get( $url );
		$data     = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( is_array( $data ) && array_key_exists( 'tested', $data ) && ! empty( $data['tested'] ) ) {
			return $data['tested'];
		} if ( is_array( $data ) && array_key_exists( 'tested', $data ) && empty( $data['tested'] ) ) {
			// WPIN-1578 - If plugin exists on wp.org but tested_upto is empty
			return 'missing';
		}

		// Possibly, remaining plugins that does not exist on wp.org
		return '';
	}

	/**
	 * Check the difference between provided WP versions.
	 *
	 * @param string $version1 the version of WordPress, plugin is tested upto
	 * @param string $version2 the version of WordPress that is installed
	 *
	 * @return int
	 */
	private function version_compare( string $version1, string $version2 ): int {
		if ( $version1 === '' || $version2 === '' ) {
			return (int) 1;
		}

		//The floatval("6.1.1") will return 6.1 into float
		$v1 = floatval( $version1 ) * 10;
		$v2 = floatval( $version2 ) * 10;

		//Special case handle
		//Tested upto v1 = 6.1  => 61
		//WP core   v2 = 5.6  => 56
		if ( $v1 > $v2 ) {
			return (int) 1;
		}

		return (int) ( $v2 - $v1 );
	}

	/**
	 * Fix the performance cache
	 * @return array
	 */
	public function fix_performance_cache(): array {
		$activation_result = '';
		$plugin_path = WP_PLUGIN_DIR . 'onecom-vcache/vcaching.php';
		if ( ! file_exists( $plugin_path ) ) {
			$activation_result = $this->install_plugin( MIDDLEWARE_URL . '/plugins/onecom-vcache/download', $this->pcache_plugin );
			if ( ! $activation_result ) {
				return $this->format_result( $this->flag_open );
			}
		} elseif ( ! $this->is_plugin_active( $this->pcache_plugin ) ) {
			$activation_result = activate_plugin( $this->pcache_plugin );
		}
		$vc_state = update_option( 'varnish_caching_enable', 'true', 'no' );
		if ( $vc_state || $activation_result || ( null === $activation_result ) ) {
			return $this->format_result(
				$this->flag_resolved,
				$this->text['performance_cache'][ $this->fix_confirmation ],
				$this->text['performance_cache'][ $this->status_desc ][ $this->status_resolved ]
			);
		}
		return $this->format_result( $this->flag_open );
	}

	/**
	 * Fix the performance cache
	 * @return array
	 */
	public function fix_performance_cdn(): array {

		$activation_result = '';
		if ( ! $this->is_plugin_active( $this->pcache_plugin ) ) {
			$activation_result = activate_plugin( $this->pcache_plugin );
		}
		$cdn_state = update_option( 'oc_cdn_enabled', 'true', 'no' );
		if ( $cdn_state || ( ! $activation_result ) ) {
			return $this->format_result(
				$this->flag_resolved,
				$this->text['enable_cdn'][ $this->fix_confirmation ],
				$this->text['enable_cdn'][ $this->status_desc ][ $this->status_resolved ]
			);
		}

		return $this->format_result( $this->flag_open );
	}

	public function undo_check_performance_cache(): array {
		if ( update_option( 'varnish_caching_enable', 'false', 'no' ) ) {
			$check = 'performance_cache';

			$ignore_text = $this->ignore_text;
			if ( ! $this->onecom_is_premium() ) {
				$ignore_text = '';
			}

			return array(
				$this->status_key      => $this->flag_resolved,
				$this->fix_button_text => $this->text[ $check ][ $this->fix_button_text ],
				$this->desc_key        => $this->text[ $check ][ $this->status_desc ][ $this->status_open ],
				$this->how_to_fix      => $this->text[ $check ][ $this->how_to_fix ],
				'ignore_text'          => $ignore_text,
			);

		}
	}

	public function check_discouraged_plugins(): array {
		$this->log_entry( 'Scanning for discouraged plugins' );
		$plugins = onecom_fetch_plugins( false, true );
		if ( ! is_wp_error( $plugins ) && ! empty( $plugins ) ) {
			$plugins = $this->discouraged_plugins( $plugins );
		}
		if ( ! empty( $plugins['active'] ) ) {
			$result         = $this->format_result( $this->flag_open );
			$result['list'] = $plugins['active'];
			$result['fix']  = true;
		} else {
			$result = $this->format_result( $this->flag_resolved );
			if ( ! empty( $plugins['inactive'] ) ) {
				$result['list'] = $plugins['inactive'];
			}
		}
		$this->log_entry( 'Finished scanning for discouraged plugins' );

		// @todo oc_sh_save_result( 'discouraged_plugins', $result[ $oc_hm_status ], 1 );

		return $result;
	}

	public function discouraged_plugins( array $plugins = array() ) {

		$discouraged_slugs = array();
		// filter out those plugins that are not installed
		foreach ( $plugins as $key => $plugin ) {
			$discouraged_slugs[] = $plugin->slug;
		}
		$list = array();
		//get slug of all the installed plugins
		$plugin_infos = get_plugins();
		if ( ! empty( $plugin_infos ) ) {
			foreach ( $plugin_infos as $file => $info ) :
				$slug = explode( '/', $file )[0];
				if ( ! in_array( $slug, $discouraged_slugs ) ) {
					continue;
				}
				if ( is_plugin_inactive( $file ) ) {
					$list['inactive'][ $file ] = $info['Name'];
				} else {
					$list['active'][ $file ] = $info['Name'];
				}
			endforeach;
		}

		return $list;
	}

	/**
	 * is_plugin_active()
	 * Check if a plugin is active
	 *
	 * @param string $plugin plugin slug
	 *
	 * @return bool
	 */
	public function is_plugin_active( string $plugin ): bool {
		if ( empty( $plugin ) ) {
			return false;
		}
		$plugin_list = get_plugins();

		if ( ! array_key_exists( $plugin, $plugin_list ) ) {
			return false;
		}
		$active_plugin_list = $this->active_plugins;

		return in_array( $plugin, $active_plugin_list );
	}

	/**
	 * Function is_imagify_setup()
	 * Check if imagify is installed and active.
	 * @return array
	 */
	public function is_imagify_setup(): array {

		/**
		 * @todo: throw error if any of following plugins is NOT activated
		 * wp-smushit
		 * ewww-image-optimizer
		 * optimole-wp
		 * shortpixel-image-optimiser
		 *
		 * if activated then in done section chnage  the text
		 *
		 */

		/* @todo: If imagify is active, additional checks
		 * 1. Plugin has api key
		 * 2. API key is valid
		 */
		$optimisation_plugins = array(
			'wp-smushit/wp-smush.php',
			'ewww-image-optimizer/ewww-image-optimizer.php',
			'optimole-wp/optimole-wp.php',
			'shortpixel-image-optimiser/wp-shortpixel.php',

		);
		foreach ( $optimisation_plugins as $optimisation_plugin ) {
			if ( in_array( $optimisation_plugin, $this->active_plugins ) ) {
				return array(
					$this->status_key => $this->flag_resolved,
				);
			}
		}

		if ( ! in_array( 'imagify/imagify.php', $this->active_plugins ) ) {
			return array(
				$this->status_key => $this->flag_open,
			);
		} elseif (
			in_array( 'imagify/imagify.php', $this->active_plugins )
			&& function_exists( 'imagify_is_api_key_valid' ) && imagify_is_api_key_valid() ) {
			return array(
				// In the condition above,
				// Added the recommended function to check Imagify API key
				// added in Imagify v2.1.1
				$this->status_key => $this->flag_resolved,
			);
		} else {
			return array(
				$this->status_key => $this->flag_open,
			);
		}
	}

	/**
	 * Deactivate discouraged plugins
	 * @return array
	 * @todo use deactivate_plugins()
	 */
	public function fix_dis_plugin(): array {
		$plugins = onecom_fetch_plugins( false, true );
		if ( ! is_wp_error( $plugins ) && ! empty( $plugins ) ) {
			$dis_plugins = $this->discouraged_plugins( $plugins )['active'];
		}
		$plugins_to_deactivate = array();
		$html                  = '<ul>';

		foreach ( $dis_plugins as $key => $plugin ) {
			$plugins_to_deactivate[] = $key;
			$html                   .= '<li>' . $plugin . '</li>';
		}
		$html          .= '</ul>';
		$active_plugins = get_option( 'active_plugins' );
		if ( empty( $active_plugins ) ) {
			$active_plugins = array();
		}
		$refined_plugins = array();
		foreach ( $active_plugins as $active_plugin ) {
			if ( in_array( $active_plugin, $plugins_to_deactivate ) ) {
				continue;
			}
			$refined_plugins[] = $active_plugin;
		}
		if ( update_option( 'active_plugins', $refined_plugins, 'no' ) ) {

			return $this->format_result(
				$this->flag_resolved,
				$this->text['dis_plugin'][ $this->fix_confirmation ] . $html,
				$this->text['dis_plugin'][ $this->status_desc ][ $this->status_resolved ] . $html
			);
		}

		return $this->format_result( $this->flag_open );
	}


	public function undo_check_performance_cdn() {
		if ( update_option( 'oc_cdn_enabled', 'false', 'no' ) ) {
			$check = 'enable_cdn';

			$ignore_text = $this->ignore_text;
			if ( ! $this->onecom_is_premium() ) {
				$ignore_text = '';
			}

			return array(
				$this->status_key      => $this->flag_resolved,
				$this->fix_button_text => $this->text[ $check ][ $this->fix_button_text ],
				$this->desc_key        => $this->text[ $check ][ $this->status_desc ][ $this->status_open ],
				$this->how_to_fix      => $this->text[ $check ][ $this->how_to_fix ],
				'ignore_text'          => $ignore_text,
			);

		} else {
			return $this->format_result( $this->status_open );
		}
	}

	public function check_inactive_plugins() {
		$inactive_plugins = array();

		// Get all plugins
		$all_plugins = get_plugins();

		// Get active plugins
		$active_plugins = get_option( 'active_plugins', array() );

		$result = $this->format_result( $this->flag_resolved, '', '' );

		// Check for inactive plugins
		foreach ( $all_plugins as $plugin_path => $plugin_data ) {
			// Check if the plugin is not from one.com
			if ( strpos( $plugin_data['Author'], 'one.com' ) !== false ) {
				continue;
			}
			if ( ! in_array( $plugin_path, $active_plugins ) ) {
				$inactive_plugins[] = $plugin_data['Name'];
			}

			if ( ! empty( $inactive_plugins ) ) {

				$inactive_plugins_count = count( $inactive_plugins );
				$title                  = __( 'Remove inactive plugins', 'onecom-wp' );
				$desc                   = sprintf(
					__( 'Your site currently has %d inactive plugins. Inactive plugins are tempting targets for hackers. We recommend removing any plugins that you no longer need.', 'onecom-wp' ),
					$inactive_plugins_count
				);

				$result         = $this->format_result( $this->flag_open, $title, $desc );
				$result['list'] = $inactive_plugins;
			} else {
				$title = __( 'Remove inactive plugins', 'onecom-wp' );
				$desc  = __( 'Inactive plugins are tempting targets for hackers. We recommend removing any plugins that you no longer need.' );

				$result = $this->format_result( $this->flag_resolved, $title, $desc );
			}
		}

		return $result;
	}

	public function check_inactive_themes() {
		$inactive_themes = array();

		// Get all installed themes
		$all_themes = wp_get_themes();

		// Get the active theme
		$active_theme = wp_get_theme();

		$ignore_authors = array( 'one.com', 'the WordPress team' );

		// Check for inactive themes
		foreach ( $all_themes as $theme_slug => $theme ) {
			// Ignore themes from specified authors
			if ( array_intersect( $ignore_authors, explode( ', ', $theme->get( 'Author' ) ) ) ) {
				continue;
			}

			// Check if the theme is not active (neither parent nor child)
			if (
				$theme->get_stylesheet() !== $active_theme->get_stylesheet()
				&& $theme->get( 'Template' ) !== $active_theme->get_stylesheet() // The theme is not the child theme of active parent theme
				&& $theme->get_stylesheet() !== $active_theme->get( 'Template' ) // The theme is not the parent theme of the active child theme
			) {
				$inactive_themes[] = $theme->get( 'Name' );
			}
		}
		if ( ! empty( $inactive_themes ) ) {
			$title          = __( 'Remove inactive themes', 'onecom-wp' );
			$result         = $this->format_result( $this->flag_open, $title, '' );
			$result['list'] = $inactive_themes;
		} else {

			$result = $this->format_result( $this->flag_resolved, '', '' );
		}

		return $result;
	}

	private function install_plugin( $plugin_download_url, $slug ): bool {
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
		require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';

		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$upgrader->install( $plugin_download_url );
		return is_null( activate_plugin( $slug ) );
	}
}
