<?php
/**
 * Name: Validator
 * Version: 0.5.0
 * Copyright: one.com
 * Any type of modification/duplication/distribution of this script is strictly prohibited.
 */

// Essential declarations
if ( ! defined( 'OC_VALIDATOR_DOMAIN' ) ) {
	define( 'OC_VALIDATOR_DOMAIN', 'validator' );
}
if ( ! defined( 'OC_DOMAIN_NAME' ) ) {
	define( 'OC_DOMAIN_NAME', isset( $_SERVER['ONECOM_DOMAIN_NAME'] ) ? $_SERVER['ONECOM_DOMAIN_NAME'] : '' );
}
if ( ! defined( 'OC_CLUSTER_ID' ) ) {
	define( 'OC_CLUSTER_ID', isset( $_SERVER['ONECOM_CLUSTER_ID'] ) ? $_SERVER['ONECOM_CLUSTER_ID'] : '' );
}

if ( ! defined( 'OC_WEBCONFIG_ID' ) ) {
	define( 'OC_WEBCONFIG_ID', isset( $_SERVER['ONECOM_WEBCONFIG_ID'] ) ? $_SERVER['ONECOM_WEBCONFIG_ID'] : '' );
}

if ( ! defined( 'OC_BRAND_NAME' ) ) {
	define( 'OC_BRAND_NAME', isset( $_SERVER['HTTP_X_ONECOM_BRAND'] ) ? $_SERVER['HTTP_X_ONECOM_BRAND'] : 'one.com' );
}

if ( ! defined( 'OC_WP_API' ) ) {
	define( 'OC_WP_API', empty( $_SERVER['ONECOM_WP_ADDONS_API'] ) ? '' : $_SERVER['ONECOM_WP_ADDONS_API'] );
}

// Formal Sonar string repetition fixes
if ( ! defined( 'OC_GENERIC_ERR_MSG' ) ) {
	define( 'OC_GENERIC_ERR_MSG', __( 'Some error occurred, please reload the page and try again.', OC_VALIDATOR_DOMAIN ) );
}
if ( ! defined( 'OC_GENERIC_LEARN_MORE' ) ) {
	define( 'OC_GENERIC_LEARN_MORE', __( 'Learn more', OC_VALIDATOR_DOMAIN ) );
}
if ( ! defined( 'OC_MAIN_GUIDE_STR' ) ) {
	define( 'OC_MAIN_GUIDE_STR', 'main_guide' );
}
if ( ! defined( 'OC_DISC_GUIDE_STR' ) ) {
	define( 'OC_DISC_GUIDE_STR', 'discouraged_guide' );
}
if ( ! defined( 'OC_COOKIE_GUIDE_STR' ) ) {
	define( 'OC_COOKIE_GUIDE_STR', 'cookie_guide' );
}
if ( ! defined( 'OC_STG_GUIDE_STR' ) ) {
	define( 'OC_STG_GUIDE_STR', 'staging_guide' );
}
if ( ! defined( 'OC_PRM_PAGE_STR' ) ) {
	define( 'OC_PRM_PAGE_STR', 'premium_page' );
}
if ( ! defined( 'OC_ERR_STR' ) ) {
	define( 'OC_ERR_STR', 'error' );
}
if ( ! defined( 'OC_SUCCESS_STR' ) ) {
	define( 'OC_SUCCESS_STR', 'success' );
}
if ( ! defined( 'OC_THM_STR' ) ) {
	define( 'OC_THM_STR', 'theme' );
}
if ( ! defined( 'OC_THMS_STR' ) ) {
	define( 'OC_THMS_STR', 'themes' );
}
if ( ! defined( 'OC_FTR_STR' ) ) {
	define( 'OC_FTR_STR', 'feature' );
}
if ( ! defined( 'OC_AUTHOR_STR' ) ) {
	define( 'OC_AUTHOR_STR', 'Author' );
}
if ( ! defined( 'OC_NAME_STR' ) ) {
	define( 'OC_NAME_STR', 'Name' );
}
if ( ! defined( 'OC_ITM_COUNT_STR' ) ) {
	define( 'OC_ITM_COUNT_STR', 'item_count' );
}
if ( ! defined( 'OC_PUSH_STATS' ) ) {
	define( 'OC_PUSH_STATS', 'OCPushStats' );
}
if ( ! defined( 'PREMIUM' ) ) {
	define( 'PREMIUM', 'premium' );
}


// Item Identifiers
if ( ! defined( 'OC_ID_OCI' ) ) {
	define( 'OC_ID_OCI', 'ONE_CLICK_INSTALL' );
}
if ( ! defined( 'OC_ID_PRM_THMS' ) ) {
	define( 'OC_ID_PRM_THMS', 'PREMIUM_THEMES' );
}
if ( ! defined( 'OC_ID_STD_THMS' ) ) {
	define( 'OC_ID_STD_THMS', 'STANDARD_THEMES' );
}
if ( ! defined( 'ONECOM_WP_CORE_VERSION' ) ) {
	global $wp_version;
	define( 'ONECOM_WP_CORE_VERSION', $wp_version );
}
if ( ! defined( 'ONECOM_PHP_VERSION' ) ) {
	define( 'ONECOM_PHP_VERSION', phpversion() );
}

/* send validation headers with api calls */
add_filter( 'http_request_args', 'oc_add_http_headers', 10, 2 );

/* TOTP lib */

use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;

$filepath = __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if ( file_exists( $filepath ) ) {
	include $filepath;

}

//To check is domain is cluster domain
if ( ! function_exists( 'is_cluster_domain' ) ) {
	function is_cluster_domain() {
		return ! empty( OC_CLUSTER_ID ) && ! empty( OC_WEBCONFIG_ID );
	}
}

//To get the domain name
if ( ! function_exists( 'oc_get_domain_name' ) ) {
	function oc_get_domain_name(): string {
		//simply return if localhost installation
		//if define X_ONECOM_CLIENT_DOMAIN in wp-config file then simply return as domain
		//check is ONECOM_DOMAIN_NAME is not empty then simply return as domain name
		//else check is cluster model domain by checking isset ONECOM_CLUSTER_ID and ONECOM_WEBCONFIG_ID
		//if yes, then return empty string
		//if not, then simply return the HTTP_HOST

		$server_name = isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : '';

		if ( $server_name === 'localhost' ) {
			return 'localhost';
		}

		$http_host          = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '';
		$onecom_domain_name = isset( $_SERVER['ONECOM_DOMAIN_NAME'] ) ? $_SERVER['ONECOM_DOMAIN_NAME'] : '';
		//return if defined X_ONECOM_CLIENT_DOMAIN in wp-config
		//TODO: Only using for testing purpose,
		//TODO: CRM have still not yet moved their hosting package structure to cluster model and still consider a domain as a unique customer and have its all hosting package linked to domain itself. We have no ETA/hint from CRM that they are going to shift their structure or not.
		//TODO: Remove the below code block during merge or after testing
		if ( defined( 'X_ONECOM_CLIENT_DOMAIN' ) && ! empty( X_ONECOM_CLIENT_DOMAIN ) ) {
			return X_ONECOM_CLIENT_DOMAIN;
		}

		//Return domain name if ONECOM_DOMAIN_NAME is set
		if ( ! empty( $onecom_domain_name ) && ! is_cluster_domain() ) {
			return $onecom_domain_name;
		} elseif ( is_cluster_domain() ) {
			//If found the domain then simply send domain name as it is else send empty string.
			if ( ! empty( $onecom_domain_name ) ) {
				return $onecom_domain_name;
			}
			return '';
		} else {
			//if ONECOM_DOMAIN_NAME and not cluster model then return HTTP_HOST
			return $http_host;
		}
	}
}

if ( ! defined( 'OC_DOMAIN_NAME' ) ) {
	$domain_name = oc_get_domain_name();
	define( 'OC_DOMAIN_NAME', ! empty( $domain_name ) ? $domain_name : '' );
}

add_action( 'init', 'oc_validator_load_textdomain' );
if ( ! function_exists( 'oc_validator_load_textdomain' ) ) {
	function oc_validator_load_textdomain() {
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
		if ( $current_locale === 'fi' ) {
			load_textdomain( OC_VALIDATOR_DOMAIN, __DIR__ . '/languages/validator-fi_FI.mo' );
		} elseif ( $current_locale === 'nb_NO' ) {
			load_textdomain( OC_VALIDATOR_DOMAIN, __DIR__ . '/languages/validator-no_NO.mo' );
		}
		if ( in_array( get_locale(), $locales_with_translation ) ) {
			load_plugin_textdomain( OC_VALIDATOR_DOMAIN, false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) . trailingslashit( 'languages' ) );
		} else {
			load_textdomain( OC_VALIDATOR_DOMAIN, __DIR__ . '/languages/validator-en_GB.mo' );
		}
	}
}

// fallback check for MIDDLEWARE_URL, define it if not already defined
if ( ! defined( 'MIDDLEWARE_URL' ) ) {
	$api_version = 'v1.0';
	if ( ! empty( OC_WP_API ) ) {
		$ONECOM_WP_ADDONS_API = OC_WP_API;
	} elseif ( defined( 'ONECOM_WP_ADDONS_API' ) && ONECOM_WP_ADDONS_API != '' && ONECOM_WP_ADDONS_API ) {
		$ONECOM_WP_ADDONS_API = ONECOM_WP_ADDONS_API;
	} else {
		$ONECOM_WP_ADDONS_API = 'https://wpapi.one.com/';
	}
	$ONECOM_WP_ADDONS_API = rtrim( $ONECOM_WP_ADDONS_API, '/' );
	define( 'MIDDLEWARE_URL', $ONECOM_WP_ADDONS_API . '/api/' . $api_version );
}

if ( ! function_exists( 'oc_generate_totp' ) ) {
	function oc_generate_totp( $valid_for = 30, $length = 6 ) {

		//check is cluster domain
		//TODO: Fix cluster TOTP logic. Use domain TOTP meanwhile.
		/*if (is_cluster_domain()) {
			$get_cluster_totp = oc_generate_totp_for_cluster();
			return $get_cluster_totp;
		}*/

		$fileString = '{}';
		if ( file_exists( '/run/domain.conf' ) ) {
			$fileString = trim( file_get_contents( '/run/domain.conf' ) );
		} elseif ( file_exists( '/run/mail.conf' ) ) {
			$fileString = trim( file_get_contents( '/run/mail.conf' ) );
		}

		$domainInfo = json_decode( $fileString );
		$hash       = 'oc';
		if ( isset( $domainInfo->hash ) && $domainInfo->hash ) {
			$hash = $domainInfo->hash;
		}
		$mySecret = trim( Base32::encodeUpper( $hash ) );
		$otp      = TOTP::create( $mySecret, $valid_for, 'sha1', $length );

		return $otp->now();
	}
}

