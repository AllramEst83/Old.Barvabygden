<?php
/* Copyright 2019: one.com */
require_once 'inc/class-onecom-logger.php';


if ( ! ( class_exists( 'OTPHP\TOTP' ) && class_exists( 'ParagonIE\ConstantTime\Base32' ) ) ) {
	require_once __DIR__ . '/inc/lib/validator.php';
}
if ( ! ( class_exists( 'OCPushStats' ) ) ) {
	require_once __DIR__ . '/inc/lib/OCPushStats.php';
}

if ( ! class_exists( 'OnecomExcludeCache' ) ) {
	require_once __DIR__ . '/inc/class-onecomexcludecache.php';
}
#[\AllowDynamicProperties]
final class OCVCaching extends VCachingOC {
	const DEFAULTTTL     = 2592000; //1 month
	const DEFAULTTTLUNIT = 'days'; // in days
	const DEFAULTENABLE  = 'true';
	const DEFAULTPREFIX  = 'varnish_caching_';
	const OPTIONCDN      = 'oc_cdn_enabled';
	const PLUGINNAME     = 'onecom-vcache';
	const TRANSIENT      = '__onecom_allowed_package';
	const PLUGINVERSION  = '4.1.10';

	const OCRULESVERSION = 1.2;

	const WR_ADDON_API             = MIDDLEWARE_URL . '/features/addon/WP_ROCKET/status';
	const WP_PURGE_CDN             = MIDDLEWARE_URL . '/purge-cdn';
	const HTTPS                    = 'https://';
	const HTTP                     = 'http://';
	const WP_ROCKET_PATH           = 'wp-rocket/wp-rocket.php';
	const ONECOM_HEADER_BEGIN_TEXT = '# One.com response headers BEGIN';


	private $logger;

	public $vc_path;
	public $oc_vc_path;
	public $oc_vc_uri;
	public $state = 'false';

	public $cdn_url;
	public $blog_url;

	private $messages = array();
	private $is_v3    = false;

	public function __construct() {

		$this->oc_vc_path = __DIR__;

		$this->oc_vc_uri = plugins_url( '', __FILE__ );
		$this->vc_path   = dirname( $this->oc_vc_path );

		$this->logger = new Onecom_Logger();
		$this->logger->set_file_name( 'vcache' );

		$this->blog_url = get_option( 'home' );
		$this->purge_id = $this->oc_json_get_option( 'onecom_vcache_info', 'vcache_purge_id' );

		if ( is_multisite() ) {
			$this->cdn_url = rtrim( 'https://usercontent.one/wp/' . str_replace( array( self::HTTPS, self::HTTP ), '', network_site_url() ), '/' );
		} else {
			$this->cdn_url = 'https://usercontent.one/wp/' . str_replace( array( self::HTTPS, self::HTTP ), '', $this->blog_url );
		}

		$this->clusterAdjustments();

		/**
		 * This commented becuase performance cache is available to all now.
		 * and Enable disable settings works with activation/deactivation hooks, no need to do it on each page load
		 * @todo - to be deleted after a while if all works well
		 */
		add_action( 'admin_init', array( $this, 'runAdminSettings' ), 1 );

		add_action( 'admin_menu', array( $this, 'remove_parent_page' ), 100 );
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		add_action( 'admin_init', array( $this, 'options_page_fields' ) );
		add_action( 'plugins_loaded', array( $this, 'filter_purge_settings' ), 1 );
		add_action( 'admin_head', array( $this, 'vcaching_reset_dev_mode' ), 10 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_resources' ) );
		add_action( 'admin_head', array( $this, 'onecom_vcache_icon_css' ) );

		add_action( 'wp_ajax_oc_set_vc_state', array( $this, 'oc_set_vc_state_cb' ) );
//		add_action( 'wp_ajax_oc_set_vc_ttl', array( $this, 'oc_set_vc_ttl_cb' ) );
//		add_action( 'wp_ajax_oc_set_cdn_state', array( $this, 'oc_cdn_state_cb' ) );
//		add_action( 'wp_ajax_oc_set_dev_mode', array( $this, 'oc_set_dev_mode_cb' ) );
//		add_action( 'wp_ajax_oc_exclude_cdn_mode', array( $this, 'oc_exclude_cdn_mode_cb' ) );
//		add_action( 'wp_ajax_oc_update_cdn_data', array( $this, 'oc_update_cdn_data_cb' ) );
		add_action( 'wp_ajax_oc_activate_wp_rocket', array( $this, 'oc_activate_wp_rocket' ) );
		add_action( 'template_redirect', array( $this, 'oc_cdn_rewrites' ) );
		add_action( 'upgrader_process_complete', array( $this, 'oc_upgrade_housekeeping' ), 10, 2 );
		add_action( 'plugins_loaded', array( $this, 'oc_update_headers_htaccess' ) );
		add_action( 'switch_theme', array( $this, 'purge_theme_cache' ) );
		add_action( 'onecom_purge_cdn', array( $this, 'oc_purge_cdn_cache' ) );
		add_action( 'wp_ajax_oc_handle_cdn_settings', array( $this, 'oc_handle_cdn_settings_cb' ) );

		// remove purge requests from Oclick demo importer
		add_filter( 'vcaching_events', array( $this, 'vcaching_events_cb' ) );
		//intercept the list of urls, replace multiple urls with a single generic url
		add_filter( 'vcaching_purge_urls', array( $this, 'vcaching_purge_urls_cb' ) );
		add_action( 'wp_ajax_purge_cache', array( $this,'handle_purge_cache_request' ));


		register_activation_hook( $this->vc_path . DIRECTORY_SEPARATOR . 'vcaching.php', array( $this, 'onActivatePlugin' ) );
		register_deactivation_hook( $this->vc_path . DIRECTORY_SEPARATOR . 'vcaching.php', array( $this, 'onDeactivatePlugin' ) );
		$exclude_cache = new OnecomExcludeCache();
	}


	/**
	 * Function to run admin settings
	 *
	 */
	public function runAdminSettings() {
		if ( 'false' !== $this->state ) {
			return;
		}

		// Following removes admin bar purge link, so commented
		// add_action( 'admin_bar_menu', array( $this, 'remove_toolbar_node' ), 999 );

		add_filter( 'post_row_actions', array( $this, 'remove_post_row_actions' ), 10, 2 );
		add_filter( 'page_row_actions', array( $this, 'remove_page_row_actions' ), 10, 2 );
	}

	/**
	 * Function will execute after plugin activated
	 *
	 **/
	public function onActivatePlugin() {
		// Enable/Disable Cache/CDN on activation based on eligibility
		$cdn_enabled = update_site_option( self::OPTIONCDN, 'true', 'no' );
		self::setDefaultSettings();
	}

	/**
	 * Function will execute after plugin deactivated
	 *
	 */
	public function onDeactivatePlugin() {
		$on_deactivate = true;
		self::disableDefaultSettings( $on_deactivate );
		self::purgeAll();
	}

	/**
	 * Function to make some checks to ensure best usage
	 **/
	private function runChecklist() {
		$this->oc_upgrade_housekeeping( 'activate' );

		// If not exist, then return
		if ( ! in_array( 'vcaching/vcaching.php', (array) get_site_option( 'active_plugins' ), true ) ) {
			return true;
		}

		$this->logger->wp_api_sendlog( 'already_exists', self::PLUGINNAME, self::PLUGINNAME . 'DefaultWP Caching plugin already exists.', self::PLUGINVERSION );
		add_action( 'admin_notices', array( $this, 'duplicateWarning' ) );

		return false;
	}

	/**
	 * Function to disable vcache promo/notice
	 *
	 */
	private function disablePromoNotice() {
		$local_promo = get_site_option( 'onecom_local_promo' );
		if ( isset( $local_promo['xpromo'] ) && '18-jul-2018' === $local_promo['xpromo'] ) {
			$local_promo['show'] = false;
			update_site_option( 'onecom_local_promo', $local_promo, 'no' );
		}
	}

	/*
	 * Show Admin notice
	 */
	public function duplicateWarning() {

		$screen       = get_current_screen();
		$warn_screens = array(
			'toplevel_page_onecom-vcache-plugin',
			'plugins',
			'options-general',
			'dashboard',
		);

		if ( ! in_array( $screen->id, $warn_screens, true ) ) {
			return;
		}

		$class = 'notice notice-warning is-dismissible';

		$dect_link = add_query_arg(
			array(
				'disable-old-varnish' => 1,
				'_wpnonce'            => wp_create_nonce( 'disable-old-varnish' ),
			)
		);

		$dect_link = wp_nonce_url( $dect_link, 'plugin-deactivation' );
		$message   = __( 'To get the best out of One.com Performance Cache, kindly deactivate the existing "Varnish Caching" plugin.&nbsp;&nbsp;', 'vcaching' );
		$message  .= sprintf( "<a href='%s' class='button'>%s</a>", ( $dect_link ), __( 'Deactivate' ) );
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
	}

	/* Function to convert boolean to string
	 *
	 *
	 */
	private function booleanCast( $value ) {
		if ( ! is_string( $value ) ) {
			$value = ( 1 === $value || true === $value ) ? 'true' : 'false';
		}
		if ( '1' === $value ) {
			$value = 'true';
		}
		if ( '0' === $value ) {
			$value = 'false';
		}
		return $value;
	}


	/**
	 * Function to set default settings for one.com
	 *
	 **/
	private function setDefaultSettings() {
		// Enable by default
		$enable  = $this->booleanCast( self::DEFAULTENABLE );
		$enabled = update_site_option( self::DEFAULTPREFIX . 'enable', $enable, 'no' );
		$check   = get_site_option( self::DEFAULTPREFIX . 'enable', $enable );
		if ( ! ( 'true' === $check || true === $check || 1 === $check ) ) {
			return;
		}

		// Update the cookie name
		if (! get_site_option(self::DEFAULTPREFIX . 'cookie')) {
			$name = bin2hex(random_bytes(16));  // changed from sha1(md5(uniqid())) to fix SAST
			update_site_option(self::DEFAULTPREFIX . 'cookie', $name, 'no');
		}

		// Set default TTL
		$ttl      = self::DEFAULTTTL;
		$ttl_unit = self::DEFAULTTTLUNIT;
		if ( ! get_site_option( self::DEFAULTPREFIX . 'ttl' ) && ! is_bool( get_site_option( self::DEFAULTPREFIX . 'ttl' ) ) && get_site_option( self::DEFAULTPREFIX . 'ttl' ) !== 0 ) {
			update_site_option( self::DEFAULTPREFIX . 'ttl', $ttl, 'no' );
			update_site_option( self::DEFAULTPREFIX . 'ttl_unit', $ttl_unit, 'no' );
		} elseif ( ! get_site_option( self::DEFAULTPREFIX . 'ttl' ) && is_bool( get_site_option( self::DEFAULTPREFIX . 'ttl' ) ) ) {
			update_site_option( self::DEFAULTPREFIX . 'ttl', $ttl, 'no' );
			update_site_option( self::DEFAULTPREFIX . 'ttl_unit', $ttl_unit, 'no' );
		}
		if ( ! get_site_option( self::DEFAULTPREFIX . 'homepage_ttl' ) && ! is_bool( get_site_option( self::DEFAULTPREFIX . 'homepage_ttl' ) ) && get_site_option( self::DEFAULTPREFIX . 'homepage_ttl' ) !== 0 ) {
			update_site_option( self::DEFAULTPREFIX . 'homepage_ttl', $ttl, 'no' );
			update_site_option( self::DEFAULTPREFIX . 'ttl_unit', $ttl_unit, 'no' );
		} elseif ( ! get_site_option( self::DEFAULTPREFIX . 'homepage_ttl' ) && is_bool( get_site_option( self::DEFAULTPREFIX . 'homepage_ttl' ) ) ) {
			update_site_option( self::DEFAULTPREFIX . 'homepage_ttl', $ttl, 'no' );
			update_site_option( self::DEFAULTPREFIX . 'ttl_unit', $ttl_unit, 'no' );
		}

		// Set default varnish IP
		$ip = getHostByName( getHostName() );
		update_site_option( self::DEFAULTPREFIX . 'ips', $ip, 'no' );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			update_site_option( self::DEFAULTPREFIX . 'debug', true, 'no' );
		}

		// Deactivate the old varnish caching plugin on user's consent.
		if ( isset( $_REQUEST['disable-old-varnish'] ) && 1 === $_REQUEST['disable-old-varnish'] ) {
			deactivate_plugins( '/vcaching/vcaching.php' );
			self::runAdminSettings();
			add_action( 'admin_bar_menu', array( $this, 'remove_toolbar_node' ), 999 );
		}

		// Check and notify if varnish plugin already active.
		if ( in_array( 'vcaching/vcaching.php', (array) get_site_option( 'active_plugins' ), true ) ) {
			add_action( 'admin_notices', array( $this, 'duplicateWarning' ) );
		}
	}

