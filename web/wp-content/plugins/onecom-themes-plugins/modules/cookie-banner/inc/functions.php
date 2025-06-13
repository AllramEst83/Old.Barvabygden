<?php
if ( ! defined( 'OC_TEXTDOMAIN' ) ) {
	define( 'OC_TEXTDOMAIN', 'onecom-wp' );
}
if ( ! defined( 'OCCB_COOKIE_NAME' ) ) {
	define( 'OCCB_COOKIE_NAME', 'onecom_cookie_consent' );
}

if ( ! defined( 'OCCB_LEARN_MORE' ) ) {
	define( 'OCCB_LEARN_MORE', 'Learn more' );
}

if ( ! defined( 'OCCB_COOKIE_EXP' ) ) {
	define( 'OCCB_COOKIE_EXP', 31536000 );
}

if ( ! defined( 'OCCB_OPTION' ) ) {
	define( 'OCCB_OPTION', 'oc_cb_configuration' );
}

if ( ! defined( 'OCCB_ASSETS' ) ) {
	define( 'OCCB_ASSETS', 'assets' );
}

if ( ! function_exists( 'oc_cb_config_page' ) ) {
	function oc_cb_config_page() {
		add_submenu_page(
			OC_TEXTDOMAIN,
			__( 'Cookie Banner', 'onecom-wp' ),
			'<span id="onecom_cookie_banner">' . __( 'Cookie Banner', 'onecom-wp' ) . '</span>',
			'manage_options',
			'onecom-wp-cookie-banner',
			'oc_cookie_banner_callback',
			4
		);
	}
}

function oc_cookie_banner_callback() {
	require_once dirname( plugin_dir_path( __FILE__ ) ) . '/templates/oc_cookie_banner_admin.php';
}

if ( ! function_exists( 'oc_cb_scripts_admin' ) ) {
	function oc_cb_scripts_admin( $hook_suffix ) {

		if ( $hook_suffix === '_page_onecom-wp-cookie-banner'
			|| $hook_suffix === 'admin_page_onecom-wp-cookie-banner'
			|| $hook_suffix === '_page_onecom-wp-error-page'
		) {
			$folder     = ( SCRIPT_DEBUG || SCRIPT_DEBUG == 'true' ) ? '' : 'min-';
			$extenstion = ( SCRIPT_DEBUG || SCRIPT_DEBUG == 'true' ) ? '' : '.min';

			wp_enqueue_style( 'oc_cb_css', ONECOM_WP_URL . OCCB_ASSETS . '/' . $folder . 'css/cookie-banner-admin' . $extenstion . '.css', array(), ONECOM_WP_VERSION );
			wp_enqueue_script( 'oc_cb_js', ONECOM_WP_URL . OCCB_ASSETS . '/' . $folder . 'js/cookie-banner-admin' . $extenstion . '.js', array( 'jquery' ), ONECOM_WP_VERSION, true );
			wp_localize_script(
				'oc_cb_js',
				'oc_constants',
				array(
					'oc_cb_token' => wp_create_nonce( 'oc_cb_token' ),
					'isPremium'   => is_Premium(),
				)
			);
		}
	}
}

if ( ! function_exists( 'oc_cb_scripts_frontend' ) ) {
	function oc_cb_scripts_frontend() {
		$settings      = get_site_option( 'oc_cb_configuration' );
		$cookie_enable = 0;
		if ( empty( $settings ) || empty( $settings['config'] ) ) {
			$cookie_enable = 0;
		} else {
			$cookie_enable = $settings['config']['show'];
		}

		if ( $cookie_enable ) {
			$folder     = ( SCRIPT_DEBUG || SCRIPT_DEBUG == 'true' ) ? '' : 'min-';
			$extenstion = ( SCRIPT_DEBUG || SCRIPT_DEBUG == 'true' ) ? '' : '.min';

			wp_enqueue_style( 'oc_cb_css_fr', ONECOM_WP_URL . OCCB_ASSETS . '/' . $folder . 'css/cookie-banner-frontend' . $extenstion . '.css', array(), ONECOM_WP_VERSION );
			wp_enqueue_script( 'oc_cb_js_fr', ONECOM_WP_URL . OCCB_ASSETS . '/' . $folder . 'js/cookie-banner-frontend' . $extenstion . '.js', array( 'jquery' ), ONECOM_WP_VERSION, true );
			wp_localize_script(
				'oc_cb_js_fr',
				'oc_constants',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
				)
			);
		}
	}
}

