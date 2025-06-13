<?php

if( !defined( 'WP_ORG_API_URL' ) ) {
	define( 'WP_ORG_API_URL' , 'https://api.wordpress.org/' );
}

if( !defined( 'WP_ORG_API_VERSION' ) ) {
	define( 'WP_ORG_API_VERSION' , '1.2' );
}

if ( ! function_exists( 'onecom_themes_listing_config' ) ) {
	function onecom_themes_listing_config( $request = null, $per_page = 12 ) {
		$config = array();
		$themes = get_site_transient( 'onecom_themes' );

		if ( is_array( $themes ) ) {
			foreach ( $themes as  $theme_group ) {
				if ( isset( $theme_group->collection ) ) {
					foreach ( $theme_group->collection as $theme ) {
						if ( $theme->slug === 'onecom-ilotheme' ) {
							$themes['total'] = ( $themes['total'] - 1 );
						}
					}
				}
			}
		}

		if ( ! $themes ) {
			return false;
		}
		if ( $request == null ) {
			$config['item_count'] = $per_page;
			$config['total']      = $themes['total'];
			return $config;
		} else {
			return ( isset( $themes->{$request} ) ) ? $themes->{$request} : false;
		}
	}
}

/* Function to count theme by categories */
if ( ! function_exists( 'onecom_themes_cat_count' ) ) {
	function onecom_themes_cat_count() {

		// define empty array early to avoid warning if no data from api somehow
		$theme_count = array();
		$themes      = get_site_transient( 'onecom_themes' );

		// If theme data not found in transients, call function to set it & call again
		if ( ! isset( $themes ) || empty( $themes ) ) {
			onecom_fetch_themes();
			$themes = get_site_transient( 'onecom_themes' );
		}

		if ( is_array( $themes ) ) {
			// Exclude old-6 as well as ilotheme
			$exclude_themes = array(
				'the-anderson-family',
				'summertime-adventure',
				'school-days',
				'personal-cv',
				'heisengard',
				'gardener',
				'onecom-ilotheme',
			);
			foreach ( $themes as $theme_group ) {
				if ( isset( $theme_group->collection ) ) {

					// Skip if excluded themes
					foreach ( $theme_group->collection as $key => $theme ) {
						if ( in_array( $theme->slug, $exclude_themes ) ) {
							unset( $theme_group->collection[ $key ] );
							continue;
						}
						$theme_count['all'] = count( $theme_group->collection );

						foreach ( $theme->tags as $categories_name ) { // Increase category count if already initiated, else initiate 1
							if ( isset( $theme_count[ $categories_name ] )
								&& ! empty( $theme_count[ $categories_name ] ) ) {
								$theme_count[ $categories_name ] = $theme_count[ $categories_name ] + 1;
							} else {
								$theme_count[ $categories_name ] = 1;
							}
						}
					}
				}
			}
		} else {
			// Additional handilng if no theme data from transient/function
		}

		if ( ! $theme_count ) {
			return false;
		} else {
			return $theme_count;
		}
	}
}

// Function to calculate plugin counts
if ( ! function_exists( 'onecom_plugins_count' ) ) {
	function onecom_plugins_count() {

		// count one.com plugins
		$oc_plugins = onecom_fetch_plugins();
		if ( is_array( $oc_plugins ) ) {
			$plugins['onecom']                   = count( $oc_plugins );
			$plugins['onecom_excluding_generic'] = count( $oc_plugins ) - 1;
		} else {
			$plugins['onecom']                   = '';
			$plugins['onecom_excluding_generic'] = '';
		}

		// count recommended plugins
		$r_plugins = onecom_fetch_plugins( true );
		if ( is_array( $r_plugins ) ) {
			//get active theme details
			$activeTheme = wp_get_theme();
			$authorName = $activeTheme->get( 'Author' );
			$countUpdate = ($authorName !== 'superbaddons') ? 1 : 0;
			$plugins['recommended'] = (count( $r_plugins ) - $countUpdate);
		} else {
			$plugins['recommended'] = '';
		}

		// count active discourage active plugin
		$all_d_plugins       = onecom_fetch_plugins( false, true );
		$active_plugins      = get_option( 'active_plugins' );
		$active_plugins_slug = array();

		// If discourage plugins list (as an array) found
		if ( is_array( $all_d_plugins ) ) {
			// prepare slugs array of all active plugin
			foreach ( $active_plugins as $active_plugin ) {
				$plugin_info           = explode( '/', $active_plugin, 2 );
				$active_plugins_slug[] = $plugin_info[0];
			}

			// prepare slugs of all discourage plugins
			$d_plugins_slug = array();
			foreach ( $all_d_plugins as $d_plugin ) {
				$d_plugins_slug[] = $d_plugin->slug;
			}

			// count if any discourage plugin is active
			$plugins['discouraged'] = count( array_intersect( $d_plugins_slug, $active_plugins_slug ) );
		} else {
			$plugins['discouraged'] = '';
		}

		// return array with count of one.com, recommended & active discourage plugins
		return $plugins;
	}
}


if ( ! function_exists( 'onecom_themes_listing_pagination' ) ) {
	function onecom_themes_listing_pagination( $config, $requsted_page_number = 1 ) {
		?>
			<div class="theme-browser-pagination text-center">
				<?php
					$total_pages = (int) ceil( ( $config['total'] / $config['item_count'] ) );
				if ( $total_pages <= 1 ) {
					return;
				}
					$url = ( is_network_admin() && is_multisite() ) ? network_admin_url( 'admin.php' ) : admin_url( 'admin.php' );
				for ( $i = 1; $i <= $total_pages; $i++ ) :
					$page_number = $i;
					$item_class  = '';
					if ( $page_number === 1 ) {
						$item_class = 'first';
					} elseif ( $page_number === $total_pages ) {
						$item_class = 'last';
					}
					if ( $page_number === $requsted_page_number ) {
						$item_class .= ' current';
					}
					$args = array(
						'page'        => 'onecom-wp-themes',
						'page_number' => $page_number,
					);
					?>
							<a href="<?php echo add_query_arg( $args, $url ); ?>" class="pagination-item <?php echo $item_class; ?>" data-request_page="<?php echo $page_number; ?>"><?php echo $page_number; ?></a>
					<?php
					endfor;
				?>
			</div>
		<?php
	}
}

