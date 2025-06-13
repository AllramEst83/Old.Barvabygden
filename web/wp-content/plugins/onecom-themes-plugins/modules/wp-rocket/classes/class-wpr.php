<?php

declare(strict_types=1);
defined( 'WPINC' ) or die(); // No Direct Access

/**
 * Class Onecom_Wp_Rocket
 *
 */
#[\AllowDynamicProperties]
class Onecom_Wp_Rocket {

	const WR_ADDON_API = MIDDLEWARE_URL . '/features/addon/WP_ROCKET/status';
	const WR_ICON      = ONECOM_WP_URL . 'modules/wp-rocket/assets/images/wp-rocket-icon.svg';
	const WR_SLUG      = 'wp-rocket/wp-rocket.php';

	// Class Constructor
	public function __construct() {}

	public $guide_links = array(
		'en' => 'https://help.one.com/hc/en-us/articles/5927991871761-What-is-WP-Rocket-',
		'da' => 'https://help.one.com/hc/da/articles/5927991871761-Hvad-er-WP-Rocket-',
		'de' => 'https://help.one.com/hc/de/articles/5927991871761-Was-ist-WP-Rocket-',
		'es' => 'https://help.one.com/hc/es/articles/5927991871761--Qu%C3%A9-es-WP-Rocket-',
		'fr' => 'https://help.one.com/hc/fr/articles/5927991871761-Que-est-ce-que-WP-Rocket-',
		'fi' => 'https://help.one.com/hc/fi/articles/5927991871761-Mik%C3%A4-on-WP-Rocket-',
		'it' => 'https://help.one.com/hc/it/articles/5927991871761-Cos-%C3%A8-WP-Rocket-',
		'nl' => 'https://help.one.com/hc/nl/articles/5927991871761-Wat-is-WP-Rocket-',
		'no' => 'https://help.one.com/hc/no/articles/5927991871761-Hva-er-WP-Rocket-',
		'pt' => 'https://help.one.com/hc/pt/articles/5927991871761-O-que-%C3%A9-o-WP-Rocket-',
		'sv' => 'https://help.one.com/hc/sv/articles/5927991871761-Vad-%C3%A4r-WP-Rocket-',
	);

