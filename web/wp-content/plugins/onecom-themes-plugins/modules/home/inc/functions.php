<?php
add_action(
	'admin_enqueue_scripts',
	function () {
		if ( function_exists( 'get_current_screen' ) && get_current_screen()->id === '_page_onecom-home' ) {
			wp_deregister_style( 'wp-block-editor' );
		}
		if ( function_exists( 'get_current_screen' ) && get_current_screen()->id === '_page_onecom-home' ) {
			wp_enqueue_script( 'oc_home_page' , ONECOM_WP_URL . 'modules/home/js/index.umd.js' , array( 'jquery' ) , ONECOM_WP_VERSION , true );

		}
		wp_enqueue_style( 'oc_gravity-css', ONECOM_WP_URL . 'modules/home/css/one.min.css', null, ONECOM_WP_VERSION );
		//  wp_enqueue_style('oc_alp_style', ONECOM_WP_URL . 'modules/advanced-login-protection/assets/css/alp.css',array(),ONECOM_WP_VERSION );
		if ( SCRIPT_DEBUG || SCRIPT_DEBUG == 'true' ) {
			wp_enqueue_script( 'oc_home_page_main', ONECOM_WP_URL . 'modules/home/js/main.js', array( 'jquery' ), ONECOM_WP_VERSION, true );
			wp_enqueue_style( 'oc_home_page-css', ONECOM_WP_URL . 'modules/home/css/main.css', array( 'oc_gravity-css' ), ONECOM_WP_VERSION );
		} else {
			wp_enqueue_script( 'oc_home_page_main', ONECOM_WP_URL . 'assets/min-js/main.min.js', array( 'jquery' ), ONECOM_WP_VERSION, true );
			wp_enqueue_style( 'oc_home_page-css', ONECOM_WP_URL . 'assets/min-css/main.min.css', array( 'oc_gravity-css' ), ONECOM_WP_VERSION );
		}
		wp_localize_script(
			'oc_home_page_main',
			'oc_home_ajax_obj',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'oc_home_ajax' ),
				'home_url' => admin_url( 'admin.php?page=onecom-home' ),
			)
		);
	}
);
function wporg_options_page_html() {
	// check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	require_once ONECOM_WP_PATH . 'modules/home/templates/home.php';
}

function wporg_options_page() {
	add_submenu_page( 'onecom-wp', __( 'Home', 'onecom-wp' ), '<span id="onecom_home">Home</span>', 'manage_options', 'onecom-home', 'wporg_options_page_html', -1 );
}

add_action( 'admin_menu', 'wporg_options_page' );
add_action(
	'wp_ajax_oc_home_silence_tour',
	function () {
		update_site_option( 'oc_home_silence_tour', true );
		wp_send_json( array( 'status' => 'success' ) );
	}
);

add_action( 'init', 'show_welcome_modal' );

// function to show the modal based on the user meta values
function show_welcome_modal(): void {
	$welcome_modal_closed = false;
	$user_id              = get_current_user_id();
	if ( $user_id ) {
		// Retrieve the user meta
		$welcome_modal_closed = get_user_meta( $user_id, 'oc-welcome-modal-closed', true );
	}
	if ( $welcome_modal_closed !== true && $welcome_modal_closed !== '1' ) {
		add_action( 'admin_footer', 'welcome_popup_init' );
	}
}


/**
 * @return void
 * function to include the template of welcome modal
 */
function welcome_popup_init() {
	require_once ONECOM_WP_PATH . 'modules/home/templates/welcome-modal.php';
}

/**
 * Check if current admin screen is for one.com plugin pages
 */
function oc_is_onecom_plugins_page() {
	$screen = get_current_screen();

	if ( ! $screen ) {
		return false;
	}

	$allowed_screens = array(
		'_page_onecom-home',
		'_page_onecom-wp-health-monitor',
		'toplevel_page_onecom-vcache-plugin',
		'_page_onecom-cdn',
		'_page_onecom-wp-rocket',
		'_page_onecom-wp-themes',
		'_page_onecom-wp-plugins',
		'admin_page_onecom-wp-recommended-plugins',
		'admin_page_onecom-wp-discouraged-plugins',
		'_page_onecom-wp-staging',
		'_page_onecom-wp-staging-blocked',
		'_page_onecom-wp-error-page',
		'_page_onecom-wp-cookie-banner',
		'toplevel_page_onecom-wp-under-construction',
		'toplevel_page_onecom-wp-spam-protection'
	);

	return in_array( $screen->id, $allowed_screens, true );
}

// Include data consent modal template on all onecom plugins screen
function oc_data_consent_modal_init() {
	if ( oc_is_onecom_plugins_page() ) {
		require_once ONECOM_WP_PATH . 'modules/home/templates/data-consent-modal.php';
	}
}
add_action( 'admin_footer', 'oc_data_consent_modal_init' );

/**
 * @return void
 * Include data consent banner template if conditions met i.e
 * * New onboarding + Consent status is never updated + onecom plugin pages + Admin capabilities
 */
function oc_data_consent_banner() {
	// Exit if not a vaid plugin page
	$screen = get_current_screen();
	if ( ! oc_is_onecom_plugins_page() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Exit if consent banner status is found
	$data_consent_status = get_site_option( 'onecom_data_consent_status', false );
	if ( '1' === $data_consent_status || '0' === $data_consent_status ) {
		return;
	}

	// If timestamp is missing OR 24 hours haven't passed, skip
	$timestamp = get_option( 'onecom_installation_timestamp' );
	if ( ! $timestamp || ( time() - (int) $timestamp ) < 86400 ) {
		return;
	}

	require_once ONECOM_WP_PATH . 'modules/home/templates/data-consent-banner.php';
}
add_action( 'admin_footer', 'oc_data_consent_banner' );

// Update consent banner status
add_action( 'wp_ajax_oc_update_consent_status', 'oc_update_consent_status' );
function oc_update_consent_status() {
	if ( ! empty( $_POST ) && isset( $_POST['consent_status'] ) ) {
		$status = isset( $_POST['consent_status'] ) ? intval( $_POST['consent_status'] ) : 0;
		update_site_option( 'onecom_data_consent_status', $status );
		wp_send_json_success( array( 'message' => 'Status updated' ) );
	} else {
		wp_send_json_error( array( 'message' => 'Failed to update status' ) );
	}
}