	/**
	 * Function to disable varnish plugin
	 *
	 **/
	private function disableDefaultSettings( $on_deactivate = false ) {
		// Disable by default
		// $enable = $this->booleanCast( false );
		// $disabled = update_option( self::DEFAULTPREFIX . 'enable', $enable );
		$disabled = false;
		$action   = ( true === $on_deactivate ) ? 'disableManual' : 'featureDisabled';
		if ( $disabled ) {
			$message = self::PLUGINNAME . ' feature disabled ' . $action;
			$this->logger->log( $message );
			self::purgeAll();
		}
		// Intentionally commented the auto-turn-off on package downgrade
		// BECAUSE it is causing auto-ON
		delete_option( self::DEFAULTPREFIX . 'ttl' );
		delete_option( self::DEFAULTPREFIX . 'homepage_ttl' );
		delete_option( self::DEFAULTPREFIX . 'ttl_unit' );
		delete_option( 'onecom_vcache_info' );
	}

	/**
	 * Remove current menu item
	 *
	 */
	public function remove_parent_page() {
		remove_menu_page( 'vcaching-plugin' );
	}

	/**
	 * Add menu item
	 *
	 */
	public function add_menu_item() {
		if ( parent::check_if_purgeable() ) {
			global $onecom_generic_menu_position;
			$position = ( function_exists( 'onecom_get_free_menu_position' ) && ! empty( $onecom_generic_menu_position ) ) ? onecom_get_free_menu_position( $onecom_generic_menu_position ) : null;
			add_menu_page( __( 'Performance Cache', 'vcaching' ), __( 'Performance Cache&nbsp;', 'vcaching' ), 'manage_options', self::PLUGINNAME . '-plugin', array( $this, 'settings_page' ), 'dashicons-dashboard', $position );

		}
	}

	/**
	 * Function to show settings page
	 *
	 */
	public static function cache_settings_page() {
		require_once plugin_dir_path( __FILE__ ) . '/templates/cache-settings.php';
	}

	public static function cdn_settings_page() {
		require_once plugin_dir_path( __FILE__ ) . '/templates/cdn-settings.php';
	}

	public static function wp_rocket_page() {
		require_once plugin_dir_path( __FILE__ ) . '/templates/wp-rocket.php';
	}

	/**
	 * Function to customize options fields
	 *
	 */
	public function options_page_fields() {
		add_settings_section( self::DEFAULTPREFIX . 'oc_options', null, null, self::DEFAULTPREFIX . 'oc_options' );

		add_settings_field( self::DEFAULTPREFIX . 'ttl', __( 'Cache TTL', 'vcaching' ) . '<span class="oc-tooltip"><span class="dashicons dashicons-editor-help"></span><span>' . __( 'The time that website data is stored in the Varnish cache. After the TTL expires the data will be updated, 0 means no caching.', 'vcaching' ) . '</span></span>', array( $this, self::DEFAULTPREFIX . 'ttl_callback' ), self::DEFAULTPREFIX . 'oc_options', self::DEFAULTPREFIX . 'oc_options' );

		if ( isset( $_POST['option_page'] ) && self::DEFAULTPREFIX . 'oc_options' === $_POST['option_page'] ) {
			register_setting( self::DEFAULTPREFIX . 'oc_options', self::DEFAULTPREFIX . 'enable' );
			register_setting( self::DEFAULTPREFIX . 'oc_options', self::DEFAULTPREFIX . 'ttl' );

			$ttl       = $_POST[ self::DEFAULTPREFIX . 'ttl' ];
			$is_update = update_site_option( self::DEFAULTPREFIX . 'homepage_ttl', $ttl, 'no' ); //overriding homepage TTL
		}

		self::disablePromoNotice();
	}