/**
* Function to handle install a theme
**/
if ( ! function_exists( 'onecom_install_theme_callback' ) ) {
	function onecom_install_theme_callback() {
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/theme.php';

		if (
			get_option( 'auto_updater.lock' ) // else if auto updater lock present
			|| get_option( 'core_updater.lock' ) // else if core updater lock present
		) {
			$response['type']    = 'error';
			$response['message'] = __( 'WordPress is being upgraded. Please try again later.', 'onecom-wp' );
			echo json_encode( $response );
			wp_die();
		}

		$theme_slug = wp_unslash( $_POST['theme_slug'] );
		$redirect   = ( isset( $_POST['redirect'] ) ) ? $_POST['redirect'] : false;
		$network    = ( isset( $_POST['network'] ) ) ? (boolean) $_POST['network'] : false;

		$theme_info = onecom_get_theme_info( $theme_slug );

		$theme_info->download_link = $_POST['template'];

		add_filter( 'http_request_host_is_external', 'onecom_http_requests_filter', 10, 3 );

		$title = __( 'Installing theme', 'onecom-wp' ) ;
		$nonce = 'theme-install';
		$url   = add_query_arg(
			array(
				'package' => basename( $theme_info->download_link ),
				'action'  => 'install',
			),
			admin_url()
		);

		$type = 'web'; //Install plugin type, From Web or an Upload.

		$skin     = new WP_Ajax_Upgrader_Skin( compact( 'type', 'title', 'nonce', 'url' ) );
		$upgrader = new Theme_Upgrader( $skin );
		$result   = $upgrader->install( $theme_info->download_link );

		$status = array(
			'slug' => $theme_info->slug,
		);

		$default_error_message = __( 'Something went wrong. Please contact the support at One.com.', 'onecom-wp' );

		if ( is_wp_error( $result ) ) {
			$status['errorCode']    = $result->get_error_code();
			$status['errorMessage'] = $result->get_error_message();
		} elseif ( is_wp_error( $skin->result ) ) {
			$status['errorCode']    = $skin->result->get_error_code();
			$status['errorMessage'] = $skin->result->get_error_message();
		} elseif ( $skin->get_errors()->get_error_code() ) {
			$status['errorMessage'] = $skin->get_error_messages();
		} elseif ( is_null( $result ) ) {
			global $wp_filesystem;

			$status['errorCode']    = 'unable_to_connect_to_filesystem';
			$status['errorMessage'] = __( 'Unable to connect to the file system. Please contact the support at One.com.', 'onecom-wp' );

			// Pass through the error from WP_Filesystem if one was raised.
			if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
				$status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
			}
		}
		$theme = wp_get_theme( $theme_slug );

		$status['themeName'] = $theme->get( 'Name' );

		$response['type']    = 'error';
		$response['message'] = ( isset( $status['errorMessage'] ) ) ? $status['errorMessage'] : $default_error_message;

		if ( $result === true ) {
			( class_exists( 'OCPUSHSTATS' ) ? \OCPushStats::push_stats_event_themes_and_plugins( 'install', 'theme', $theme_slug, "themes_page" ) : '' );

			// Check if the theme is a child theme
			if ( $theme->get( 'Template' ) !== $theme->get_stylesheet() ) {
				// It's a child theme; include the parent theme slug
				$response['parentThemeSlug'] = $theme->get( 'Template' );
			} else {
				// Not a child theme; set parentThemeSlug to false
				$response['parentThemeSlug'] = false;
			}
			$response['type']    = 'success';
			$response['message'] = __( 'Theme installed successfully', 'onecom-wp' );
			$button_html =  __( 'Activate', 'onecom-wp' ) ;

			$response['button_html'] = $button_html;
		}

		$response['status'] = $status;

		echo json_encode( $response );

		wp_die();
	}
}
add_action( 'wp_ajax_onecom_install_theme', 'onecom_install_theme_callback' );

if ( ! function_exists( 'onecom_fetch_plugins' ) ) {
	function onecom_fetch_plugins( $recommended = false, $discouraged = false ) {
		$plugins = array();

		if ( $recommended ) {
			$plugins           = get_site_transient( 'onecom_recommended_plugins' );
			$fetch_plugins_url = MIDDLEWARE_URL . '/recommended-plugins';
		} elseif ( $discouraged ) {
			$plugins           = get_site_transient( 'onecom_discouraged_plugins' );
			$fetch_plugins_url = MIDDLEWARE_URL . '/discouraged-plugins';
		} else {
			$plugins           = get_site_transient( 'onecom_plugins' );
			$fetch_plugins_url = MIDDLEWARE_URL . '/plugins';
		}

		$fetch_plugins_url = onecom_query_check( $fetch_plugins_url );

		$ip     = onecom_get_client_ip_env();
		$domain = ( isset( $_SERVER['ONECOM_DOMAIN_NAME'] ) && ! empty( $_SERVER['ONECOM_DOMAIN_NAME'] ) ) ? $_SERVER['ONECOM_DOMAIN_NAME'] : 'localhost';

		if ( ( ! $plugins ) || empty( $plugins ) ) {
			global $wp_version;
			$args = array(
				'timeout'     => 5,
				'httpversion' => '1.0',
				'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
				'body'        => null,
				'compress'    => false,
				'decompress'  => true,
				'sslverify'   => true,
				'stream'      => false,
				'headers'     => array(
					'X-ONECOM-CLIENT-IP'     => $ip,
					'X-ONECOM-CLIENT-DOMAIN' => $domain,
				),
			);

			$response = wp_remote_get( $fetch_plugins_url, $args );

			if ( is_wp_error( $response ) ) {
				if ( isset( $response->errors['http_request_failed'] ) ) {
					$errorMessage = __( 'Connection timed out', 'onecom-wp' );
				} else {
					$errorMessage = $response->get_error_message();
				}
			} else {
				if ( wp_remote_retrieve_response_code( $response ) != 200 ) {
					$errorMessage = '(' . wp_remote_retrieve_response_code( $response ) . ') ' . wp_remote_retrieve_response_message( $response );
				} else {
					$body = wp_remote_retrieve_body( $response );

					$body = json_decode( $body );

					if ( ! empty( $body ) && $body->success ) {
						if ( $recommended || $discouraged ) {
							$plugins = $body->data;

						} else {
							$plugins = $body->data->collection;
						}
					} elseif ( $body->success == false ) {
						if ( $body->error == 'RESOURCE NOT FOUND' ) {
							if ( $recommended ) {
								$args = array(
									'request' => 'recommended_plugins',
								);
							} elseif ( $discouraged ) {
								$args = array(
									'request' => 'discouraged_plugins',
								);
							} else {
								$args = array(
									'request' => 'plugins',
								);
							}
							$try_again_url = add_query_arg(
								$args,
								''
							);
							$try_again_url = wp_nonce_url( $try_again_url, '_wpnonce' );
							$errorMessage  = __( 'Sorry, no compatible plugins found with your version of WordPress and PHP.', 'onecom-wp' ) . '&nbsp;<a href="' . $try_again_url . '">' . __( 'Try again', 'onecom-wp' ) . '</a>';
						} else {
							echo $body->error;
						}
					}
				}

				if ( $recommended ) {
					set_site_transient( 'onecom_recommended_plugins', $plugins, 3 * HOUR_IN_SECONDS );
				} elseif ( $discouraged ) {
					set_site_transient( 'onecom_discouraged_plugins', $plugins, 3 * HOUR_IN_SECONDS );
				} else {
					set_site_transient( 'onecom_plugins', $plugins, 3 * HOUR_IN_SECONDS );
				}
			}
		}

		if ( empty( $plugins ) ) {
			$plugins = new WP_Error( 'message', $errorMessage );
		}

		return $plugins;
	}
}

/**
* Ajax handler to activate theme
**/
if ( ! function_exists( 'onecom_activate_theme_callback' ) ) {
	function onecom_activate_theme_callback() {
		// Check if the theme exists
		$theme = wp_get_theme( $_POST['theme_slug'] );
		$author = $theme->get( 'Author' );

		if ( ! $theme->exists() ) {
			wp_send_json_error( array(
				'message' => __( 'The specified theme does not exist or is not installed.', 'onecom-wp' ),
			) );
			wp_die();
		}

		// Check if the theme is a child theme
		if ( $theme->get( 'Template' ) !== $theme->get_stylesheet() ) {
			// Retrieve the parent theme
			$parent_theme = wp_get_theme( $theme->get( 'Template' ) );

			// Check if the parent theme exists
			if ( ! $parent_theme->exists() ) {
				wp_send_json_error( array(
					'message' => sprintf(
						__( 'The theme "%1$s" is a child theme, but its parent theme "%2$s" is not installed.', 'onecom-wp' ),
						$theme->get( 'Name' ),
						$parent_theme->get( 'Name' )
					),
				) );
				wp_die();
			}
		}

		if ( is_multisite() ) {
			WP_Theme::network_enable_theme( $_POST['theme_slug'] );
		} else {
			$old_theme        = wp_get_theme();
			$old_theme_author = $old_theme->get( 'Author' );
			if ( 'superbaddons' === $old_theme_author ) {
				( class_exists( OCPUSHSTATS ) ? \OCPushStats::push_stats_event_themes_and_plugins( 'deactivate', 'theme', $old_theme->stylesheet, 'themes_page' ) : '' );
			}

			switch_theme( $_POST['theme_slug'] );
		}
		if ( 'one.com' !== $author ) {
			( class_exists( 'OCPUSHSTATS' ) ? \OCPushStats::push_stats_event_themes_and_plugins( 'activate', 'theme', $_POST['theme_slug'], 'themes_page' ) : '' );
		}
		$response         = array();
		$response['success'] = true;
		$response['install_text'] = __('Customise', 'onecom-wp');
		$response['link'] = admin_url('customize.php');
		echo json_encode( $response );
		wp_die();
	}
}
add_action( 'wp_ajax_onecom_activate_theme', 'onecom_activate_theme_callback' );