//TODO: Currently not using
if ( ! function_exists( 'oc_generate_totp_for_cluster' ) ) {
	function oc_generate_totp_for_cluster( $valid_for = 30, $length = 6 ) {
		$fileString = '{}';
		if ( file_exists( '/run/mail.conf' ) ) {
			$fileString = trim( file_get_contents( '/run/mail.conf' ) );
		} else {
			error_log( 'mail.conf file does\'nt exist on cluster' );
			return 'mail.conf file does\'nt exist on cluster';
		}

		$domainInfo = json_decode( $fileString );

		$hash = 'oc';
		if ( isset( $domainInfo->wp->hash ) && $domainInfo->wp->hash ) {
			$hash = $domainInfo->wp->hash;
		}

		global $webconfig_name;

		$webconfig_name = $domainInfo->wp->webconfig;

		$mySecret = trim( Base32::encodeUpper( $hash ) );
		$otp      = TOTP::create( $mySecret, $valid_for, 'sha1', $length );

		return $otp->now();
	}
}

if ( ! function_exists( 'oc_validate_domain' ) ) {
	function oc_validate_domain( $force = false, $domain = null ) {
		// check transient
		$oc_validate_domain = get_site_transient( 'oc_validate_domain' );
		if ( ! empty( $oc_validate_domain ) && ! $force ) {
			return $oc_validate_domain;
		}
		if ( ! $domain ) {
			$domain = isset( $_SERVER['ONECOM_DOMAIN_NAME'] ) ? $_SERVER['ONECOM_DOMAIN_NAME'] : false;
		}
		//TODO: uncomment later
		if ( ! $domain /*&& !is_cluster_domain()*/ ) {
			return array(
				'data'    => null,
				'error'   => 'Empty domain',
				'success' => false,
			);
		}

		global $webconfig_name;
		$totp = oc_generate_totp();

		//change curl url
		if ( is_cluster_domain() ) {
			//create header for cluster model
			$curl_url = MIDDLEWARE_URL . '/features/cluster';

			$http_header = array(
				'Cache-Control: no-cache',
				'X-Onecom-Client-Domain: ' . $domain, //need to use from wp-config if available otherwise use domain parse
				'X-TOTP: ' . $totp,
				'cache-control: no-cache',
			);

			$http_header[] = 'X-ONECOM-CLUSTER-ID: ' . OC_CLUSTER_ID;
			$http_header[] = 'X-ONECOM-WEBCONFIG-NAME: ' . $_SERVER['HTTP_X_GROUPONE_WEBCONFIG_NAME'];

		} else {
			//prepare headers for domain model
			$curl_url    = MIDDLEWARE_URL . '/features';
			$http_header = array(
				'Cache-Control: no-cache',
				'X-Onecom-Client-Domain: ' . $domain, //need to use from wp-config if available otherwise use domain parse
				'X-TOTP: ' . $totp,
				'cache-control: no-cache',
			);
		}

		$curl = curl_init();
		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => $curl_url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST  => 'GET',
				CURLOPT_HTTPHEADER     => $http_header,
			)
		);
		$response = curl_exec( $curl );
		$response = json_decode( $response, true );
		$err      = curl_error( $curl );
		curl_close( $curl );

		if ( $err ) {
			return array(
				'data'    => null,
				'error'   => __( 'Some error occurred, please reload the page and try again.', 'validator' ),
				'success' => false,
			);
		} else {
			// save transient for next calls
			if ( is_blog_installed() ) {
				set_site_transient( 'oc_validate_domain', $response, 12 * HOUR_IN_SECONDS );
			}
			// return latest response
			return $response;
		}
	}
}
add_action( 'admin_init', 'oc_set_premi_flag' );
if ( ! function_exists( 'oc_set_premi_flag' ) ) {
	function oc_set_premi_flag( $force = false ) {
		$oc_premi_flag = get_site_transient( 'oc_premi_flag' );
		if ( ( ! $oc_premi_flag ) || $force ) {
			$oc_premi_flag = oc_validate_domain( $force );
			if ( isset( $oc_premi_flag['data'] ) && $oc_premi_flag['data'] ) {
				if ( is_blog_installed() ) {
					set_site_transient( 'oc_premi_flag', $oc_premi_flag['data'], 12 * HOUR_IN_SECONDS );
				}
			}
		}

		if ( ! isset( $oc_premi_flag['data'] ) ) {
			$oc_premi_flag['data'] = $oc_premi_flag;
		}

		return $oc_premi_flag;
	}
}
// hook onto WP CRON to check transients
add_action( 'wp_version_check', 'oc_set_premi_flag_cron' );
if ( ! function_exists( 'oc_set_premi_flag_cron' ) ) {
	function oc_set_premi_flag_cron() {
		oc_set_premi_flag( true );
	}
}

/**
 * Feature mapping
 */
if ( ! function_exists( 'oc_pm_features' ) ) {
	function oc_pm_features( $key, $val ) {
		$operations = array(
			'ins'    => OC_ID_OCI,
			'stg'    => 'STAGING_ENV',
			'stheme' => OC_ID_STD_THMS,
			'ptheme' => OC_ID_PRM_THMS,
			'pcache' => 'PERFORMANCE_CACHE',
			'mwp'    => 'MWP_ADDON',
		);
		if ( ! array_key_exists( $key, $operations ) ) {
			return false;
		}

		return in_array( $operations[ $key ], (array) $val );
	}
}

/**
 * Function oc_get_logline()
 * Function to prepare log data to be sent to WPAPI
 *
 * @param array $post_data An array of varialbles intercepted from $_POST.
 *
 * @return string
 * @since v0.1.3
 */
if ( ! function_exists( 'oc_get_logline' ) ) {
	function oc_get_logline( $post_data ) {
		$is_premium = strip_tags( $post_data['isPremium'] );
		$message    = "isPremium:$is_premium";

		$state = 'state';

		$feature_condition = ( isset( $post_data[ OC_FTR_STR ] ) && ( $post_data[ OC_FTR_STR ] != '' ) );
		$theme_condition   = ( isset( $post_data[ OC_THM_STR ] ) && ( $post_data[ OC_THM_STR ] != '' ) );

		if ( ! ( $feature_condition || $theme_condition ) ) {
			return false;
		}

		if ( isset( $post_data[ OC_FTR_STR ] ) && $post_data[ OC_FTR_STR ] != '' ) {
			$feature  = strip_tags( $post_data[ OC_FTR_STR ] );
			$message .= ";feature:$feature";
		}

		if ( isset( $post_data[ $state ] ) && $post_data[ $state ] != '' ) {
			$state    = filter_var( $post_data[ $state ], FILTER_SANITIZE_NUMBER_INT );
			$message .= ";state:$state";
		}

		if ( isset( $post_data['featureAction'] ) && $post_data['featureAction'] != '' ) {
			$feature_action = strip_tags( $post_data['featureAction'] );
			$message       .= ";featureAction:$feature_action";
		}

		if ( isset( $post_data[ OC_THM_STR ] ) && ( $post_data[ OC_THM_STR ] != '' ) ) {
			$theme    = strip_tags( $post_data[ OC_THM_STR ] );
			$message .= ";theme:$theme";
		}

		//append the available features at the end
		$feature_array  = oc_set_premi_flag( true );
		$feature_string = implode( '|', $feature_array['data'] );
		$feature_string = rtrim( $feature_string, '|' );

		return $message . ";features_available:$feature_string";
	}
}

/**
 * Function to handle validation by ajax
 */
add_action( 'wp_ajax_oc_validate_action', 'oc_validate_action_cb' );

if ( ! function_exists( 'oc_validate_action_cb' ) ) {
	function oc_validate_action_cb() {
		$data        = isset( $_POST['operation'] ) ? strip_tags( $_POST['operation'] ) : '';
		$action_type = isset( $_POST['actionType'] ) ? strip_tags( $_POST['actionType'] ) : '';
		$result      = oc_set_premi_flag( true );
		$status      = 'status';
		$data_str    = 'data';
		$referrer    = '';

		if ( isset( $_POST['referrer'] ) ) {

			$referrer = strpos( $_POST['referrer'], 'step=theme' ) ? 'install_wizard' : 'themes_page';
		}

		if ( $result[ $data_str ] == null && $result[ OC_SUCCESS_STR ] != 1 ) {
			$response = array(
				$status => '',
				'msg'   => OC_GENERIC_ERR_MSG . ' [' . $result[ OC_ERR_STR ] . ']',
			);
		} elseif ( oc_pm_features( $data, $result[ $data_str ] ) || in_array( 'MWP_ADDON', $result[ $data_str ] ) ) {
			$response = array(
				$status => OC_SUCCESS_STR,
			);
		} else {
			$response = array(
				$status => 'failed',
			);
		}

		// push stats

		if ( $action_type != '' && class_exists( OC_PUSH_STATS ) ) {

			$premium = ( empty( $_POST['isPremium'] ) || $_POST['isPremium'] !== 'true' ) ? 0 : 1;
			$slug    = isset( $_POST['theme'] ) ? $_POST['theme'] : '';

			if ( $action_type === 'wppremium_install_theme' && isset( $_POST['theme'] ) ) {
				( class_exists( OC_PUSH_STATS ) ? \OCPushStats::push_stats_event_themes_and_plugins( 'install', 'theme', "$slug", $referrer, array( PREMIUM => "$premium" ) ) : '' );

			} elseif ( $action_type === 'wppremium_preview_theme' && isset( $_POST['theme'] ) ) {
				( class_exists( OC_PUSH_STATS ) ? \OCPushStats::push_stats_event_themes_and_plugins( 'preview', 'theme', "$slug", $referrer, array( PREMIUM => "$premium" ) ) : '' );

			} elseif ( $action_type === 'wppremium_click_upgrade' && $_POST['feature'] === 'theme' ) {

				( class_exists( OC_PUSH_STATS ) ? \OCPushStats::push_stats_event_themes_and_plugins( 'click_upgrade', 'theme', $_POST['theme'], $referrer, array( PREMIUM => "$premium" ) ) : '' );

			} elseif ( $action_type === 'wppremium_close_upgrade' && $_POST['feature'] === 'theme' ) {

				( class_exists( OC_PUSH_STATS ) ? \OCPushStats::push_stats_event_themes_and_plugins( 'close_upgrade', 'theme', $_POST['theme'], $referrer, array( PREMIUM => "$premium" ) ) : '' );

			}
		}
		echo wp_send_json( $response );
		wp_die();
	}
}