	/**
	 * Function enqueue resources
	 *
	 */
	public function enqueue_resources( $hook ) {
		$pages = array(
			'toplevel_page_onecom-vcache-plugin',
			'_page_onecom-vcache-plugin',
			'_page_onecom-cdn',
			'_page_onecom-wp-rocket',
		);
		if ( ! in_array( $hook, $pages, true ) ) {
			return;
		}

		if ( SCRIPT_DEBUG || SCRIPT_DEBUG === 'true' ) {
			$folder     = '';
			$extenstion = '';
		} else {
			$folder     = 'min-';
			$extenstion = '.min';
		}

		wp_register_style(
			self::PLUGINNAME,
			$this->oc_vc_uri . '/assets/' . $folder . 'css/style' . $extenstion . '.css',
			null,
			'2.0.0',
			'all'
		);

		wp_enqueue_style(
			'onecss',
			$this->oc_vc_uri . '/assets/min-css/one.min.css',
			null,
			'2.0.0',
			'all'
		);
		wp_register_script(
			self::PLUGINNAME,
			$this->oc_vc_uri . '/assets/' . $folder . 'js/scripts' . $extenstion . '.js',
			array( 'jquery' ),
			'2.0.0',
			'all'
		);
		wp_enqueue_style( self::PLUGINNAME );
		wp_enqueue_script( self::PLUGINNAME );

		if( 'toplevel_page_onecom-vcache-plugin' === $hook ) {
			wp_enqueue_script(
				'cache-admin-form',
				plugins_url('/assets/js/blocks/oc-cache-settings.js', __FILE__),
				['wp-element'],
				'1.0.0',
				true
			);
		}
		if( '_page_onecom-cdn' === $hook ) {
			wp_enqueue_script(
				'cdn-admin-form' ,
				plugins_url( '/assets/js/blocks/oc-cdn-settings.js' , __FILE__ ) ,
				[ 'wp-element' ] ,
				'1.0.0' ,
				true
			);
		}
		$varnish_caching_ttl      = get_site_option( 'varnish_caching_ttl' );
		$varnish_caching_ttl_unit = get_site_option( 'varnish_caching_ttl_unit' );
		$varnish_caching          = get_site_option( 'varnish_caching_enable' );
		if ( 'minutes' === $varnish_caching_ttl_unit ) {
			$vc_ttl_as_unit = $varnish_caching_ttl / 60;
		} elseif ( 'hours' === $varnish_caching_ttl_unit ) {
			$vc_ttl_as_unit = $varnish_caching_ttl / 3600;
		} elseif ( 'days' === $varnish_caching_ttl_unit ) {
			$vc_ttl_as_unit = $varnish_caching_ttl / 86400;
		} else {
			$vc_ttl_as_unit = $varnish_caching_ttl;
		}


		wp_localize_script('cache-admin-form', 'vcacheSettings', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('vcaching'),
			'vc_ttl' => $vc_ttl_as_unit,
			'vc_ttl_unit' => $varnish_caching_ttl_unit,
			'vc_status' => $varnish_caching,
			'cache_ttl' => __( 'Cache TTL' , 'vcaching' ),
			'frequency_ttl' => __( 'Frequency' , 'vcaching' ),
			'clearCacheMessage' => array(
				'success' => __('Your cache was cleared.', 'vcaching'),
				'failure' => __('Couldn’t clear your cache. Please try again and contact our support if the issue persists.', 'vcaching'),
			),
            'settingsSaveMessage' => array(
				'success' => __('Your changes were saved.', 'vcaching'),
				'failure' => __('Couldn’t save your changes.  Please try again and contact our support if the issue persists.', 'vcaching'),
			),
            'ttlValidationMsg' => __('TTL value must be at least 1 second.','vcaching'),
			'frequency_options' => [
				'seconds' => __('Seconds', 'vcaching'),
				'minutes' => __('Minutes', 'vcaching'),
				'hours'   => __('Hours', 'vcaching'),
				'days'    => __('Days', 'vcaching'),
			],
            'LblActive' => __('Active', 'vcaching'),
            'LblInactive' => __('Inactive', 'vcaching'),
            'TooltipMessage' => __('The time your website data will be stored in the Varnish cache. Our default value is 30 days. When the TTL expires, the cached content will be refreshed.', 'vcaching'),
            'Ttlplaceholder' =>__('Enter Cache TTL','vcaching'),
            'ClearCache' =>__('Clear Cache now','vcaching'),
            'clearingCache' =>__('Clearing cache','vcaching'),
             'labelSave'         => __('Save', 'vcaching'),
            'labelSaving'         => __('Saving', 'vcaching'),


		]);

		$cdn_enabled = get_site_option( 'oc_cdn_enabled' );
		$dev_mode_duration     = parent::oc_json_get_option( 'onecom_vcache_info', 'dev_mode_duration' );
		$oc_dev_mode_status    = parent::oc_json_get_option( 'onecom_vcache_info', 'oc_dev_mode_enabled' );
		$oc_exclude_cdn_data   = parent::oc_json_get_option( 'onecom_vcache_info', 'oc_exclude_cdn_data' );
		$oc_exclude_cdn_status = parent::oc_json_get_option( 'onecom_vcache_info', 'oc_exclude_cdn_enabled' );