/**
*   It will return key of array of objects based on search value and search key
**/
if ( ! function_exists( 'onecom_search_key_in_object' ) ) {
	function onecom_search_key_in_object( $search_value, $array, $search_key ) {
		foreach ( $array as $key => $val ) {
			if ( $val->$search_key === $search_value ) {
				return $key;
			}
		}
		return null;
	}
}

/**
* Function to get theme info
**/
if ( ! function_exists( 'onecom_get_theme_info' ) ) {
	function onecom_get_theme_info( $slug ) {
		$found_theme = false;
		if ( $slug == '' ) {
			return new WP_Error( 'message', 'Theme slug should not be empty' );
		}

		$themes_pages = get_site_transient( 'onecom_themes' );
		if ( empty( $themes_pages ) ) {
			return new WP_Error( 'message', 'No themes found locally' );
		}

		foreach ( $themes_pages as $page_number_key => $theme_set ) :
			if ( empty( $theme_set->collection ) ) {
				continue;
			}

			$collection = $theme_set->collection;

			foreach ( $collection as $key => $theme ) {
				if ( $theme->slug == $slug ) {
					$found_theme = $theme;
					break 2;
				}
			}

		endforeach;

		if ( $found_theme != false ) {
			return $found_theme;
		} else {
			return new WP_Error( 'message', 'Theme not found' );
		}
	}
}

/**
* Function to get theme info
**/
if ( ! function_exists( 'onecom_get_plugin_info' ) ) {
	function onecom_get_plugin_info( $slug, $type ) {
		if ( $slug == '' ) {
			return new WP_Error( 'message', 'Plugin slug should not be empty' );
		}
		$plugins = ( $type == 'recommended' ) ? get_site_transient( 'onecom_recommended_plugins_meta' ) : get_site_transient( 'onecom_plugins' );
		if ( empty( $plugins ) ) {
			if ( $type == 'recommended' ) {
				$plugins = onecom_fetch_plugins( true );
			} else {
				$plugins = onecom_fetch_plugins();
			}
			if ( empty( $plugins ) ) {
				return new WP_Error( 'message', 'No plugins found locally' );
			}
		}
		$key = onecom_search_key_in_object( $slug, $plugins, 'slug' );

		if ( is_object( $plugins ) ) {
			return $plugins->$key;
		} elseif ( is_array( $plugins ) ) {
			return $plugins[ $key ];
		} else {
			return $plugins[ $key ];
		}
	}
}

/**
* Check if theme installed
**/
if ( ! function_exists( 'onecom_is_theme_installed' ) ) {
	function onecom_is_theme_installed( $theme_slug ) {
		$path = get_theme_root() . '/' . $theme_slug . '/';
		if ( file_exists( $path ) ) {
			return true;
		} else {
			return false;
		}
	}
}

/**
* Function to handle plugin installation
**/
if ( ! function_exists( 'onecom_install_plugin_callback' ) ) {
	function onecom_install_plugin_callback( $isAjax = true, $pluginSlugParam = '' ) {
		$plugin_type  = ( isset( $_POST['plugin_type'] ) ) ? wp_unslash( $_POST['plugin_type'] ) : 'normal';
		$download_url = ( isset( $_POST['download_url'] ) ) ? $_POST['download_url'] : '';
		$plugin_slug  = ( isset( $_POST['plugin_slug'] ) ) ? wp_unslash( $_POST['plugin_slug'] ) : $pluginSlugParam;
		$plugin_name  = ( isset( $_POST['plugin_name'] ) ) ? wp_unslash( $_POST['plugin_name'] ) : '';
		$redirect     = ( isset( $_POST['redirect'] ) ) ? $_POST['redirect'] : false;

		if (
			get_option( 'auto_updater.lock' ) // else if auto updater lock present
			|| get_option( 'core_updater.lock' ) // else if core updater lock present
		) {
			return false;
		}

		$plugin_info = onecom_get_plugin_info( $plugin_slug, $plugin_type );

		if ( is_null( $plugin_info ) || empty( $plugin_info ) ) {
			$plugin_info = new stdClass();
		}
		$plugin_info->slug = $plugin_slug;

		if ( $plugin_type === 'onecom-plugins' ) {
			$plugin_info->download_link = MIDDLEWARE_URL . '/plugins/' . $plugin_info->slug . '/download';
			$log_referer                = 'onecom_plugins';
		} elseif ( $plugin_type === 'recommended' ) {
			$plugin_info->download_link = $download_url;
			$log_referer                = 'recommended_plugins';
		} elseif ( $plugin_type === 'external' ) {
			$plugin_info->download_link = $plugin_info->download;
			$log_referer                = 'onecom_plugins';
		} else {
			$plugin_info->download_link = $download_url;
			$log_referer                = 'unknown';
		}

		// Filter download url
		$plugin_info->download_link = apply_filters( 'onecom_plugin_download_url', $plugin_info->download_link, $plugin_info->slug );

		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		add_filter( 'http_request_host_is_external', 'onecom_http_requests_filter', 10, 3 );

		$title = sprintf( __( 'Installing plugin', 'onecom-wp' ) );
		$nonce = 'plugin-install';
		$url   = add_query_arg(
			array(
				'package' => basename( $plugin_info->download_link ),
				'action'  => 'install',
				//'page' => 'page',
				//'step' => 'theme'
			),
			admin_url()
		);

		$type = 'web'; //Install plugin type, From Web or an Upload.

		$skin     = new WP_Ajax_Upgrader_Skin( compact( 'type', 'title', 'nonce', 'url' ) );
		$upgrader = new Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $plugin_info->download_link );

		$default_error_message = __( 'Something went wrong. Please contact the support at One.com.', 'onecom-wp' );

		if ( is_wp_error( $result ) ) {
			$status['errorCode']    = $result->get_error_code();
			$status['errorMessage'] = $result->get_error_message();

		} elseif ( is_wp_error( $skin->result ) ) {
			$status['errorCode']    = $skin->result->get_error_code();
			$status['errorMessage'] = $skin->result->get_error_message();

		} elseif ( $skin->get_errors()->get_error_code() ) {
			$status['errorMessage'] = $skin->get_error_messages();

		} elseif ( is_null( $result ) ) {
			global $wp_filesystem;

			$status['errorCode']    = 'unable_to_connect_to_filesystem';
			$status['errorMessage'] = __( 'Unable to connect to the file system. Please contact the support at One.com.', 'onecom-wp' );

			// Pass through the error from WP_Filesystem if one was raised.
			if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
				$status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
			}
		}
		$response['type']    = 'error';
		$response['message'] = __( 'Couldn’t install plugin.', 'onecom-wp' );
		$response['debugMessage'] = ( isset( $status['errorMessage'] ) ) ? $status['errorMessage'] : $default_error_message;

		if ( $result == true ) {
			$status              = install_plugin_install_status( $plugin_info );
			$response['type']    = 'success';
			$response['message'] = __( 'Plugin installed.', 'onecom-wp' );
            unset($response['debugMessage']);
			$admin_url           = ( is_multisite() ) ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' );

			( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_event_themes_and_plugins( 'install', 'plugin', $plugin_slug, $log_referer ) : '' );
			$activateUrl = add_query_arg(
				array(
					'_wpnonce' => wp_create_nonce( 'activate-plugin_' . $status['file'] ),
					'action'   => 'activate',
					'plugin'   => $status['file'],
				),
				$admin_url
			);
			if ( $redirect == false || $redirect == '' || is_multisite() ) {
				$button_html = '<a href="' . $activateUrl . '" class="activate-plugin btn button_1">' . __( 'Activate', 'onecom-wp' ) . '</a>';
			} else {
				$button_html = '<a class="activate-plugin activate-plugin-ajax btn button_1" href="javascript:void(0)" data-action="onecom_activate_plugin" data-redirect="' . $redirect . '" data-slug="' . $status['file'] . '" data-name="' . $plugin_name . '">' . __( 'Activate', 'onecom-wp' ) . '</a>';
			}
			$response['button_html'] = $button_html;
			$response['info']        = $plugin_info;
		}

		$response['status'] = $status;

		if ( false === $isAjax ) {
			return $response;
		}

		echo json_encode( $response );

		wp_die();
	}
}
add_action( 'wp_ajax_onecom_install_plugin', 'onecom_install_plugin_callback' );