/* validator scripts */
add_action(
	'admin_print_scripts',
	function () {
		?>
	<script>
		/**
		 * Top Notifier
		 */
		function oc_alert(msg = '', type = '<?php echo OC_ERR_STR; ?>', time = 5000) {

			jQuery('.onecom-notifier').html(msg).attr('type', type).addClass('show');
			setTimeout(function () {
				jQuery('.onecom-notifier').removeClass('show');
				jQuery('.loading-overlay.fullscreen-loader').removeClass('show');
			}, time);
		}
	</script>

	<script type="text/javascript">
		function oc_validate_action(action) {

			return jQuery.ajax({
				url: ajaxurl,
				type: "POST",
				dataType: "JSON",
				data: {
					action: 'oc_validate_action',
					operation: action
				},
				error: function (xhr, textStatus, errorThrown) {
					oc_alert("<?php echo htmlentities( OC_GENERIC_ERR_MSG ); ?>", OC_ERR_STR, 5000)
				}
			});
		}
	</script>
	<script>
		/**
		 * Function oc_trigger_log()
		 * Function to trigger log after a feature is activated successfully.
		 * This checks the domain's eligibility for a feature redundantly and this can be improved.
		 * @param: Object data - an object consisting of following keys
		 * actionType, isPremium, feature, featureAction, state and theme
		 */

		function oc_trigger_log(logData) {
			jQuery.ajax({
				url: ajaxurl,
				type: "POST",
				dataType: "JSON",
				data: {
					action: 'oc_validate_action',
					actionType: logData.actionType,
					isPremium: logData.isPremium,
					feature: logData.feature,
					theme: logData.theme || null,
					featureAction: logData.featureAction || null,
					referrer: logData.referrer || null,
					state: logData.state || null
				},
				error: function (xhr, textStatus, errorThrown) {
					console.log("Some error occured during logging!");
				}
			});
		}
	</script>
		<?php
	},
	9999
);

/**
 * function to add Modal HTML in wp-admin footer
 */
add_action( 'wp_ajax_show_plugin_dependent_popup', 'onecom_upgrade_modal' );
add_action( 'admin_footer', 'onecom_upgrade_modal' );
if ( ! function_exists( 'onecom_upgrade_modal' ) ) {
	function onecom_upgrade_modal() {
		$type           = null;
		$current_screen = get_current_screen();
		$thm_screens    = array( OC_THMS_STR, 'one-com_page_onecom-wp-themes' );
		if ( in_array( $current_screen->base, $thm_screens ) ) {
			$type = OC_THMS_STR;
		}

		if ( isset( $_POST['action'] ) && $_POST['action'] === 'show_plugin_dependent_popup' ) {
			$dependent_plugin = prepare_dependent_plugin_popup( $_POST['popupContent'] );
			wp_send_json(
				array(
					'success' => true,
					'data'    => $dependent_plugin,
				)
			);
		} else {
			?>
			<div id="oc_um_overlay" style="display:none;">
				<div id="oc_um_wrapper">
					<div class="oc-bg-white">
						<div id="oc_um_head">
							<h5>
								<?php echo __( 'Make your WordPress more powerful', OC_VALIDATOR_DOMAIN ); ?>
							</h5>
						</div>
						<div id="oc_um_body">
							<?php
							echo __( 'Spend less time worrying about your site and more time growing your business with one.com Managed WordPress.', OC_VALIDATOR_DOMAIN );
							echo '<ul>' . '<li><span>' . __( 'Quick fix or ignore recommendations', OC_VALIDATOR_DOMAIN ) . '</span></li>';
							echo '<li><span>' . __( 'Get better performance with Performance Cache and CDN', OC_VALIDATOR_DOMAIN ) . '</span></li>';
							echo '<li><span>' . __( 'Get notified about security with Vulnerability Monitoring', OC_VALIDATOR_DOMAIN ) . '</span></li>';
							echo '<li><span>' . __( 'Get helpful tips with Advanced Error Page', OC_VALIDATOR_DOMAIN ) . '</span></li>';
							echo '<li><span>' . __( 'Get access to our Premium themes', OC_VALIDATOR_DOMAIN ) . '</span></li>';
							echo '<li><span>' . __( 'Increase your authentication security', OC_VALIDATOR_DOMAIN ) . '</span></li>';
							echo '<li><span>' . __( 'Host on our WordPress servers built for speed', OC_VALIDATOR_DOMAIN ) . '</span></li>';
							echo '</ul>';
							?>
						</div>

						<div id="oc_um_footer">
							<a href="<?php echo oc_upgrade_link( 'upgrade_modal' ); ?>"
								target="_blank"
								class="oc_um_btn oc_up_btn"><?php echo __( 'Free upgrade', OC_VALIDATOR_DOMAIN ); ?></a>
							<a href="javascript:;"
								onclick="jQuery('#oc_um_overlay').hide();jQuery('.loading-overlay.fullscreen-loader').removeClass('show');"
								class="oc_um_btn oc_cancel_btn"><?php echo __( 'Cancel', OC_VALIDATOR_DOMAIN ); ?></a>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>
		<style>
			#oc_um_overlay {
				position: fixed;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
				background: rgba(0, 0, 0, 0.2);
				z-index: 99999;
			}

			.oc-bg-white {
				background: #fff;
				padding: 40px;
				width: calc(100% - 80px);
			}

			#oc_phased-in {
				background: #fff;
				margin: 8px 0 0 0;
				padding: 24px 40px;
				display: flex;
				align-items: center;
			}

			#oc_um_body ul {
				font-family: "Open Sans", sans-serif;
				font-size: 16px;
				font-style: normal;
				font-weight: 600;
				line-height: 24px;
				letter-spacing: 0px;
			}

			#oc_um_body ul li {
				margin: 20px 0;
			}

			#oc_um_body .extra-footerspace {
				height: 15px;
			}

			#oc_um_body ul li > span {
				display: table-cell;
			}

			#oc_phased-in p {
				font-family: "Open Sans", sans-serif;
				font-size: 14px;
				font-style: normal;
				font-weight: 400;
				line-height: 22px;
				letter-spacing: 0px;
				margin: 0 0 0 24px;
			}

			#oc_um_wrapper {
				margin: 0;
				position: fixed;
				top: 50%;
				left: 50%;
				-ms-transform: translate(-50%, -50%);
				transform: translate(-50%, -50%);
				-webkit-transform: translate(-50%, -50%);
				min-width: 504px;
				min-height: 246px;
				color: #3C3C3C;
				-webkit-font-smoothing: antialiased;
				-moz-osx-font-smoothing: grayscale;
				box-shadow: 0 0 12px rgba(0, 0, 0, .4);
				display: flex;
				justify-content: space-between;
				align-items: flex-start;
				flex-direction: column;
				max-width: 578px;
				width: 100%;
			}

			.oc_notice {
				border: 1px solid #0078C8;
				padding: 0 12px;
				padding-left: 32px;
				padding-right: 32px;
				background: #D9EBF7;
				margin: 40px 0 0 0;
				display: flex;
				align-items: center;
				justify-content: flex-end;
			}

			.oc_notice div:first-child {
				margin-right: auto;
			}

			.oc_notice div {
				margin: 12px 0;
				padding: 11px 0;
			}

			/* new button design */
			div#oc_um_footer a {
				text-decoration: none;
				display: inline-block;
			}

			div#oc_um_footer a.cancel-plugin-dependent,
			div#oc_um_footer a.upgrade-plugin-dependent {
				font-style: normal;
				font-weight: 600;
				font-size: 14px;
				line-height: 22px;
				letter-spacing: 0.25px;
				padding: 12px 24px;
				border-radius: 100px;
				font-family: "Open Sans", sans-serif;
			}

			div#oc_um_footer a.cancel-plugin-dependent {
				color: #3C3C3C;
				border: 1px solid #BBBBBB;
				margin-right: 24px;
			}

			div#oc_um_footer a.upgrade-plugin-dependent {
				background: #0078C8;
				color: #FFFFFF;
			}

			/* new button design end */

			@media (max-width: 960px) {
				.oc_notice {
					padding: 32px;
					display: block;
				}

				.oc_notice div {
					margin: 0;
					padding: 0;
				}

				.oc_notice div .inline_icon,
				.oc_notice span {
					display: block !important;
					margin-bottom: 32px;
				}
			}

			@media (max-width: 767px) {
				#oc_um_footer {
					text-align: center;
				}

				#oc_um_footer .oc_up_btn {
					margin-bottom: 15px;
				}

				#oc_um_footer .oc_cancel_btn {
					margin: 0;
					display: block;
				}

				/* new button design */
				div#oc_um_footer a.upgrade-plugin-dependent {
					margin-top: 24px;
				}

				div#oc_um_footer a.cancel-plugin-dependent {
					margin-right: 0 !important;
				}

				/* new button design end */
			}

			@media (max-width: 600px) {
				#oc_um_wrapper {
					max-width: 75%;
					min-width: 75%;
					max-height: 80%;
					overflow-y: scroll;
				}

				#oc_um_body, #oc_um_body p {
					margin: 25px 0;
				}
			}

			#oc_um_wrapper h5 {
				font-family: "Open Sans", sans-serif;
				font-size: 24px;
				font-style: normal;
				font-weight: 600;
				line-height: 32px;
				letter-spacing: 0px;
				text-align: left;
				margin: 0 0 12px 0;
			}

			#oc_um_overlay #oc_um_wrapper h5 {
				margin-bottom: 40px;
			}

			#oc_um_head {
				min-height: 56px;
			}

			#oc_um_footer {
				margin: 36px 0 0 0;
			}

			.oc_um_btn,
			.oc_um_btn:hover {
				font-family: "Open Sans", sans-serif;
				font-style: normal;
				font-weight: 600;
				font-size: 16px;
				line-height: 24px;
				color: #8A8989;
				background: #fff;
				align-items: center;
				display: inline-flex;
				border-radius: 0;
				cursor: pointer;
				text-decoration: none;
			}

			.oc_up_btn,
			.oc_up_btn:hover {
				background: #0078C8;
				border-color: #0078C8;
				color: #fff;
				padding: 8px 32px;
				font-family: "Open Sans", sans-serif;
				font-size: 16px;
				font-style: normal;
				font-weight: 600;
				line-height: 24px;
				letter-spacing: 0em;
				text-align: center;
				border-radius: 100px;
			}

			.oc_cancel_btn {
				margin: 0 0 0 46px;
			}

			.oc_up_btn:focus, .oc_up_btn:active, .oc_up_btn:visited {
				color: #fff;
			}

			#oc_um_body, #oc_um_body p {
				font-family: "Open Sans", sans-serif;
				font-size: 16px;
				font-style: normal;
				font-weight: 400;
				line-height: 24px;
				letter-spacing: 0px;
				text-align: left;
			}

			#oc_um_body ul li:before {
				content: url(data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTMiIHZpZXdCb3g9IjAgMCAxNiAxMyIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxwYXRoIGQ9Ik0wLjc1IDUuNzVMNS43NSAxMC43NUwxNS4yNSAxLjI1IiBzdHJva2U9IiM3NkI4MkEiIHN0cm9rZS13aWR0aD0iMiIvPgo8L3N2Zz4K);
				display: table-cell;
				padding-right: 20px;
			}
		</style>
		<script>
			jQuery(document).ready(function () {
				if (jQuery('#oc_um_overlay').length) {
					var oldpopup = jQuery('#oc_um_overlay').html();
					jQuery(document).on('click', '#oc_um_footer a.oc_um_btn.oc_cancel_btn', function () {
						jQuery('#oc_um_overlay').html(oldpopup);
					});
				}
			});
		</script>
		<?php
	}
}

