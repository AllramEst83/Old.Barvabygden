<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName


/*
Plugin Name: Performance Cache
Description: Make your website faster with Performance Cache Pro by saving a cached copy of it. With the Pro version, you get more optimization.
Version: 4.1.10
Author: one.com
Author URI: https://one.com
License: http://www.apache.org/licenses/LICENSE-2.0
Text Domain: vcaching
Network: true

This plugin is a modified version of the WordPress plugin "Varnish Caching" by Razvan Stanga.
Copyright 2017: Razvan Stanga (email: varnish-caching@razvi.ro)
*/

#[\AllowDynamicProperties]
class VCachingOC {
	protected $blog_id;
	protected $plugin                = 'vcaching';
	protected $prefix                = 'varnish_caching_';
	protected $purge_urls            = array();
	protected $varnish_ip            = null;
	protected $varnish_host          = null;
	protected $dynamic_host          = null;
	protected $ips_to_hosts          = array();
	protected $stats_jsons           = array();
	protected $purge_key             = null;
	protected $purge_cache           = 'purge_varnish_cache';
	protected $post_types            = array( 'page', 'post' );
	protected $override              = 0;
	protected $custom_fields         = array();
	protected $notice_message        = '';
	protected $truncate_notice       = false;
	protected $truncate_notice_shown = false;
	protected $truncate_count        = 0;
	protected $debug                 = 0;
	protected $vcl_generator_tab     = true;
	protected $purge_on_menu_save    = false;
	protected $current_tab           = false;
	protected $use_ssl = false;

