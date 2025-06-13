<?php
/**
 * Class OnecomPremiumChecks
 * Register callbacks form premium checks.
 * @todo Split OnecomHealthMonitorAjax in multiple subclasses. One of the subclass will be this file.
 */
declare( strict_types=1 );

class OnecomChecks extends OnecomHealthMonitor {
	/**
	 * Add AJAX callbacks for checks
	 */
	public function init(): void {
		add_action( 'wp_ajax_ocsh_check_php_updates', array( $this, 'php_updates' ) );
		add_action( 'wp_ajax_ocsh_check_plugin_updates', array( $this, 'plugin_updates' ) );
		add_action( 'wp_ajax_ocsh_check_theme_updates', array( $this, 'theme_updates' ) );
		add_action( 'wp_ajax_ocsh_check_wp_updates', array( $this, 'wp_updates' ) );
		add_action( 'wp_ajax_ocsh_check_wp_connection', array( $this, 'wp_connection' ) );
		add_action( 'wp_ajax_ocsh_check_core_updates', array( $this, 'core_updates' ) );
		add_action( 'wp_ajax_ocsh_check_ssl', array( $this, 'check_ssl' ) );
		add_action( 'wp_ajax_ocsh_check_file_execution', array( $this, 'file_execution' ) );
		add_action( 'wp_ajax_ocsh_check_file_permissions', array( $this, 'file_permissions' ) );
		add_action( 'wp_ajax_ocsh_check_DB', array( $this, 'database' ) );
		add_action( 'wp_ajax_ocsh_check_file_edit', array( $this, 'file_edit' ) );
		add_action( 'wp_ajax_ocsh_check_usernames', array( $this, 'usernames' ) );
		add_action( 'wp_ajax_ocsh_check_dis_plugin', array( $this, 'dis_plugin' ) );
		add_action( 'wp_ajax_ocsh_save_result', array( $this, 'save_result_cb' ) );
		add_action( 'wp_ajax_ocsh_check_uploads_index', array( $this, 'uploads_index_cb' ) );
		add_action( 'wp_ajax_ocsh_check_options_table_count', array( $this, 'options_table_count' ) );
		add_action( 'wp_ajax_ocsh_check_staging_time', array( $this, 'staging_time' ) );
		add_action( 'wp_ajax_ocsh_check_backup_zips', array( $this, 'backup_zips' ) );
		add_action( 'wp_ajax_ocsh_check_performance_cache', array( $this, 'performance_cache' ) );
		add_action( 'wp_ajax_ocsh_check_updated_long_ago', array( $this, 'updated_long_ago' ) );
		add_action( 'wp_ajax_ocsh_check_pingbacks', array( $this, 'pingbacks' ) );
		add_action( 'wp_ajax_ocsh_check_logout_duration', array( $this, 'logout_duration' ) );
		add_action( 'wp_ajax_ocsh_check_xmlrpc', array( $this, 'xmlrpc' ) );
		add_action( 'wp_ajax_ocsh_check_spam_protection', array( $this, 'spam_protection' ) );
		add_action( 'wp_ajax_ocsh_check_login_attempts', array( $this, 'login_attempts' ) );
		add_action( 'wp_ajax_ocsh_check_login_recaptcha', array( $this, 'login_recaptcha' ) );
		add_action( 'wp_ajax_ocsh_check_asset_minification', array( $this, 'asset_minification' ) );
		add_action( 'wp_ajax_ocsh_check_error_reporting', array( $this, 'error_reporting' ) );
		add_action( 'wp_ajax_ocsh_check_user_enumeration', array( $this, 'user_enumeration' ) );
		add_action( 'wp_ajax_ocsh_check_optimize_uploaded_images', array( $this, 'optimize_uploaded_images' ) );
		add_action( 'wp_ajax_ocsh_check_enable_cdn', array( $this, 'enable_cdn' ) );
		if ( ! empty( $this->active_plugins ) && in_array( 'woocommerce/woocommerce.php', $this->active_plugins ) ) {
			add_action( 'wp_ajax_ocsh_check_woocommerce_sessions', array( $this, 'woocommerce_session' ) );
		}
	}

	public function php_updates() {
		$php_update = new OnecomCheckUpdates();
		$result     = $php_update->php_updates();
		self::send_json( $result, 'php_updates' );
	}

	public function plugin_updates() {
		$php_update = new OnecomCheckUpdates();
		$result     = $php_update->plugin_updates();
		self::send_json( $result, 'plugin_updates' );
	}

	public function theme_updates() {
		$php_update = new OnecomCheckUpdates();
		$result     = $php_update->theme_updates();
		self::send_json( $result, 'theme_updates' );
	}

	public function wp_updates() {
		$updates = new OnecomCheckUpdates();
		$result  = $updates->check_wp_updates();
		self::send_json( $result, 'wp_updates' );
	}