/**
 * prepare dependent plugin popup
 */
if ( ! function_exists( 'prepare_dependent_plugin_popup' ) ) {
	function prepare_dependent_plugin_popup( $data ) {
		ob_start();
		?>
		<div id="oc_um_wrapper">
			<div class="oc-bg-white">
				<div id="oc_um_head">
					<h5>
						<?php echo nl2br( stripslashes( __( $data['title'], OC_VALIDATOR_DOMAIN ) ) ); ?>
					</h5>
				</div>
				<div id="oc_um_body">
					<?php
					echo nl2br( stripslashes( __( $data['top-desc'], OC_VALIDATOR_DOMAIN ) ) );
					echo '<ul>';
					foreach ( $data['bodylist'] as $key => $liVal ) {
						echo '<li><span>' . nl2br( stripslashes( __( $liVal, OC_VALIDATOR_DOMAIN ) ) ) . '</span></li>';
					}
					echo '</ul>';
					echo '<div class="extra-footerspace"></div>';
					echo nl2br( stripslashes( __( $data['footer-desc'], OC_VALIDATOR_DOMAIN ) ) );
					?>
				</div>
				<div id="oc_um_footer">
					<a href="javascript:;"
						onclick="jQuery('#oc_um_overlay').hide();jQuery('.loading-overlay.fullscreen-loader').removeClass('show');"
						class="cancel-plugin-dependent"><?php echo __( 'Cancel', OC_VALIDATOR_DOMAIN ); ?></a>
					<a href="<?php echo oc_upgrade_link( 'upgrade_modal' ); ?>"
						target="_blank"
						class="upgrade-plugin-dependent"><?php echo __( 'Get Managed WordPress', OC_VALIDATOR_DOMAIN ); ?></a>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}

/**
 * Function to show premium Ribbon on theme thumbnails.
 **/
add_filter( 'onecom_premium_theme_badge', 'oc_theme_badge', 10 );
if ( ! function_exists( 'oc_theme_badge' ) ) {
	function oc_theme_badge( $tag ) {
		if ( ! ( is_array( $tag ) && in_array( PREMIUM, $tag ) ) ) {
			return;
		}
		echo '<span class="badge_bg" style="position: absolute; top: 0; right: 0; padding: 4px 10px; background-color: #fff; color: #76B82A; font-size: 16px; line-height: 24px; z-index: 100; font-weight: 600;">' . __( 'Premium', OC_VALIDATOR_DOMAIN ) . '</span>';
	}
}

/**
 * Function to show inline premium badge
 */
$oc_inline_badge_fn = 'oc_inline_badge';
add_filter( 'onecom_premium_inline_badge', $oc_inline_badge_fn, 10, 4 ); // attach with MM
add_filter( 'oc_preview_install', $oc_inline_badge_fn, 10, 4 ); // attach with preview install
add_filter( 'oc_staging_button_create', $oc_inline_badge_fn, 10, 4 ); // attach with staging create
add_filter( 'oc_staging_button_delete', $oc_inline_badge_fn, 10, 4 ); // attach with staging delete
if ( ! function_exists( $oc_inline_badge_fn ) ) {
	function oc_inline_badge( $html, $type = '', $feature = '', $cuEventTrackId = '' ) {
		$features = (array) oc_set_premi_flag();
		// Check if Premium features

		if ( isset( $features['data'] ) ) {
			$features = $features['data'];
		}

		if ( oc_pm_features( $feature, $features ) || in_array( 'MWP_ADDON', $features ) ) {
			$badge = '<span class="inline_badge" style="display: inline-flex;height: 28px;vertical-align: middle;margin-left: 20px;align-items: center;color: #76a338;font-size: 14px;-webkit-font-smoothing: antialiased;"><svg style="width: 16px;height: 16px;pointer-events: none;margin-right:6px;"><use xlink:href="#premium_checkmark_91c2f8cf40d052f90c7b36218d17f875"><svg viewBox="0 0 13 13" id="premium_checkmark_91c2f8cf40d052f90c7b36218d17f875"><path d="M5.815 7.383L8.95 4.271l1.06 1.06-3.255 3.232-.953.953L3.354 7.01l1.06-1.06 1.4 1.433zM6.5 12.5a6 6 0 1 1 0-12 6 6 0 0 1 0 12zm0-1a5 5 0 1 0 0-10 5 5 0 0 0 0 10z" fill="#76a338"></path></svg></use></svg> Premium</span>';
		} else {
			if ( $type == '' ) {
				$type = __( 'This is a Premium Theme', OC_VALIDATOR_DOMAIN );
			}
            $badge = '<span class="inline_badge" style="display: inline-flex; height: 28px; vertical-align: middle; margin-left: 20px; align-items: center;"><em class="inline_icon" style="background:url(\'data:image/svg+xml;base64,PHN2ZyBzdHlsZT0iZmlsbDojOTUyNjVFOyIgd2lkdGg9IjkiIGhlaWdodD0iMTQiIHZpZXdCb3g9IjAgMCA5IDE0IiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxwYXRoIGQ9Ik0xLjQ5IDBoNi4wMTdsLTIgNC44NzNMOSA0Ljg2NSAyLjE0MiAxNGwxLjYzLTYuNzIzTDAgNy4yNzR6IiBmaWxsLXJ1bGU9ImV2ZW5vZGQiLz48L3N2Zz4=\');height: 13.5px;display: inline-block;vertical-align: middle;background-repeat: no-repeat;width: 9px;"></em><span class="inline_badge_text" style="-webkit-font-smoothing: antialiased;margin-left: 10px; opacity: 0.9;color: #333;font-family: Open Sans;font-size: 13px;line-height: 18px;">' . $type . '</span> <a class="inline_badge_link '.$cuEventTrackId.'" target="_blank" style="border-bottom:0;margin-left: 5px;color: #95265e;font-family: Open Sans;-webkit-font-smoothing: antialiased;font-size: 13px;font-weight: 600;line-height: 18px;cursor: pointer;text-decoration:none;" href="' . oc_upgrade_link( 'inline_badge' ) . '">' . __( 'Learn more', OC_VALIDATOR_DOMAIN ) . '</a></span>';
		}

		return $html . $badge;
	}
}

/**
 * Function oc_val_exclude_themes
 * Remove ILO app theme from Theme listing  in plugin section
 *
 * @param array $themes , an array of onecom themes
 * @param bool $exclude_themes , weather or not to exclude ilo theme?
 *
 * @return array
 */
if ( ! function_exists( 'oc_val_exclude_themes' ) ) {
	function oc_val_exclude_themes( $themes, $exclude_themes ) {
		if ( ! $exclude_themes || ! is_array( $themes ) ) {
			return $themes;
		}
		foreach ( $themes as $theme_item ) {
			if ( isset( $theme_item->collection ) ) {
				foreach ( $theme_item->collection as $key => $theme ) {
					if ( isset( $theme->slug ) && ( $theme->slug === 'onecom-ilotheme' ) ) {
						unset( $theme_item->collection[ $key ] );
					}
				}
			}
		}

		return $themes;
	}
}

/**
 * Function to query update
 **/
if ( ! function_exists( 'onecom_query_check' ) ) {
	function onecom_query_check( $url, $page = null ) {
		if ( $page != null || $page != 1 || $page != '1' ) {
			$url = add_query_arg(
				array(
					'page' => $page,
				),
				$url
			);
		}

		return add_query_arg(
			array(
				'wp'             => ONECOM_WP_CORE_VERSION,
				'php'            => ONECOM_PHP_VERSION,
				OC_ITM_COUNT_STR => 1000,
			),
			$url
		);
	}
}

/**
 * Fetch one.com themes
 */
if ( ! function_exists( 'onecom_fetch_themes' ) ) {
	function onecom_fetch_themes( $page = 1, $exclude_ilotheme = false ) {
		$themes        = array();
		$transientName = 'onecom_themes';

		$themes = (array) get_site_transient( $transientName );

		/* Note- simple switch over from previous data to new data structure */
		if ( ! isset( $themes['total'] ) && ! empty( $themes ) ) {
			delete_site_transient( $transientName );
			$themes = (array) get_site_transient( $transientName );
		}

		// If requested page already exists in transient, return
		if ( ! empty( $themes ) && isset( $themes[ OC_ITM_COUNT_STR ] ) && $themes[ OC_ITM_COUNT_STR ] >= 1000 ) {
			if ( array_key_exists( $page, $themes ) ) { // page exists in current themes
				$themes = oc_val_exclude_themes( $themes, $exclude_ilotheme );

				return $themes[ $page ];
			}
		}

		$fetch_themes_url = MIDDLEWARE_URL . '/themes';

		$fetch_themes_url = onecom_query_check( $fetch_themes_url, $page );

		global $wp_version;
		$args     = array(
			'timeout'     => 5,
			'httpversion' => '1.0',
			'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
			'body'        => null,
			'compress'    => false,
			'decompress'  => true,
			'sslverify'   => true,
			'stream'      => false,
		);
		$response = wp_remote_get( $fetch_themes_url, $args );

		if ( is_wp_error( $response ) ) {
			if ( isset( $response->errors['http_request_failed'] ) ) {
				$errorMessage = __( 'Connection timed out', OC_VALIDATOR_DOMAIN );
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
					$themes[ OC_ITM_COUNT_STR ]                       = $body->data->item_count;
					$themes['total']                                  = $body->data->total;
					$themes[ $body->data->current_page ]              = (object) array();
					$themes[ $body->data->current_page ]->collection  = $body->data->collection;
					$themes[ $body->data->current_page ]->page_number = $body->data->current_page;
				} elseif ( ! $body->success ) {
					if ( $body->error == 'RESOURCE NOT FOUND' ) {
						$try_again_url = add_query_arg(
							array(
								'request' => OC_THMS_STR,
							),
							''
						);
						$try_again_url = wp_nonce_url( $try_again_url, '_wpnonce' );
						$errorMessage  = __( 'Sorry, no compatible themes found for your version of WordPress and PHP.', OC_VALIDATOR_DOMAIN ) . '&nbsp;<a href="' . $try_again_url . '">' . __( 'Try again', OC_VALIDATOR_DOMAIN ) . '</a>';
					} else {
						$errorMessage = $body->error;
					}
				}
			}
			$themes = oc_val_exclude_themes( $themes, $exclude_ilotheme );
			if ( is_blog_installed() ) {
				set_site_transient( $transientName, $themes, 24 * HOUR_IN_SECONDS );
			}
		}

		if ( empty( $themes ) || ! isset( $themes[ $page ] ) ) {
			return new WP_Error( 'message', $errorMessage );
		} else {
			return $themes[ $page ];
		}
	}
}

/**
 * Get premium themes names
 */
