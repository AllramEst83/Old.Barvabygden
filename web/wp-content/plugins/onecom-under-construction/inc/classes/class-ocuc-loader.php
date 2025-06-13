<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @since      0.1.0
 *
 * @package    Under_Construction
 * @subpackage OCUC_Loader
 */

// Exit if file accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OCUC_Loader {


	// Constructor
	public function init_loader() {
		$this->define_constants();
		$this->includes();
		add_action( 'init', array( $this, 'ocuc_wp_load_textdomain' ), -1 );
	}

	// Define required constants.
	public function define_constants() {

		define( 'ONECOM_UC_VERSION', '3.0.10' );

		define( 'ONECOM_UC_TEXT_DOMAIN', 'onecom-uc' );
		define( 'ONECOM_UC_PLUGIN_NAME', 'Maintenance Mode' );
		define( 'ONECOM_UC_PLUGIN_SLUG', dirname( plugin_basename( ONECOM_UC_PLUGIN_MAIN_FILE ) ) );
		define( 'ONECOM_UC_OPTION_FIELD', 'onecom_under_construction_info' );
	}

	// load text domain for language translation
	public function ocuc_wp_load_textdomain() {
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
			$result = load_textdomain( 'onecom-uc', ONECOM_UC_PLUGIN_URL . '/languages/onecom-uc-fi_FI.mo' );
		} elseif ( 'nb_NO' === $current_locale ) {
			$result = load_textdomain( 'onecom-uc', ONECOM_UC_PLUGIN_URL . '/languages/onecom-uc-no_NO.mo' );
		} if ( in_array( get_locale(), $locales_with_translation, true ) ) {
			$result = load_plugin_textdomain( 'onecom-uc', false, ONECOM_UC_PLUGIN_SLUG . '/languages' );
		} else {
			$result = load_textdomain( 'onecom-uc', ONECOM_UC_PLUGIN_URL . '/languages/onecom-uc-en_GB.mo' );
		}

		return $result;
	}

	// Include assets, hooks, admin, public files
	public function includes() {
		// General functions: validator, stats, update and api-hooks
		// @phpunit-todo - if needed, comment 2 inclusion before phpunit & uncomment before deploy
		if ( ! ( class_exists( 'OTPHP\TOTP' ) && class_exists( 'ParagonIE\ConstantTime\Base32' ) ) ) {
			require_once ONECOM_UC_PLUGIN_URL . 'inc' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'validator.php';

		}

		if ( ! class_exists( 'Onecom_Nested_Menu' ) ) {
			require_once ONECOM_UC_PLUGIN_URL . 'inc' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'onecom-nested-menu.php';
			$onecom_menu = new Onecom_Nested_Menu();
			$onecom_menu->init();
		}
		if ( ! class_exists( 'OCPushStats' ) ) {
			require_once ONECOM_UC_PLUGIN_URL . 'inc' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'OCPushStats.php';
		}
		if ( ! class_exists( 'ONECOMUPDATER' ) ) {
			require_once ONECOM_UC_PLUGIN_URL . '/inc/class-onecomupdater.php';
		}

		// include plugin specific admin & frontend assets
		include_once ONECOM_UC_PLUGIN_URL . 'inc/classes/class-ocuc-assets.php';

		// include admin related files
		if ( is_admin() ) {
			include_once ONECOM_UC_PLUGIN_URL . 'inc/classes/class-ocuc-admin-settings-api.php';
			include_once ONECOM_UC_PLUGIN_URL . 'inc/classes/class-ocuc-admin-settings.php';
		}

		include_once ONECOM_UC_PLUGIN_URL . 'inc/classes/class-ocuc-activator.php';
		include_once ONECOM_UC_PLUGIN_URL . 'inc/classes/class-ocuc-stats.php';
		include_once ONECOM_UC_PLUGIN_URL . 'inc/classes/class-ocuc-cache-purge.php';
		include_once ONECOM_UC_PLUGIN_URL . 'inc/classes/class-ocuc-toolbar.php';

		// include frontend related files
		include_once ONECOM_UC_PLUGIN_URL . 'inc/classes/class-ocuc-captcha.php';
		include_once ONECOM_UC_PLUGIN_URL . 'inc/classes/class-ocuc-newsletter.php';
		include_once ONECOM_UC_PLUGIN_URL . 'inc/classes/class-ocuc-render-views.php';
		include_once ONECOM_UC_PLUGIN_URL . 'inc/classes/class-ocuc-themes.php';

		return null;
	}
}