/**
* Ajax handler to activate theme
**/
if ( ! function_exists( 'onecom_activate_plugin_callback' ) ) {
	function onecom_activate_plugin_callback() {
		$plugin_slug = wp_unslash( $_POST['plugin_slug'] );

		// Load necessary WP functions
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		// Get all installed plugins
		$installed_plugins = get_plugins();

		// Find the correct plugin file path
		$plugin_file = '';

		// Check if the provided slug is already in the expected format
		if (isset($installed_plugins["$plugin_slug/$plugin_slug.php"])) {
			$plugin_file = "$plugin_slug/$plugin_slug.php";
		} else {
			// Search for the correct plugin file
			foreach ($installed_plugins as $file => $plugin_data) {
				if (strpos($file, $plugin_slug . '/') === 0 || dirname($file) === $plugin_slug) {
					$plugin_file = $file;
					break;
				}
			}
		}
		$is_activate = activate_plugin($plugin_file);
		$response = array();
		if (is_wp_error($is_activate)) {
			$response['type'] = 'error';
			$response['message'] = __('Couldn’t activate plugin.', 'onecom-wp');
		} else {
			$response['status'] = 'success';
			$response['message'] = __('Plugin activated.', 'onecom-wp');

			// Check if the activated plugin is Imagify
			if ( str_contains( $plugin_file , 'imagify' ) ) {
				$response['type'] = 'redirect';
				$response['url'] = admin_url( 'options-general.php?page=imagify' ); // Redirect only for Imagify
			}
		}

		echo json_encode($response);
		wp_die();
	}
}
add_action( 'wp_ajax_onecom_activate_plugin', 'onecom_activate_plugin_callback' );

/**
* An alternative for thumbnail
**/
if ( ! function_exists( 'onecom_string_acronym' ) ) {
	function onecom_string_acronym( $name ) {
		preg_match_all( '/\b\w/', $name, $acronym );
		$str = implode( '', $acronym[0] );
		return substr( $str, 0, 3 );
	}
}

/**
 * Get thumbnails from  WordPress.org
 */

function oc_get_plugin_thumbnail( $slug ) {
	return 'https://ps.w.org/' . $slug . '/assets/icon-128x128.png';
}

/**
* Pick random flat color
**/
if ( ! function_exists( 'onecom_random_color' ) ) {
	function onecom_random_color( $key = null ) {
		$array = array(
			'#FFC107', //yellow
			'#3498db', // peter river
			'#2ecc71', // emerald
			'#9b59b6', // Amethyst
			'#f1c40f', // sun flower
			'#e74c3c', // alizarin
			'#1abc9c', // turquoise
			'#00BCD4', // cyan,
			'#E91E63', // pink
			'#34495e', // wet asphalt
			'#CDDC39', // lime
			'#03A9F4', // light blue,
			'#8BC34A', // light green
			'#9C27B0', // purple
			'#3F51B5', // indigo
			'#F44336', // red
			'#009688', // teal

		);
		if ( $key == null ) {
			$key = array_rand( $array );
		} else {
			$array_keys = array_keys( $array );
			if ( ! in_array( $key, $array_keys ) ) {
				$key = array_rand( $array );
			}
		}

		return $array[ $key ];
	}
}

/**
* Function which will display admin notices
**/
if ( ! function_exists( 'onecom_generic_promo' ) ) {
	function onecom_generic_promo() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		global $wp_version;

		$is_transient = get_site_transient( 'onecom_promo' );

		if ( ! $is_transient ) {
			$url  = MIDDLEWARE_URL . '/promo';
			$args = array(
				'timeout'     => 10,
				'httpversion' => '1.0',
				'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
				'body'        => null,
				'compress'    => false,
				'decompress'  => true,
				'sslverify'   => true,
				'stream'      => false,
			);

			$x_promo_transient = 48; // default transient value

			$response = wp_remote_get( $url, $args );
			if ( ! is_wp_error( $response ) ) {
				$local_promo           = array();
				$local_promo           = get_site_option( 'onecom_local_promo', null );
				$x_promo_check         = wp_remote_retrieve_header( $response, 'X-ONECOM-Promo' );
				$x_promo_transient_val = wp_remote_retrieve_header( $response, 'X-ONECOM-Transient' ) || $x_promo_transient;

				$x_promo_transient = ( empty( $x_promo_transient_val ) || is_bool( $x_promo_transient_val ) ) ? $x_promo_transient : $x_promo_transient_val;

				$x_promo_include = wp_remote_retrieve_header( $response, 'X-ONECOM-Promo-Include' );
				$x_promo_exclude = wp_remote_retrieve_header( $response, 'X-ONECOM-Promo-Exclude' );

				$result = wp_remote_retrieve_body( $response );

				$json = json_decode( $result );
				if ( json_last_error() === 0 ) {
					$result = '';
				}

				if ( isset( $local_promo['xpromo'] ) && $local_promo['xpromo'] == $x_promo_check ) {
					$local_promo['html'] = $result;
				} else {
					$local_promo['show']   = true;
					$local_promo['html']   = $result;
					$local_promo['xpromo'] = $x_promo_check;
					if ( trim( $x_promo_include ) != '' ) {
						$local_promo['include'] = explode( '|', $x_promo_include );
					}
					if ( trim( $x_promo_exclude ) != '' ) {
						$local_promo['exclude'] = explode( '|', $x_promo_exclude );
					}
				}
				update_site_option( 'onecom_local_promo', $local_promo, 'no' );
			}
			set_site_transient( 'onecom_promo', true, $x_promo_transient * HOUR_IN_SECONDS );
		}

		$local_promo = get_site_option( 'onecom_local_promo' );

		$screen   = get_current_screen();
		$restrict = false;

		if ( isset( $local_promo['include'] ) && ! empty( $local_promo['include'] ) ) {
			$restrict = true;
			if (
				in_array( $screen->base, $local_promo['include'] )
				|| in_array( $screen->id, $local_promo['include'] )
				|| in_array( $screen->parent_base, $local_promo['include'] )
				|| in_array( $screen->parent_file, $local_promo['include'] )
			) {
				$restrict = false;
			}
		}
		if ( isset( $local_promo['exclude'] ) && ! empty( $local_promo['exclude'] ) && ! $restrict ) {
			$restrict = false;
			if (
				in_array( $screen->base, $local_promo['exclude'] )
				|| in_array( $screen->id, $local_promo['exclude'] )
				|| in_array( $screen->parent_base, $local_promo['exclude'] )
				|| in_array( $screen->parent_file, $local_promo['exclude'] )
			) {
				$restrict = true;
			}
		}

		if ( ( $restrict == false ) && ( isset( $local_promo['show'] ) && $local_promo['show'] == true ) && ( isset( $local_promo['html'] ) && $local_promo['html'] != '' ) ) {
			wp_enqueue_style( 'onecom-promo' );
			wp_enqueue_script( 'onecom-promo' );
			echo apply_filters( 'onecom_filter_promo_html', $local_promo['html'] );
		}
	}
}
if ( is_network_admin() && is_multisite() ) {
	add_action( 'network_admin_notices', 'onecom_generic_promo' );
} else {
	add_action( 'admin_notices', 'onecom_generic_promo' );
}