if ( ! function_exists( 'onecom_is_premium_theme' ) ) {
	function onecom_is_premium_theme( $name = null ) {
		$themes = onecom_fetch_themes();
		$themes = ( isset( $themes->collection ) && ! empty( $themes->collection ) ) ? $themes->collection : array();
		$themes = array_reverse( array_reverse( $themes ) );

		$premium_themes = array();
		foreach ( $themes as $theme ) {
			if ( in_array( PREMIUM, (array) $theme->tags ) ) {
				$premium_themes[] = $theme->name;
			}
		}

		if ( $name == null ) {
			return $premium_themes;
		}

		if ( in_array( $name, $premium_themes ) ) {
			return true;
		}

		return false;
	}
}

/**
 * Check if theme is to be activated
 */
if ( ! function_exists( 'oc_check_theme_eligibility' ) ) {
	function oc_check_theme_eligibility( $features, $stylesheet = '' ) {
		// exit if it is not a one.com theme
		$theme = wp_get_theme( $stylesheet );

		if ( 'one.com' !== strtolower( $theme->display( OC_AUTHOR_STR, false ) ) ) {
			return true;
		}

		// exit if premium package
		if ( in_array( OC_ID_PRM_THMS, $features ) ) {
			return true;
		}

		// check if non-premium WP package & trying to use STANDARD THEME
		if (
			( in_array( OC_ID_OCI, $features ) || in_array( OC_ID_STD_THMS, $features ) )
			&& ! onecom_is_premium_theme( $theme->display( OC_AUTHOR_STR, false ) )
		) {
			return true;
		}

		return false;
	}
}

// Show notice for a non-premium WP package
if ( ! function_exists( 'onecom_premium_theme_admin_notice' ) ) {
	function onecom_premium_theme_admin_notice( $html = '' ) {
		global $current_screen;
		// only show banner on the onecom plugins allowed screens
		$allowed_screens = array(
			'_page_onecom-wp-plugins',
			'admin_page_onecom-wp-recommended-plugins',
			'admin_page_onecom-wp-discouraged-plugins',
		);
		if ( isset( $current_screen->id ) && ! in_array( $current_screen->id, $allowed_screens ) ) {
			return false;
		}
		$badge = '<div class="oc_notice"><div><em class="inline_icon" style="background:url(\'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDgiIGhlaWdodD0iNDgiIHZpZXdCb3g9IjAgMCA0OCA0OCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTAuNSA0Ny41VjYuNUg0MS41VjQ3LjVIMC41WiIgZmlsbD0id2hpdGUiIHN0cm9rZT0iIzNDM0MzQyIgc3Ryb2tlLW1pdGVybGltaXQ9IjEuNSIgc3Ryb2tlLWxpbmVjYXA9InNxdWFyZSIvPgo8cGF0aCBkPSJNMTEuNzcyNiAxMy44QzEyLjQzMzQgMTMuOCAxMi45NjkgMTMuMjYyOCAxMi45NjkgMTIuNkMxMi45NjkgMTEuOTM3MyAxMi40MzM0IDExLjQgMTEuNzcyNiAxMS40QzExLjExMTggMTEuNCAxMC41NzYyIDExLjkzNzMgMTAuNTc2MiAxMi42QzEwLjU3NjIgMTMuMjYyOCAxMS4xMTE4IDEzLjggMTEuNzcyNiAxMy44WiIgZmlsbD0id2hpdGUiIHN0cm9rZT0iIzNDM0MzQyIgc3Ryb2tlLW1pdGVybGltaXQ9IjEuNSIgc3Ryb2tlLWxpbmVjYXA9InNxdWFyZSIvPgo8cGF0aCBkPSJNNi45ODIwNyAxMy44QzcuNjQyODQgMTMuOCA4LjE3ODUgMTMuMjYyOCA4LjE3ODUgMTIuNkM4LjE3ODUgMTEuOTM3MyA3LjY0Mjg0IDExLjQgNi45ODIwNyAxMS40QzYuMzIxMyAxMS40IDUuNzg1NjQgMTEuOTM3MyA1Ljc4NTY0IDEyLjZDNS43ODU2NCAxMy4yNjI4IDYuMzIxMyAxMy44IDYuOTgyMDcgMTMuOFoiIGZpbGw9IndoaXRlIiBzdHJva2U9IiMzQzNDM0MiIHN0cm9rZS1taXRlcmxpbWl0PSIxLjUiIHN0cm9rZS1saW5lY2FwPSJzcXVhcmUiLz4KPHBhdGggZD0iTTUuNSA0MC41QzUuNSAzOS4zOTU0IDYuMzk1NDMgMzguNSA3LjUgMzguNUgzNC41QzM1LjYwNDYgMzguNSAzNi41IDM5LjM5NTQgMzYuNSA0MC41QzM2LjUgNDEuNjA0NiAzNS42MDQ2IDQyLjUgMzQuNSA0Mi41SDcuNUM2LjM5NTQzIDQyLjUgNS41IDQxLjYwNDYgNS41IDQwLjVaIiBmaWxsPSIjRkZDMzgyIiBzdHJva2U9IiMzQzNDM0MiIHN0cm9rZS1taXRlcmxpbWl0PSIxLjUiIHN0cm9rZS1saW5lY2FwPSJzcXVhcmUiLz4KPHBhdGggZD0iTTUuNSAzNC41VjE4LjVIMzYuNVYzNC41SDUuNVoiIGZpbGw9IiNFREVERUQiIHN0cm9rZT0iIzNDM0MzQyIgc3Ryb2tlLW1pdGVybGltaXQ9IjEuNSIgc3Ryb2tlLWxpbmVjYXA9InNxdWFyZSIvPgo8cGF0aCBkPSJNMjEgMjEuNzVWMzEuMjUiIHN0cm9rZT0iIzNDM0MzQyIgc3Ryb2tlLW1pdGVybGltaXQ9IjEuNSIgc3Ryb2tlLWxpbmVjYXA9InNxdWFyZSIvPgo8cGF0aCBkPSJNMjUuNDI4NiAyNi41SDE2IiBzdHJva2U9IiMzQzNDM0MiIHN0cm9rZS1taXRlcmxpbWl0PSIxLjUiIHN0cm9rZS1saW5lY2FwPSJzcXVhcmUiLz4KPGNpcmNsZSBjeD0iMzQuNSIgY3k9IjEzLjUiIHI9IjEzIiBmaWxsPSIjNzZCODJBIiBzdHJva2U9IiMzQzNDM0MiIHN0cm9rZS1taXRlcmxpbWl0PSIxLjUiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgo8bWFzayBpZD0ibWFzazAiIG1hc2stdHlwZT0iYWxwaGEiIG1hc2tVbml0cz0idXNlclNwYWNlT25Vc2UiIHg9IjIxIiB5PSIwIiB3aWR0aD0iMjciIGhlaWdodD0iMjciPgo8Y2lyY2xlIGN4PSIzNC41IiBjeT0iMTMuNSIgcj0iMTMiIGZpbGw9IiNGRkMzODIiIHN0cm9rZT0iIzNDM0MzQyIgc3Ryb2tlLW1pdGVybGltaXQ9IjEuNSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIi8+CjwvbWFzaz4KPGcgbWFzaz0idXJsKCNtYXNrMCkiPgo8cGF0aCBkPSJNNDUuODUwOCA2LjkwMzY3TDQ2LjA2MTkgNi4yNUg0NS4zNzVINDIuNjExSDQyLjIzOTNMNDIuMTMyMiA2LjYwNTk3TDM5LjE2NTEgMTYuNDY5NkwzNi4yMzE1IDYuNjA3NDVMMzYuMTI1MiA2LjI1SDM1Ljc1MjNIMzMuNDQwNEgzMy4wNzA0TDMyLjk2MjIgNi42MDM3NEwyOS45NjI3IDE2LjQwOThMMjcuMDgwNiA2LjYwODk0TDI2Ljk3NTEgNi4yNUgyNi42MDA5SDIzLjYyNUgyMi45MzgxTDIzLjE0OTIgNi45MDM2N0wyNy45OTM5IDIxLjkwMzdMMjguMTA1OCAyMi4yNUgyOC40Njk3SDMxLjE0NzdIMzEuNTE3OEwzMS42MjU5IDIxLjg5NjFMMzQuNTM0NCAxMi4zNzQ5TDM3LjM5MjcgMjEuODkzOEwzNy40OTk2IDIyLjI1SDM3Ljg3MTZINDAuNTMwM0g0MC44OTQyTDQxLjAwNjEgMjEuOTAzN0w0NS44NTA4IDYuOTAzNjdaIiBmaWxsPSJ3aGl0ZSIgc3Ryb2tlPSIjM0MzQzNDIi8+CjwvZz4KPC9zdmc+Cg==\');height: 48px;display: inline-block;vertical-align: middle;background-repeat: no-repeat;width: 48px; margin-right: 16px;"></em><span style="font-size: 16px; line-height: 24px;">' . sprintf( __( 'Make your website even more powerful with %sManaged WordPress%s', OC_VALIDATOR_DOMAIN ), '<strong>', '</strong>' ) . '</div><div><a class="inline_badge_link oc_um_btn oc_up_btn ocwp_ocp_plugins_notice_mwp_upgrade_initiated_event" style="font-size: 14px; padding: 8px 30px;" href="' . oc_upgrade_link( 'top_banner' ) . '" target="_blank" class="oc_num_btn oc_up_bt" >' . OC_GENERIC_LEARN_MORE . '</a></div></div>';
		if ( $html != '' ) {
			return $html . $badge;
		}
		echo $badge;
	}
}

// Admin one.com notice css
if ( ! function_exists( 'onecom_premium_error_activation_style' ) ) {
	function onecom_premium_error_activation_style() {
		echo '<style>span.inline_badge{margin-left: 0px !important;height: 17px !important;}.notice:not(.oc_notice){display:none !important;}</style>';
	}
}

/**
 * Validate premium theme activation
 */
add_action( 'after_switch_theme', 'onecom_premium_theme_check', 10, 2 );
if ( ! function_exists( 'onecom_premium_theme_check' ) ) {
	function onecom_premium_theme_check( $oldtheme_name, $oldtheme ) {

		// just using the variable to reduce sonar warning
		$oldtheme_name;

		// exit if it is not a one.com theme
		$theme = wp_get_theme();
		if ( 'one.com' !== strtolower( $theme->display( OC_AUTHOR_STR, false ) ) ) {
			return true;
		}

		// exit if premium package
		$features = (array) oc_set_premi_flag( true )['data'];

		if ( in_array( OC_ID_PRM_THMS, $features ) || in_array( 'MWP_ADDON', $features ) ) {
			return true;
		}

		// check if non-premium WP package & trying to use STANDARD THEME
		if (
			( in_array( OC_ID_OCI, $features ) || in_array( OC_ID_STD_THMS, $features ) ) &&
			! onecom_is_premium_theme( $theme->display( OC_NAME_STR, false ) )
		) {
			return true;
		}

		// Show notice for a non-WP package
		add_action( 'admin_notices', 'onecom_premium_theme_admin_notice', 2 );

		// Custom styling for default admin notice.
		add_action( 'admin_head', 'onecom_premium_error_activation_style' );

		// Switch back to previous theme.
		switch_theme( $oldtheme->stylesheet );

		onecom_show_modal( true );

		return true;
	}
}