	public function __construct() {
		global $blog_id;
		defined( $this->plugin ) || define( $this->plugin, true );

		$this->blog_id = $blog_id;
		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'activity_box_end', array( $this, 'varnish_glance' ), 100 );
		add_action( 'admin_init', array( $this, 'oc_vcache_shortcut_call' ) );
	}

	public function init() {
		/** load english en_US tranlsations [as] if any unsupported language en is selected in WP-Admin
		 *  Eg: If en_NZ selected, en_US will be loaded
		 * */

		$current_locale           = get_locale();
		$locales_with_translation = array(
			'da_DK',
			'de_DE',
			'es_ES',
			'fr_FR',
			'it_IT',
			'pt_PT',
			'nl_NL',
			'sv_SE',
		);

		// Locales fallback and load english translations [as] if selected unsupported language in WP-Admin
		if ( 'fi' === $current_locale ) {
			load_textdomain( 'vcaching', __DIR__ . '/languages/vcaching-fi_FI.mo' );
		} elseif ( 'nb_NO' === $current_locale ) {
			load_textdomain( 'vcaching', __DIR__ . '/languages/vcaching-no_NO.mo' );
		} if ( in_array( get_locale(), $locales_with_translation, true ) ) {
			load_plugin_textdomain( 'vcaching', false, basename( __DIR__ ) . '/languages' );
		} else {
			load_textdomain( 'vcaching', __DIR__ . '/languages/vcaching-en_GB.mo' );
		}

		$this->custom_fields = array(
			array(
				'name'        => 'ttl',
				'title'       => 'TTL',
				/* translators:  %s are the seconds for TTL */
				'description' => __( 'Not required. If filled in overrides default TTL of %s seconds. 0 means no caching.', 'vcaching' ),
				'type'        => 'text',
				'scope'       => array( 'post', 'page' ),
				'capability'  => 'manage_options',
			),
		);

		$this->setup_ips_to_hosts();
		$purge_key       = trim( get_option( $this->prefix . 'purge_key' ) );
		$this->purge_key = $purge_key ? $purge_key : null;
		$this->admin_menu();

		add_action( 'wp', array( $this, 'buffer_start' ), 1000000 );
		add_action( 'shutdown', array( $this, 'buffer_end' ), 1000000 );

		$this->truncate_notice = get_site_option( $this->prefix . 'truncate_notice' );
		$this->debug           = get_site_option( $this->prefix . 'debug' );

		// send headers to varnish
		add_action( 'send_headers', array( $this, 'send_headers' ), 1000000 );

		// logged in cookie
		add_action( 'wp_login', array( $this, 'wp_login' ), 1000000 );
		add_action( 'wp_logout', array( $this, 'wp_logout' ), 1000000 );

		// register events to purge post
		foreach ( $this->get_register_events() as $event ) {
			add_action( $event, array( $this, 'purge_post' ), 10, 2 );
		}

		// purge all cache from admin bar
		if ( $this->check_if_purgeable() ) {
			add_action( 'admin_bar_menu', array( $this, 'purge_varnish_cache_all_adminbar' ), 100 );
			if ( isset( $_GET[ $this->purge_cache ] ) && 1 === (int) $_GET[ $this->purge_cache ] && check_admin_referer( 'vcaching' ) ) {
				if ( get_option( 'permalink_structure' ) === '' && current_user_can( 'manage_options' ) ) {
					add_action( 'admin_notices', array( $this, 'pretty_permalinks_message' ) );
				}
				if ( null === $this->varnish_ip ) {
					add_action( 'admin_notices', array( $this, 'purge_message_no_ips' ) );
				} else {
					$this->purge_cache();
				}
			} elseif ( isset( $_GET[ $this->purge_cache ] ) && 'cdn' === $_GET[ $this->purge_cache ] && check_admin_referer( 'vcaching' ) ) {
				if ( get_option( 'permalink_structure' ) === '' && current_user_can( 'manage_options' ) ) {
					add_action( 'admin_notices', array( $this, 'pretty_permalinks_message' ) );
				}
				if ( null === $this->varnish_ip ) {
					add_action( 'admin_notices', array( $this, 'purge_message_no_ips' ) );
				} else {
					$purge_id     = time();
					$updated_data = array( 'vcache_purge_id' => $purge_id );
					$this->oc_json_update_option( 'onecom_vcache_info', $updated_data );
					// Purge cache needed after purge CDN
					$this->purge_cache();
				}
			}
		}

		// purge post/page cache from post/page actions
		if ( $this->check_if_purgeable() ) {
			//[28-May-2019] Removing $_SESSION usage
			// if(!session_id()) {
			//     session_start();
			// }
			add_filter(
				'post_row_actions',
				array(
					&$this,
					'post_row_actions',
				),
				0,
				2
			);
			add_filter(
				'page_row_actions',
				array(
					&$this,
					'page_row_actions',
				),
				0,
				2
			);
			if ( isset( $_GET['action'] ) && isset( $_GET['post_id'] ) && ( 'purge_post' === $_GET['action'] || 'purge_page' === $_GET['action'] ) && check_admin_referer( 'vcaching' ) ) {
				$this->purge_post( $_GET['post_id'] );
				//[28-May-2019] Removing $_SESSION usage
				// $_SESSION['vcaching_note'] = $this->notice_message;
				$referer = str_replace( 'purge_varnish_cache=1', '', wp_get_referer() );
				wp_redirect( $referer . ( strpos( $referer, '?' ) ? '&' : '?' ) . 'vcaching_note=' . $_GET['action'] );
			}
		}

		// Assign the result of get_option to $this->override
		$this->override = get_option( $this->prefix . 'override' );

		// Check if $this->override has a truthy value
		if ( $this->override ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'override_ttl' ), 1000 );
		}

		// console purge
		if ( $this->check_if_purgeable() && isset( $_POST['varnish_caching_purge_url'] ) ) {
			$this->purge_url( home_url() . $_POST['varnish_caching_purge_url'] );
		}
		$this->current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'options';
		$this->current_tab = get_option( $this->prefix . 'ssl' );

		require_once plugin_dir_path( __FILE__ ) . '/onecom-addons/inc/class-onecom-vcache-shortcuts.php';
	}

	public function oc_vcache_shortcut_call() {

		if ( ! is_multisite() ) {

			$shortcuts = new Onecom_Vcache_Shortcuts();
		}
	}

	/**
	 * Function to check if mwp
	 */
	public function oc_premium() {
		$features = oc_set_premi_flag();
		return ( isset( $features['data'] ) && ( ! empty( $features['data'] ) ) && ( in_array( 'MWP_ADDON', $features['data'], true ) ) );
	}

	/**
	 * Function to check if pcache feature available
	 */
	public function oc_pcache() {
		// Get premium features
		$features = (array) oc_set_premi_flag();

		// If features received, else false
		if ( isset( $features['data'] ) ) {
			$features = $features['data'];
		} else {
			return false;
		}

		// return true if given feature found, else false
		if ( in_array( 'PERFORMANCE_CACHE', $features, true ) ) {
			return true;
		} else {
			return false;
		}
	}



	/**
	 * Update WordPress option data as a json
	 * option_name - WordPress option meta name
	 * data - Pass array as a key => value
	 * * oc_json_update_option($option_name, array)
	 */
	public function oc_json_update_option( $option_name, $data ) {

		// return if no option_name and data
		if ( empty( $option_name ) || empty( $data ) ) {
			return false;
		}

		// If exising data exists, merge else update as a fresh data
		$option_data = get_site_option( $option_name );
		if ( $option_data && ! empty( $data ) ) {
			$existing_data = json_decode( $option_data, true );
			$new_array     = array_merge( $existing_data, $data );
			return update_site_option( $option_name, json_encode( $new_array ), 'no' );
		} else {
			return update_site_option( $option_name, json_encode( $data ), 'no' );
		}
	}

	public function oc_json_delete_option( $option_name, $key ) {

		// return if no option_name and key
		if ( empty( $option_name ) || empty( $key ) ) {
			return false;
		}

		// If not a valid JSON, or key does not exist, return
		$result = json_decode( get_site_option( $option_name ), true );
		// Number can also be treated as valid json, so also check if array
		if ( json_last_error() === JSON_ERROR_NONE && is_array( $result ) && key_exists( $key, $result ) ) {
			unset( $result[ $key ] );
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get WordPress option json data
	 * option_name - WordPress option meta name
	 * key (optional) - get only certain key value
	 */
	public function oc_json_get_option( $option_name, $key = false ) {

		// If option name does not exit, return
		$option_data = get_site_option( $option_name );

		if ( false === $option_data ) {
			return false;
		}

		// If key exist, return only its value, else return complete option array
		if ( $key ) {
			// If not a valid JSON, or key does not exist, return
			$result = json_decode( get_site_option( $option_name ), true );
			// Number can also be treated as valid json, so also check if array
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $result ) && key_exists( $key, $result ) ) {
				return $result[ $key ];
			} else {
				return false;
			}
		} else {
			return json_decode( get_site_option( $option_name ), true );
		}
	}

	public function override_ttl( $post ) {
		$post_id = isset( $GLOBALS['wp_the_query']->post->ID ) ? $GLOBALS['wp_the_query']->post->ID : 0;
		if ( $post_id && ( is_page() || is_single() ) ) {
			$ttl = get_post_meta( $post_id, $this->prefix . 'ttl', true );
			if ( trim( $ttl ) !== '' ) {
				Header( 'X-VC-TTL: ' . intval( $ttl ), true );
			}
		}
	}

	public function buffer_callback( $buffer ) {
		return $buffer;
	}

	public function buffer_start() {
		ob_start( array( $this, 'buffer_callback' ) );
	}

	public function buffer_end() {
		if ( ob_get_level() > 0 ) {
			ob_end_flush();
		}
	}

	protected function setup_ips_to_hosts() {
		$this->varnish_ip         = get_site_option( $this->prefix . 'ips' );
		$this->varnish_host       = get_site_option( $this->prefix . 'hosts' );
		$this->dynamic_host       = get_site_option( $this->prefix . 'dynamic_host' );
		$this->stats_jsons        = get_site_option( $this->prefix . 'stats_json_file' );
		$this->purge_on_menu_save = get_site_option( $this->prefix . 'purge_menu_save' );
		$varnish_ip               = explode( ',', $this->varnish_ip );
		$varnish_ip               = apply_filters( 'vcaching_varnish_ips', $varnish_ip );
		$varnish_host             = explode( ',', $this->varnish_host );
		$varnish_host             = apply_filters( 'vcaching_varnish_hosts', $varnish_host );
		$stats_jsons              = explode( ',', $this->stats_jsons );

		foreach ( $varnish_ip as $key => $ip ) {
			$this->ips_to_hosts[] = array(
				'ip'        => $ip,
				'host'      => $this->dynamic_host ? $_SERVER['HTTP_HOST'] : $varnish_host[ $key ],
				'statsJson' => isset( $stats_jsons[ $key ] ) ? $stats_jsons[ $key ] : null,
			);
		}
	}

	public function check_if_purgeable() {
		return ( ! is_multisite() && current_user_can( 'activate_plugins' ) ) || current_user_can( 'manage_network' ) || ( is_multisite() && ! current_user_can( 'manage_network' ) && ( SUBDOMAIN_INSTALL || ( ! SUBDOMAIN_INSTALL && ( BLOG_ID_CURRENT_SITE !== $this->blog_id ) ) ) );
	}


	public function purge_message_no_ips() {
		/* translators: %s is link for onecom */
		echo '<div id="message" class="error fade"><p><strong>' . sprintf( __( 'Performance cache works with domains which are hosted on %1$sone.com%2$s.', 'vcaching' ), '<a href="https://one.com/" target="_blank" rel="noopener noreferrer">', '</a>' ) . '</strong></p></div>';
	}

	public function pretty_permalinks_message() {
		$message = '<div id="message" class="error"><p>' . __( 'Performance Cache requires you to use custom permalinks. Please go to the <a href="options-permalink.php">Permalinks Options Page</a> to configure them.', 'vcaching' ) . '</p></div>';
		echo apply_filters( 'ocvc_permalink_notice', $message );
	}

	public function purge_varnish_cache_all_adminbar( $admin_bar ) {
		$admin_bar->add_menu(
			array(
				'id'    => 'purge-all-varnish-cache',
				'title' => '<span class="ab-icon dashicons dashicons-trash"></span>' . __( 'Clear Performance Cache', 'vcaching' ),
				'href'  => wp_nonce_url( add_query_arg( $this->purge_cache, 'cdn' ), 'vcaching' ),
				'meta'  => array(
					'title' => __( 'Clear Performance Cache', 'vcaching' ),
				),
			)
		);

		$admin_bar->add_menu(
			array(
				'id'     => 'purge-onecom-cache-only',
				'parent' => 'purge-all-varnish-cache',
				'title'  => __( 'Clear Cache', 'vcaching' ),
				'href'   => wp_nonce_url( add_query_arg( $this->purge_cache, 1 ), 'vcaching' ),
				'meta'   => array(
					'title' => __( 'Clear Cache', 'vcaching' ),
				),
			)
		);

		$admin_bar->add_menu(
			array(
				'id'     => 'purge-onecom-cdn-only',
				'parent' => 'purge-all-varnish-cache',
				'title'  => __( 'Clear CDN', 'vcaching' ),
				'href'   => wp_nonce_url( add_query_arg( $this->purge_cache, 'cdn' ), 'vcaching' ),
				'meta'   => array(
					'title' => __( 'Clear CDN', 'vcaching' ),
				),
			)
		);
	}

	public function varnish_glance() {
		$url          = wp_nonce_url( admin_url( '?' . $this->purge_cache ), 'vcaching' );
		$button       = '';
		$nopermission = '';
		$intro        = '';
		if ( null === $this->varnish_ip ) {
			$intro .= __( 'Varnish environment not present for Performance cache to work.' );
		} else {
			/* translators: %1$s is the URL performance cache */
			$intro        .= sprintf( __( '<a href="%1$s">Performance Cache</a> automatically purges your posts when published or updated. Sometimes you need a manual flush.', 'vcaching' ), 'http://wordpress.org/plugins/varnish-caching/' );
			$button       .= __( 'Press the button below to force it to purge your entire cache.', 'vcaching' );
			$button       .= '</p><p><span class="button"><a href="' . $url . '"><strong>';
			$button       .= __( 'Purge Performance Cache', 'vcaching' );
			$button       .= '</strong></a></span>';
			$nopermission .= __( 'You do not have permission to purge the cache for the whole site. Please contact your adminstrator.', 'vcaching' );
		}
		if ( $this->check_if_purgeable() ) {
			$text = $intro . ' ' . $button;
		} else {
			$text = $intro . ' ' . $nopermission;
		}
		echo '<p class="varnish-glance">' . $text . '</p>';
	}

	protected function get_register_events() {
		$actions = array(
			'save_post',
			'deleted_post',
			'trashed_post',
			'edit_post',
			'delete_attachment',
			'switch_theme',
		);
		return apply_filters( 'vcaching_events', $actions );
	}

	public function purge_cache() {
		$purge_urls = array_unique( $this->purge_urls );
		if ( empty( $purge_urls ) ) {
			if ( ( isset( $_GET[ $this->purge_cache ] ) || ( 'purge_cache' === $_POST['action'] ) ) && $this->check_if_purgeable() && check_admin_referer( 'vcaching' ) ) {
				$this->purge_url( home_url() . '/?vc-regex' );
			}
		} else {
			foreach ( $purge_urls as $url ) {
				$this->purge_url( $url );
			}
		}
		if ( $this->truncate_notice && false === $this->truncate_notice_shown ) {
			$this->truncate_notice_shown = true;
			$this->notice_message       .= '<br />' . __( 'Truncate message activated. Showing only first 3 messages.', 'vcaching' );
		}
	}

	public function purge_url( $url ) {
		$p = parse_url( $url );

		if ( isset( $p['query'] ) && ( 'vc-regex' === $p['query'] ) ) {
			$pregex      = '.*';
			$purgemethod = 'regex';
		} else {
			$pregex      = '';
			$purgemethod = 'default';
		}

		if ( isset( $p['path'] ) ) {
			$path = $p['path'];
		} else {
			$path = '';
		}

		$schema  = apply_filters( 'vcaching_schema', $this->use_ssl ? 'https://' : 'http://' );
		$purgeme = '';
		foreach ( $this->ips_to_hosts as $key => $ips_to_hosts ) {
			$purgeme = $schema . $ips_to_hosts['ip'] . $path . $pregex;
			$headers = array(
				'host'              => $ips_to_hosts['host'],
				'X-VC-Purge-Method' => $purgemethod,
				'X-VC-Purge-Host'   => $ips_to_hosts['host'],
			);
			if ( ! is_null( $this->purge_key ) ) {
				$headers['X-VC-Purge-Key'] = $this->purge_key;
			}
			$purgeme  = apply_filters( 'ocvc_purge_url', $url, $path, $pregex );
			$headers  = apply_filters( 'ocvc_purge_headers', $url, $headers );
			$response = wp_remote_request(
				$purgeme,
				array(
					'method'    => 'PURGE',
					'headers'   => $headers,
					'sslverify' => false,
				)
			);

			apply_filters( 'ocvc_purge_notices', $response, $purgeme );
			if ( $response instanceof WP_Error ) {
				foreach ( $response->errors as $error => $errors ) {
					$this->notice_message .= '<br />Error ' . $error . '<br />';
					foreach ( $errors as $error => $description ) {
						$this->notice_message .= ' - ' . $description . '<br />';
					}
				}
			} else {
				if ( $this->truncate_notice && $this->truncate_count <= 2 || false === $this->truncate_notice ) {
					$this->notice_message .= '' . __( 'Trying to purge URL : ', 'vcaching' ) . $purgeme;
					preg_match( '/<title>(.*)<\/title>/i', $response['body'], $matches );
					$this->notice_message .= ' => <br /> ' . (isset( $matches[1] ) ? ' => ' . $matches[1] : $response['body']);
					$this->notice_message .= '<br />';
					if ( $this->debug ) {
						$this->notice_message .= $response['body'] . '<br />';
					}
				}
				++$this->truncate_count;
			}
		}

		do_action( 'vcaching_after_purge_url', $url, $purgeme );
	}

	public function purge_post( $post_id, $post = null ) {
		// Do not purge menu items
		if ( 'nav_menu_item' === get_post_type( $post ) && false === $this->purge_on_menu_save ) {
			return;
		}

		// If this is a valid post we want to purge the post, the home page and any associated tags & cats
		// If not, purge everything on the site.
		$valid_post_status = array( 'publish', 'trash' );
		$this_post_status  = get_post_status( $post_id );

		// If this is a revision, stop.
		if ( get_permalink( $post_id ) !== true && ! in_array( $this_post_status, $valid_post_status, true ) ) {
			return;
		} else {
			// array to collect all our URLs
			$listofurls = array();

			// Category purge based on Donnacha's work in WP Super Cache
			$categories = get_the_category( $post_id );
			if ( $categories ) {
				foreach ( $categories as $cat ) {
					array_push( $listofurls, get_category_link( $cat->term_id ) );
				}
			}
			// Tag purge based on Donnacha's work in WP Super Cache
			$tags = get_the_tags( $post_id );
			if ( $tags ) {
				foreach ( $tags as $tag ) {
					array_push( $listofurls, get_tag_link( $tag->term_id ) );
				}
			}

			// Author URL
			array_push(
				$listofurls,
				get_author_posts_url( get_post_field( 'post_author', $post_id ) ),
				get_author_feed_link( get_post_field( 'post_author', $post_id ) )
			);

			// Archives and their feeds
			$archiveurls = array();
			if ( true === get_post_type_archive_link( get_post_type( $post_id ) ) ) {
				array_push(
					$listofurls,
					get_post_type_archive_link( get_post_type( $post_id ) ),
					get_post_type_archive_feed_link( get_post_type( $post_id ) )
				);
			}

			// Post URL
			array_push( $listofurls, get_permalink( $post_id ) );

			// Feeds
			array_push(
				$listofurls,
				get_bloginfo_rss( 'rdf_url' ),
				get_bloginfo_rss( 'rss_url' ),
				get_bloginfo_rss( 'rss2_url' ),
				get_bloginfo_rss( 'atom_url' ),
				get_bloginfo_rss( 'comments_rss2_url' ),
				get_post_comments_feed_link( $post_id )
			);

			// Home Page and (if used) posts page
			array_push( $listofurls, home_url( '/' ) );
			if ( get_option( 'show_on_front' ) === 'page' ) {
				array_push( $listofurls, get_permalink( get_option( 'page_for_posts' ) ) );
			}

			// If Automattic's AMP is installed, add AMP permalink
			if ( function_exists( 'amp_get_permalink' ) ) {
				array_push( $listofurls, amp_get_permalink( $post_id ) );
			}

			// Now flush all the URLs we've collected
			foreach ( $listofurls as $url ) {
				array_push( $this->purge_urls, $url );
			}
		}
		// Filter to add or remove urls to the array of purged urls
		// @param array $purge_urls the urls (paths) to be purged
		// @param int $post_id the id of the new/edited post
		$this->purge_urls = apply_filters( 'vcaching_purge_urls', $this->purge_urls, $post_id );
		$this->purge_cache();
	}

	public function send_headers() {
		if ( function_exists( 'is_user_logged_in' ) && ! is_user_logged_in() ) {
			$exclude_from_cache = false;
			if ( strpos( $_SERVER['REQUEST_URI'], 'favicon.ico' ) === false ) {
				$post_id = url_to_postid( $_SERVER['REQUEST_URI'] );
				if ( 0 !== $post_id && ! empty( get_post_meta( $post_id, '_oct_exclude_from_cache', true ) ) ) {
					$exclude_from_cache = get_post_meta( $post_id, '_oct_exclude_from_cache', true );
				}
			}

			$enable = get_site_option( $this->prefix . 'enable' );
			if ( ( 'true' === $enable || true === $enable || 1 === $enable ) && ! $exclude_from_cache ) {
				Header( 'X-VC-Enabled: true', true );
				if ( is_user_logged_in() ) {
					Header( 'X-VC-Cacheable: NO:User is logged in', true );
					$ttl = 0;
				} else {
					$ttl_conf = get_site_option( $this->prefix . 'ttl' );
					$ttl      = ( trim( $ttl_conf ) ? $ttl_conf : 2592000 );
				}
				Header( 'X-VC-TTL: ' . $ttl, true );
			} else {
				Header( 'X-VC-Enabled: false', true );
			}
		}
	}

	public function wp_login() {
		$cookie     = get_option( $this->prefix . 'cookie' );
		$cookie     = ( strlen( $cookie ) ? $cookie : sha1( md5( uniqid() ) ) );
		$set_cookie = setcookie( $cookie, '', time() - 3600 * 24 * 100, COOKIEPATH, COOKIE_DOMAIN, false, true );
		if ( ! $set_cookie ) {
			// Handle the error appropriately
			error_log( "Failed to set cookie: $cookie" );

		}
	}

	public function wp_logout() {
		$cookie     = get_option( $this->prefix . 'cookie' );
		$cookie     = ( strlen( $cookie ) ? $cookie : sha1( md5( uniqid() ) ) );
		$set_cookie = setcookie( $cookie, '', time() - 3600 * 24 * 100, COOKIEPATH, COOKIE_DOMAIN, false, true );
		if ( ! $set_cookie ) {
			// Handle the error appropriately
			error_log( "Failed to set cookie: $cookie" );

		}
	}


	public function admin_menu() {
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );
	}

	public function add_menu_item() {
		if ( $this->check_if_purgeable() ) {
			add_menu_page( __( 'Performance Cache', 'vcaching' ), __( 'Performance Cache', 'vcaching' ), 'manage_options', 'vcaching' . '-plugin', array( $this, 'settings_page' ), plugins_url() . '/' . 'vcaching' . '/icon.png', 99 );
		}
	}

	public function settings_page() {}



	public function post_row_actions( $actions, $post ) {
		if ( $this->check_if_purgeable() ) {
			$actions = array_merge(
				$actions,
				array(
					'vcaching_purge_post' => sprintf( '<a href="%s">' . __( 'Purge from Varnish', 'vcaching' ) . '</a>', wp_nonce_url( sprintf( 'admin.php?page=vcaching-plugin&tab=console&action=purge_post&post_id=%d', $post->ID ), 'vcaching' ) ),
				)
			);
		}
		return $actions;
	}

	public function page_row_actions( $actions, $post ) {
		if ( $this->check_if_purgeable() ) {
			$actions = array_merge(
				$actions,
				array(
					'vcaching_purge_page' => sprintf( '<a href="%s">' . __( 'Purge from Varnish', 'vcaching' ) . '</a>', wp_nonce_url( sprintf( 'admin.php?page=vcaching-plugin&tab=console&action=purge_page&post_id=%d', $post->ID ), 'vcaching' ) ),
				)
			);
		}
		return $actions;
	}
}