if ( ! function_exists( 'onecom_filter_promo_html_callback' ) ) {
	function onecom_filter_promo_html_callback( $html ) {
		$admin_url = ( is_network_admin() && is_multisite() ) ? network_admin_url() : admin_url();
		$html      = str_replace( '{admin_url}', $admin_url, $html );
		return $html;
	}
}
add_filter( 'onecom_filter_promo_html', 'onecom_filter_promo_html_callback' );

/**
* Ajax handler for dismissable notice request
**/
if ( ! function_exists( 'onecom_dismiss_notice_callback' ) ) {
	function onecom_dismiss_notice_callback() {
		$local_promo         = get_site_option( 'onecom_local_promo' );
		$local_promo['show'] = false;
		$is_update           = update_site_option( 'onecom_local_promo', $local_promo, 'no' );
		if ( $is_update ) {
			echo 'Notice dismissed';
		} else {
			echo 'Notice cannot dismissed';
		}
		wp_die();
	}
}
add_action( 'wp_ajax_onecom_dismiss_notice', 'onecom_dismiss_notice_callback' );

/**
* Function to handle HTTP requests to GO API
**/
if ( ! function_exists( 'onecom_http_requests_filter' ) ) {
	function onecom_http_requests_filter( $allow, $host, $url ) {
		$check_host = '';
		if ( isset( $_SERVER['ONECOM_WP_ADDONS_API'] ) && $_SERVER['ONECOM_WP_ADDONS_API'] != '' ) {
			$check_host = rtrim( $_SERVER['ONECOM_WP_ADDONS_API'], '/' );
		} elseif ( defined( 'ONECOM_WP_ADDONS_API' ) && ONECOM_WP_ADDONS_API != '' && ONECOM_WP_ADDONS_API ) {
			$check_host = rtrim( ONECOM_WP_ADDONS_API, '/' );
		}

		if ( $host === $check_host ) {
			$allow = true;
			add_filter( 'http_request_reject_unsafe_urls', '__return_false' );
		}
		return $allow;
	}
}

/**
* Function to write logs
**/
if ( ! function_exists( 'onecom_write_log' ) ) {
	function onecom_write_log( $log ) {
		if ( true === WP_DEBUG_LOG ) {
			if ( is_array( $log ) || is_object( $log ) ) {
				error_log( print_r( $log, true ) );
			} else {
				error_log( $log );
			}
		}
	}
}

/**
 * Function to check if the supplied timestamp
 * is a valid unix/linux timestamp
 */
function onecom_checkdate_timestamp( $timestamp ) {

	$now = time();
	$end = $timestamp + 0;  // adding zero to typecast "string" to "number"

	// exit
	// if empty or invalid timestamp
	// if current timestamp is equal to or bigger than enddate

	if (
		empty( $timestamp )
		|| ! checkdate( (int) date( 'm', $timestamp ), (int) date( 'd', $timestamp ), (int) date( 'Y', $timestamp ) )
		|| $now >= $end
	) {
		return false;
	}

	// if current timestamp is lesser than enddate
	if ( $now < $end ) {
		return true;
	}

	// "fail-safe" condition to hide the badge
	return false;
}

/*
 * Filter out themes marked as hidden
 * */
function onecom_filter_hidden_themes( $themes_arr = array() ) {
	// return if empty themes array
	if ( empty( $themes_arr ) ) {
		return $themes_arr;
	}

	// iterate through themes array and filter out which are hidden
	foreach ( $themes_arr as $key => $theme ) {
		if ( $theme->hidden === 'true' || $theme->hidden === true ) {
			unset( $themes_arr[ $key ] );
		}
	}
	return $themes_arr;
}
/*
 * Admin notices
 **/
if ( file_exists( ONECOM_WP_PATH . 'modules' . DIRECTORY_SEPARATOR . 'notice-discouraged-plugins.php' ) ) {
	include_once ONECOM_WP_PATH . 'modules' . DIRECTORY_SEPARATOR . 'notice-discouraged-plugins.php';
}

if ( file_exists( ONECOM_WP_PATH . 'modules' . DIRECTORY_SEPARATOR . 'notice-spam-protection.php' ) ) {
	include_once ONECOM_WP_PATH . 'modules' . DIRECTORY_SEPARATOR . 'notice-spam-protection.php';
}


if ( ! function_exists( 'isPremium' ) ) {
	function isPremium() {
		$features = oc_set_premi_flag();
		if (
			( isset( $features['data'] ) && ! empty( $features['data'] ) )
			&& ( in_array( 'MWP_ADDON', $features['data'] ) || in_array( 'STAGING_ENV', $features['data'] ) )
		) {
			return true;
		}
		return false;
	}

}

// Function to check MWP_ADDON only
if ( ! function_exists( 'ismWP' ) ) {
	function ismWP() {
		$features = oc_set_premi_flag();
		if (
			isset( $features['data'] ) &&
			( ! empty( $features['data'] ) ) && ( in_array( 'MWP_ADDON', $features['data'] ) )
		) {
			return true;
		}
		return false;
	}
}

// Function to delete install.php (left by installer) if exists
if ( ! function_exists( 'delete_onboarding_install_file' ) ) {
	function delete_onboarding_install_file(): void {
		$file_path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'install.php';

		if ( file_exists( $file_path ) ) {
			if ( unlink( $file_path ) ) {
				error_log( '[INFO] install.php file deleted successfully.' );
			} else {
				error_log( '[ERROR] Failed to delete install.php file.' );
			}
		}
	}
}


// new themes page //
/**
 * Prepare theme block on theme preview page
 *
 */
if(!function_exists('prepare_theme_block')) {
	function prepare_theme_block($theme, $key): string
	{
		// Load the template content
		$template = file_get_contents( ( dirname( __FILE__,2 ) ) . '/templates/theme-template.html' );
		// Calculate variables
		$tags          = $theme->tags;
		$themeSlug     = $theme->slug;
		$is_installed = onecom_is_theme_installed( $themeSlug );
		$current_theme = wp_get_theme();
		$current_theme_stylesheet = $current_theme->get_stylesheet();

//		$current_theme = get_option( 'template' );
		$is_premium    = ( is_array( $tags ) && in_array( 'premium' , $tags ) ) ? 1 : 0;
		$thumbnail_url = preg_replace( "(^https?:)" , "" , $theme->thumbnail );
		$current_time  = time();
		$tQueryParam   = '&t=' . $current_time;
		$preview_url   = $theme->preview . $tQueryParam;
		$download_url  = property_exists( $theme , 'override_download_url' ) && $theme->override_download_url ? $theme->download : MIDDLEWARE_URL . '/themes/' . $themeSlug . '/download';
		$redirect      = $theme->redirect ? add_query_arg( array( 'auto-import' => true ) , $theme->redirect ) : '';
		if ( $is_installed && ( $current_theme_stylesheet == $theme->slug ) ){
			$install_text = __('Customise', 'onecom-wp');
			$link = admin_url('customize.php');
			$status_class = 'showstatus';
			$status_text = __('Active', 'onecom-wp');
			$action = '';
			$class = '';
			$gv_badge_class = 'gv-badge-success';
		}elseif ( $is_installed && ( $current_theme_stylesheet != $theme->slug ) ){
			$install_text = __('Activate', 'onecom-wp');
			$status_class = 'showstatus';
			$status_text = __('Installed', 'onecom-wp');
			$action = 'onecom_activate_theme';
			$link = 'javascript:;';
			$class = 'one-installed';
			$gv_badge_class = 'gv-badge-generic';
		}else{
			$install_text = __('Install', 'onecom-wp');
			$status_class = 'gv-hidden';
			$status_text = '';
			$action = 'onecom_install_theme';
			$link = 'javascript:;';
			$class = 'one-install ocwp_ocp_themes_page_theme_selected_event';
			$gv_badge_class = 'gv-badge-generic';
		}

		// Create an array of placeholders and their replacements
		$replacements = [
			'{{index}}' => $key + 1,
			'{{is_premium}}' => (int)$is_premium,
			'{{themeSlug}}' => $themeSlug,
			'{{tags_class}}' => implode(' ', $tags),
			'{{theme_type}}' => property_exists($theme, 'classic_theme') && $theme->classic_theme ? 'classic-theme' : 'all',
			'{{thumbnail_url}}' => $thumbnail_url,
			'{{theme_id}}' => $theme->id,
			'{{preview_url}}' => $preview_url,
			'{{download_url}}' => $download_url,
			'{{redirect}}' => $redirect,
			'{{theme_name}}' => $theme->name,
			'{{select_text}}' => __('Select', 'onecom-wp'),
			'{{continue_text}}' => __('Continue', 'onecom-wp'),
			'{{preview_text}}' => __('Preview', 'onecom-wp'),
			'{{selected_text}}' => __('', 'onecom-wp'),
			'{{install_text}}' => $install_text,
			'{{install_class}}' => $class,
			'{{theme_action}}' => $action,
			'{{status_class}}' => $status_class,
			'{{status}}' => $status_text,
			'{{link}}' => $link,
			'{{gv-badge-class}}' => $gv_badge_class
		];

		// Replace placeholders with actual values and return the final output
		return strtr($template, $replacements);
	}
}