	public function wp_connection() {
		$updates = new OnecomCheckUpdates();
		$result  = $updates->check_wp_connection();
		self::send_json( $result, 'wp_connection' );
	}

	public function core_updates() {
		$updates = new OnecomCheckUpdates();
		$result  = $updates->check_auto_updates();
		self::send_json( $result, 'core_updates' );
	}

	public function check_ssl() {
		$ssl    = new OnecomCheckSsl();
		$result = $ssl->oc_sh_check_ssl();
		self::send_json( $result, 'ssl' );
	}

	public function file_execution() {
		$file_object = new OnecomCheckFiles();
		$result      = $file_object->check_execution();
		self::send_json( $result, 'file_execution' );
	}

	public function file_permissions() {
		$file_object = new OnecomCheckFiles();
		$result      = $file_object->check_permission();
		self::send_json( $result, 'file_permissions' );
	}

	public function database() {
		$db     = new OnecomCheckDB();
		$result = $db->check_db_security();
		self::send_json( $result, 'DB' );
	}

	public function file_edit() {
		$file   = new OnecomCheckFiles();
		$result = $file->check_file_editing();
		self::send_json( $result, 'file_edit' );
	}

	public function usernames() {
		$usernames = new OnecomCheckUsername();
		$result    = $usernames->check_usernames();
		self::send_json( $result, 'usernames' );
	}

	public function dis_plugin() {
		$plugins = new OnecomCheckPlugins();
		$result  = $plugins->check_discouraged_plugins();
		self::send_json( $result, 'dis_plugin' );
	}

	public function save_result_cb(): float {
		return floatval( $_POST['osch_Result'] );
	}

	public function uploads_index_cb() {
		$fs             = new OnecomCheckFiles();
		$result         = $fs->check_index();
		$result['html'] = $this->get_html( 'uploads_index', $result );
		parent::save_result( 'uploads_index', $result['status'] );
		self::send_json( $result, 'uploads_index' );
	}

	public function woocommerce_session() {
		$db             = new OnecomCheckDB();
		$result         = $db->check_woocommerce_session();
		$result['fix']  = true;
		$result['html'] = $this->get_html( 'woocommerce_sessions', $result );
		parent::save_result( 'woocommerce_sessions', $result['status'] );
		self::send_json( $result, 'woocommerce_sessions' );
	}

	public function options_table_count() {
		$db             = new OnecomCheckDB();
		$result         = $db->check_options_table();
		$result['html'] = $this->get_html( 'options_table_count', $result );
		parent::save_result( 'options_table_count', $result['status'] );
		self::send_json( $result, 'options_table_count' );
	}

	public function staging_time() {
		$stg                = new OnecomCheckStaging();
		$result             = $stg->check_staging_time();
		$result['fix']      = true;
		$result['fix_text'] = 'Review staging';
		$result['fix_url']  = admin_url('admin.php?page=onecom-wp-staging');
		$result['html']     = $this->get_html('check_staging_time', $result);
		parent::save_result('check_staging_time', $result['status']);
		self::send_json($result, 'check_staging_time');
	}

	public function backup_zips() {
		$fs                    = new OnecomCheckFiles();
		$result                = $fs->check_backup_zips();
		$result['delete-link'] = true;
		$result['html']        = $this->get_html( 'check_backup_zip', $result );
		parent::save_result( 'check_backup_zip', $result['status'] );
		self::send_json( $result, 'check_backup_zip' );
	}

	public function performance_cache() {
		$plugins            = new OnecomCheckPlugins();
		$result             = $plugins->check_performance_cache();
		$result['fix']      = true;
		$result['undo']     = true;
		$result['fix_text'] = __( 'Enable features', 'onecom-wp' );
		if ( isset( $result['activate_plugin'] ) && $result['activate_plugin'] ) {
			$result['fix_text'] = __( 'Activate Performance cache', 'onecom-wp' );
			$result['fix_url']  = admin_url( 'plugins.php?plugin_status=inactive' );
		}
		$result['html'] = $this->get_html( 'check_performance_cache', $result );
		parent::save_result( 'check_performance_cache', $result['status'] );
		self::send_json( $result, 'performance_cache' );
	}

	public function updated_long_ago() {
		$plugins        = new OnecomCheckPlugins();
		$result         = $plugins->check_plugins_last_update();
		$result['html'] = $this->get_html( 'check_updated_long_ago', $result );
		parent::save_result( 'check_updated_long_ago', $result['status'] );
		self::send_json( $result, 'check_updated_long_ago' );
	}

	public function pingbacks() {
		$pingback           = new OnecomPingback();
		$result             = $pingback->check_pingbacks();
		$result['fix']      = true;
		$result['undo']     = true;
		$result['fix_text'] = __( 'Disable pingback', 'onecom-wp' );
		$result['html']     = $this->get_html( 'check_pingbacks', $result );
		parent::save_result( 'check_pingbacks', $result['status'] );
		self::send_json( $result, 'check_pingbacks' );
	}