add_action( 'admin_footer', 'onecom_show_modal' );


/**
 * @param $show
 * function to show upgrade modal on theme switch
 * @return void
 */
if ( ! function_exists( 'onecom_show_modal' ) ) {
	function onecom_show_modal( $show = false ) {
		if ( $show === true) {
			?>
			<script>
				document.addEventListener('DOMContentLoaded', function () {
					let overlayElement = document.getElementById('oc_um_overlay');

					if (overlayElement) {
						overlayElement.style.display = 'block';
					}
				});
			</script>

			<?php
		}
	}
}

/**
 * Add headers to the provided object
 * This function intends to add domain validation headers in outgoing requests
 */
if ( ! function_exists( 'oc_add_http_headers' ) ) {
	function oc_add_http_headers( $data, $url ) {
		if ( strpos( $url, 'wpapi' ) === false || strpos( $url, '.one.com' ) === false ) {
			return $data;
		}
		$totp                                      = oc_generate_totp();
		$domain                                    = ! empty( OC_DOMAIN_NAME ) ? OC_DOMAIN_NAME : 'localhost';
		$data['headers']['X-Onecom-Client-Domain'] = $domain;
		$data['headers']['X-TOTP']                 = $totp;
		$data['headers']['X-ONECOM-CLIENT-IP']     = onecom_get_client_ip_env();

		//add keys if domain is cluster model exist
		global $webconfig_name;

		if ( is_cluster_domain() ) {

			if ( empty( $webconfig_name ) ) {
				$webconfig_name = ! empty( $_SERVER['HTTP_X_GROUPONE_WEBCONFIG_NAME'] ) ? $_SERVER['HTTP_X_GROUPONE_WEBCONFIG_NAME'] : null;
			}

			$data['headers']['X-ONECOM-CLUSTER-ID']     = OC_CLUSTER_ID;
			$data['headers']['X-ONECOM-WEBCONFIG-NAME'] = $webconfig_name;
		}

		return $data;
	}
}
/**
 * Function for checking the theme before activating/enabling it.
 */
add_action( 'load-themes.php', 'oc_check_theme_before_switch' );
if ( ! function_exists( 'oc_check_theme_before_switch' ) ) {
	function oc_check_theme_before_switch() {

		// Exit if not a GET request
		if ( ! isset( $_GET ) || empty( $_GET ) ) {
			return;
		}
		// return if no action
		if ( ! isset( $_GET['action'] ) ) {
			return;
		}

		if ( ! ( isset( $_GET['enable'] ) ) && ! ( isset( $_GET[ OC_THM_STR ] ) || isset( $_GET['stylesheet'] ) ) ) {
			return;
		}

		// Exit for all these types of requests
		if ( defined( 'XMLRPC_REQUEST' ) || defined( 'DOING_AJAX' ) || defined( 'IFRAME_REQUEST' ) || wp_is_json_request() ) {
			return;
		}

		// get current screen
		$current_page = get_current_screen()->base;

		// exit if not on themes page
		if ( ! ( isset( $_GET ) && ! empty( $_GET ) && ( $current_page == OC_THMS_STR || $current_page == 'themes-network' ) ) ) {
			return;
		}

		// check if oc_error
		if ( isset( $_GET[ OC_ERR_STR ] ) && 'oc_error' == $_GET[ OC_ERR_STR ] ) {
			$network = is_multisite() ? 'network_' : '';
			add_action( $network . 'admin_notices', 'onecom_premium_theme_admin_notice', 2 );
		}

		// exit if theme action is not for activating/enabling
		if ( ! ( $_GET['action'] == 'enable' || $_GET['action'] == 'activate' ) ) {
			return;
		}

		// exit if theme stylesheet not available
		if ( ! ( isset( $_GET[ OC_THM_STR ] ) || isset( $_GET['stylesheet'] ) ) ) {
			return;
		}

		// get stylesheet name for single/network site
		$stylesheet = isset( $_GET[ OC_THM_STR ] ) ? $_GET[ OC_THM_STR ] : $_GET['stylesheet'];

		// check if this theme is available for the current package; exit if available
		$features = (array) oc_set_premi_flag( true );
		if ( oc_check_theme_eligibility( $features, $stylesheet ) ) {
			return true;
		}

		// if theme not available, prepare for redirecting back on the themes page
		$temp_args              = array( 'enabled', OC_ERR_STR );
		$_SERVER['REQUEST_URI'] = remove_query_arg( $temp_args, $_SERVER['REQUEST_URI'] );
		$referer                = remove_query_arg( $temp_args, wp_get_referer() );

		if ( false === strpos( $referer, '/network/themes.php' ) ) {
			wp_redirect( network_admin_url( 'themes.php?error=oc_error' ) );
		} else {
			wp_safe_redirect( add_query_arg( OC_ERR_STR, 'oc_error', $referer ) );
		}
		exit;
	}
}

add_action( 'admin_print_footer_scripts', 'oc_pm_badge_injection', 999 );

if ( ! function_exists( 'oc_pm_badge_injection' ) ) {
	function oc_pm_badge_injection( $hook_suffix ) {
		$installed_themes     = wp_get_themes();
		$themes_to_mark_array = array();
		foreach ( $installed_themes as $theme ) {
			if ( 'one.com' === strtolower( $theme->display( OC_AUTHOR_STR, false ) )
				&& onecom_is_premium_theme( $theme->display( OC_AUTHOR_STR, false ) )
			) {
				$themes_to_mark_array[] = str_replace( ' ', '-', strtolower( $theme->display( OC_AUTHOR_STR, false ) ) );
				$themes_to_mark         = json_encode( $themes_to_mark_array );
			}
		}
		?>
		<script>
			function _oc_pm_ribbon_injection(element) {
				jQuery(element).append('<span class="badge_bg" style="position: absolute;transform: rotate(45deg);z-index: 80;width: 105px;height: 73px;padding-top: 0px;top: -26px;right: -42px;background-color: #95265e;"></span><span class="badge_icon" style="position: absolute;transform: rotate(45deg);z-index: 80;pointer-events: none;top: 8px;right: 13px;"><svg style="height: 15px;width: 9px;display: inline-block;"><use xlink:href="#topmenu_upgrade_large_d56dd1cace1438b6cbed4763fd6e5119"><svg viewBox="0 0 9 15" id="topmenu_upgrade_large_d56dd1cace1438b6cbed4763fd6e5119"><path d="M1.486 0h6L5.492 5.004l3.482-.009-6.839 9.38 1.627-6.903L0 7.469z" fill="#FFF" fill-rule="evenodd"></path></svg></use></svg></span><span class="badge_text" style="position: absolute;transform: rotate(45deg);z-index: 80;color: #fff;text-transform: uppercase;font-style: normal;font-weight: 600;font-family: \'Open Sans\', sans-serif;display: block;text-align: center;top: 18px;font-size: 11px;right: 2px;-webkit-font-smoothing: antialiased;">Premium</span>').css('overflow', 'hidden');
			}

			function _oc_pm_ribbon_btn(themes_list) {
				if (!jQuery('.theme-info .theme-name').html()) {
					return
				}
				$exp_name = jQuery('.theme-info .theme-name').html().toLowerCase();
				$exp_name = $exp_name.split("<span");
				if (-1 !== themes_list.indexOf($exp_name[0])) {
					_oc_pm_ribbon_injection('.theme-overlay .screenshot');
				}
			}

			jQuery(document).ready(function () {
				//get a list of themes to mark as premium, if found none, initiate with empty json array
				var themes_to_mark = '<?php echo isset( $themes_to_mark ) ? $themes_to_mark : '[]'; ?>';
				var themes_list = JSON.parse(themes_to_mark);
				var dataslug;
				jQuery(".theme-browser .themes .theme").each(function (i, v) {
					dataslug = jQuery(v).attr('data-slug');
					if (dataslug) {
						if (-1 !== themes_list.indexOf(dataslug) || -1 !== themes_list.indexOf(dataslug.replace('onecom-', ''))) {
							_oc_pm_ribbon_injection(v)
						}
					}

				});
				jQuery(document).on('click', ".theme-browser .themes .theme", function () {
					_oc_pm_ribbon_btn(themes_list);
					jQuery(document).on('click', ".theme-header button.left,   .theme-header button.right", function () {
						_oc_pm_ribbon_btn(themes_list);
					});
				});

			});

		</script>
		<!-- Bind action with Upgrade button -->
		<script>
			function ocSetModalData(data) {
				if (!data) {
					console.info('ValidateAction :: No data to set!');
				}
				jQuery('#oc_um_wrapper').attr({
					'data-is_premium': data.isPremium,
					'data-feature': data.feature,
					'data-theme': data.theme,
					'data-feature_action': data.featureAction,
					'data-state': data.state || null
				});
			}

			jQuery(document).ready(function () {
				jQuery("#oc_um_footer a.oc_up_btn").click(function () {
					jQuery.ajax({
						url: ajaxurl,
						type: "POST",
						dataType: "JSON",
						data: {
							action: 'oc_validate_action',
							operation: 'click_upgrade',
							actionType: 'wppremium_click_upgrade',
							isPremium: jQuery('#oc_um_wrapper').attr('data-is_premium'),
							feature: jQuery('#oc_um_wrapper').attr('data-feature'),
							theme: jQuery('#oc_um_wrapper').attr('data-theme') || null,
							featureAction: jQuery('#oc_um_wrapper').attr('data-feature_action')
						},
						error: function (xhr, textStatus, errorThrown) {
							console.log("Some error occured during logging!");
						}
					});
					jQuery('#oc_um_wrapper').removeAttr('data-is_premium data-feature data-theme data-feature_action');
				});

				jQuery("#oc_um_close").click(function () {
					if (!jQuery('#oc_um_wrapper').attr('data-feature')) {
						return;
					}
					jQuery.ajax({
						url: ajaxurl,
						type: "POST",
						dataType: "JSON",
						data: {
							action: 'oc_validate_action',
							operation: 'close_upgrade',
							actionType: 'wppremium_close_upgrade',
							isPremium: jQuery('#oc_um_wrapper').attr('data-is_premium'),
							feature: jQuery('#oc_um_wrapper').attr('data-feature'),
							theme: jQuery('#oc_um_wrapper').attr('data-theme') || null,
							featureAction: jQuery('#oc_um_wrapper').attr('data-feature_action'),
							state: jQuery('#oc_um_wrapper').attr('data-state') || null
						},
						error: function (xhr, textStatus, errorThrown) {
							console.log("Some error occured during logging!");
						}
					});
					jQuery('#oc_um_wrapper').removeAttr('data-is_premium data-feature data-theme data-feature_action');
				});

				jQuery("#oc_um_footer a.oc_cancel_btn").click(function () {
					if (!jQuery('#oc_um_wrapper').attr('data-feature')) {
						return;
					}
					jQuery.ajax({
						url: ajaxurl,
						type: "POST",
						dataType: "JSON",
						data: {
							action: 'oc_validate_action',
							operation: 'close_upgrade',
							actionType: 'wppremium_close_upgrade',
							isPremium: jQuery('#oc_um_wrapper').attr('data-is_premium'),
							feature: jQuery('#oc_um_wrapper').attr('data-feature'),
							theme: jQuery('#oc_um_wrapper').attr('data-theme') || null,
							featureAction: jQuery('#oc_um_wrapper').attr('data-feature_action'),
							state: jQuery('#oc_um_wrapper').attr('data-state') || null
						},
						error: function (xhr, textStatus, errorThrown) {
							console.log("Some error occured during logging!");
						}
					});
					jQuery('#oc_um_wrapper').removeAttr('data-is_premium data-feature data-theme data-feature_action');
				});


			});
		</script>

		<?php
	}
}