/**
 * Merge wp theme listing and classic theme
 */
if(!function_exists('merge_classic_wp_themes')) {
	function merge_classic_wp_themes($oci_theme_fetch): array
	{
		$wpOrgThemesSlugOrCat = ['lakeside-blogger', 'the-minimal-blogger', 'ruby-ecommerce', 'bakery-and-pastry', 'twentytwentyfive', 'digitalis-one', 'foodify', 'idea-flow','awardify', 'creativity-hub', 'wordcraft-x', 'sonoran', 'simple-nova', 'minimalistix', 'twentytwentyfour', 'twentytwentythree', 'newslink-magazine', 'newspaper-builder', 'link-folio' ];
		$themes = get_site_transient('superb-themes');

		//fetch wp org theme info from session if set otherwise fetch from wp org api
		$fetchWPOrgThemes = ($themes !== false) ? $themes : oci_fetch_wp_org_themes('theme-slug', $wpOrgThemesSlugOrCat);


		$totalThemes = count($fetchWPOrgThemes);
		$tags = ['business-services','design','e-commerce','entertainment','events','food-hospitality','lifestyle']; // Add your tags here
		// add ai-demo-data entry for themes which require demo data
		$tagMappings = [
			'lakeside-blogger' => ['blog', 'events', 'ai-demo-data', 'superb-theme'],
			'the-minimal-blogger' => ['design', 'events', 'ai-demo-data', 'superb-theme'],
			'ruby-ecommerce' => ['events', 'business-services', 'ai-demo-data', 'blog', 'superb-theme'],
			'bakery-and-pastry' => ['food-hospitality', 'business-services', 'ai-demo-data'],
			'twentytwentyfive' => ['blog', 'events', 'ai-demo-data'],
			'digitalis-one' => ['lifestyle','business-services', 'ai-demo-data'],
			'foodify' => ['events','food-hospitality', 'ai-demo-data'],
			'idea-flow' => ['lifestyle', 'business-services', 'ai-demo-data'],
			'awardify' => ['events', 'entertainment', 'ai-demo-data'],
			'creativity-hub' => ['e-commerce','entertainment', 'ai-demo-data'],
			'wordcraft-x' => ['design', 'events', 'ai-demo-data'],
			'sonoran' => ['events','food-hospitality', 'ai-demo-data'],
			'simple-nova' => ['design', 'events', 'ai-demo-data'],
			'minimalistix' => ['design', 'events', 'ai-demo-data'],
			'twentytwentyfour' => ['lifestyle', 'business-services', 'ai-demo-data'],
			'twentytwentythree' => ['blog', 'ai-demo-data'],
			'newslink-magazine' => ['e-commerce','entertainment', 'ai-demo-data'],
			'newspaper-builder' => ['events','food-hospitality', 'ai-demo-data'],
			'link-folio' => ['events','blog', 'ai-demo-data']
			// Add mappings for other themes here
		];
		$modifiedThemes = addTagsToThemes($fetchWPOrgThemes, $tagMappings);

		//Add new key for old theme
		$old_themes = array_map(function ($theme) {
			$theme->{'classic_theme'} = true;
			return $theme;
		}, $oci_theme_fetch);
		//Merge one.com themes and wp.org themes
		return array_merge($old_themes, $modifiedThemes);
	}
}

if(!function_exists('addTagsToThemes')) {
	/**
	 * @param $themes
	 * @param $tagMappings
	 * function to add tags to the themes as per the tag-mapping array
	 * @return mixed
	 */
	function addTagsToThemes( $themes , $tagMappings ) {
		// Iterate over each theme
		foreach ( $themes as $theme ) {
			// Get the theme slug
			$themeSlug = $theme->slug;

			// Check if the theme slug exists in the tag mappings
			if ( isset( $tagMappings[ $themeSlug ] ) ) {
				// Get the tags to add for this theme
				$tagsToAdd = $tagMappings[ $themeSlug ];

				// Loop through each tag to add
				foreach ( $tagsToAdd as $tag ) {
					// Check if the tag is not already present
					if ( ! in_array( $tag , $theme->tags ) ) {
						// Add the tag to the theme
						$theme->tags[] = $tag;
					}
				}
			}
		}

		// Return the modified themes array
		return $themes;
	}
}

/**
 * Convert wp theme info structure as per our default theme preview
 */

if(!function_exists('convert_theme_info')) {
	function convert_theme_info($theme, $key): stdClass
	{
		// Define the new structure and map fields from the old structure
		$new_theme_info = new stdClass();

		//Ensure screenshot_url starts with https
		$screenshot_url = $theme->screenshot_url;
		if (!str_contains($screenshot_url, 'http://') && !str_contains($screenshot_url, 'https://')) {
			$screenshot_url = 'https:' . $screenshot_url;
		}

		$new_theme_info->id = $theme->slug.'-'.$key; // Assuming no ID in the old structure
		$new_theme_info->name = $theme->name;
		$new_theme_info->slug = $theme->slug;
		$new_theme_info->description = $theme->sections->description;
		$new_theme_info->version = $theme->version;
		$new_theme_info->thumbnail = $screenshot_url;
		$new_theme_info->thumbnail_name = basename(parse_url($screenshot_url, PHP_URL_PATH));
		$new_theme_info->banner = $screenshot_url; // Assuming banner is same as screenshot
		$new_theme_info->banner_name = basename(parse_url($screenshot_url, PHP_URL_PATH));
		$new_theme_info->categories = ['business-services']; // Assuming a default category
		$new_theme_info->tags = array_keys((array)$theme->tags);
		$new_theme_info->tags[] = 'blog';//added custom tag later we can decide on this
		$new_theme_info->author = $theme->author->display_name;
		$new_theme_info->author_url = $theme->author->author_url;
		$new_theme_info->more_details = $theme->homepage;
		$new_theme_info->download = $theme->download_link;
		$new_theme_info->preview = $theme->preview_url.'?v='.$theme->version;
		$new_theme_info->type = 'free'; // Assuming free type
		$new_theme_info->requires = new stdClass();
		$new_theme_info->requires->wordpress = $theme->requires;
		$new_theme_info->requires->php = $theme->requires_php;
		$new_theme_info->redirect = null;
		$new_theme_info->compatible_upto = new stdClass();
		$new_theme_info->compatible_upto->wordpress = null;
		$new_theme_info->compatible_upto->php = null;
		$new_theme_info->new = 0; // Assuming not new
		$new_theme_info->hidden = null; // Assuming not hidden
		$new_theme_info->override_download_url = true;
		return $new_theme_info;
	}
}