	// Initiatize actions
	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_activate_oc_wp_rocket', array( $this, 'activate_wp_rocket' ) );
		add_action( 'activate_wp-rocket/wp-rocket.php', array( $this, 'wp_rocket_activation_action' ) );
	}

	// Load scripts on relevant page(s) only
	public function enqueue_scripts( $hook_suffix ) {
		if ( $hook_suffix !== '_page_onecom-wp-rocket' &&
			$hook_suffix !== '_page_onecom-wp-plugins' ) {
			return;
		} elseif ( $hook_suffix === '_page_onecom-wp-rocket' ) {
			wp_enqueue_style( 'oc_wpr_style', ONECOM_WP_URL . 'modules/wp-rocket/assets/css/wp-rocket.css', array(), ONECOM_WP_VERSION );
		}
		// Load JS on both pages
		wp_enqueue_script( 'oc_wpr_script', ONECOM_WP_URL . 'modules/wp-rocket/assets/js/wp-rocket.js', array( 'jquery' ), ONECOM_WP_VERSION, true );
	}

	/**
	 * WP Rocket activation hooks
	 */
	public function wp_rocket_activation_action(): void {
		/**
		 * Call to the features endpoint for restoring transient value
		 * Why? So that wp-rocket page and its plugin entry shows latest state in plugins list after activation immediately
		 */
		oc_set_premi_flag( true );
	}

	// WP-Rocket translated guide link with en fallback
	public function wp_rocket_translated_guide() {
		$locale = explode( '_', get_locale() )[0];
		if ( ! array_key_exists( $locale, $this->guide_links ) ) {
			$locale = 'en';
		}
		return $this->guide_links[ $locale ];
	}

	/**
	 * Activate a plugin
	 */
	public function activate_wp_rocket() {
		$activation_status = is_null( activate_plugin( self::WR_SLUG ) );
		wp_send_json( array( 'status' => $activation_status ) );
	}

	/**
	 * Function to include WP-Rocket admin page template
	 */
	public static function wp_rocket_page() {
		require_once plugin_dir_path( __DIR__ ) . '/templates/wp-rocket-admin-page.php';
	}

	// Fetch wp rocket addon info via feature endpoint
	// @todo - make it cluster compatible
	public function wp_rocket_addon_info( $force = false, $domain = '' ) {
		// check transient
		$wp_rocket_addon_info = get_site_transient( 'onecom_wp_rocket_addon_info' );
		if ( ! empty( $wp_rocket_addon_info ) && false === $force ) {
			return $wp_rocket_addon_info;
		}
		if ( ! $domain ) {
			$domain = isset( $_SERVER['ONECOM_DOMAIN_NAME'] ) ? $_SERVER['ONECOM_DOMAIN_NAME'] : false;
		}
		if ( ! $domain ) {
			return array(
				'data'    => null,
				'error'   => 'Empty domain',
				'success' => false,
			);
		}
		$totp = oc_generate_totp();
		$curl = curl_init();
		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => self::WR_ADDON_API,

				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST  => 'GET',
				CURLOPT_HTTPHEADER     => array(
					'Cache-Control: no-cache',
					'X-Onecom-Client-Domain: ' . $domain,
					'X-TOTP: ' . $totp,
					'cache-control: no-cache',
				),
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
			// save transient for next calls, & return latest response
			set_site_transient( 'onecom_wp_rocket_addon_info', $response, 12 * HOUR_IN_SECONDS );
			return $response;
		}
	}

	/**
	 * Check if wp_rocket plugin addon purchased
	 */
	public function is_wp_rocket_addon_purchased(): bool {
		$this->wp_rocket_addon_info = $this->wp_rocket_addon_info();

		return (
			is_array( $this->wp_rocket_addon_info ) &&
			array_key_exists( 'success', $this->wp_rocket_addon_info ) &&
			$this->wp_rocket_addon_info['success'] &&
			array_key_exists( 'data', $this->wp_rocket_addon_info ) &&
			array_key_exists( 'source', $this->wp_rocket_addon_info['data'] ) &&
			$this->wp_rocket_addon_info['data']['source'] === 'PURCHASED' &&
			array_key_exists( 'product', $this->wp_rocket_addon_info['data'] ) &&
			$this->wp_rocket_addon_info['data']['product'] === 'WP_ROCKET'
		);
	}

	// Check if WP Rocket is provisioned/installed via one.com
	public function is_oc_wp_rocket_flag_exists() {
		return get_site_option( 'oc-wp-rocket-activation' );
	}

	// Check if WP Rocket plugin is active
	public function is_wp_rocket_active(): bool {
		return is_plugin_active( self::WR_SLUG );
	}

	// Check if WP Rocket is installed
	public function is_wp_rocket_installed(): bool {
		wp_clean_plugins_cache();
		$plugins = get_plugins();
		return array_key_exists( self::WR_SLUG, $plugins );
	}

	// WP-Rocket plugin json for entry in one.com plugins
	public function wp_rocket_plugin_info(): array {
		$plugins = onecom_fetch_plugins();

		return array(
			'id'             => count( $plugins ) + 1,
			'name'           => 'WP Rocket',
			'slug'           => 'wp-rocket',
			'description'    => __( 'Speed up your site, improve loading times, and boost your search rankings with one of the most popular performance optimisation plugins.' ),
			'new'            => '1656829808',
			'thumbnail'      => self::WR_ICON,
			'thumbnail_name' => 'thumbnail.svg',
			'redirect'       => 'options-general.php?page=wprocket',
			'type'           => 'external',
		);
	}
}