$vcaching = new VCachingOC();

if ( ! class_exists( 'OCVCaching' ) ) {
	include_once 'onecom-addons/class-ocvcaching.php';
}

// Nested Menu
if ( ! class_exists( 'Onecom_Nested_Menu' ) ) {

	require_once plugin_dir_path( __FILE__ ) . '/onecom-addons/inc/lib/onecom-nested-menu.php';
	$onecom_menu = new Onecom_Nested_Menu();
	$onecom_menu->init();
}

if ( ! class_exists( 'ONECOMUPDATER' ) ) {
	require_once plugin_dir_path( __FILE__ ) . '/onecom-addons/inc/class-onecomupdater.php';
}

// WP-CLI
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	$filepath = __DIR__ . '/class-wp-cli-vcaching-purge-command.php';
	if ( file_exists( 'class-wp-cli-vcaching-purge-command.php' ) ) {
		include_once 'class-wp-cli-vcaching-purge-command.php';
	}
}

if ( ! defined( 'OC_HTTP_HOST' ) ) {
	$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
	define( 'OC_HTTP_HOST', $host );
}

if ( ! defined( 'OC_CP_LOGIN_URL' ) ) {
	$domain = $_SERVER['ONECOM_DOMAIN_NAME'] ?? '';
	define( 'OC_CP_LOGIN_URL', sprintf( 'https://one.com/admin/select-admin-domain.do?domain=%s&targetUrl=/admin/managedwp/%s/managed-wp-dashboard.do', $domain, OC_HTTP_HOST ) );
}

if ( ! defined( 'OC_WPR_BUY_URL' ) ) {
	$domain = $_SERVER['ONECOM_DOMAIN_NAME'] ?? '';
	define( 'OC_WPR_BUY_URL', sprintf( 'https://one.com/admin/wprocket-prepare-buy.do?directToDomainAfterPurchase=%s&amp;domain=%s', OC_HTTP_HOST, $domain ) );
}

register_uninstall_hook( __FILE__, 'oc_vcache_plugin_uninstall' );

function oc_vcache_plugin_uninstall() {

	( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_event_themes_and_plugins( 'delete', 'plugin', 'onecom-vcache', 'plugins_page' ) : '' );
}