/* Save cookie banner settings */
if ( ! function_exists( 'oc_cb_settings' ) ) {
	function oc_cb_settings() {

		if ( ! check_ajax_referer( 'oc_cb_token', 'oc_cb_sec' ) ) {
			die(
				json_encode(
					array(
						'error'   => true,
						'message' => 'unauthenticated request!',
					)
				)
			);
		}

		//TODO: implement security - CSRF token
		if ( empty( $_POST ) || ! array_key_exists( 'settings', $_POST ) ) {
			die(
				json_encode(
					array(
						'error'   => true,
						'message' => 'No data provided!',
					)
				)
			);
		}

		$query = $_POST['settings'];

		parse_str( $query, $settings );

		$default = array(
			'show'             => 0,
			'banner_text'      => '',
			'policy_link'      => '',
			'policy_link_text' => '',
			'policy_link_url'  => '',
			'button_text'      => '',
			'banner_style'     => 'grey',
		);

		$settings = array_merge( $default, $settings );

		$new_settings = array();

		// update the status of showing admin notice
		// if user has enabled the cookie banner then we should not show the admin notice

		$new_settings['show_notice'] = ( $settings['show'] != 1 );

		$new_settings['config'] = $settings;

		update_site_option( 'oc_cb_configuration', $new_settings, 'no' );

		// Clear WP-Rocket cache to reflect latest Cookie banner frontend state
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}

		// Clear WP-Optimize cache to reflect latest Cookie banner frontend state
		if ( function_exists( 'wpo_cache_flush' ) ) {
			wpo_cache_flush();
		}

		// purge one.com performance (vanish) cache
		$blog_url = get_site_option( 'home' );
		wp_remote_request( $blog_url, array( 'method' => 'PURGE' ) );

		if ( $settings['show'] == 1 ) {
			( class_exists( 'OCPushStats' ) ? OCPushStats::push_cookie_banner_stats_request( 'enable', 'setting', 'banner', 'cookie_banner', $new_settings['config']['banner_style'], $new_settings['config']['policy_link'] ) : '' );

		} else {

			( class_exists( 'OCPushStats' ) ? OCPushStats::push_cookie_banner_stats_request( 'disable', 'setting', 'banner', 'cookie_banner' ) : '' );

		}
		die(
			json_encode(
				array(
					'error'   => null,
					'message' => 'settings saved!',
				)
			)
		);
	}
}
add_action( 'wp_ajax_oc_cb_settings', 'oc_cb_settings' );


/* Save cookie banner acceptance */
function oc_cb_cookie_consent() {
	//TODO: implement security - CSRF token
	if ( empty( $_POST ) ) {
		die(
			json_encode(
				array(
					'error'   => true,
					'message' => 'No data provided!',
				)
			)
		);
	}

	// set a cookie for 1 year
	if ( ! isset( $_COOKIE[ OCCB_COOKIE_NAME ] ) ) {
		$time = time();
		// serve cookie via https only if page itself was requested via https
		$secure = ( $_SERVER['HTTPS'] != '' );
		setcookie( OCCB_COOKIE_NAME, $time, $time + OCCB_COOKIE_EXP, COOKIEPATH, COOKIE_DOMAIN, $secure );

		// Clear WP-Rocket cache to reflect latest Cookie banner frontend state
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}

		// Clear WP-Optimize cache to reflect latest Cookie banner frontend state
		if ( function_exists( 'wpo_cache_flush' ) ) {
			wpo_cache_flush();
		}

		// purge one.com performance (vanish) cache
		$blog_url = get_site_option( 'home' );
		wp_remote_request( $blog_url, array( 'method' => 'PURGE' ) );

	}

	$cookie_consent = isset( $_COOKIE[ OCCB_COOKIE_NAME ] ) ? $_COOKIE[ OCCB_COOKIE_NAME ] : '';

	die(
		json_encode(
			array(
				'error'   => null,
				'message' => $cookie_consent,
			)
		)
	);
}

