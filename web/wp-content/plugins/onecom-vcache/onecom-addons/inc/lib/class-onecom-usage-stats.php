<?php
/**
 * one.com general wp usage stats
 * version 0.1.1
 */


if ( ! class_exists( 'Onecom_Usage_Stats' ) ) {

	class Onecom_Usage_Stats {


		const HOSTING_PACKAGE = 'hosting_package';
		const VERSION         = 'version';

		public function __construct() {
			add_action( 'activated_plugin', array( $this, 'monitor_plugin_activation' ), 10, 2 );
			add_action( 'deactivated_plugin', array( $this, 'monitor_plugin_deactivations' ) );

			// WP Growth data capture
			add_action('wp_login', array( $this, 'login_event'), 10, 2);
			add_action('admin_init', array( $this, 'admin_view_event'));

			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
		}

		// Send wp admin page access weekly event
		public function login_event($user_login, $user)
		{
			$current_week = date('o-\WW'); // 2025-W21

			// Return if consent is not given, event already captured but not for onboarding login
			if (
				( ! isset( $_GET['onboarding-flow'] ) ) &&
				( get_site_option('onecom_data_consent_status', false) !== '1'
					|| get_site_option('ocwp_wp_admin_login') === $current_week)
			) {
				return;
			}

			// Push login stat for current week
			$additional_info['path'] = $_SERVER['REQUEST_URI'] ?? 'unknown';
			(class_exists('OCPushStats') ? \OCPushStats::push_stats_event_themes_and_plugins('ocwp_wp_admin_login', 'blog', 'wp_admin', $this->login_referrer(), $additional_info) : '');
			update_site_option('ocwp_wp_admin_login', $current_week);
		}

		// Send wp admin page access weekly event
		public function admin_view_event()
		{
			
			// Onboarding login captured by query param
			if ( isset( $_GET['onboarding-flow'] ) ) {
				$this->login_event(null, null);
			}

			$current_week = date('o-\WW'); // 2025-W21

			// Return if consent is not given or event already captured
			if (
				get_site_option('onecom_data_consent_status', false) !== '1'
				|| get_site_option('ocwp_wp_admin_page_viewed') === $current_week
			) {
				return;
			}

			// First-time admin view detection
			if (!get_site_option('ocwp_first_admin_view')) {
				update_site_option('ocwp_first_admin_view', $current_week);
				$additional_info['first_view'] = '1';
			}

			// Push admin page view stat and update in db
			$additional_info['path'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'unknown';
			(class_exists('OCPushStats') ? \OCPushStats::push_stats_event_themes_and_plugins('ocwp_wp_admin_page_viewed', 'blog', 'wp_admin', '', $additional_info) : '');
			update_site_option('ocwp_wp_admin_page_viewed', $current_week);
		}

		// Identify referrer for event
		public function login_referrer()
		{
			// Get referrer from request header
			$referrer = $_SERVER['HTTP_REFERER'] ?? '';

			if (strpos($referrer, 'wp-login.php') !== false) {
				$ref = 'wp_default_login_form';
			} elseif (isset($_GET['onecom-auth'])) {
				$ref = 'cp_1_click_login';
			} elseif (isset($_GET['onboarding-flow'])) {
				$ref = 'wp_onboarding';
			} elseif (!empty($referrer)) {
				$ref = $referrer;
			} else {
				$ref = 'unknown';
			}

			return $ref;
		}

		/**
		 * gets and returns users array in required format
		 */
		function get_users_array() {
			$users    = count_users();
			$user_arr = array();

			if ( is_array( $users['avail_roles'] ) ) {

				foreach ( $users['avail_roles'] as $role => $count ) {
					$user_arr[ $role ] = "$count";
				}
			}
			return $user_arr;
		}

		/**
		 * gets and returns plugin array in required format
		 */
		function get_plugins_array() {
			$plugins    = get_plugins();
			$plugin_arr = array();
			foreach ( $plugins as $plugin => $data ) {
				if ( strpos( $plugin, '/' ) ) {
					$plugin_slug = substr( $plugin, 0, strpos( $plugin, '/' ) );
				} else {
					$plugin_slug = $plugin;
				}
				$plugin_arr[ $plugin_slug ]['name']          = $data['Name'];
				$plugin_arr[ $plugin_slug ]['uri']           = $data['PluginURI'];
				$plugin_arr[ $plugin_slug ][ self::VERSION ] = $data['Version'];
				$plugin_arr[ $plugin_slug ]['author']        = $data['Author'];
				$plugin_arr[ $plugin_slug ]['status']        = ( is_plugin_active( $plugin ) ) ? 'active' : 'inactive';
			}
			return $plugin_arr;
		}

		/**
		 * gets and returns themes array in required format
		 */
		function get_themes_array() {
			$themes        = wp_get_themes();
			$theme_arr     = array();
			$current_theme = get_template();
			foreach ( $themes as $theme => $data ) {
				$theme_arr[ $theme ]['name']          = $data->get( 'Name' );
				$theme_arr[ $theme ]['uri']           = $data->get( 'ThemeURI' );
				$theme_arr[ $theme ][ self::VERSION ] = $data->get( 'Version' );
				$theme_arr[ $theme ]['author']        = $data->get( 'Author' );
				$theme_arr[ $theme ]['status']        = ( $theme == $current_theme ) ? 'active' : 'inactive';
			}
			return $theme_arr;
		}

		/**
		 * executes the curl request
		 */
		function curl_request( $payload ) {
			// Get cURL resource
			$curl = curl_init();
			curl_setopt_array(
				$curl,
				array(
					CURLOPT_URL            => MIDDLEWARE_URL . '/collect/usage',
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_VERBOSE        => false,
					CURLOPT_TIMEOUT        => 0,
					CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST  => 'POST',
					CURLOPT_POSTFIELDS     => $payload,
					CURLOPT_HTTPHEADER     => array(
						'Content-Type: application/json',
					),
				)
			);
			@curl_exec( $curl );
			$err = curl_error( $curl );
			// $response = json_decode($response, true);
			// Close request to clear up some resources
			curl_close( $curl );
			if ( $err ) {
				return array(
					'data'    => null,
					'error'   => __( 'Some error occurred, please reload the page and try again.', 'validator' ),
					'success' => false,
				);
			}
			return true;
		}

		/**
		 * Function to detect the plugin name from plugin slug
		 *
		 * @param $plugin_slug
		 *
		 * @return false|string|null
		 */
		public function get_plugin_name_from_slug( $plugin_slug ) {
			// Split the slug by '/'.
			if ( is_string( $plugin_slug ) && ! empty( $plugin_slug ) ) {
				$parts = explode( '/', $plugin_slug );

				// Get the first part (the plugin name).
				$plugin_name = reset( $parts );

				return $plugin_name;
			} else {
				// Return null for invalid input.
				return null;
			}
		}

		/**
		 * Function to return the list of partner plugins
		 *
		 * @return array
		 */
		public function get_partner_plugins(): array {
			// List of plugins to monitor.
			return array(
				'wp-rocket/wp-rocket.php',
				'imagify/imagify.php',
				'one-marketgoo/one-marketgoo.php',
				'seo-by-rank-math/rank-math.php',
				'backwpup/backwpup.php',
				'superb-blocks/plugin.php'
				// Add more plugins to monitor as needed
			);
		}


		/**
		 * /**
		 *  Callback function to monitor partner plugin activations.
		 *
		 *  @return void
		 * /
		 * @param $plugin
		 * @param $network_activation
		 *
		 * @return void
		 */
		public function monitor_plugin_activation( $plugin, $network_activation ) {
			global $pagenow;
			$partner_plugins = $this->get_partner_plugins();

			// Determine referrer for installer, plugin tabs, default plugins or other
			if ( ! empty( $_POST['action'] ) && $_POST['action'] === 'oci_install_dependancy' ) {
				$referrer = 'install_wizard';
			} elseif ( ! empty( $_POST['plugin_type'] ) && $_POST['plugin_type'] === 'recommended' ) {
				$referrer = 'recommended_plugins';
			} elseif ( ! empty( $_POST['plugin_type'] ) && $_POST['plugin_type'] === 'onecom-plugins' ) {
				$referrer = 'onecom_plugins';
			} elseif ( isset( $pagenow ) && $pagenow === 'plugins.php' ) {
				$referrer = 'default_plugins_page';
			} elseif ( isset( $pagenow ) && $pagenow === 'onecom-plugin-installer.php' ) {
				$referrer = 'onecom_plugin_installer';
			} else {
				$referrer = 'unknown';
			}

			// Push stats if one.com related referrers or it is onecom or partner plugin
			if (
				( in_array( $referrer, array( 'install_wizard', 'recommended_plugins', 'onecom_plugins' ), true )
					|| in_array( $plugin, $partner_plugins, true )
					|| strpos( $plugin, 'onecom-' ) === 0 )
				&& ! $network_activation
			) {
				$plugin_name = $this->get_plugin_name_from_slug( $plugin );
				( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_event_themes_and_plugins( 'activate', 'plugin', $plugin_name, $referrer ) : '' );
			}
		}

		/**
		 * Callback function to monitor partner plugin deactivations.
		 *
		 * @param $plugin
		 *
		 * @return void
		 */
		public function monitor_plugin_deactivations( $plugin ) {
			global $pagenow;
			$partner_plugins = $this->get_partner_plugins();

			// Determine referrer for installer, plugin tabs, default plugins or other
			if ( ! empty( $_POST['action'] ) && $_POST['action'] === 'oci_install_dependancy' ) {
				$referrer = 'install_wizard';
			} elseif ( ! empty( $_POST['plugin_type'] ) && $_POST['plugin_type'] === 'recommended' ) {
				$referrer = 'recommended_plugins';
			} elseif ( ! empty( $_POST['plugin_type'] ) && $_POST['plugin_type'] === 'onecom-plugins' ) {
				$referrer = 'onecom_plugins';
			} elseif ( isset( $pagenow ) && $pagenow === 'plugins.php' ) {
				$referrer = 'default_plugins_page';
			} else {
				$referrer = 'unknown';
			}

			// Push stats if one.com related referrers or it is onecom or partner plugin
			if (
				( in_array( $referrer, array( 'install_wizard', 'recommended_plugins', 'onecom_plugins' ), true )
					|| in_array( $plugin, $partner_plugins, true )
					|| strpos( $plugin, 'onecom-' ) === 0
				)
			) {
				$plugin_name = $this->get_plugin_name_from_slug( $plugin );
				( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_event_themes_and_plugins( 'deactivate', 'plugin', $plugin_name, $referrer ) : '' );
			}
		}
	}
}