	public function enable_cdn() {
		$plugins        = new OnecomCheckPlugins();
		$result         = $plugins->check_cdn();
		$result['fix']  = true;
		$result['undo'] = true;
		if ( isset( $result['activate_plugin'] ) && $result['activate_plugin'] ) {
			$result['fix_text'] = __( 'Activate Performance cache', 'onecom-wp' );
			$result['fix_url']  = admin_url( 'plugins.php?plugin_status=inactive' );
		}
		$result['html'] = $this->get_html( 'enable_cdn', $result );
		parent::save_result( 'enable_cdn', $result['status'] );
		self::send_json( $result, 'enable_cdn' );
	}

	public function user_enumeration() {
		$usr            = new OnecomCheckUsername();
		$result         = $usr->check_user_enumeration();
		$result['fix']  = true;
		$result['html'] = $this->get_html( 'user_enumeration', $result );
		self::send_json( $result, 'user_enumeration' );
	}

	public function optimize_uploaded_images() {
		$plugin         = new OnecomCheckPlugins();
		$result         = $plugin->is_imagify_setup();
		$result['html'] = $this->get_html( 'optimize_uploaded_images', $result );
		self::send_json( $result, 'optimize_uploaded_images' );
	}

	public function asset_minification() {
		$minification   = new OnecomCheckAssetMinification();
		$result         = $minification->check_minification();
		$result['html'] = $this->get_html( 'asset_minification', $result );
		self::send_json( $result, 'asset_minification' );
	}

	public function error_reporting() {
		$err            = new OnecomDebugMode();
		$result         = $err->check_error_reporting();
		$result['html'] = $this->get_html( 'error_reporting', $result );
		self::send_json( $result, 'error_reporting' );
	}

	public function login_recaptcha() {
		$login                  = new OnecomCheckLogin();
		$result                 = $login->login_recaptcha();
		$result['fix']          = true;
		$result['undo']         = true;
		$result['fix_text']     = __( 'Enable recaptcha', 'onecom-wp' );
		$result['input_fields'] = array(
			array(
				'name'  => 'oc_hm_site_key',
				'type'  => 'text',
				'label' => __( 'Site key', 'onecom-wp' ),
			),
			array(
				'name'  => 'oc_hm_site_secret',
				'type'  => 'text',
				'label' => __( 'Site secret', 'onecom-wp' ),
			),
		);
		$result['info_text']    = sprintf( __( 'You can obtain these values <a href="%s">here</a>', 'onecom-wp' ), 'https://www.google.com/recaptcha/admin/create' );
		$result['html']         = $this->get_html( 'login_recaptcha', $result );
		self::send_json( $result, 'login_recaptcha' );
	}

	public function spam_protection() {
		$spam   = new OnecomCheckSpam();
		$result = $spam->check_spam_protection();
		if ( $result[ $this->status_key ] === $this->flag_open ) {
			$theme_result  = $spam->is_onecom_theme();
			$result['fix'] = true;
			if ( $theme_result['onecom_theme'] && $theme_result['url'] != '' ) {
				$result['fix_url']  = $theme_result['url'];
				$result['fix_text'] = __( 'Enable spam protection', 'onecom-wp' );
			} else {
				$result['fix_url']  = admin_url( 'admin.php?page=onecom-wp-recommended-plugins' );
				$result['fix_text'] = __( 'View recommended plugins', 'onecom-wp' );
			}
		}

		$result['html'] = $this->get_html( 'spam_protection', $result );
		self::send_json( $result, 'spam_protection' );
	}

	public function login_attempts() {
		$login              = new OnecomCheckLogin();
		$result             = $login->check_failed_login();
		$result['fix']      = true;
		$result['fix_text'] = __( 'Reset failed login data', 'onecom-wp' );
		$result['html']     = $this->get_html( 'login_attempts', $result );
		self::send_json( $result, 'login_attempts' );
	}

	public function logout_duration() {
		$pc                 = new OnecomCheckLogin();
		$result             = $pc->check_logout_time();
		$result['fix']      = true;
		$result['fix_text'] = sprintf( __( 'Change logout time to %s hours', 'onecom-wp' ), '4' );
		$result['undo']     = true;
		$result['html']     = $this->get_html( 'logout_duration', $result );
		self::send_json( $result, 'logout_duration' );
	}

	public function xmlrpc() {
		$xmlrpc             = new OnecomXmlRpc();
		$result             = $xmlrpc->check_xmlrpc();
		$result['fix']      = true;
		$result['fix_text'] = __( 'Disable XML RPC', 'onecom-wp' );
		$result['undo']     = true;
		$result['html']     = $this->get_html( 'xmlrpc', $result );
		self::send_json( $result, 'xmlrpc' );
	}
}