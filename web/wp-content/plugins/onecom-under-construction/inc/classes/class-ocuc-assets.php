<?php

/**
 * Defines assets functions
 *
 * This class includes all assets for admin and public.
 *
 * @since      0.1.0
 * @package    Under_Construction
 * @subpackage OCUC_Assets
 */

// Exit if file accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OCUC_Assets {


	/**
	 * Constructor to add actions for enqueue styles and scripts
	 */
	public function init_assets() {
		add_action( 'admin_head', array( $this, 'uc_custom_css' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}

	/**
	 * Enqueue admin styles.
	 */
	public function admin_styles() {

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Register admin styles.
		wp_register_style( 'onecom_uc_flatpickr_styles', ONECOM_UC_DIR_URL . 'assets/css/flatpickr.css', array(), ONECOM_UC_VERSION );
		wp_register_style( 'onecom_uc_admin_styles', ONECOM_UC_DIR_URL . 'assets/css/admin.css', array(), ONECOM_UC_VERSION );
		wp_register_style( 'onecom_uc_select2_styles', ONECOM_UC_DIR_URL . 'assets/css/select2.min.css', array(), ONECOM_UC_VERSION );

		// Enqueue style only on required plugin pages
		if ( in_array( $screen_id, array( 'toplevel_page_onecom-wp-under-construction', 'toplevel_page_onecom-wp-under-construction-network' ), true ) ) {
			wp_enqueue_style( 'onecom_uc_flatpickr_styles' );
			wp_enqueue_style( 'onecom_uc_admin_styles' );
			wp_enqueue_style( 'onecom_uc_select2_styles' );
		}

		return null;
	}

	/**
	 * Enqueue admin scripts.
	 */
	public function admin_scripts() {
		$screen       = get_current_screen();
		$screen_id    = $screen ? $screen->id : '';
		$settings_api = new OCUC_Admin_Settings_API();

		$jquery_core_handle  = 'jquery';
		$wpcoloralpha_handle = 'wp-color-picker-alpha-uc';
		// Register scripts.
		//wp_register_script('onecom_uc_slick_migrate', ONECOM_UC_DIR_URL . 'assets/js/slick-jquery-migrate.min.js', array($jquery_core_handle), ONECOM_UC_VERSION, true);
		wp_register_script( 'onecom_uc_flatpickr_script', ONECOM_UC_DIR_URL . 'assets/js/flatpickr.js', array( $jquery_core_handle ), ONECOM_UC_VERSION, true );
		//slick js and css
		wp_register_style( 'oncecom_uc_slick_css', ONECOM_UC_DIR_URL . 'assets/css/slick.css', array(), ONECOM_UC_VERSION );
		wp_register_style( 'oncecom_uc_slick_theme_css', ONECOM_UC_DIR_URL . 'assets/css/slick-theme.css', array(), ONECOM_UC_VERSION );
		wp_register_script( 'oncecom_uc_slick_js', ONECOM_UC_DIR_URL . 'assets/js/slick.min.js', array( $jquery_core_handle ), ONECOM_UC_VERSION, true );
		//select2 js
		wp_register_script( 'onecom_uc_select2_script', ONECOM_UC_DIR_URL . 'assets/js/select2.min.js', array( $jquery_core_handle ), ONECOM_UC_VERSION, true );

		wp_register_script( $wpcoloralpha_handle, ONECOM_UC_DIR_URL . 'assets/js/wp-color-picker-alpha.js', array( $jquery_core_handle, 'wp-color-picker' ), ONECOM_UC_VERSION, true );
		wp_add_inline_script(
			$wpcoloralpha_handle,
			'jQuery( function() { jQuery( ".wp-color-picker-field" ).wpColorPicker(); } );'
		);
		//Admin uc js
		wp_register_script( 'onecom_uc_admin_script', ONECOM_UC_DIR_URL . 'assets/js/admin.js', array( $jquery_core_handle ), ONECOM_UC_VERSION, true );

		// Enqueue script only on plugin pages
		if ( in_array( $screen_id, array( 'toplevel_page_onecom-wp-under-construction' ), true ) ) {

			wp_enqueue_style( 'oncecom_uc_slick_css' );//slick css
			wp_enqueue_style( 'oncecom_uc_slick_theme_css' );//slick theme css
			// wp_enqueue_script('onecom_uc_slick_migrate');
			wp_enqueue_script( 'onecom_uc_flatpickr_script' );
			wp_enqueue_script( 'oncecom_uc_slick_js' );//slick js
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( $wpcoloralpha_handle );
			wp_enqueue_script( 'onecom_uc_select2_script' );
			wp_enqueue_script( 'onecom_uc_admin_script' );

			$theme_info = array(
				'theme_directory_uri' => ONECOM_UC_DIR_URL . 'assets/images',
				'isPremium'           => $settings_api->oc_premium( 'all_plugins' ),
				'savingLabel'         => __( 'Saving', 'onecom-uc' ),
			);
			wp_localize_script( 'onecom_uc_admin_script', 'theme_info_obj', $theme_info );
		}

		return null;
	}

	/**
	 * Hide UC tabs initially,
	 * * else it shows unformatted tabs
	 */
	public function uc_custom_css() {
		echo '<style>
		.ddresponsiveTabsDemo {
			display: none;
		  } 
		</style>';
	}
}