/**
 * Function to fetch wp org themes
 */
if(!function_exists('oci_fetch_wp_org_themes')){
	function oci_fetch_wp_org_themes($fetchBy = 'theme-slug', $list = []): array
	{
		//Note: else part can be category api url for wp org, then the below loop logic will change
		$requestURI = ($fetchBy === 'theme-slug') ? WP_ORG_API_URL.'themes/info/'.WP_ORG_API_VERSION.'/?action=theme_information&request[slug]=' : '';
		$theme_info = [];
		foreach ($list as $key => $slug) {
			//here we are fetching the theme info by theme slug
			$theme_info[] = convert_theme_info(json_decode(file_get_contents($requestURI.$slug) ), $key);

		}

		//Add theme which is not hosted on wp-org
		$nonWP_theme_info = array();
		$get_themes_not_hosted_on_wp_org = get_themes_not_hosted_on_wp_org();
		foreach ($get_themes_not_hosted_on_wp_org as $key => $singleTheme) {
//			$nonWP_theme_info[] = convert_theme_info($singleTheme , $key);
		}

		$merged_themes = array_merge($nonWP_theme_info, $theme_info);
		//set theme info in session
		set_site_transient('superb-themes', $theme_info, 24 * HOUR_IN_SECONDS);
		$_SESSION['wp_org_themes'] = $theme_info;
		return $theme_info;
	}
}

if( ! function_exists( 'get_themes_not_hosted_on_wp_org' ) ) {
	function get_themes_not_hosted_on_wp_org() {
		// Load the template content
		return json_decode('{
            "awardify": {
                "name": "Awardify",
                "slug": "awardify",
                "version": "4.0",
                "preview_url": "https://superbdemo.com/themes/awardify/?superbthemepreview=awardify",
                "author": {
                    "user_nicename": "superbaddons",
                    "profile": "https://profiles.wordpress.org/superbaddons/",
                    "avatar": "https://secure.gravatar.com/avatar/0334582afe1ea6c5679c4f12c3c85677?s=96&d=monsterid&r=g",
                    "display_name": "Superb Addons",
                    "author_url": "http://superbthemes.com/"
                },
                "screenshot_url": "https://superbthemes.com/wp-content/uploads/2024/04/awardify-theme-img-min.jpg",
                "rating": 90,
                "num_ratings": 10,
                "reviews_url": "",
                "downloaded": 15000,
                "last_updated": "2024-06-30",
                "last_updated_time": "2024-06-30 12:34:56",
                "creation_time": "2024-01-01 10:00:00",
                "homepage": "https://superbdemo.com/themes/awardify/",
                "sections": {
                    "description": "Awardify is a easy to use WordPress theme designed with travel and lifestyle bloggers and writers in mind. It’s compatible with the best popular page builders like Brizy, Elementor, Divi Builder, and Visual Compose, Gutenberg. Its clean and simple minimalist layout is perfect for showcasing photography and travel stories, while also being mobile friendly and responsive across all devices. It supports plugins like WooCommerce for ecommerce, AdSense & affiliate space for banners and affiliate links for monetization through advertisements. It’s optimized for fast loading speeds, making it one of the fastest themes available, which is crucial for SEO. It comes with dark mode and color customization for a cooler design, schema markup for better search engine optimization, and translation. Awardify includes layouts for landing pages, one pages, reviews, writing, marketing, business, news, newspaper publishing, magazines, and personal portfolios, making it a top choice. Whether for a blogging startup, a fashion journal, or a corporate agency, Awardify’s flexibility and ease of use make it an ideal template for creating a professional online presence."
                },
                "download_link": "https://superbthemes.com/themes/one_h5b04/awardify.zip",
                "tags": {
                    "Entertainment": "Entertainment",
                    "event": "Event"
                },
                "requires": "6.1",
                "requires_php": "7.0",
                "is_commercial": false,
                "external_support_url": false,
                "is_community": false,
                "external_repository_url": ""
            }
        }');
	}
}

if( ! function_exists( 'oci_fetch_themes' ) ) {
	function oci_fetch_themes() {
		$themes = array();

		$url = onecom_query_check( MIDDLEWARE_URL.'/themes' );

		$url = add_query_arg(
			array(
				'item_count' => 1000
			), $url
		);

		$ip = onecom_get_client_ip_env();
		$domain = ( isset( $_SERVER[ 'ONECOM_DOMAIN_NAME' ] ) && ! empty( $_SERVER[ 'ONECOM_DOMAIN_NAME' ] ) ) ? $_SERVER[ 'ONECOM_DOMAIN_NAME' ] : 'localhost';

		if( empty( $themes ) || $themes == false ) {
			global $wp_version;
			$args = array(
				'timeout'     => 5,
				'httpversion' => '1.0',
				'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
				'body'        => null,
				'compress'    => false,
				'decompress'  => true,
				'sslverify'   => true,
				'stream'      => false,
				'headers'       => array(
					'X-ONECOM-CLIENT-IP' => $ip,
					'X-ONECOM-CLIENT-DOMAIN' => $domain
				)
			);

			$response = wp_remote_get( $url, $args );
			$body = wp_remote_retrieve_body( $response );
			$body = json_decode( $body );

			$themes = array();

			if( !empty($body->success) && $body->success ) {
				$themes = $body->data->collection;
				if (is_array($themes) && !empty($themes)){
					foreach ($themes as $key=>$theme){
						if (isset($theme->slug) && $theme->slug === 'onecom-ilotheme'){
							unset($themes[$key]);
						}
					}
				}
			} else {
				//adding error case if wpapi not works
				return new WP_Error('message', $body->error);
			}

		}

		return $themes;
	}
}

if ( ! function_exists( 'get_oc_plugin_data' ) ) {
	function get_oc_plugin_data( $plugin_type = 'onecom-plugins' ): bool|array {
		$plugins_data = array();

		// Fetch plugins based on type
		$plugins = fetch_plugins_by_type( $plugin_type );
		if ( is_wp_error( $plugins ) ) {
			return false;
		}

		// Filter out hidden plugins
		$plugins = array_filter( $plugins, fn($p) => !isset($p->hidden) || !$p->hidden );

		if ( $plugin_type === 'onecom-plugins' ) {
			$plugins = append_wp_rocket_plugin( $plugins );
		}

		foreach ( $plugins as $plugin ) {
			$plugins_data[] = format_plugin_data( $plugin, $plugin_type );
		}

		return $plugins_data;
	}
}

if ( ! function_exists( 'fetch_plugins_by_type' ) ) {
	function fetch_plugins_by_type( string $plugin_type ) {
		if ( $plugin_type === 'recommended' ) {
			return onecom_fetch_plugins( true );
		} elseif ( $plugin_type === 'discouraged' ) {
			return filter_discouraged_plugins();
		}

		return onecom_fetch_plugins();
	}
}

if ( ! function_exists( 'filter_discouraged_plugins' ) ) {
// Filter discouraged plugins
	function filter_discouraged_plugins() {
		$plugins = onecom_fetch_plugins( false , true );
		if ( is_wp_error( $plugins ) ) {
			error_log( 'Error fetching plugins: ' . $plugins->get_error_message() );

			return [];
		}

		$active_plugins = get_option( 'active_plugins' , [] );
		$active_slugs   = array_map( fn( $path ) => explode( '/' , $path )[0] , $active_plugins );

		$filtered_plugins = array_filter( $plugins , fn( $plugin ) => isset( $plugin->slug ) && in_array( $plugin->slug , $active_slugs , true ) );

		if ( empty( $filtered_plugins ) ) {
			wp_send_json_success( [
				'plugins'            => [] ,
				'discouragedListUrl' => onecom_generic_locale_link( 'discouraged_guide' , get_locale() )
			] );
		}

		foreach ( $filtered_plugins as &$plugin ) {
			$plugin_info = oc_get_plugin_info_from_wporg( $plugin->slug );
			if ( $plugin_info ) {
				$plugin->short_description = $plugin_info['short_description'] ?? '';
				$plugin->thumbnail         = $plugin_info['icon'] ?? '';
                $plugin->author            = $plugin_info['author'] ?? '';
			}
		}
		unset( $plugin );

		return $filtered_plugins;
	}
}