		wp_localize_script('cdn-admin-form', 'CdnSettings', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' =>  wp_create_nonce('vcaching'),
			'clearCacheMessage' => array(
				'success' => __('Your CDN cache was cleared.', 'vcaching'),
				'failure' => __('Couldn’t clear your CDN cache. Please try again and contact our support if the issue persists.', 'vcaching'),
			),
			'settingsSaveMessage' => array(
				'success' => __('Your changes were saved.', 'vcaching'),
				'failure' => __('Couldn’t save your changes.  Please try again and contact our support if the issue persists.', 'vcaching'),
			),
			'CdnStatus' => $cdn_enabled,
			'devModeDuration' => $dev_mode_duration,
			'devModeStatus' => $oc_dev_mode_status,
			'excludeCdnData' => $oc_exclude_cdn_data,
			'excludeCdnStatus' => $oc_exclude_cdn_status,
			'lblActive' => __('Active', 'vcaching'),
			'lblInactive' => __('Inactive', 'vcaching'),
            'excludeTitle' => __('Exclude items from CDN' , 'vcaching'),
            'excludeDescription' => __('Enter file names (myfile.jpg), file extensions (.jpg) or paths (wp-content/uploads/2025/file.jpg) that shouldn’t be delivered through CDN. Enter one item per line.' , 'vcaching'),
            'excludeValidationMsg' => __('Enter what you want to exclude.' , 'vcaching'),
            'excludeCdnMsg' => __('Enter one item per line' , 'vcaching'),
            'CdnTitle' => __('CDN settings' , 'vcaching'),
            'clearCache' => __('Clear CDN cache' , 'vcaching'),
            'clearingCache' => __('Clearing CDN cache' , 'vcaching'),
            'excludeValidationMsg' => __('Enter what you want to exclude.' , 'vcaching'),
            'devModeTitle' => __('Activate development mode', 'vcaching'),
            'devModeDescription' => __('Specify when development mode should get deactivated again (hours).  Note that CDN is inactive for logged-in users while development mode is active.', 'vcaching'),
            'mwpDisabledHeading' => __('This is a Managed WP feature', 'vcaching'),
            'mwpDisabledMessage' => __('Upgrade now to get access to development mode and exclude CDN feature.', 'vcaching'),
            'isMWP'             => $this->oc_premium(),
            'labelSave'         => __('Save', 'vcaching'),
            'labelSaving'         => __('Saving', 'vcaching'),
            'labelExclude'         => __('Items to exclude', 'vcaching'),
            'upgradeBtn'            => __('Upgrade now', 'vcaching'),

		]);

	}

	/* Function to enqueue style tag in admin head
	 * */
	public function onecom_vcache_icon_css() {
		echo "<style>.toplevel_page_onecom-vcache-plugin > .wp-menu-image{display:flex !important;align-items: center;justify-content: center;}.toplevel_page_onecom-vcache-plugin > .wp-menu-image:before{content:'';background-image:url('" . $this->oc_vc_uri . "/assets/images/performance-inactive-icon.svg');font-family: sans-serif !important;background-repeat: no-repeat;background-position: center center;background-size: 18px 18px;background-color:#fff;border-radius: 100px;padding:0 !important;width:18px;height: 18px;}.toplevel_page_onecom-vcache-plugin.current > .wp-menu-image:before{background-size: 16px 16px; background-image:url('" . $this->oc_vc_uri . "/assets/images/performance-active-icon.svg');}.ab-top-menu #wp-admin-bar-purge-all-varnish-cache .ab-icon:before,#wpadminbar>#wp-toolbar>#wp-admin-bar-root-default>#wp-admin-bar-onecom-wp .ab-item:before, .ab-top-menu #wp-admin-bar-onecom-staging .ab-item .ab-icon:before{top: 2px;}a.current.menu-top.toplevel_page_onecom-vcache-plugin.menu-top-last{word-spacing: 10px;}@media only screen and (max-width: 960px){.auto-fold #adminmenu a.menu-top.toplevel_page_onecom-vcache-plugin{height: 55px;}}</style>";
		return;
	}

	/* Function to show inline promo on premium cdn switches */
	public function mwp_promo($cuJourneyEvent = '') {
		ob_start(); ?>
		<div class="mwp-promo">
			<div class="oc-flex-start">
				<svg width="9" height="22" viewBox="0 0 9 14" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path fill-rule="evenodd" clip-rule="evenodd" d="M1.49012 0H7.50733L5.50748 4.87344L9 4.86469L2.14153 14L3.7723 7.2768L0 7.27442L1.49012 0Z" fill="#0078C8"/>
				</svg>
				<span>
					<?php _e( 'This is a Managed WordPress feature', 'vcaching' ); ?> <a href="<?php echo oc_upgrade_link( 'inline_badge' ); ?>" target="_blank" class="<?php echo $cuJourneyEvent;?>"> <?php _e( 'Learn more', 'vcaching' ); ?> </a>
				</span>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Function to purge all
	 *
	 */
	private function purgeAll() {
		$pregex      = '.*';
		$purgemethod = 'regex';
		$path        = '/';
		$schema      = self::HTTP;

		$ip = get_site_option( self::DEFAULTPREFIX . 'ips' );

		$purgeme = $schema . $ip . $path . $pregex;

		$headers  = array(
			'host'              => $_SERVER['SERVER_NAME'],
			'X-VC-Purge-Method' => $purgemethod,
			'X-VC-Purge-Host'   => $_SERVER['SERVER_NAME'],
		);
		$response = wp_remote_request(
			$purgeme,
			array(
				'method'    => 'PURGE',
				'headers'   => $headers,
				'sslverify' => false,
			)
		);
		if ( $response instanceof WP_Error ) {
			error_log( 'Cannot purge: ' . $purgeme );
		}
	}

	/**
	 * Function to change purge settings
	 *
	 */
	public function filter_purge_settings() {
		add_filter( 'ocvc_purge_notices', array( $this, 'ocvc_purge_notices_callback' ), 10, 2 );
		add_filter( 'ocvc_purge_url', array( $this, 'ocvc_purge_url_callback' ), 1, 3 );
		add_filter( 'ocvc_purge_headers', array( $this, 'ocvc_purge_headers_callback' ), 1, 2 );
		add_filter( 'ocvc_permalink_notice', array( $this, 'ocvc_permalink_notice_callback' ) );
		add_filter( 'vcaching_purge_urls', array( $this, 'vcaching_purge_urls_callback' ), 10, 2 );

		add_action( 'admin_notices', array( $this, 'oc_vc_notice' ) );
		add_action( 'network_admin_notices', array( $this, 'oc_vc_notice' ) );
	}

	/**
	 * Function to filter the purge request response
	 *
	 * @param object $response //request response object
	 * @param string $url // url trying to purge
	 */
	public function ocvc_purge_notices_callback( $response, $url ) {

		$response = wp_remote_retrieve_body( $response );

		$find = array(
			/* translators: %s is the URL of the resource being purged */
			'404 Key not found' => sprintf( __( 'It seems that %s is already purged. There is no resource in the cache to purge.', 'vcaching' ), $url ),
			/* translators: %s is the URL of the resource that was purged */
			'Error 200 Purged'  => sprintf( __( '%s is purged successfully.', 'vcaching' ), $url ),
		);

		foreach ( $find as $key => $message ) {
			if ( strpos( $response, $key ) !== false ) {
				array_push( $this->messages, $message );
			}
		}
	}

	/**
	 * Function to add notice
	 *
	 */
	public function oc_vc_notice() {
		if ( empty( $this->messages ) && empty( $_SESSION['ocvcaching_purge_note'] ) ) {
			return;
		}
		?>
		<div class="notice notice-warning">
			<ul>
				<?php
				if ( ! empty( $this->messages ) ) {
					foreach ( $this->messages as $key => $message ) {
						if ( $key > 0 ) {
							break;
						}
						?>
						<li><?php echo $message; ?></li>
						<?php
					}
				} elseif ( ! empty( $_SESSION['ocvcaching_purge_note'] ) ) {
					foreach ( $_SESSION['ocvcaching_purge_note'] as $key => $message ) {
						if ( $key > 0 ) {
							break;
						}
						?>
						<li><?php echo $message; ?></li>
						<?php
					}
				}
				?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Function to change purge URL
	 *
	 * @param string $url //URL to be purge
	 * @param string $path //Path of URL
	 * @param string $prefex //Regex if any
	 * @return string $purgeme //URL to be purge
	 */
	public function ocvc_purge_url_callback( $url, $path, $pregex ) {
		$p = parse_url( $url );

		$scheme  = ( isset( $p['scheme'] ) ? $p['scheme'] : '' );
		$host    = ( isset( $p['host'] ) ? $p['host'] : '' );
		$purgeme = $scheme . '://' . $host . $path . $pregex;

		return $purgeme;
	}

	/**
	 * Function to change purge request headers
	 *
	 * @param string $url //URL to be purge
	 * @param array $headers //Headers for the request
	 * @return array $headers //New headers
	 */
	public function ocvc_purge_headers_callback( $url, $headers ) {
		$p = parse_url( $url );
		if ( isset( $p['query'] ) && ( 'vc-regex' === $p['query'] ) ) {
			$purgemethod = 'regex';
		} else {
			$purgemethod = 'exact';
		}
		$headers['X-VC-Purge-Host']   = $_SERVER['SERVER_NAME'];
		$headers['host']              = $_SERVER['SERVER_NAME'];
		$headers['X-VC-Purge-Method'] = $purgemethod;
		return $headers;
	}

	/**
	 * Function to change permalink message
	 *
	 */
	public function ocvc_permalink_notice_callback( $message ) {
		$message = __( 'A custom URL or permalink structure is required for the Performance Cache plugin to work correctly. Please go to the <a href="options-permalink.php">Permalinks Options Page</a> to configure them.', 'vcaching' );
		return '<div class="notice notice-warning"><p>' . $message . '</p></div>';
	}


	/**
	 * Function to to remove menu item from admin menu bar
	 *
	 */
	public function remove_toolbar_node( $wp_admin_bar ) {
		// replace 'updraft_admin_node' with your node id
		$wp_admin_bar->remove_node( 'purge-all-varnish-cache' );
	}

	/**
	 * Function to to remove purge cache from post
	 *
	 */
	public function remove_post_row_actions( $actions, $post ) {
		if ( isset( $actions['vcaching_purge_post'] ) ) {
			unset( $actions['vcaching_purge_post'] );
		}
		return $actions;
	}

	/**
	 * Function to to remove purge cache from page
	 *
	 */
	public function remove_page_row_actions( $actions, $post ) {
		if ( isset( $actions['vcaching_purge_page'] ) ) {
			unset( $actions['vcaching_purge_page'] );
		}
		return $actions;
	}

	/**
	 * Function to set purge single post/page URL
	 *
	 * @param array $array_urls // array of urls
	 * @param number $post_id //POST ID
	 */
	public function vcaching_purge_urls_callback( $array_urls, $post_id ) {
		$url = get_permalink( $post_id );
		array_unshift( $array_urls, $url );
		return $array_urls;
	}

	/**
	 * Function vcaching_events_cb
	 * Callback function for vcaching_events WP filter
	 * This function checks if the registered events are to be returned, judging from request payload.
	 * e.g. the events are nulled for request actions like "heartbeat" and  "ocdi_import_demo_data"
	 * @param $events, an array of events on which caching is hooked.
	 * @return array
	 */
	public function vcaching_events_cb( $events ) {

		$no_post_action     = ! isset( $_REQUEST['action'] );
		$action_not_watched = isset( $_REQUEST['action'] ) && ( 'ocdi_import_demo_data' === $_REQUEST['action'] || 'heartbeat' === $_REQUEST['action'] );

		if ( $no_post_action || $action_not_watched ) {
			return array();
		} else {
			return $events;
		}
	}

	/**
	 * Function vcaching_purge_urls_cb
	 * Callback function for vcaching_purge_urls WP filters
	 * This function removes all the urls that are to be purged and returns single url that purges entire cache.
	 * @param $urls, an array of urls that were originally to be purged.
	 * @return array
	 */
	public function vcaching_purge_urls_cb( $urls ) {
		$site_url  = trailingslashit( get_site_url() );
		$purge_url = $site_url . '.*';
		$urls      = array( $purge_url );
		return $urls;
	}

	/**
	 * Function vcaching_reset_dev_mode
	 * This function deletes/reset development mode data on admin init
	 * ** if development mode expire time passed
	 */
	public function vcaching_reset_dev_mode() {
		$cdn_dev_enabled = $this->oc_json_get_option( 'onecom_vcache_info', 'oc_dev_mode_enabled' );
		$dev_expire_time = $this->oc_json_get_option( 'onecom_vcache_info', 'dev_expire_time' );

		if ( 'true' === $cdn_dev_enabled && 'false' !== $dev_expire_time && $dev_expire_time < time() ) {
			// if development mode exists and expired, reset it
			$this->oc_json_delete_option( 'onecom_vcache_info', 'oc_dev_mode_enabled' );
			$this->oc_json_delete_option( 'onecom_vcache_info', 'dev_expire_time' );
			$this->oc_json_delete_option( 'onecom_vcache_info', 'dev_mode_duration' );
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Function oc_set_vc_state_cb()
	 * Enable/disable vcaching. Used as AJAX callback
	 * @since v0.1.24
	 * @param null
	 * @return null
	 */
	public function oc_set_vc_state_cb() {
		if ( ! isset( $_POST['oc_csrf'] ) && ! wp_verify_nonce( 'one_vcache_nonce' ) ) {
			return false;
		}
		$state = intval( $_POST['vc_state'] ) === 0 ? 'false' : 'true';

		// check eligibility if Performance Cache is being enabled. If it is being disabled, allow to continue
		if ( 'true' === $state ) {
			$event_action = 'enable';
			$res          = $this->oc_check_pc_activation( $state );
			if ( 'success' !== $res['status'] ) {
				wp_send_json( $res );
				return false;
			}
		} else {
			$event_action = 'disable';
		}

		if ( get_site_option( self::DEFAULTPREFIX . 'enable' ) === $state ) {
			$result_status = true;
		} else {
			$result_status = update_site_option( self::DEFAULTPREFIX . 'enable', $state, 'no' );
		}
		$result_ttl = $this->oc_set_vc_ttl_cb( false );
		$response   = array();
		if ( $result_ttl && $result_status ) {
			$response = array(
				'status'  => 'success',
				'message' => __( 'Performance cache settings updated' ),
			);
			( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_performance_cache( "$event_action", 'setting', 'cache', 'performance_cache' ) : '' );
		} else {
			$response = array(
				'status'  => 'error',
				'message' => __( 'Something went wrong!' ),
			);
		}
		wp_send_json( $response );
	}

	public function oc_set_vc_ttl_cb( $echo_oc ) {
		if ( wp_doing_ajax() && ! isset( $_POST['oc_csrf'] ) && ! wp_verify_nonce( 'one_vcache_nonce' ) ) {
			return false;
		}
		if ( '' === $echo_oc ) {
			$echo_oc = true;
		}
		$ttl_value = intval( trim( $_POST['vc_ttl'] ) );
		$ttl       = 0 === $ttl_value ? 2592000 : $ttl_value;
		$ttl_unit  = trim( $_POST['vc_ttl_unit'] );
		$ttl_unit  = empty( $ttl_unit ) ? 'days' : $ttl_unit;

		// Convert into seconds except default value
		if ( 2592000 !== $ttl && 'minutes' === $ttl_unit ) {
			$ttl = $ttl * 60;
		} elseif ( 2592000 !== $ttl && 'hours' === $ttl_unit ) {
			$ttl = $ttl * 3600;
		} elseif ( 2592000 !== $ttl && 'days' === $ttl_unit ) {
			$ttl = $ttl * 86400;
		}

		if ( ( get_site_option( 'varnish_caching_ttl' ) === (string)$ttl ) && ( get_site_option( 'varnish_caching_homepage_ttl' ) === (string)$ttl ) && ( get_site_option( 'varnish_caching_ttl_unit' ) === (string)$ttl_unit ) ) {
			$result = true;
		} else {
			$result = update_site_option( 'varnish_caching_ttl', $ttl, 'no' );
			update_site_option( 'varnish_caching_homepage_ttl', $ttl, 'no' );
			update_site_option( 'varnish_caching_ttl_unit', $ttl_unit, 'no' );
			( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_performance_cache( 'update', 'setting', 'ttl', 'performance_cache' ) : '' );
		}
		$response = array();
		if ( $result ) {
			$response = array(
				'status'  => 'success',
				'message' => __( 'TTL updated' ),
			);
		} else {
			$response = array(
				'status'  => 'error',
				'message' => __( 'Something went wrong!' ),
			);
		}
		if ( $echo_oc ) {
			wp_send_json( $response );
		} else {
			return $result;
		}
	}

	/**
	 * Activate a plugin
	 */
	public function oc_activate_wp_rocket() {
		$activation_status = is_null( activate_plugin( self::WP_ROCKET_PATH ) );
		wp_send_json( array( 'status' => $activation_status ) );
	}

	/**
	 * Function oc_cdn_rewrites
	 * Intercept the html being sent to browser, replace the eligible urls with the CDN version
	 * @since v0.1.24
	 * @param null
	 * @return null
	 */
	public function oc_cdn_rewrites() {
		$cdn_state = get_site_option( 'oc_cdn_enabled' );
		if ( 'true' !== $cdn_state ) {
			return false;
		}
		// check if Development mode is enabled and Not expired for CDN
		$cdn_dev_enabled = $this->oc_json_get_option( 'onecom_vcache_info', 'oc_dev_mode_enabled' );
		$dev_expire_time = $this->oc_json_get_option( 'onecom_vcache_info', 'dev_expire_time' );

		// If development mode is not expired, skip CDN rewrite
		if ( 'true' === $cdn_dev_enabled && $dev_expire_time > time() && current_user_can( 'administrator' ) ) {
			return null;
		} elseif ( 'true' === $cdn_dev_enabled && 'false' !== $dev_expire_time && $dev_expire_time < time() ) {
			// if development mode exists but expired, reset it
			$this->oc_json_delete_option( 'onecom_vcache_info', 'oc_dev_mode_enabled' );
			$this->oc_json_delete_option( 'onecom_vcache_info', 'dev_expire_time' );
			$this->oc_json_delete_option( 'onecom_vcache_info', 'dev_mode_duration' );
		}
		ob_start( array( $this, 'rewrite' ) );
	}
	/**
	 * Function rewrite
	 * Rewrite assets url, replace native ones with the CDN version if the url meets rewrite conditions.
	 * @since v0.1.24
	 * @param array $html, the html source of the page, provided by ob_start
	 * @return string modified html source
	 */
	public function rewrite( $html ) {
		$url = get_option( 'home' );
		if ( is_multisite() ) {
			$protocols = array( self::HTTPS, self::HTTP );
		} else {
			$protocols = array( self::HTTPS, self::HTTP, '/' );
		}
		$domain_name = str_replace( $protocols, '', $url );

		$directories = 'wp-content';
		if ( is_multisite() ) {
			$pattern = "#(?:https://{$domain_name}/{$directories})(\S*\.[0-9a-z]+)\b#m";
		} else {
			$pattern = "/(?:https:\/\/$domain_name\/$directories)(\S*\.[0-9a-z]+)\b/m";
		}
		$updated_html = preg_replace_callback( $pattern, array( $this, 'rewrite_asset_url' ), $html );
		return $updated_html;
	}

	/**
	 * Function rewrite_asset_url
	 * Returns the url that is to be modified to point to CDN.
	 * This function acts as a callback to preg_replace_callback called in rewrite()
	 * @since v0.1.24
	 * @param array $asset, first element in the array will have the url we are interested in.
	 * @return string modified single url
	 */
	protected function rewrite_asset_url( $asset ) {
		/**
		 * Set conditions to rewrite urls.
		 * To maintain consistency, write conditions in a way that if they yield positive value,
		 * the url should not be modified
		 */
		$preview_condition = ( is_admin_bar_showing() && array_key_exists( 'preview', $_GET ) && 'true' === $_GET['preview'] );
		$path_condition    = ( strpos( $asset[0], 'wp-content' ) === false );
		//skip cdn rewrite in yoast-schema-graph
		$skip_yoast_path     = ( strpos( $asset[0], 'contentUrl' ) !== false );
		$extension_condition = ( strpos( $asset[0], '.php' ) !== false ) || ( strpos( $asset[0], '.elementor' ) !== false );
		$existing_live       = get_option( 'onecom_staging_existing_live' );

		$staging_condition       = ( ! empty( $existing_live ) && isset( $existing_live->directory_name ) );
		$template_path_condition = ( ( strpos( $asset[0], 'plugins' ) !== false ) && ( strpos( $asset[0], 'assets/templates' ) !== false ) );

		// If any condition is true, skip cdn rewrite
		if ( $preview_condition || $path_condition || $extension_condition || $staging_condition || $template_path_condition || $skip_yoast_path ) {
			return $asset[0];
		}

		$blog_url = $this->relative_url( $this->blog_url );
		// both http and https urls are to be replaced
		$subst_urls = array(
			'http:' . $blog_url,
			'https:' . $blog_url,
		);

		// Get all rules in array
		$cdn_exclude           = $this->oc_json_get_option( 'onecom_vcache_info', 'oc_exclude_cdn_data' );
		$oc_exclude_cdn_status = $this->oc_json_get_option( 'onecom_vcache_info', 'oc_exclude_cdn_enabled' );
		$explode_rules         = explode( "\n", $cdn_exclude );

		// If CDN exclude is enabled and any rule exists
		if ( 'true' === $oc_exclude_cdn_status && count( $explode_rules ) > 0 ) {
			// If any rule match to exclude CDN, replace CDN with domain URL
			foreach ( $explode_rules as $explode_rule ) {
				// If rule start with dot (.), check for file extension,
				if ( strpos( $explode_rule, $asset[0] ) === 0 && ! empty( trim( $explode_rule ) ) ) {
					// Exclude if current URL have given file extension
					if ( substr_compare( $explode_rule, $asset[0], -strlen( $asset[0] ) ) === 0 ) {
						return $asset[0];
					}
					return $asset[0];
				} elseif ( strpos( $asset[0], $explode_rule ) > 0 && ! empty( trim( $explode_rule ) ) ) {
					// else simply exclude folder/path etc if rule string find anywhere
					return $asset[0];
				}
			}
		}
		// don't change url if this is a v3 setup and urls is other than uploads
		if ( $this->is_v3 && ! strpos( $asset[0], '/wp-content/uploads/' ) ) {
			return $asset[0];
		}
		// is it a protocol independent URL?
		if ( strpos( $asset[0], '//' ) === 0 ) {
			$final_url = str_replace( $blog_url, $this->cdn_url, $asset[0] );
		}

		// check if not a relative path
		if ( strpos( $asset[0], $blog_url ) !== 0 ) {
			$final_url = str_replace( $subst_urls, $this->cdn_url, $asset[0] );
		}

		/**
		 *  Append query paramter to purge CDN files
		 *  * rawurlencode() to handle CDN Purge with Brizy builder URLs
		 */
		if ( $this->purge_id && strpos( $final_url, 'wp-content/uploads/brizy/' ) ) {
			// raw_url_encode with add_query_arg if used in other cases will return unexpected results such as /?ver?media
			$new_url = add_query_arg( 'media', $this->purge_id, rawurlencode( $final_url ) );

			return rawurldecode( $new_url );
		} elseif ( $this->purge_id ) {
			return add_query_arg( 'media', $this->purge_id, $final_url );
		} else {
			return $final_url;
		}
	}


	/**
	 * Function relative_url
	 * Check if given string is a relative url
	 * @since v0.1.24
	 * @param string $url
	 * @return string
	 */
	protected function relative_url( $url ) {
		return substr( $url, strpos( $url, '//' ) );
	}


	/**
	 * Function oc_upgrade_housekeeping
	 * Perform actions after plugin is upgraded or activated
	 * @since v0.1.24
	 * @param $upgrade_data - data passed by WP hooks, used only in case of activation
	 * @return void
	 */
	public function oc_upgrade_housekeeping( $upgrade_data = null, $options = null ) {

		// exit if this plugin is not being upgraded
		if ( $options && isset( $options['pugins'] ) && ! in_array( 'onecom-vcache/vcaching.php', $options['plugins'], true ) ) {
			return;
		}

		$existing_version_db = trim( get_site_option( 'onecom_plugin_version_vcache' ) );
		$current_version     = trim( self::PLUGINVERSION );

		//exit if plugin version is same in plugin and DB. If plugin is activated, bypass this condition
		if ( ( $current_version === $existing_version_db ) && ( 'activate' !== $upgrade_data ) ) {
			return;
		}
		// update plugin version in DB
		update_site_option( 'onecom_plugin_version_vcache', $current_version, 'no' );

		//
		( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_event_themes_and_plugins( 'update', 'plugin', self::PLUGINNAME, 'plugins_page' ) : '' );

		// if current subscription is eligible for Performance Cache, enable the plugins
		if ( get_site_option( self::DEFAULTPREFIX . 'enable' ) === '' ) {
			update_site_option( self::DEFAULTPREFIX . 'enable', 'true', 'no' );
		}

		if ( get_site_option( 'oc_cdn_enabled' ) === '' ) {
			update_site_option( 'oc_cdn_enabled', 'true', 'no' );
		}

		//set TTL for varnish caching, default for 1 month in seconds
		if ( get_site_option( 'varnish_caching_ttl' ) === '' ) {
			update_site_option( 'varnish_caching_ttl', '2592000', 'no' );
		}
		if ( get_site_option( 'varnish_caching_homepage_ttl' ) === '' ) {
			update_site_option( 'varnish_caching_homepage_ttl', '2592000', 'no' );
		}
	}

	/**
	 * Function oc_check_pc_activation
	 * Check if operation should be allowed or not.
	 * This function checks the features provided with the subscription package.
	 * @since v0.1.24
	 * @param $state - the state of switch, either true or false. True => enable the features, False => disable the features
	 * @return void
	 */
	public function oc_check_pc_activation( $state, $data = 'pcache' ) {
		if ( 'true' === $state ) {
			$result = oc_set_premi_flag( true );
			if ( null === $result['data'] && 1 !== $result['success'] ) {
				$response = array(
					'status' => '',
					'msg'    => __( 'Some error occurred, please reload the page and try again.', 'validator' ) . ' [' . $result['error'] . ']',
				);
			} elseif ( ( isset( $result['data'] ) && ( empty( $result['data'] ) ) && 'mwp' !== $data ) || ( in_array( 'ONE_CLICK_INSTALL', $result['data'], true ) && 'mwp' !== $data ) ) {
				$response = array(
					'status' => 'success',
					'sender' => 'verification',
				);
			} elseif ( oc_pm_features( $data, $result['data'] ) || in_array( 'MWP_ADDON', $result['data'], true ) ) {
				$response = array(
					'status' => 'success',
					'sender' => 'verification',
				);
			} else {
				$response = array(
					'status' => 'failed',
					'sender' => 'verification',
				);
			}
			return $response;
		}
	}

	// Fetch wp rocket addon info via feature endpoint
	// @todo - make it cluster compatible
	public function oc_wp_rocket_addon_info( $force = false, $domain = '' ) {
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
		$this->wp_rocket_addon_info = $this->oc_wp_rocket_addon_info();

		return (
			is_array( $this->wp_rocket_addon_info ) &&
			array_key_exists( 'success', $this->wp_rocket_addon_info ) &&
			$this->wp_rocket_addon_info['success'] &&
			array_key_exists( 'data', $this->wp_rocket_addon_info ) &&
			array_key_exists( 'source', $this->wp_rocket_addon_info['data'] ) &&
			'PURCHASED' === $this->wp_rocket_addon_info['data']['source'] &&
			array_key_exists( 'product', $this->wp_rocket_addon_info['data'] ) &&
			'WP_ROCKET' === $this->wp_rocket_addon_info['data']['product']
		);
	}

	// Check if WP Rocket is provisioned/installed via one.com
	public function is_oc_wp_rocket_flag_exists() {
		return get_site_option( 'oc-wp-rocket-activation' );
	}

	// Check if WP Rocket plugin is active
	public function is_wp_rocket_active(): bool {
		return is_plugin_active( self::WP_ROCKET_PATH );
	}

	// Check if WP Rocket is installed
	public function is_wp_rocket_installed(): bool {
		$plugins = get_plugins();
		return array_key_exists( self::WP_ROCKET_PATH, $plugins );
	}

	public function oc_update_headers_htaccess() {

		// exit if not logged in or not admin
		$user = wp_get_current_user();
		if ( ( ! isset( $user->roles ) ) || ( ! in_array( 'administrator', (array) $user->roles, true ) ) ) {
			return;
		}

		// exit for some of the common conditions
		if (
			defined( 'XMLRPC_REQUEST' )
			|| defined( 'DOING_AJAX' )
			|| defined( 'IFRAME_REQUEST' )
			|| ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() )
		) {
			return;
		}

		// check if CDN is enabled
		$cdn_enabled = get_site_option( 'oc_cdn_enabled' );
		if ( 'true' !== $cdn_enabled ) {
			return;
		}
		// check if rules version is saved. If saved, do we need to updated them?
		// removed to match the site URL

		$origin = ! empty( site_url() ) ? site_url() : '*';

		$file  = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . '.htaccess';
		$rules = self::ONECOM_HEADER_BEGIN_TEXT
		         . PHP_EOL
		         . '<IfModule mod_headers.c>
    <FilesMatch "\.(ttf|ttc|otf|eot|woff|woff2|css|js|png|jpg|jpeg|svg|pdf)$">
        Header set Access-Control-Allow-Origin ' . $origin . '
    </FilesMatch>
</IfModule>' . PHP_EOL . '# One.com response headers END';

		if ( file_exists( $file ) ) {

			$contents        = @file_get_contents( $file );
			$file_rules      = $this->get_file_rules_in_array( $file );
			$duplicate_rules = $this->check_duplicate_entries( $file_rules );
			if ( $duplicate_rules && ! ( $this->check_recently_modified_file( $file ) ) ) {

				$file_write_result = file_put_contents( $file, $rules );
				if ( false === $file_write_result ) {
					// Handle the error appropriately
					error_log( "Failed to write to file: $file" );

				}

				return;
			}

			$file_string = '';

			foreach ( $file_rules as $line ) {
				if ( strpos( $line, 'Header set Access-Control-Allow-Origin' ) !== false ) {
					$parts       = explode( ' ', $line );
					$file_string = end( $parts );
					break; // Stop searching after finding the header.
				}
			}
			if ( is_multisite() ) {
				$site_url = rtrim( network_site_url(), '/' );
			} else {
				$site_url = site_url();
			}
			// if file exists but rules not found, add them
			if ( strpos( $contents, self::ONECOM_HEADER_BEGIN_TEXT ) === false ) {
				$file_write_result = file_put_contents( $file, PHP_EOL . $rules, FILE_APPEND );
				if ( false === $file_write_result ) {
					// Handle the error appropriately
					error_log( "Failed to write to file: $file" );

				}
			} elseif ( rtrim( $file_string, '/' ) !== $site_url ) {
				//if file exists, rules are present but existing rules need to be updated due to mismatch of siteurl
				//replace content between our BEGIN and END markers
				$content_array = preg_split( '/\r\n|\r|\n/', $contents );
				$start         = array_search( self::ONECOM_HEADER_BEGIN_TEXT, $content_array, true );
				$end           = array_search( '# One.com response headers END', $content_array, true );
				$length        = ( $end - $start ) + 1;
				array_splice( $content_array, $start, $length, preg_split( '/\r\n|\r|\n/', $rules ) );
				$file_write_result = file_put_contents( $file, implode( PHP_EOL, $content_array ) );
				if ( false === $file_write_result ) {
					// Handle the error appropriately
					error_log( "Failed to write to file: $file" );

				}
				do_action( 'onecom_purge_cdn' );
			}
		} else {
			$file_write_result = file_put_contents( $file, $rules );
			if ( false === $file_write_result ) {
				// Handle the error appropriately
				error_log( "Failed to write to file: $file" );

			}
		}
		//finally, if file was changed, update the self::OCRULESVERSION as oc_rules_version in options for future reference
		update_site_option( 'oc_rules_version', self::OCRULESVERSION, 'no' );
	}

	/**
	 * @param $file
	 * returns the rules present in the file in form of an array after sanitizing them
	 * @return array
	 */
	public function get_file_rules_in_array( $file ): array {
		$arr = file( $file );
		if ( is_array( $arr ) ) {
			$arr = array_map( 'strip_tags', $arr );
			$arr = array_map( 'trim', $arr );
		} else {
			$arr = array();
		}

		return $arr;
	}

	/**
	 * @param $arr
	 * checks for the broken and duplicate rules present in the htaccess file
	 * @return bool
	 */
	public function check_duplicate_entries( $arr ): bool {

		// check for duplicate values in htaccess file
		$check_values = array_count_values( $arr );
		if (
			( array_key_exists( '# One.com response headers BEGIN', $check_values ) && ( $check_values['# One.com response headers BEGIN'] > 1 ) )
			||
			( array_key_exists( '# One.com response headers END', $check_values ) && ( $check_values['# One.com response headers END'] > 1 ) )
		) {
			//                if duplicate entries found then will further check for the file edited or not
			//                for broken rules compare # One.com response headers BEGIN and # One.com response headers END count
			if ( $check_values['# One.com response headers BEGIN'] !== $check_values['# One.com response headers END'] ) {

				// if rules are broken then override the file since this can cause 500 errors
				return true;
			}

			$arr = array_filter( array_unique( array_values( $arr ) ) ); // get the unique values from the file array

			if ( count( $arr ) <= 3 ) { // file is having only onecom rules and hence safe to override
				return true;
			}
		}
		return false;
	}

	/**
	 * @param $file
	 * check if file edited recently
	 * @return bool
	 */
	public function check_recently_modified_file( $file ): bool {
		if ( filemtime( $file ) > strtotime( '-60 minutes' ) ) {
			return true;
		}
		return false;
	}

	public function purge_theme_cache() {
		wp_remote_request( $this->blog_url, array( 'method' => 'PURGE' ) );
	}

	public function oc_purge_cdn_cache() {

		$domain = $_SERVER['ONECOM_DOMAIN_NAME'] ?? false;
		if ( ! $domain ) {
			error_log(
				json_encode(
					array(
						'data'    => null,
						'error'   => 'Empty domain',
						'success' => false,
					)
				)
			);

			return false;
		}
		global $wp_version;
		$args = array(
			'method'      => 'POST',
			'timeout'     => 5,
			'httpversion' => '1.0',
			'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
			'compress'    => false,
			'decompress'  => true,
			'sslverify'   => true,
			'stream'      => false,
			// headers are getting sent from oc_add_http_headers(validator)
		);

		// arrangement done for the wp-cli command call
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$totp = oc_generate_totp();
			remove_filter( 'http_request_args', 'oc_add_http_headers', 10 );
			$args['headers'] = array(
				'X-Onecom-Client-Domain' => $domain,
				'X-TOTP'                 => $totp,
			);

		}
		$response = wp_remote_post( self::WP_PURGE_CDN, $args );
		if ( is_wp_error( $response ) ) {
			if ( isset( $response->errors['http_request_failed'] ) ) {
				$error_message = __( 'Connection timed out', 'vcaching' );
			} else {
				$error_message = $response->get_error_message();
			}
			error_log( print_r( $error_message, true ) );

			return false;
		} else {
			if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
				$error_message = '(' . wp_remote_retrieve_response_code( $response ) . ') ' . wp_remote_retrieve_response_message( $response );

				error_log( print_r( $error_message, true ) );
				$additonal_info = array(
					'purge_status' => 'error',
					'message'      => $error_message,
				);
				( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_performance_cache( 'purge', 'setting', 'purge_cdn', 'performance_cache', $additonal_info ) : '' );
				return false;

			} else {
				$body = wp_remote_retrieve_body( $response );
				$body = json_decode( $body );

				if ( ! empty( $body ) && $body->success ) {
					error_log( print_r( 'CDN purged successfully (' . $body->data . ') ', true ) );
					$additonal_info = array(
						'purge_status' => 'success',
						'message'      => $body->data ?? '',
					);
					( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_performance_cache( 'purge', 'setting', 'purge_cdn', 'performance_cache', $additonal_info ) : '' );
					return true;

				} elseif ( ! empty( $body ) && ! $body->success ) {
					error_log( print_r( json_encode( $body ), true ) );
					$additonal_info = array(
						'purge_status' => 'error',
						'message'      => $body->data ?? '',
					);
					( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_performance_cache( 'purge', 'setting', 'purge_cdn', 'performance_cache', $additonal_info ) : '' );
					return false;

				} else {
					error_log( print_r( 'Some unexpected error occured', true ) );
					( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_performance_cache( 'purge', 'setting', 'purge_cdn', 'performance_cache', array( 'Unexpected error occured' ) ) : '' );
					return false;
				}
			}
		}
	}

	/**
	 * Function clusterAdjustments()
	 * Modify CDN url for cluster model domain
	 * @return void
	 */
	private function clusterAdjustments() {
		if ( empty( $_SERVER['ONECOM_CLUSTER_ID'] ) ) {
			return;
		}
		$this->is_v3 = true;
		$host        = $_SERVER['HTTP_HOST'];
		$domain      = $_SERVER['ONECOM_DOMAIN_NAME'];
		if ( $host === $domain ) {
			$this->cdn_url = "https://www-static.{$domain}";
		} else {
			$subdomain     = str_replace( '.' . $domain, '', $host );
			$this->cdn_url = "https://{$subdomain}-static.{$domain}";
		}
	}

	function handle_purge_cache_request() {
		// Verify nonce
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'vcaching' ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce.', 'vcaching' ) ] );
			wp_die();
		}


		// Check permalink structure and user capabilities
		if ( get_option( 'permalink_structure' ) === '' && current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Pretty permalinks are not enabled.', 'vcaching' ) ] );
			wp_die();
		}

        $this->setup_ips_to_hosts();

		// Check if Varnish IPs exist
		if ( null === $this->varnish_ip ) {
			wp_send_json_error( [ 'message' => __( 'No Varnish IPs found.', 'vcaching' ) ] );
			wp_die();
		}

		if ( isset( $_POST['purge_cache'] ) && 'cdn' === $_POST['purge_cache'] ) {
			$purge_id     = time();
			$updated_data = array( 'vcache_purge_id' => $purge_id );
			$this->oc_json_update_option( 'onecom_vcache_info', $updated_data );
		}

		try {
			// Call purge_cache function
			$this->purge_cache();

			// Respond with success
			wp_send_json_success( [ 'message' => __( 'Cache cleared successfully!', 'vcaching' ) ] );
		} catch ( Exception $e ) {
			// Catch any errors and send a failure response
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}

		wp_die();
	}

	// Handle CDN settings and state form submission
	public function oc_handle_cdn_settings_cb() {
		if ( ! isset( $_POST['oc_csrf'] ) && ! wp_verify_nonce( 'one_vcache_nonce' ) ) {
			return false;
		}
		$response = [];

		if (isset($_POST['cdn_state'])) {
			$response[] = $this->handle_cdn_state($_POST['cdn_state']);
		}

		if (isset($_POST['dev_mode'])) {
			$response[] = $this->handle_dev_mode($_POST['dev_mode'], $_POST['dev_duration']);
		}

		if (isset($_POST['exclude_cdn_mode'])) {
			$response[] = $this->handle_exclude_cdn_mode($_POST['exclude_cdn_mode']);
		}

		if (isset($_POST['exclude_cdn_data'])) {
			$response[] = $this->handle_exclude_cdn_data($_POST['exclude_cdn_data']);
		}

		$this->send_response($response);
	}

	/**
	 * Handles CDN state change.
	 */
	private function handle_cdn_state($cdn_state) {
		$state = intval($cdn_state) === 0 ? 'false' : 'true';

		if ($state === 'true') {
			$event_action = 'enable';
			$res = $this->oc_check_pc_activation($state);
			if ('success' !== $res['status']) {
				return [
					'status'  => 'error',
					'message' => 'verification failed',
				];
			}
		} else {
			$event_action = 'disable';
		}

		$current_state = get_site_option('oc_cdn_enabled');

		if ($current_state === $state) {
			return [
				'status'  => 'success',
				'message' => __('No changes were made, settings are already up to date.'),
			];
		}

		if (!update_site_option('oc_cdn_enabled', $state, 'no')) {
			return [
				'status'  => 'error',
				'message' => __('Failed to update CDN settings.'),
			];
		}

		if (class_exists('OCPushStats')) {
			\OCPushStats::push_stats_performance_cache($event_action, 'setting', 'cdn', 'performance_cache');
		}

		return [
			'status'  => 'success',
			'message' => __('CDN settings updated successfully!'),
		];
	}

	/**
	 * Handles Development Mode settings.
	 */
	private function handle_dev_mode($dev_mode, $dev_duration) {
		$state = intval($dev_mode) === 0 ? 'false' : 'true';

		// Ensure a valid positive integer for duration, defaulting to 48 hours if invalid
		$dev_mode_duration = intval(trim($dev_duration));
		if ($dev_mode_duration <= 0) {
			$dev_mode_duration = 48;
		}

		// Convert hours to expiration time
		$dev_expire_time = strtotime("+{$dev_mode_duration} hours");

		// Get current state from the database
		$current_options = $this->oc_json_get_option('onecom_vcache_info');
		$current_state = $current_options['oc_dev_mode_enabled'] ?? 'false';
		$current_duration = $current_options['dev_mode_duration'] ?? 48;

		// If the state and duration haven't changed, return success immediately
		if ($current_state === $state && $current_duration === $dev_mode_duration) {
			return [
				'status'  => 'success',
				'message' => __('No changes were made, settings are already up to date.'),
			];
		}

		if ($state === 'true') {
			$event_action = 'enable';
			$res = $this->oc_check_pc_activation($state, 'mwp');
			if ('success' !== $res['status']) {
				wp_send_json( $res );
				return;
			}
		} else {
			$event_action = 'disable';
		}

		// Update the option
		$update_result = $this->oc_json_update_option('onecom_vcache_info', [
			'oc_dev_mode_enabled' => $state,
			'dev_mode_duration'   => $dev_mode_duration,
			'dev_expire_time'     => $dev_expire_time,
		]);

		if (!$update_result) {
			return [
				'status'  => 'error',
				'message' => __('Failed to update settings.'),
			];
		}

		\OCPushStats::push_stats_performance_cache($event_action, 'setting', 'dev_mode', 'performance_cache');

		return [
			'status'  => 'success',
			'message' => __('Settings updated successfully!'),
		];
	}

	/**
	 * Handles Exclude CDN Mode.
	 */
	private function handle_exclude_cdn_mode($exclude_cdn_mode) {
		$state = intval($exclude_cdn_mode) === 0 ? 'false' : 'true';

		if ( 'true' === $state ) {
			$event_action = 'enable';
			$res          = $this->oc_check_pc_activation( $state, 'mwp' );
			if ( 'success' !== $res['status'] ) {
				wp_send_json( $res );
				return;
			}
		} else {
			$event_action = 'disable';
		}

		$current_state = $this->oc_json_get_option('onecom_vcache_info', 'oc_exclude_cdn_enabled');

		if ($current_state === $state) {
			return [
				'status'  => 'success',
				'message' => __('No changes were made, settings are already up to date.'),
			];
		}

		if (!$this->oc_json_update_option('onecom_vcache_info', ['oc_exclude_cdn_enabled' => $state])) {
			return [
				'status'  => 'error',
				'message' => __('Failed to update Exclude CDN settings.'),
			];
		}

		\OCPushStats::push_stats_performance_cache($event_action, 'setting', 'exclude_cdn', 'performance_cache');

		return [
			'status'  => 'success',
			'message' => __('Exclude CDN settings updated successfully!'),
		];
	}

	/**
	 * Handles Exclude CDN Data.
	 */
	private function handle_exclude_cdn_data($exclude_cdn_data) {
		$exclude_cdn_data = trim($exclude_cdn_data);

		$current_data = $this->oc_json_get_option('onecom_vcache_info', 'oc_exclude_cdn_data');

		if ($current_data === $exclude_cdn_data) {
			return [
				'status'  => 'success',
				'message' => __('No changes were made, settings are already up to date.'),
			];
		}

		if (!$this->oc_json_update_option('onecom_vcache_info', ['oc_exclude_cdn_data' => $exclude_cdn_data])) {
			return [
				'status'  => 'error',
				'message' => __('Failed to update Exclude CDN data.'),
			];
		}

		$this->purge_cache();
		\OCPushStats::push_stats_performance_cache('update', 'setting', 'exclude_cdn', 'performance_cache');

		return [
			'status'  => 'success',
			'message' => __('Exclude CDN data updated successfully!'),
		];
	}

	/**
	 * Sends JSON response based on update status.
	 */
	private function send_response($responses) {
		$messages = array_column($responses, 'message'); // Extract messages from responses
		$errors = array_filter($responses, fn($response) => $response['status'] === 'error'); // Filter errors

		// If there are errors, return them as they are
		if (!empty($errors)) {
			wp_send_json([
				'status'  => 'error',
				'message' => implode(' ', array_unique(array_column($errors, 'message'))),
			]);
		}

		// Remove duplicate messages
		$unique_messages = array_unique($messages);

		// If all messages are "No changes were made," send only that message
		if (count($unique_messages) === 1 && $unique_messages[0] === __('No changes were made, settings are already up to date.', 'vaching')) {
			wp_send_json([
				'status'  => 'success',
				'message' => $unique_messages[0],
			]);
		}

		// If there was at least one successful update, return "Settings saved successfully."
		wp_send_json([
			'status'  => 'success',
			'message' => __('Settings saved successfully.', 'vaching'),
		]);
	}

}
$oc_vcaching = new OCVCaching();