/* Cutomizer controls */
add_action(
	'customize_controls_enqueue_scripts',
	function () {

		$installed_themes   = wp_get_themes();
		$themes_to_mark_arr = array();
		$themes_to_mark     = '[]';
		foreach ( $installed_themes as $theme ) {
			if ( 'one.com' === strtolower( $theme->display( OC_AUTHOR_STR, false ) )
			&& onecom_is_premium_theme( $theme->display( OC_AUTHOR_STR, false ) )
			) {
				$themes_to_mark_arr[] = strtolower( $theme->display( OC_AUTHOR_STR, false ) );
				$themes_to_mark       = json_encode( $themes_to_mark_arr );
			}
		}

		wp_add_inline_script(
			'customize-controls',
			'(function ( api ) {
        api.bind( "ready", function () {
            var _query = api.previewer.query;

            api.previewer.query = function () {
                var theme_ = ' . $themes_to_mark . ';
                var query = _query.call( this );
                // console.log($themes_to_mark);
                // console.log(query.customize_theme)
                if(-1 !== theme_.indexOf(query.customize_theme)){
                   //alert("halt!!");
                }
                query.foo = "bar";
                return query;
            };
        });
    })( wp.customize );'
		);
	}
);

/**
 * Function to get the client ip address
 **/
if ( ! function_exists( 'onecom_get_client_ip_env' ) ) {
	function onecom_get_client_ip_env() {
		if ( getenv( 'HTTP_CLIENT_IP' ) ) {
			$clientIP = @getenv( 'HTTP_CLIENT_IP' );
		} elseif ( getenv( 'REMOTE_ADDR' ) ) {
			$clientIP = @getenv( 'REMOTE_ADDR' );
		} else {
			$clientIP = $_SERVER['ONECOM_CLIENT_IP'] = '0.0.0.0';
		}

		return $clientIP;
	}
}

/**
 * Function to buil URLs as per locale
 */
global $onecom_global_links;
$onecom_global_links          = array();
$onecom_global_links['en']    = array(
	OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/en-us/categories/360002171377-WordPress',
	OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/en-us/articles/115005586029-Discouraged-WordPress-plugins',
	OC_STG_GUIDE_STR    => 'https://help.one.com/hc/en-us/articles/360000020617',
	OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/en-us/articles/360001472758',
	OC_PRM_PAGE_STR     => 'https://www.one.com/en/wordpress-hosting',
);
$onecom_global_links['cs_CZ'] = array(
	OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/cs/categories/360002171377-WordPress',
	OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/cs/articles/115005586029-Nedoporu%C4%8Dovan%C3%A9-moduly-plug-in-ve-WordPressu',
	OC_STG_GUIDE_STR    => 'https://help.one.com/hc/cs/articles/360000020617',
	OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/cs/articles/360001472758',
	OC_PRM_PAGE_STR     => 'https://www.one.com/cs/wordpress',
);
$onecom_global_links['da_DK'] = array(
	OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/da/categories/360002171377-WordPress',
	OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/da/articles/115005586029-Frar%C3%A5dede-WordPress-plugins',
	OC_STG_GUIDE_STR    => 'https://help.one.com/hc/da/articles/360000020617',
	OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/da/articles/360001472758',
	OC_PRM_PAGE_STR     => 'https://www.one.com/da/wordpress',
);
$onecom_global_links['de_DE'] = array(
	OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/de/categories/360002171377-WordPress',
	OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/de/articles/115005586029-Nicht-empfohlene-Plugins',
	OC_STG_GUIDE_STR    => 'https://help.one.com/hc/de/articles/360000020617',
	OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/de/articles/360001472758',
	OC_PRM_PAGE_STR     => 'https://www.one.com/de/wordpress',
);
$onecom_global_links['es_ES'] = array(
	OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/es/categories/360002171377-WordPress',
	OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/es/articles/115005586029-Plugins-de-WordPress-no-recomendados',
	OC_STG_GUIDE_STR    => 'https://help.one.com/hc/es/articles/360000020617',
	OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/es/articles/360001472758',
	OC_PRM_PAGE_STR     => 'https://www.one.com/es/wordpress',
);
$onecom_global_links['fr_FR'] = array(
	OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/fr/categories/360002171377-WordPress',
	OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/fr/articles/115005586029-Les-plugins-WordPress-d%C3%A9conseill%C3%A9s',
	OC_STG_GUIDE_STR    => 'https://help.one.com/hc/fr/articles/360000020617',
	OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/fr/articles/360001472758',
	OC_PRM_PAGE_STR     => 'https://www.one.com/fr/wordpress',
);
$onecom_global_links['it_IT'] = array(
	OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/it/categories/360002171377-WordPress',
	OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/it/articles/115005586029-Plugin-per-WordPress-sconsigliati',
	OC_STG_GUIDE_STR    => 'https://help.one.com/hc/it/articles/360000020617',
	OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/it/articles/360001472758',
	OC_PRM_PAGE_STR     => 'https://www.one.com/it/wordpress',
);
$onecom_global_links['nb_NO'] = array(
	OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/no/categories/360002171377-WordPress',
	OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/no/articles/115005586029-Ikke-anbefalte-WordPress-plugins',
	OC_STG_GUIDE_STR    => 'https://help.one.com/hc/no/articles/360000020617',
	OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/no/articles/360001472758',
	OC_PRM_PAGE_STR     => 'https://www.one.com/no/wordpress',
);
$onecom_global_links['nl_NL'] = array(
	OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/nl/categories/360002171377-WordPress',
	OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/nl/articles/115005586029-Niet-aanbevolen-WordPress-plugins',
	OC_STG_GUIDE_STR    => 'https://help.one.com/hc/nl/articles/360000020617',
	OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/nl/articles/360001472758',
	OC_PRM_PAGE_STR     => 'https://www.one.com/nl/wordpress-hosting',
);
$onecom_global_links['pl_PL'] = array(
	OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/pl/categories/360002171377-WordPress',
	OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/pl/articles/115005586029-Niezalecane-wtyczki-WordPress',
	OC_STG_GUIDE_STR    => 'https://help.one.com/hc/pl/articles/360000020617',
	OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/pl/articles/360001472758',
	OC_PRM_PAGE_STR     => 'https://www.one.com/pl/wordpress',
);
$onecom_global_links['pt_PT'] = array(
	OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/pt/categories/360002171377-WordPress',
	OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/pt/articles/115005586029-Plugins-para-o-WordPress-desaconselh%C3%A1veis',
	OC_STG_GUIDE_STR    => 'https://help.one.com/hc/pt/articles/360000020617',
	OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/pt/articles/360001472758',
	OC_PRM_PAGE_STR     => 'https://www.one.com/pt/wordpress',
);
$onecom_global_links['fi']    = array(
	OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/fi/categories/360002171377-WordPress',
	OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/fi/articles/115005586029-WordPress-lis%C3%A4osat-joiden-k%C3%A4ytt%C3%B6%C3%A4-ei-suositella',
	OC_STG_GUIDE_STR    => 'https://help.one.com/hc/fi/articles/360000020617',
	OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/fi/articles/360001472758',
	OC_PRM_PAGE_STR     => 'https://www.one.com/fi/wordpress',
);
$onecom_global_links['sv_SE'] = array(
	OC_MAIN_GUIDE_STR   => 'https://help.one.com/hc/sv/categories/360002171377-WordPress',
	OC_DISC_GUIDE_STR   => 'https://help.one.com/hc/sv/articles/115005586029-WordPress-till%C3%A4gg-som-vi-avr%C3%A5der-fr%C3%A5n',
	OC_STG_GUIDE_STR    => 'https://help.one.com/hc/sv/articles/360000020617',
	OC_COOKIE_GUIDE_STR => 'https://help.one.com/hc/sv/articles/360001472758',
	OC_PRM_PAGE_STR     => 'https://www.one.com/sv/wordpress-hosting',
);

if ( ! function_exists( 'onecom_generic_locale_link' ) ) {
	function onecom_generic_locale_link( $request, $locale, $lang_only = 0 ) {
		global $onecom_global_links;
		if ( ! empty( $onecom_global_links ) && array_key_exists( $locale, $onecom_global_links ) ) {

			if ( $lang_only != 0 ) {
				return strstr( $locale, '_', true );
			}

			if ( ! empty( $onecom_global_links[ $locale ][ $request ] ) ) {
				return $onecom_global_links[ $locale ][ $request ];
			}
		}

		if ( $lang_only != 0 ) {
			return 'en';
		}

		return $onecom_global_links['en'][ $request ];
	}
}

/**
 * Hide one.com plugins from WordPress plugin listing
 */
if ( ! function_exists( 'onecom_hide_plugins' ) ) {
	function onecom_hide_plugins( $plugins ) {
		global $wp_list_table;
		$url_condition = ( empty( $_GET['premium'] ) || intval( $_GET['premium'] ) !== 1 );
		foreach ( $wp_list_table->items as $key => $plugin ) {

			if ( $url_condition && in_array( $plugin['Author'], array( 'onecom', 'one.com' ) ) ) {
				unset( $wp_list_table->items[ $key ] );
			}
		}
	}
}
/* Deactivated hiding the plugins until we ensure a reliable auto-updates solution */
//add_action( 'pre_current_active_plugins', 'onecom_hide_plugins' );

/*
 * Force enable automatic updates for one.com plugins
 * Disable email notification if one.com plugin update
 * */
function onecom_autoupdates( $update, $item ) {
	if (
		is_object( $item )
		&& property_exists( $item, 'Author' )
		&& in_array( $item->Author, array( 'one.com', 'onecom' ) )
	) {
		add_filter( 'auto_plugin_update_send_email', '__return_false' );
		return true;
	}
	return $update;
}

add_filter( 'auto_update_plugin', 'onecom_autoupdates', 10, 2 );