if ( ! function_exists( 'append_wp_rocket_plugin' ) ) {
// Append WP Rocket plugin
	function append_wp_rocket_plugin( array $plugins ) {
		$wp_rocket = new Onecom_Wp_Rocket();
		$plugins[] = (object) $wp_rocket->wp_rocket_plugin_info();

		return array_filter( $plugins , fn( $plugin ) => $plugin->slug !== 'onecom-themes-plugins' );
	}
}

if ( ! function_exists( 'format_plugin_data' ) ) {

// Format plugin data
	function format_plugin_data( $plugin , string $plugin_type ): array {
		$plugin_installed = is_dir( WP_PLUGIN_DIR . '/' . $plugin->slug );
		$plugin_activated = false;
		$activate_url     = '';
		$file_path        = '';

		if ( $plugin_installed ) {
			$plugin_infos = get_plugins( '/' . $plugin->slug );
			foreach ( $plugin_infos as $file => $info ) {
				$is_inactive = is_plugin_inactive( $plugin->slug . '/' . $file );
				$file_path   = $plugin->slug . '/' . $file;
				if ( ! $is_inactive ) {
					$plugin_activated = true;
				} else {
					$activate_url = $file_path;
				}
			}
		}

		if ( 'onecom-plugins' === $plugin_type && 'wp-rocket' === $plugin->slug ) {
			return format_wp_rocket_data( $plugin , $plugin_activated );
		}


		return [
			'slug'             => $plugin->slug ,
			'name'             => html_entity_decode( $plugin->name , ENT_QUOTES , 'UTF-8' ) ,
			'activateParam'    => $file_path ,
			'description'      => isset($plugin->description) ? __($plugin->description, OC_PLUGIN_DOMAIN) : '' ,
			'thumbnail'        => $plugin->thumbnail ,
			'installed'        => $plugin_installed ,
			'activated'        => $plugin_activated ,
			'activateUrl'      => $activate_url ,
			'redirect'         => $plugin->redirect ?? '' ,
			'type'             => $plugin->type ?? '' ,
			'shortDescription' => isset($plugin->short_description) ? __($plugin->short_description, OC_PLUGIN_DOMAIN) : '',
			'downloadLink'     => $plugin->download_link ?? '' ,
			'pluginType'       => $plugin_type,
            'author'           => $plugin->author
		];
	}
}

if ( ! function_exists( 'format_wp_rocket_data' ) ) {

// Format WP Rocket plugin data
	function format_wp_rocket_data( $plugin , bool $plugin_activated ): array {
		$wp_rocket = new Onecom_Wp_Rocket();

		return [
			'slug'         => $plugin->slug ,
			'name'         => esc_html( $plugin->name ) ,
			'installed'    => is_dir( WP_PLUGIN_DIR . '/' . $plugin->slug ) ,
			'activated'    => $plugin_activated ,
			'is_purchased' => method_exists( $wp_rocket , 'is_wp_rocket_addon_purchased' ) ? $wp_rocket->is_wp_rocket_addon_purchased() : false ,
			'redirect'     => $plugin->redirect ?? '' ,
			'guide_url'    => method_exists( $wp_rocket , 'wp_rocket_translated_guide' ) ? $wp_rocket->wp_rocket_translated_guide() : '' ,
			'thumbnail'    => $plugin->thumbnail ,
			'description'  => isset($plugin->description) ? __($plugin->description, OC_PLUGIN_DOMAIN) : '' ,
			'cpLogin'      => OC_CP_LOGIN_URL
		];
	}
}

if ( ! function_exists( 'oc_get_plugin_info_from_wporg' ) ) {

	function oc_get_plugin_info_from_wporg( $plugin_slug ) {
		// Construct API URL with query parameters
		// API URL for plugin search (fetches name, short description, icon, etc.)
		$url = 'https://api.wordpress.org/plugins/info/1.2/?action=query_plugins&request[search]=' . urlencode( $plugin_slug );

		$response = wp_remote_get( $url , [ 'timeout' => 15 ] );
		$body     = wp_remote_retrieve_body( $response );
		$data     = json_decode( $body , true );

		if ( ! $data || empty( $data['plugins'] ) ) {
			error_log( "Failed to retrieve plugin info: " . print_r( $body , true ) );

			return false;
		}

		// Find the exact plugin by slug
		$matched_plugin = null;
		foreach ( $data['plugins'] as $plugin ) {
			if ( $plugin['slug'] === $plugin_slug ) {
				$matched_plugin = $plugin;
				break;
			}
		}

		if ( ! $matched_plugin ) {
			error_log( "No matching plugin found for slug: $plugin_slug" );

			return false;
		}


		return [
			'name'              => $matched_plugin['name'] ?? '' ,
			'short_description' => $matched_plugin['short_description'] ?? 'No description available.' ,
			'icon'              => $matched_plugin['icons']['2x'] ?? '',
            'author'            => $matched_plugin['author'] ?? ''
		];
	}
}

if ( ! function_exists( 'onecom_fetch_plugins_ajax' ) ) {
	function onecom_fetch_plugins_ajax(): void {
		// Check for permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
		}

		// Get plugin type from request (e.g., free/premium/addons)
		$plugin_type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '';

		// Fetch plugins based on type
		$plugins = get_oc_plugin_data( $plugin_type ); // Modify `get_oc_plugin_data()` to accept type if needed

		wp_send_json_success( array( 'plugins' => $plugins ) );
	}
}
add_action( 'wp_ajax_onecom_fetch_plugins', 'onecom_fetch_plugins_ajax' );

add_action('wp_ajax_onecom_deactivate_plugin', 'onecom_handle_plugin_deactivation');
function onecom_handle_plugin_deactivation() {
	// Check if the request is valid and has the required data
	if (!isset($_POST['plugin_slug']) || !current_user_can('activate_plugins')) {
		wp_send_json_error(['message' =>  __('Couldn’t deactivate plugin.', 'onecom-wp' )]);
	}

	$plugin_slug = sanitize_text_field($_POST['plugin_slug']);
	// Load necessary WP functions
	require_once ABSPATH . 'wp-admin/includes/plugin.php';

	// Get all installed plugins
	$installed_plugins = get_plugins();

	// Find the correct plugin file path
	$plugin_file = '';

	// Check if the provided slug is already in the expected format
	if (isset($installed_plugins["$plugin_slug/$plugin_slug.php"])) {
		$plugin_file = "$plugin_slug/$plugin_slug.php";
	} else {
		// Search for the correct plugin file
		foreach ($installed_plugins as $file => $plugin_data) {
			if (strpos($file, $plugin_slug . '/') === 0 || dirname($file) === $plugin_slug) {
				$plugin_file = $file;
				break;
			}
		}
	}


	if ( is_plugin_active( $plugin_file ) ) {
		if ( $plugin_file === 'seo-by-rank-math/rank-math.php' ) {
			// Also deactivate the Pro version if it's active
			if ( is_plugin_active( 'seo-by-rank-math-pro/rank-math-pro.php' ) ) {
				deactivate_plugins( 'seo-by-rank-math-pro/rank-math-pro.php' );
			}
		}
		deactivate_plugins( $plugin_file );
		wp_send_json_success( [ 'message' => __( 'Plugin deactivated.' , 'onecom-wp' ) ] );
	} else {
		wp_send_json_error( [ 'message' => __( 'Couldn’t deactivate plugin.' , 'onecom-wp' ) ] );
	}
}