add_action( 'wp_ajax_oc_cb_cookie_consent', 'oc_cb_cookie_consent' );
add_action( 'wp_ajax_nopriv_oc_cb_cookie_consent', 'oc_cb_cookie_consent' );


/* Display cookie banner notice inside WP-admin */
if ( ! function_exists( 'oc_cookie_banner_notice' ) ) {
	function oc_cookie_banner_notice() {
		$screen = get_current_screen();

		$skip_screens = array(
			'one-com_page_onecom-wp-cookie-banner',
			/* 'plugins', */
		);

		// return if screen not allowed
		if ( in_array( $screen->base, $skip_screens ) ) {
			return false;
		}

		// get installed plugins
		$active_plugins = get_site_option( 'active_plugins' );
		$config_status  = get_site_option( 'oc_cb_configuration' );
		$flag           = false;

		// show banner if this is a fresh install and user hasn't taken any action
		if ( empty( $config_status ) ) {
			$flag = true;
		}
		// hide banner if the user has intentionally disabled the banner
		elseif ( isset( $config_status['show_notice'] ) && ! $config_status['show_notice'] ) {
			$flag = false;
		}

		// exit if OneCom-web-analytics plugin is not active.
		if (
			empty( $active_plugins ) ||
			! in_array( 'OneCom-web-analytics/wp-sbs-analytics.php', $active_plugins ) ||
			! $flag

		) {
			return false;
		}

		// prepare text for notice
		$text = __( 'Since you are using One.com Analytics plugin, we recommend that you enable a cookie banner. This banner tells your visitors about your site storing data about them.', 'onecom-wp' );

		// display notice
		echo sprintf(
			'<div class="notice notice-error is-dismissible"><p>%s  <span style="display:block; margin-top:10px"><a class="button button-primary" href="%s">%s</a>&nbsp;&nbsp;<a class="button" href="%s">%s</a></span></p></div>',
			$text,
			menu_page_url( 'onecom-wp-cookie-banner', false ),
			__( 'Setup cookie banner', 'onecom-wp' ),
			admin_url( 'admin-post.php' ) . '?action=oc_cb_notice&data=dismiss',
			__( 'Skip setup', 'onecom-wp' )
		);
	}
}
add_action( 'admin_notices', 'oc_cookie_banner_notice', 2 );

/* Display cookie banner on website frontend */
function oc_cb_output_banner() {

	/* check if cookie already exists and has not expired */
	$time = time();

	if (
		! isset( $_COOKIE[ OCCB_COOKIE_NAME ] ) ||
		( isset( $_COOKIE[ OCCB_COOKIE_NAME ] ) && ( (int) $time - (int) $_COOKIE[ OCCB_COOKIE_NAME ] >= OCCB_COOKIE_EXP ) )
	) {
		include_once ONECOM_WP_PATH . 'modules' . DIRECTORY_SEPARATOR . 'cookie-banner' . DIRECTORY_SEPARATOR . 'templates/oc_cookie_banner_frontend.php';
	}
}
add_action( 'wp_footer', 'oc_cb_output_banner' );


/* Get params from URL */
function oc_cb_dismiss_notice() {
	$config_status = get_site_option( 'oc_cb_configuration' );

	$config_status['show_notice'] = false;

	update_site_option( OCCB_OPTION, $config_status, 'no' );
	wp_safe_redirect( admin_url() );
}
add_action( 'admin_post_oc_cb_notice', 'oc_cb_dismiss_notice' );
/**
 * @return bool
 * Check premium
 */
function is_Premium() {
	$features = oc_set_premi_flag();
	if ( ( isset( $features['data'] ) && ( empty( $features['data'] ) ) ) || ( in_array( 'MWP_ADDON', $features['data'] ) || in_array( 'ONE_CLICK_INSTALL', $features['data'] ) ) ) {
		return true;
	}
	return false;
}