// one.com actions (stats) after automatic update completed
function onecom_automatic_updates_complete( $update_results ) {
	// return if it is not a plugin update
	if ( empty( $update_results ) || ! isset( $update_results['plugin'] ) ) {
		return;
	}

	// fetch each one.com plugin update
	foreach ( $update_results['plugin'] as $result ) {
		if ( property_exists( $result, 'item' )
			&& property_exists( $result->item, 'Author' )
			&& in_array( $result->item->Author, array( 'one.com', 'onecom' ) )
		) {

			// prepare data to push into stats
			$plugin_slug     = $result->item->slug;
			$additional_info = array(
				'additional_info' => json_encode(
					array(
						'current_version' => $result->item->current_version,
						'new_version'     => $result->item->new_version,
					)
				),
			);
			if ( strpos( 'onecom-vcache', __DIR__ ) ) {
				$referrer = basename( dirname( __DIR__, 3 ) );
			} else {
				$referrer = basename( dirname( __DIR__, 2 ) );
			}
			// Push auto update stats
			( class_exists( OC_PUSH_STATS ) ? \OCPushStats::push_stats_event_themes_and_plugins( 'auto_update', 'plugin', $plugin_slug, $referrer, $additional_info ) : '' );
		}
	}
}

add_action( 'automatic_updates_complete', 'onecom_automatic_updates_complete' );

if ( ! function_exists( 'oc_upgrade_link' ) ) {

	function oc_upgrade_link( $type = '' ) {

		$first_part  = 'https://www.one.com/admin/select-admin-domain.do?domain=';
		$middle_part = ! empty( OC_DOMAIN_NAME ) ? OC_DOMAIN_NAME : '';
		$last_part   = '&targetUrl=/admin/managedwp/upgrade.do';

		return $first_part . $middle_part . $last_part;
	}

}

if ( ! defined( 'OC_INLINE_LOGO' ) ) {
	define( 'OC_INLINE_LOGO', sprintf( '<img src="%s" alt="%s" />', plugin_dir_url( __FILE__ ) . '/assets/images/one.com.black.svg', __( 'One.com', OC_VALIDATOR_DOMAIN ) ) );
}

// function moved from generic plugin to validator //


if ( ! function_exists( 'onecom_get_free_menu_position' ) ) {
	function onecom_get_free_menu_position( $start, $increment = 0.3 ) {
		foreach ( $GLOBALS['menu'] as $key => $menu ) {
			$menus_positions[] = $key;
		}

		if ( ! in_array( $start, $menus_positions ) ) {
			return $start;
		}

		/* the position is already reserved find the closet one */
		while ( in_array( $start, $menus_positions ) ) {
			$start += $increment;
		}

		return (string) $start;
	}
}

// ------------ redesign notice --------------- //
//ajax handle for set trasient forever
add_action( 'wp_ajax_oc_set_redesign_notice_forever', 'oc_set_redesign_notice_forever_callback' );
function oc_set_redesign_notice_forever_callback() {

	$status  = false;
	$message = __( 'Some error occurred, please reload the page and try again.', OC_VALIDATOR_DOMAIN );

	if ( isset( $_POST['action'] ) && $_POST['action'] === 'oc_set_redesign_notice_forever' && $_POST['setTransient'] === 'forever' ) {
		$plugin_forever_key = 'redesign_notices_forever_dismiss_' . get_current_user_id();
		set_site_transient( $plugin_forever_key, 1, 0 );//WITH NO EXPIRATION
		$status  = true;
		$message = __( 'Updated', OC_VALIDATOR_DOMAIN );
	}

	wp_send_json(
		array(
			'status'  => $status,
			'message' => $message,
		)
	);
	wp_die();
}

//To add admin notices on dashboard about plugin redesign
if ( ! function_exists( 'oc_plugin_redesign_notices' ) ) {
	function oc_plugin_redesign_notices() {

		$user = wp_get_current_user();

		//return if not administrator user role
		if ( ( ! isset( $user->roles ) ) || ( ! in_array( 'administrator', (array) $user->roles ) ) ) {
			return;
		}

		//Prepare redesign transient keys
		$plugin_30days_key  = 'redesign_notices_show_for_30_days_' . get_current_user_id();
		$plugin_forever_key = 'redesign_notices_forever_dismiss_' . get_current_user_id();
		$isExecuted         = 'redesign_notices_for_executed_' . get_current_user_id();

		$get_30days_transient          = get_site_transient( $plugin_30days_key );
		$get_forever_dismiss_transient = get_site_transient( $plugin_forever_key );
		$isSetExecuted                 = get_site_transient( $isExecuted );

		//return if dismiss for forever
		if ( $get_forever_dismiss_transient || ! empty( $get_forever_dismiss_transient ) ) {
			return;
		}

		if ( ( ! $get_30days_transient || empty( $get_30days_transient ) ) && ( ! $isSetExecuted || empty( $isSetExecuted ) ) ) {
			set_site_transient( $plugin_30days_key, 1, 30 * DAY_IN_SECONDS );//for 30 days
			//set_site_transient($plugin_30days_key, 1, 2 * MINUTE_IN_SECONDS);//for 2 minute
			set_site_transient( $isExecuted, 1, 0 );
		}

		$get_30days_transient = get_site_transient( $plugin_30days_key );
		//if 30 days transient expire then simply return
		if ( ! $get_30days_transient || empty( $get_30days_transient ) ) {
			return;
		}

		$img_url             = plugin_dir_url( __FILE__ ) . 'assets/images/one.com-pl.svg';
		$plugin_redesign_img = plugin_dir_url( __FILE__ ) . 'assets/images/plugin-redesign.svg';
		$crossIcon           = plugin_dir_url( __FILE__ ) . 'assets/images/notice-cross-icon.svg';
		?>
		<style>
			.oc-plugin-redesign-notices-wrap {
				display: flex;
				align-items: flex-start;
				padding: 0 43px 0 16px;
			}

			.oc-plugin-redesign-notices-desc {
				flex: 2;
				padding-top: 12px;
				padding-bottom: 12px;
				padding-right: 40px;
			}

			.oc-plugin-redesign-notices {
				padding: 16px;
				background: #E6F2FA;
				/*min-height: 157px;*/
				position: relative;
				display: inline-table;
				border: 1px solid #80BBE3;
				width: 100%;
				width: -moz-available; /* WebKit-based browsers will ignore this. */
				width: -webkit-fill-available; /* Mozilla-based browsers will ignore this. */
				width: fill-available;
			}

			.notice.plugin-redesign {
				padding: 0;
				border: 0;
				box-shadow: none;
				margin: 16px 0;
				width: 100%;
				display: inline-table;
				background: #E6F2FA;
			}

			.oc-plugin-redesign-notices-img {
				height: 125px;
			}

			.oc-plugin-redesign-notices-img img {
				height: 100%;
			}

			.oc-plugin-redesign-notices-desc .welcome_text {
				font-family: "Open Sans", sans-serif;
				font-style: normal;
				font-weight: 400;
				font-size: 23px;
				line-height: 28px;
				color: #3C3C3C;
				flex: none;
				order: 0;
				flex-grow: 0;
				margin: 0;
				padding: 0;
			}

			.oc-plugin-redesign-notices-desc p {
				font-family: "Open Sans", sans-serif;
				font-style: normal;
				font-weight: normal;
				font-size: 14px;
				line-height: 19px;
				color: #6D6D6D;
				flex: none;
				order: 0;
				align-self: stretch;
				flex-grow: 0;
				margin: 5px 0;
			}

			.oc-plugin-redesign-cta {
				text-decoration: none;
				display: inline-block;
				text-align: center;
				font-size: 12px;
				line-height: 30px;
				font-weight: 600;
				color: #0078C8;
				padding: 0 30px;
				border: 1px solid #80BBE3;
				border-radius: 100px;
				margin-top: 12px;
			}

			.redesign-notice-cross {
				width: 14px;
				position: absolute;
				right: 20px;
				height: 14px;
				z-index: 999;
				padding: 17px;
				cursor: pointer;
			}

			@media (min-width: 320px) and (max-width: 480px) {
				.oc-plugin-redesign-notices-desc {
					flex: 1;
					padding: 0;
				}

				.oc-plugin-redesign-notices-img {
					display: none;
				}
			}
		</style>
		<div class="notice plugin-redesign">
			<div class="redesign-notice-cross"><img src="<?php echo $crossIcon; ?>" alt="x"></div>
			<div class="oc-plugin-redesign-notices">
				<div class="oc-plugin-redesign-notices-wrap">
					<div class="oc-plugin-redesign-notices-desc">
						<p class="welcome_text"><?php echo sprintf( __( 'Welcome to %s plugins', OC_VALIDATOR_DOMAIN ), 'one.com' ); ?></p>
						<p><?php echo __( 'Look after the health, security and performance of your website, and customise it with recommended themes and plugins.', OC_VALIDATOR_DOMAIN ); ?></p>
						<a class="oc-plugin-redesign-cta"
							href="<?php menu_page_url( 'onecom-wp-health-monitor', true ); ?>"><?php echo __( 'Explore plugins', OC_VALIDATOR_DOMAIN ); ?></a>
					</div>
					<div class="oc-plugin-redesign-notices-img">
						<img src="<?php echo $plugin_redesign_img; ?>"
							alt="<?php echo __( 'one.com', OC_VALIDATOR_DOMAIN ); ?>">
					</div>
				</div>
			</div>
		</div>

		<script type="text/javascript">
			jQuery(document).ready(function () {
				jQuery(document).on('click', '.redesign-notice-cross', function () {
					jQuery.ajax({
						url: ajaxurl,
						type: "POST",
						dataType: "JSON",
						data: {
							action: 'oc_set_redesign_notice_forever',
							setTransient: 'forever'
						},
						success: function (response) {

							//let result = response;//jQuery.parseJSON(response);
							if (response.message === 'Updated') {
								jQuery('.notice.plugin-redesign').remove();
							} else {
								oc_alert("<?php echo htmlentities( OC_GENERIC_ERR_MSG ); ?>", response.message, 5000)
							}
						},
						error: function (xhr, textStatus, errorThrown) {
							oc_alert("<?php echo htmlentities( OC_GENERIC_ERR_MSG ); ?>", 'error', 5000)
						}
					});
				});
			});
		</script>
		<?php
	}
}

// Script to capture UI events and stats
function oc_event_capture_script() {
	wp_enqueue_script(
		'oc-event-capture',
		plugin_dir_url( __FILE__ ) . 'assets/js/event-capture.js',
		array('jquery'),
		null,
		true
	);
}
add_action('admin_enqueue_scripts', 'oc_event_capture_script');

//Condition to register below script for only for onboarding
if(defined('OCI_URL') && !wp_script_is('oc-event-capture', 'registered')){
    wp_register_script('oc-event-capture', OCI_URL . "validator/assets/js/event-capture.js");
}

//load notices only dashboard
add_action( 'load-index.php', 'wp_dashboard_notices_call' );
if ( ! function_exists( 'wp_dashboard_notices_call' ) ) {
	function wp_dashboard_notices_call() {
		add_action( 'admin_notices', 'oc_plugin_redesign_notices' );
	}
}
// ------------ redesign notice end --------------- //
// --------------------------inclusion ends ------------//

// A small code block to expose a <script> tag in website page source based on one.com plugins
require_once plugin_dir_path( __FILE__ ) . 'oc-js-vars.php';