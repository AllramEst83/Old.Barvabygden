<?php
/**
 * one.com Stats Push Functionality
 * Version: 0.1.5
 */

if ( ! class_exists( 'OCPushStats' ) ) {

	final class OCPushStats {


		const STATS_URL                  = MIDDLEWARE_URL . '/collect/hit';
		const GENERIC_LOG_URL            = MIDDLEWARE_URL . '/log';
		const ONECOM_DOMAIN              = 'ONECOM_DOMAIN_NAME';
		const SERVER_AGENT               = 'HTTP_USER_AGENT';
		const DOMAIN                     = 'domain';
		const SUB_DOMAIN                 = 'subdomain';
		const WP_USER                    = 'wp_user';
		const WP_ROLE                    = 'wp_role';
		const USER_AGENT                 = 'user_agent';
		const HIT_TYPE                   = 'hit_type';
		const ITEM_AVAIL                 = 'item_avail';
		const HOSTING_PACKAGE            = 'hosting_package';
		const PACKAGE_FEATURES           = 'package_features';
		const CONTENT_TYPE               = 'Content-Type: application/json';
		const ITEM_SOURCE                = 'item_source';
		const ITEM_CATEGORY              = 'item_category';
		const EVENT                      = 'event';
		const EVENT_ACTION               = 'event_action';
		const ITEM_NAME                  = 'item_name';
		const HIT_DURATION               = 'hit_duration';
		const REFERRER                   = 'referrer';
		const WP_VERSION                 = 'wp_version';
		const PLUGIN_VERSION             = 'generic_plugin_version';
		const ADDDITIONAL_INFO           = 'additional_info';
		const WP_LANG                    = 'wp_locale';
		const DEFAULT_IP                 = '0.0.0.0';
		const MOBILE_CHECKOUT            = 'mobile_checkout';
		const CART_POSITION              = 'cart_position';
		const COOKIE_POLICY_LINK_ENABLED = 'cookie_policy_link_enabled';
		const BANNER_STYLE               = 'banner_style';
		const PREMIUM                    = 'premium';
		const PLUGIN                     = 'plugin';
		const ONE_COM_CLUSTER_ID         = 'ONECOM_CLUSTER_ID';
		const ONECOM_BRAND               = 'HTTP_X_ONECOM_BRAND';
		const HTTP_HOST                  = 'HTTP_HOST';


		public function __construct() {
			if ( function_exists( 'add_action' ) ) {
				add_action( 'admin_head', array( $this, 'javascript_handler' ) );
				add_action( 'wp_ajax_handle_ajax_request', array( $this, 'handle_ajax_request' ) );
			}

			if ( ! class_exists( 'Onecom_Usage_Stats' ) ) {
				require __DIR__ . '/class-onecom-usage-stats.php';
				new Onecom_Usage_Stats();
			}
		}

		/**
		 * Function to get the client ip address
		 * */
		public static function onecom_get_client_ip_env() {
			if ( getenv( 'HTTP_CLIENT_IP' ) ) {
				$ipaddress = getenv( 'HTTP_CLIENT_IP' );
			} elseif ( getenv( 'REMOTE_ADDR' ) ) {
				$ipaddress = getenv( 'REMOTE_ADDR' );
			} else {
				$ipaddress = self::DEFAULT_IP;
			}
			return $ipaddress;
		}

		/**
		 * Function to get the domain
		 * */
		public static function get_domain() {
			if ( isset( $_SERVER[ self::ONECOM_DOMAIN ] ) && ! empty( $_SERVER[ self::ONECOM_DOMAIN ] ) && ! is_cluster_domain() ) {
				return $_SERVER[ self::ONECOM_DOMAIN ];
			} elseif ( is_cluster_domain() ) {
				if ( $_SERVER[ self::ONECOM_DOMAIN ] != $_SERVER[ self::HTTP_HOST ] ) {
					$domain = explode( '.', $_SERVER[ self::HTTP_HOST ] );  // Whatever remains after trimming subdomain
					unset( $domain[0] );
					return implode( '.', $domain );
				} else {
					$domain = $_SERVER[ self::ONECOM_DOMAIN ];
				}
			} else {

				return 'localhost';
			}
		}

		public static function get_subdomain() {
			if ( self::get_domain() === 'localhost' ) {
				return null;
			}
			if ( $_SERVER[ self::ONECOM_DOMAIN ] != $_SERVER[ self::HTTP_HOST ] ) {
				$sub_domain = explode( '.', $_SERVER[ self::HTTP_HOST ] );
				return trim( $sub_domain[0] );
			}
			$subdomain = substr( $_SERVER['SERVER_NAME'], 0, -( strlen( $_SERVER[ self::ONECOM_DOMAIN ] ) ) );
			if ( $subdomain && $subdomain !== '' ) {
				return rtrim( $subdomain, '.' );
			} else {
				return 'www';
			}
		}

		/**
		 * Function to get the user name and role
		 * */
		public static function get_users() {
			if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				$oc_user      = array();
				// condition improved as per case WPIN-2551
				$oc_user['name'] = $current_user->display_name ?? '';
				$oc_user['role'] = $current_user->roles[0] ?? '';

			} else {
				$oc_user['name'] = '';
				$oc_user['role'] = '';
			}
			return $oc_user;
		}

		/**
		 * Function to send curl request to stats url
		 * @param $payload
		 * @return void|array|mixed
		 */
		public static function curl_request( $payload ) {

			// Exit if stats push is not eligible
			if (!self::should_send_stats($payload)) {
				return;
			}
			
			// Get cURL resource
			$curl = curl_init();
			curl_setopt_array(
				$curl,
				array(
					CURLOPT_URL            => self::STATS_URL,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_VERBOSE        => false,
					CURLOPT_TIMEOUT        => 0,
					CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST  => 'POST',
					CURLOPT_POSTFIELDS     => $payload,
					CURLOPT_HTTPHEADER     => array(
						self::CONTENT_TYPE,
					),
				)
			);

			// silent call
			@curl_exec( $curl );
			$err = curl_error( $curl );

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
		 * Check if eligible to send stats
		 */
		public static function should_send_stats($payload) {

			// Whitelist onboarding events
			if ( defined('WP_INSTALLING') && WP_INSTALLING === true ) {
				return true;
			}

			// Return false if one.com plugin is not active
			if ( ! is_plugin_active( 'onecom-themes-plugins/onecom-themes-plugins.php' ) ) {
				return false;
			}

			// Allow all events if consent is given
			$data_consent_status = get_site_option('onecom_data_consent_status', false);
			if ('1' === $data_consent_status) {
				return true;
			}

			// Return false if payload is not valid JSON
			$data = json_decode( $payload, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				return false;
			}

			// Allow specific whitelisted event: VM scan
			if (
				isset($data['event_action'], $data['item_name'])
				&& $data['event_action'] === 'scan'
				&& $data['item_name'] === 'vulnerability_monitor'
			) {
				return true;
			}

			// Allow admin login event after onboarding
			if (
				isset($_GET['onboarding-flow'])
				&& isset($data['event_action'])
				&& $data['event_action'] === 'ocwp_wp_admin_login'
			) {
				return true;
			}

			// Allow whitelisted onboarding events
			$whitelisted_event_actions = [
				'ocwp_wpo_welcome_modal_closed',
				'ocwp_wpo_welcome_modal_tour_started'
			];

			if (
				isset($data['event_action'])
				&& in_array($data['event_action'], $whitelisted_event_actions, true)
			) {
				return true;
			}

			return false;
		}

		/**
		 * Generic log function
		 */
		public static function generic_log( $action, $message = '', $error = null ) {
			// exit if no action passed
			if ( ! isset( $action ) && strlen( $action ) ) {
				return false;
			}

			// check if WP constants are available
			if ( ! ( defined( 'ABSPATH' ) && defined( 'WPINC' ) ) ) {
				return false;
			}

			// wp http functions file
			$wp_http = ABSPATH . WPINC . '/http.php';

			// check if wp http functions file exists
			if ( ! file_exists( $wp_http ) ) {
				return false;
			}

			// include wp http functions file
			require_once $wp_http;

			// if message is not an array
			if ( ! is_array( $message ) ) {

				@json_decode( $message );

				// if message is neither an array nor a JSON
				if ( json_last_error() != JSON_ERROR_NONE ) {
					$message = strip_tags( $message );
				}
			} else {
				$message = json_encode( $message );
			}

			$payload = json_encode(
				array(
					'action_type' => strip_tags( $action ),
					'message'     => $message,
					'error'       => $error,
				)
			);

			// post request array
			$postArr = array(
				'method'     => 'POST',
				'timeout'    => 10,
				'compress'   => false,
				'decompress' => true,
				'sslverify'  => true,
				'stream'     => false,
				'body'       => $payload,
				'headers'    => array(
					'X-ONECOM-CLIENT-IP'     => self::onecom_get_client_ip_env(),
					'X-ONECOM-CLIENT-DOMAIN' => self::get_domain(),
				),
			);

			// user agent
			global $wp_version;
			if ( ! empty( $wp_version ) && function_exists( 'home_url' ) ) {
				$postArr['user-agent'] = 'WordPress/' . $wp_version . '; ' . home_url();
			}
			wp_safe_remote_post( self::GENERIC_LOG_URL, $postArr );
			return true;
		}

		/**
		 * Wrapper function of wp core's is_plugin_active
		 * with additional check if the plugin is installed
		 * @param $plugin_path string
		 * @return bool
		 */
		public static function oc_is_plugin_active( $plugin_path ): bool {
			if ( ! file_exists( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin_path ) ) {
				return false;
			}
			return is_plugin_active( $plugin_path );
		}

		public static function oc_get_versions() {
			$ver = array();
			if ( function_exists( 'get_plugin_data' ) && self::oc_is_plugin_active( 'onecom-themes-plugins/onecom-themes-plugins.php' ) ) {
				$ver[ self::PLUGIN_VERSION ] = get_plugin_data( WP_PLUGIN_DIR . '/onecom-themes-plugins/onecom-themes-plugins.php' )['Version'];
			} else {
				$ver[ self::PLUGIN_VERSION ] = '';
			}
			$ver[ self::WP_LANG ]    = ( function_exists( 'get_locale' ) ) ? get_locale() : '';
			$ver[ self::WP_VERSION ] = ( function_exists( 'get_bloginfo' ) ) ? (string) get_bloginfo( 'version' ) : '';
			return $ver;
		}

		public static function oc_get_web_root() {
			if ( isset( $_SERVER['ONECOM_DOCUMENT_ROOT'] ) ) {
				$webroot = $_SERVER['ONECOM_DOCUMENT_ROOT'];
				$webroot = explode( '/', $webroot );
				return end( $webroot );
			}
			return '';
		}


		public static function stats_base_parametres() {
			// fetch transient
			$get_features = get_site_transient( 'oc_validate_domain' );

			// fetch new if transient empty
			if ( empty( $get_features ) || empty( $get_features['data'] ) || empty( $get_features['hosting_package'] ) ) {
				$get_features = oc_validate_domain();
			}

			$get_version                     = self::oc_get_versions();
			$package_features                = isset( $get_features['data'] ) ? json_encode( $get_features['data'] ) : null;
			$hosting_package                 = isset( $get_features['hosting_package'] ) ? $get_features['hosting_package'] : null;
			$webroot                         = self::oc_get_web_root();
			$cluster                         = isset( $_SERVER[ self::ONE_COM_CLUSTER_ID ] ) ? $_SERVER[ self::ONE_COM_CLUSTER_ID ] : '';
			$brand                           = isset( $_SERVER[ self::ONECOM_BRAND ] ) ? $_SERVER[ self::ONECOM_BRAND ] : '';
			$fqdn                            = isset( $_SERVER[ self::HTTP_HOST ] ) ? $_SERVER[ self::HTTP_HOST ] : '';
			$user                            = self::get_users();
			$param                           = array();
			$param[ self::DOMAIN ]           = self::get_domain();
			$param[ self::SUB_DOMAIN ]       = self::get_subdomain();
			$param[ self::WP_USER ]          = $user['name'];
			$param[ self::WP_ROLE ]          = $user['role'];
			$param['ip']                     = self::onecom_get_client_ip_env();
			$param['php_version']            = PHP_VERSION;
			$param[ self::WP_LANG ]          = $get_version[ self::WP_LANG ];
			$param[ self::WP_VERSION ]       = $get_version[ self::WP_VERSION ];
			$param[ self::PLUGIN_VERSION ]   = $get_version[ self::PLUGIN_VERSION ];
			$param[ self::USER_AGENT ]       = $_SERVER[ self::SERVER_AGENT ];
			$param[ self::PACKAGE_FEATURES ] = $package_features;
			$param[ self::HOSTING_PACKAGE ]  = $hosting_package;
			$param['webroot']                = $webroot;
			$param['cluster']                = $cluster;
			$param['brand']                  = $brand;
			$param['fqdn']                   = $fqdn;
			return $param;
		}

		/**
		 * Function to push stats for events of control panel
		 * @param $event_action
		 * @param null $item_category
		 * @param null $item_name
		 * @param null $referrer
		 * @param array $additional_info
		 * @return mixed
		 */
		public static function push_stats_event_control_panel(
			$event_action,
			$item_category = null,
			$item_name = null,
			$referrer = null,
			$additional_info = array()
		) {
			$base_params    = self::stats_base_parametres();
			$dynamic_params = array(
				self::HIT_TYPE      => self::EVENT,
				self::EVENT_ACTION  => $event_action,
				self::ITEM_CATEGORY => $item_category,
				self::ITEM_NAME     => $item_name,
				self::REFERRER      => $referrer,

			);
			$dynamic_params = array_filter(
				$dynamic_params,
				function ( $value ) {
					return ! is_null( $value ) && $value !== '';
				}
			);
			if ( ! empty( $additional_info ) ) {
				$dynamic_params = array_merge( $dynamic_params, $additional_info );
			}
			$payload = json_encode( array_merge( $base_params, $dynamic_params ) );
			return self::curl_request( $payload );
		}


		/**
		 * Function to push stats for events of themes and plugins
		 * @param $event_action
		 * @param null $item_category
		 * @param null $item_name
		 * @param null $referrer
		 * @param array $additional_info
		 * @return boolean
		 */

		public static function push_stats_event_themes_and_plugins(
			$event_action,
			$item_category = null,
			$item_name = null,
			$referrer = null,
			$additional_info = array()
		) {
			$premium = 0;
			if ( $item_category == 'theme' ) {
				if ( $event_action !== 'preview' && $event_action !== 'install' && $event_action !== 'click_upgrade' && $event_action !== 'close_upgrade' ) {
					$theme   = wp_get_theme( $item_name );
					$premium = (int) onecom_is_premium_theme( $theme->display( 'Name', false ) );
				} elseif ( isset( $additional_info[ self::PREMIUM ] ) ) {
					$premium = $additional_info[ self::PREMIUM ];
				} else {
					$premium = 0;
				}
				$key = ( $premium != 0 ) ? 'ptheme' : 'stheme';
			} elseif ( $item_category == self::PLUGIN && $item_name === 'onecom-vcache' ) {
				$premium = 1;
				$key     = 'pcache';

			} elseif ( $item_category == self::PLUGIN ) {
				$key = 'ins';
			} else {
				$key = null;
			}
			$result         = oc_set_premi_flag();
			$item_avail     = (int) oc_pm_features( $key, $result['data'] );
			$base_params    = self::stats_base_parametres();
			$dynamic_params = array(
				self::HIT_TYPE      => self::EVENT,
				self::EVENT_ACTION  => $event_action,
				self::ITEM_CATEGORY => $item_category,
				self::ITEM_NAME     => $item_name,
				self::ITEM_AVAIL    => "$item_avail",
				self::REFERRER      => $referrer,
				self::PREMIUM       => "$premium",
			);
			$dynamic_params = array_filter(
				$dynamic_params,
				function ( $value ) {
					return ! is_null( $value ) && $value !== '';
				}
			);
			if ( ! empty( $additional_info ) && ! isset( $additional_info[ self::PREMIUM ] ) ) {
				$dynamic_params = array_merge( $dynamic_params, $additional_info );
			}
			$payload = ( json_encode( array_merge( $base_params, $dynamic_params ) ) );
			self::curl_request( $payload );
			return true;
		}

		/**
		 * Function to push stats for events of staging
		 * @param $event_action
		 * @param null $item_category
		 * @param null $hit_duration
		 * @param null $size
		 * @param array $additional_info
		 * @return mixed
		 */

		public static function push_stats_event_staging(
			$event_action,
			$item_category = null,
			$hit_duration = null,
			$size = null,
			$additional_info = array()
		) {
			$result         = oc_set_premi_flag();
			$item_avail     = (int) oc_pm_features( 'stg', $result['data'] );
			$base_params    = self::stats_base_parametres();
			$dynamic_params = array(
				self::HIT_TYPE      => self::EVENT,
				self::EVENT_ACTION  => $event_action,
				self::ITEM_CATEGORY => $item_category,
				self::ITEM_AVAIL    => "$item_avail",
				self::HIT_DURATION  => $hit_duration,
				'size'              => $size,
			);
			$dynamic_params = array_filter(
				$dynamic_params,
				function ( $value ) {
					return ! is_null( $value ) && $value !== '';
				}
			);
			if ( ! empty( $additional_info ) ) {
				$dynamic_params = array_merge( $dynamic_params, $additional_info );
			}
			$payload = json_encode( array_merge( $base_params, $dynamic_params ) );
			return self::curl_request( $payload );
		}

		/**
		 * Function to push stats for events of staging or migration
		 * @param $event_action
		 * @param null $item_category
		 * @param null $item_name
		 * @param string $item_avail
		 * @param null $hit_duration
		 * @param array $additional_info for logging any additional information related to event.
		 * @return mixed
		 */

		public static function push_stats_event_migration(
			$event_action,
			$item_category = null,
			$item_name = null,
			$item_avail = '1',
			$hit_duration = null,
			$additional_info = array()
		) {
			$base_params    = self::stats_base_parametres();
			$dynamic_params = array(
				self::HIT_TYPE      => self::EVENT,
				self::EVENT_ACTION  => $event_action,
				self::ITEM_CATEGORY => $item_category,
				self::ITEM_NAME     => $item_name,
				self::ITEM_AVAIL    => "$item_avail",
				self::HIT_DURATION  => $hit_duration,

			);
			$dynamic_params = array_filter(
				$dynamic_params,
				function ( $value ) {
					return ! is_null( $value ) && $value !== '';
				}
			);

			if ( ! empty( $additional_info ) ) {
				$dynamic_params = array_merge( $dynamic_params, $additional_info );
			}
			$payload = json_encode( array_merge( $base_params, $dynamic_params ) );
			return self::curl_request( $payload );
		}

		public static function handle_ajax_request() {
			$event_action    = isset( $_POST['args']['event_action'] ) ? $_POST['args']['event_action'] : '';
			$referrer        = isset( $_POST['args'][ self::REFERRER ] ) ? $_POST['args'][ self::REFERRER ] : '';
			$item_avail      = isset( $_POST['args'][ self::ITEM_AVAIL ] ) ? $_POST['args'][ self::ITEM_AVAIL ] : '';
			$additional_info = isset( $_POST['args'][ self::ADDDITIONAL_INFO ] ) ? $_POST['args'][ self::ADDDITIONAL_INFO ] : '';
			$item_category   = isset( $_POST['args'][ self::ITEM_CATEGORY ] ) ? $_POST['args'][ self::ITEM_CATEGORY ] : '';
			$item_name       = isset( $_POST['args'][ self::ITEM_NAME ] ) ? $_POST['args'][ self::ITEM_NAME ] : '';
			$item_source     = isset( $_POST['args'][ self::ITEM_SOURCE ] ) ? $_POST['args'][ self::ITEM_SOURCE ] : '';
			$response        = '';
			switch ( $item_category ) {
				case 'blog':
				case 'blog_installation':
					$response = self::push_stats_event_control_panel( $event_action, $item_category, $item_name, $referrer, $additional_info );
					break;
				case 'theme':
				case self::PLUGIN:
					$response = self::push_stats_event_themes_and_plugins( $event_action, $item_category, $item_name, $referrer, $additional_info );
					break;
				case 'staging':
					$hit_duration = $_POST['args'][ self::HIT_DURATION ];
					$size         = $_POST['args']['size'];
					$response     = self::push_stats_event_staging(
						$event_action,
						$item_category,
						$hit_duration,
						$size,
						$additional_info
					);
					break;
				case 'migration':
					$hit_duration = $_POST['args'][ self::HIT_DURATION ];
					$response     = self::push_stats_event_migration(
						$event_action,
						$item_category,
						$item_name,
						$item_avail,
						$hit_duration,
						$additional_info
					);
					break;

				case 'setting':
					switch ( $item_source ) {
						case 'performance_cache':
							$response = self::push_stats_performance_cache(
								$event_action,
								$item_category,
								$item_source,
								$additional_info
							);
							break;
						case 'cookie_banner':
							$banner_style               = $_POST['args']['banner_style'];
							$cookie_policy_link_enabled = $_POST['args']['cookie_policy_link_enabled'];
							$response                   = self::push_cookie_banner_stats_request(
								$event_action,
								$item_category,
								$item_name,
								$item_source,
								$banner_style,
								$cookie_policy_link_enabled,
								$additional_info
							);
							break;
						case 'onephoto':
							$response = self::push_stats_onephoto_request(
								$event_action,
								$item_source,
								null,
								null,
								array( 'item_name' => $item_name )
							);
							break;

						case 'online_shop':
							$cart_position   = isset( $_POST['args']['cart_position'] ) ? $_POST['args']['cart_position'] : '';
							$mobile_checkout = isset( $_POST['args']['mobile_checkout'] ) ? $_POST['args']['mobile_checkout'] : '';

							$response = self::push_online_shop_stats_request(
								$event_action,
								$item_category,
								$item_name,
								$item_source,
								$cart_position,
								$mobile_checkout,
								$additional_info
							);
							break;
						default:
							$response = null;
					}
					break;
				case 'misc':
					// A flexible standard function to capture misc stats, supports maximum event parameters
					$item_category = 'setting'; // Since misc is not actual allowed value for item category, map to 'setting'
					$response = self::push_stats_misc_events( $event_action, $item_category, $item_name, $item_source, $referrer, $additional_info );
					break;
				default:
					$response = null;
			}

			if ( $response && function_exists( 'wp_send_json' ) ) {
				wp_send_json( $response );
			} elseif ( $response && ! function_exists( 'wp_send_json' ) ) {
				echo json_encode( $response );
			} else {
				return false;
			}
		}


		public static function javascript_handler() {
			?>
			<script>
                function oc_push_stats_by_js(args) {

                    let eventAction = 'handle_ajax_request';
                    let ajaxUrl = ajaxurl;

                    try {
                        //Below block will be in action during onboarding
                        // Added condition to remove keep duplicate code in onboarding
                        if ($.isPlainObject(oci.LANG)) {
                            eventAction = 'push_onboarding_stats';
                            ajaxUrl = oci.ajaxurl;
                        }
                    } catch (e) {
                        //do nothing
                    }

                    if (!args) {
                        return false;
                    }
                    let data;
                    if (typeof (args.item_category) !== 'undefined' && typeof (args.event_action) !== 'undefined') {
                        data = {
                            'action': eventAction,
                            'args': args
                        };
                    } else {
                        return false;
                    }
                    jQuery.post(ajaxUrl, data, function (response) {

                    });
                }
			</script>

			<?php
		}


		/**
		 * Function to push stats for event action pageview
		 * @param $page_name
		 * @param null $item_category
		 * @param array $additional_info
		 * @return array|mixed
		 */

		public static function push_pageview_stats_request(
			$page_name,
			$item_category = null,
			$additional_info = array()
		) {
			$result         = oc_set_premi_flag();
			$item_avail     = (int) oc_pm_features( 'stg', $result['data'] );
			$base_params    = self::stats_base_parametres();
			$dynamic_params = array(
				self::HIT_TYPE      => 'pageview',
				'page_name'         => $page_name,
				self::ITEM_CATEGORY => $item_category,
				self::ITEM_AVAIL    => "$item_avail",
			);
			if ( ! empty( $additional_info ) ) {
				$dynamic_params = array_merge( $dynamic_params, $additional_info );
			}
			$payload = json_encode( array_merge( $base_params, $dynamic_params ) );
			return self::curl_request( $payload );
		}

		/**
		 * Function to push stats for events of one photo
		 * @param $event_action
		 * @param null $item_source
		 * @param null $onephoto_email
		 * @param null $image_count
		 * @param null $video_count
		 * @param array $additional_info
		 * @return array|mixed
		 */

		public static function push_stats_onephoto_request(
			$event_action,
			$item_source = null,
			$image_count = null,
			$video_count = null,
			$additional_info = array()
		) {
			$result         = oc_set_premi_flag();
			$item_avail     = (int) oc_pm_features( 'ins', $result['data'] );
			$base_params    = self::stats_base_parametres();
			$dynamic_params = array(
				self::HIT_TYPE     => self::EVENT,
				self::EVENT_ACTION => $event_action,
				self::ITEM_SOURCE  => $item_source,
				self::ITEM_AVAIL   => "$item_avail",
				'image_count'      => $image_count,
				'video_count'      => $video_count,
			);
			$dynamic_params = array_filter(
				$dynamic_params,
				function ( $value ) {
					return ! is_null( $value ) && $value !== '';
				}
			);
			if ( ! empty( $additional_info ) ) {
				$dynamic_params = array_merge( $dynamic_params, $additional_info );
			}
			$payload = json_encode( array_merge( $base_params, $dynamic_params ) );
			return self::curl_request( $payload );
		}

		/**
		 * Function to push stats for events of performance cache
		 * @param $event_action
		 * @param $item_category
		 * @param null $item_name
		 * @param null $item_source
		 * @param array $additional_info
		 * @return array|mixed
		 */

		public static function push_stats_performance_cache(
			$event_action,
			$item_category = null,
			$item_name = null,
			$item_source = null,
			$additional_info = array()
		) {
			$result         = oc_set_premi_flag();
			$item_avail     = (int) oc_pm_features( 'pcache', $result['data'] );
			$base_params    = self::stats_base_parametres();
			$dynamic_params = array(
				self::HIT_TYPE      => self::EVENT,
				self::EVENT_ACTION  => $event_action,
				self::ITEM_CATEGORY => $item_category,
				self::ITEM_NAME     => $item_name,
				self::ITEM_SOURCE   => $item_source,
				self::ITEM_AVAIL    => "$item_avail",
			);

			$dynamic_params = array_filter(
				$dynamic_params,
				function ( $value ) {
					return ! is_null( $value ) && $value !== '';
				}
			);
			if ( ! empty( $additional_info ) ) {
				$dynamic_params = array_merge( $dynamic_params, $additional_info );
			}
			$payload = json_encode( array_merge( $base_params, $dynamic_params ) );
			return self::curl_request( $payload );
		}

		/**
		 * Function to push stats for misc events
		 */
		public static function push_stats_misc_events(
			$event_action,
			$item_category = null,
			$item_name = null,
			$item_source = null,
			$referrer = null,
			$additional_info = array()
		) {
			$result         = oc_set_premi_flag();
			$item_avail     = (int) oc_pm_features( 'ins', $result['data'] );
			$base_params    = self::stats_base_parametres();
			$dynamic_params = array(
				self::HIT_TYPE                   => self::EVENT,
				self::EVENT_ACTION               => $event_action,
				self::ITEM_CATEGORY              => $item_category,
				self::ITEM_NAME                  => $item_name,
				self::ITEM_SOURCE                => $item_source,
				self::REFERRER					 => $referrer,
				self::ITEM_AVAIL                 => "$item_avail",
			);

			$dynamic_params = array_filter(
				$dynamic_params,
				function ( $value ) {
					return ! is_null( $value ) && $value !== '';
				}
			);
			if ( ! empty( $additional_info ) ) {
				$dynamic_params = array_merge( $dynamic_params, $additional_info );
			}
			$payload = json_encode( array_merge( $base_params, $dynamic_params ) );
			return self::curl_request( $payload );
		}

		/**
		 * Function to push stats for events of cookie banner
		 * @param $event_action
		 * @param null $item_category
		 * @param null $item_name
		 * @param null $item_source
		 * @param null $banner_style
		 * @param null $cookie_policy_link_enabled
		 * @param array $additional_info
		 * @return array|mixed
		 */

		public static function push_cookie_banner_stats_request(
			$event_action,
			$item_category = null,
			$item_name = null,
			$item_source = null,
			$banner_style = null,
			$cookie_policy_link_enabled = null,
			$additional_info = array()
		) {
			$result         = oc_set_premi_flag();
			$item_avail     = (int) oc_pm_features( 'ins', $result['data'] );
			$base_params    = self::stats_base_parametres();
			$dynamic_params = array(
				self::HIT_TYPE                   => self::EVENT,
				self::EVENT_ACTION               => $event_action,
				self::ITEM_CATEGORY              => $item_category,
				self::ITEM_NAME                  => $item_name,
				self::ITEM_SOURCE                => $item_source,
				self::ITEM_AVAIL                 => "$item_avail",
				self::BANNER_STYLE               => $banner_style,
				self::COOKIE_POLICY_LINK_ENABLED => $cookie_policy_link_enabled,
			);

			$dynamic_params = array_filter(
				$dynamic_params,
				function ( $value ) {
					return ! is_null( $value ) && $value !== '';
				}
			);
			if ( ! empty( $additional_info ) ) {
				$dynamic_params = array_merge( $dynamic_params, $additional_info );
			}
			$payload = json_encode( array_merge( $base_params, $dynamic_params ) );
			return self::curl_request( $payload );
		}

		/**
		 * Stringify array integers (also in nested arrays)
		 * @param array $array
		 * @return array
		 */
		public static function castToString( $array = array() ) {
			// seat belt
			if ( empty( $array ) ) {
				return $array;
			}

			$array = json_decode( json_encode( $array ), 1 );

			return array_map(
				function ( $v ) {
					if ( is_array( $v ) ) {
						return self::castToString( $v );
					}
					$v = (string) $v; // Cast $v to string
					// Replace occurrences of '&amp;' with 'and' to prevent stats json corruption
					$v = str_replace( array( ' &amp; ', ' & ' ), array( ' and ', ' and ' ), $v );
					return $v;
				},
				$array
			);
		}

		/**
		 * Function to push stats for events of Vulnerability monitor
		 * @param $event_action
		 * @param null $item_category
		 * @param null $item_name
		 * @param array $additional_info
		 * @return array|mixed
		 */

		public static function push_vul_monitor_stats(
			$event_action,
			$item_category = null,
			$item_name = null,
			$additional_info = array()
		) {
			$result         = oc_set_premi_flag();
			$item_avail     = (int) oc_pm_features( 'ins', $result['data'] );
			$base_params    = self::stats_base_parametres();
			$dynamic_params = array(
				self::HIT_TYPE      => self::EVENT,
				self::EVENT_ACTION  => $event_action,
				self::ITEM_CATEGORY => $item_category,
				self::ITEM_NAME     => $item_name,
				self::ITEM_AVAIL    => "$item_avail",
			);
			$dynamic_params = array_filter(
				$dynamic_params,
				function ( $value ) {
					return ! is_null( $value ) && $value !== '';
				}
			);
			if ( ! empty( $additional_info ) ) {
				$additional_info = self::castToString( $additional_info );
				$key             = array_key_first( $additional_info );

				$dynamic_params = array_merge( $dynamic_params, array( $key => json_encode( $additional_info[ $key ] ) ) );
			}
			$payload = json_encode( array_merge( $base_params, $dynamic_params ) );
			return self::curl_request( $payload );
		}


		/**
		 * Function to push stats for events of Health monitor
		 * @param $event_action
		 * @param null $item_category
		 * @param null $item_name
		 * @param null $check
		 * @param array $scan_result
		 * @param array $additional_info
		 * @return array|mixed
		 */

		public static function push_health_monitor_stats_request(
			$event_action,
			$item_category = null,
			$item_name = null,
			$check = null,
			$scan_result = array(),
			$additional_info = array()
		) {
			$result         = oc_set_premi_flag();
			$item_avail     = (int) oc_pm_features( 'ins', $result['data'] );
			$base_params    = self::stats_base_parametres();
			$dynamic_params = array(
				self::HIT_TYPE      => self::EVENT,
				self::EVENT_ACTION  => $event_action,
				self::ITEM_CATEGORY => $item_category,
				self::ITEM_NAME     => $item_name,
				self::ITEM_AVAIL    => "$item_avail",
				'check'             => $check,
				'scan_result'       => json_encode( $scan_result ),
			);
			$dynamic_params = array_filter(
				$dynamic_params,
				function ( $value ) {
					return ! is_null( $value ) && $value !== '';
				}
			);
			if ( ! empty( $additional_info ) ) {
				$dynamic_params = array_merge( $dynamic_params, $additional_info );
			}
			$payload = json_encode( array_merge( $base_params, $dynamic_params ) );
			return self::curl_request( $payload );
		}


		/**
		 * Function to push stats for events of online shop
		 * @param $event_action
		 * @param null $item_category
		 * @param null $item_name
		 * @param null $item_source
		 * @param null $cart_position
		 * @param null $mobile_checkout
		 * @param array $additional_info
		 * @return array|mixed
		 */
		public static function push_online_shop_stats_request(
			$event_action,
			$item_category = null,
			$item_name = null,
			$item_source = null,
			$cart_position = null,
			$mobile_checkout = null,
			$additional_info = array()
		) {
			$result         = oc_set_premi_flag();
			$item_avail     = (int) oc_pm_features( 'ins', $result['data'] );
			$base_params    = self::stats_base_parametres();
			$dynamic_params = array(
				self::HIT_TYPE        => self::EVENT,
				self::EVENT_ACTION    => $event_action,
				self::ITEM_CATEGORY   => $item_category,
				self::ITEM_NAME       => $item_name,
				self::ITEM_SOURCE     => $item_source,
				self::ITEM_AVAIL      => "$item_avail",
				self::CART_POSITION   => $cart_position,
				self::MOBILE_CHECKOUT => $mobile_checkout,
			);
			$dynamic_params = array_filter(
				$dynamic_params,
				function ( $value ) {
					return ! is_null( $value ) && $value !== '';
				}
			);
			if ( ! empty( $additional_info ) ) {
				$dynamic_params = array_merge( $dynamic_params, $additional_info );
			}
			$payload = json_encode( array_merge( $base_params, $dynamic_params ) );
			return self::curl_request( $payload );
		}
	}
}
$data = new OCPushStats();