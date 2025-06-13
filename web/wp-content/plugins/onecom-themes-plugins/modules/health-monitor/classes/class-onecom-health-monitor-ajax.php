<?php
declare(strict_types=1);

/**
 * Class OnecomAjax
 * Deals with ajax requests
 */
class OnecomHealthMonitorAjax extends OnecomHealthMonitor {

	private $file_object;

	public function __construct() {
		parent::__construct();
		if ( ! function_exists( 'oc_sh_check_php_updates' ) ) {
			require_once MODULE_PATH . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'functions.php';
		}
		$this->file_object = new OnecomCheckFiles();
	}

	public function init() {
		$fixes = new OnecomFixes();
		add_action( 'wp_ajax_ocsh_mark_resolved', array( $this, 'ocsh_mark_resolved' ) );
		add_action( 'wp_ajax_onecom_unignore', array( $this, 'unignore' ) );
		add_action( 'wp_ajax_ocsh_reset_checks', array( $this, 'reset_checks' ) );
		$this->add_check_callbacks();
		$fixes->init();
		$this->add_undo_callbacks();
	}

	/**
	 * Add AJAX callbacks for checks
	 */
	public function add_check_callbacks(): void {
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
		add_action( 'wp_ajax_ocsh_check_woocommerce_sessions', array( $this, 'woocommerce_session' ) );
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
		add_action( 'wp_ajax_ocsh_check_debug_enabled', array( $this, 'debug_enabled' ) );
		add_action( 'wp_ajax_ocsh_check_debug_log_size', array( $this, 'debug_log_size' ) );
		add_action( 'wp_ajax_ocsh_check_user_enumeration', array( $this, 'user_enumeration' ) );
		add_action( 'wp_ajax_ocsh_check_optimize_uploaded_images', array( $this, 'optimize_uploaded_images' ) );
		add_action( 'wp_ajax_ocsh_check_enable_cdn', array( $this, 'enable_cdn' ) );
		add_action( 'wp_ajax_ocsh_check_inactive_plugins', array( $this, 'inactive_plugins' ) );
		add_action( 'wp_ajax_ocsh_check_inactive_themes', array( $this, 'inactive_themes' ) );
	}

	public function add_undo_callbacks(): void {
		add_action( 'wp_ajax_ocsh_undo_check_pingbacks', array( $this, 'undo_check_pingbacks' ) );
		add_action( 'wp_ajax_ocsh_undo_check_performance_cache', array( $this, 'undo_check_performance_cache' ) );
		add_action( 'wp_ajax_ocsh_undo_enable_cdn', array( $this, 'undo_enable_cdn' ) );
		add_action( 'wp_ajax_ocsh_undo_logout_duration', array( $this, 'undo_check_logout_duration' ) );
		add_action( 'wp_ajax_ocsh_undo_xmlrpc', array( $this, 'undo_fix_xmlrpc' ) );
		add_action( 'wp_ajax_ocsh_undo_login_recaptcha', array( $this, 'undo_login_recaptcha' ) );
		add_action( 'wp_ajax_ocsh_undo_login_attempts', array( $this, 'undo_login_attempts' ) );
	}

	/**
	 * Response format based on type of request
	 */
	public function send_json( array $result, string $check = '' ) {
		// add "html" key if not present
		if ( ! isset( $result['html'] ) ) {
			$result['html'] = $this->get_html( $check, $result );
		}
		$prev_result = get_site_transient( 'ocsh_site_previous_scan' );

		if ( ! $prev_result ) {
			$prev_result = array();
		}

		// Added to remove the ALP audit from HM
		if ( isset( $prev_result['login_protection'] ) ) {
			unset( $prev_result['login_protection'] );
		}
		$prev_result[ $check ] = $result;
		unset( $prev_result[ $check ]['html'] );
		if ( 0 !== $result[ $this->status_key ] && $this->is_ignored( $check ) ) {
			$result[ $this->status_key ] = 3;
		} elseif ( 0 === $result[ $this->status_key ] && $this->is_ignored( $check ) ) {
			$this->remove_ignored_check( $check );
		}

		set_site_transient( 'ocsh_site_previous_scan', $prev_result );
		if ( ! ( defined( 'REST_REQUEST' ) || defined( 'DOING_CRON' ) ) ) {
			wp_send_json( $result );
		}

			return $result;
	}

	public function php_updates() {
		$php_update = new OnecomCheckUpdates();
		$result     = $php_update->php_updates();
		parent::save_result( 'php_updates', $result['status'] );
		self::send_json( $result, 'php_updates' );
	}

	public function plugin_updates() {
		$php_update = new OnecomCheckUpdates();
		$result     = $php_update->plugin_updates();
		parent::save_result( 'plugin_updates', $result['status'] );
		self::send_json( $result, 'plugin_updates' );
	}

	public function theme_updates() {
		$php_update = new OnecomCheckUpdates();
		$result     = $php_update->theme_updates();
		parent::save_result( 'theme_updates', $result['status'] );
		self::send_json( $result, 'theme_updates' );
	}

	public function wp_updates() {
		$updates = new OnecomCheckUpdates();
		$result  = $updates->check_wp_updates();
		parent::save_result( 'wp_updates', $result['status'] );
		self::send_json( $result, 'wp_updates' );
	}

	public function wp_connection() {
		$updates = new OnecomCheckUpdates();
		$result  = $updates->check_wp_connection();
		parent::save_result( 'wp_connection', $result['status'] );
		self::send_json( $result, 'wp_connection' );
	}

	public function core_updates() {
		$updates = new OnecomCheckUpdates();
		$result  = $updates->check_auto_updates();
		parent::save_result( 'core_updates', $result['status'] );
		self::send_json( $result, 'core_updates' );
	}

	public function check_ssl() {
		$ssl    = new OnecomCheckSsl();
		$result = $ssl->oc_sh_check_ssl();
		parent::save_result( 'ssl', $result['status'] );
		self::send_json( $result, 'ssl' );
	}

	public function file_execution() {
		$result           = $this->file_object->check_execution();
		$result['fix']    = true;
		$result['revert'] = true;
		parent::save_result( 'file_execution', $result['status'] );
		self::send_json( $result, 'file_execution' );
	}

	public function file_permissions() {
		$result = $this->file_object->check_permission();
		parent::save_result( 'file_permissions', $result['status'] );
		self::send_json( $result, 'file_permissions' );
	}

	public function database() {
		$db     = new OnecomCheckDB();
		$result = $db->check_db_security();
		parent::save_result( 'DB', $result['status'] );
		self::send_json( $result, 'DB' );
	}

	public function file_edit() {
		$file   = new OnecomCheckFiles();
		$result = $file->check_file_editing();
		parent::save_result( 'file_edit', $result['status'] );
		self::send_json( $result, 'file_edit' );
	}

	public function usernames() {
		$usernames = new OnecomCheckUsername();
		$result    = $usernames->check_usernames();
		parent::save_result( 'usernames', $result['status'] );
		self::send_json( $result, 'usernames' );
	}

	public function dis_plugin() {
		$plugins = new OnecomCheckPlugins();
		$result  = $plugins->check_discouraged_plugins();
		parent::save_result( 'dis_plugin', $result['status'] );
		self::send_json( $result, 'dis_plugin' );
	}

	public function save_result_cb(): float {
		// return floatval($_POST['osch_Result']);

		$scan_result    = get_site_transient( 'ocsh_site_scan_result' );
		$last_scan_time = $scan_result['time'] ?? __( 'No scan available', 'onecom-wp' );

		/* Format the last scan date time as per WP date-time settings */
		if ( is_numeric( $last_scan_time ) && function_exists( 'wp_date' ) ) {
			$frmt                     = 'l ' . get_site_option( 'date_format' ) . ' ' . get_site_option( 'time_format' );
			$tz                       = get_site_option( 'timezone_string' ) && ! empty( get_site_option( 'timezone_string' ) ) ? get_site_option( 'timezone_string' ) : 'UTC';
			$last_scan_time_localised = wp_date( $frmt, $last_scan_time, new DateTimeZone( $tz ) );
		} else {
			$last_scan_time_localised = __( 'No scan available', 'onecom-wp' );

		}
		return wp_send_json( array( 'last_scan_time' => $last_scan_time_localised ) );
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
		$result['fix_url']  = admin_url( 'admin.php?page=onecom-wp-staging' );
		$result['html']     = $this->get_html( 'check_staging_time', $result );
		parent::save_result( 'check_staging_time', $result['status'] );
		self::send_json( $result, 'check_staging_time' );
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
		$plugins        = new OnecomCheckPlugins();
		$result         = $plugins->check_performance_cache();
		$result['fix']  = true;
		$result['undo'] = true;
		if ( isset( $result['activate_plugin'] ) && $result['activate_plugin'] ) {
			$result['fix_url'] = admin_url( 'plugins.php?plugin_status=inactive' );
		}
		$result['html'] = $this->get_html( 'check_performance_cache', $result );
		parent::save_result( 'performance_cache', $result['status'] );
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
		$result['fix_text'] = 'Disable pingback';
		$result['html']     = $this->get_html( 'check_pingbacks', $result );
		parent::save_result( 'check_pingbacks', $result['status'] );
		self::send_json( $result, 'check_pingbacks' );
	}

	public function inactive_plugins() {
		$plugins           = new OnecomCheckPlugins();
		$result            = $plugins->check_inactive_plugins();
		$result['fix']     = true;
		$result['fix_url'] = admin_url( 'plugins.php?plugin_status=inactive' );
		$result['html']    = $this->get_html( 'inactive_plugins', $result );
		parent::save_result( 'inactive_plugins', $result['status'] );
		self::send_json( $result, 'inactive_plugins' );
	}

	public function inactive_themes() {
		$plugins           = new OnecomCheckPlugins();
		$result            = $plugins->check_inactive_themes();
		$result['fix']     = true;
		$result['fix_url'] = admin_url( 'themes.php' );
		$result['html']    = $this->get_html( 'inactive_themes', $result );
		parent::save_result( 'inactive_themes', $result['status'] );
		self::send_json( $result, 'inactive_themes' );
	}

	/**
	 * Ignore a check from future scans
	 */
	public function ocsh_mark_resolved() {
		$check              = strip_tags( $_POST['check'] );
		$check              = str_replace( 'check_', '', $check );
		$marked_as_resolved = $this->ignored;
		if ( empty( $marked_as_resolved ) ) {
			$marked_as_resolved = array();
		}
		if ( ! in_array( $check, $marked_as_resolved ) ) {
			$marked_as_resolved[] = $check;
		}
		$result = update_option( 'oc_marked_resolved', $marked_as_resolved, 'no' );
		$this->push_stats( 'ignore', $check );
		if ( $result ) {
			wp_send_json( $this->format_result( $this->flag_resolved, __( 'Ignored in future scans', 'onecom-wp' ) ) );
		} else {
			wp_send_json( $this->format_result( $this->flag_open, __( 'Could not ignore from future scans', 'onecom-wp' ) ) );
		}
	}

	/**
	 * Remove a check from ignore list
	 */
	public function unignore(): void {
		$check              = sanitize_text_field( $_POST['check'] );
		$check              = str_replace( 'check_', '', $check );
		$marked_as_resolved = $this->ignored;
		if ( empty( $marked_as_resolved ) ) {
			$marked_as_resolved = array();
		}

		if ( ( $key = array_search( $check, $marked_as_resolved ) ) !== false ) {
			unset( $marked_as_resolved[ $key ] );
		}
		$this->push_stats( 'unignore', $check );
		$result = update_option( 'oc_marked_resolved', $marked_as_resolved, 'no' );
		if ( $result ) {
			wp_send_json( $this->format_result( $this->flag_resolved, __( 'Unignored from future scans', 'onecom-wp' ) ) );
		} else {
			wp_send_json( $this->format_result( $this->flag_open, __( 'Could not remove from ignored list', 'onecom-wp' ) ) );
		}
	}

	/**
	 * Reset the list of ignored checks
	 *
	 * @todo    not used, removed
	 */
	public function reset_checks() {
		$result = delete_option( $this->resolved_option );
		if ( $result ) {
			wp_send_json(
				$this->format_result( $this->flag_resolved, __( 'Success', 'onecom-wp' ) )
			);
		} else {
			wp_send_json(
				$this->format_result( $this->flag_open, __( 'Failed', 'onecom-wp' ) )
			);
		}
	}

	public function undo_check_pingbacks() {
		$pingbacks = new OnecomPingback();
		$result    = $pingbacks->undo();
		$this->update_previous_scan( 'check_pingbacks', 1 );
		$this->push_stats( 'revert', 'pingbacks' );
		wp_send_json( $result );
	}

	public function undo_check_performance_cache() {
		$pc     = new OnecomCheckPlugins();
		$result = $pc->undo_check_performance_cache();
		$this->update_previous_scan( 'performance_cache', 1 );
		$this->push_stats( 'revert', 'performance_cache' );
		wp_send_json( $result );
	}

	public function logout_duration() {
		$pc                 = new OnecomCheckLogin();
		$result             = $pc->check_logout_time();
		$result['fix']      = true;
		$result['undo']     = true;
		$result['fix_text'] = sprintf( __( 'Change logout time to %s hours', 'onecom-wp' ), '4' );
		$result['html']     = $this->get_html( 'logout_duration', $result );
		parent::save_result( 'logout_duration', $result['status'] );
		self::send_json( $result, 'logout_duration' );
	}

	public function undo_check_logout_duration() {
		$logout = new OnecomCheckLogin();
		$this->push_stats( 'revert', 'logout_duration' );
		wp_send_json( $logout->undo_check_logout_time() );
	}

	public function xmlrpc() {
		$xmlrpc         = new OnecomXmlRpc();
		$result         = $xmlrpc->check_xmlrpc();
		$result['fix']  = true;
		$result['undo'] = true;
		$result['html'] = $this->get_html( 'xmlrpc', $result );
		parent::save_result( 'xmlrpc', $result['status'] );
		self::send_json( $result, 'xmlrpc' );
	}

	public function undo_fix_xmlrpc() {
		$xmlrpc = new OnecomXmlRpc();
		$result = $xmlrpc->undo_check_xmlrpc();
		$this->update_previous_scan( 'xmlrpc', 1 );
		$this->push_stats( 'revert', 'xmlrpc' );
		wp_send_json( $result );
	}

	public function spam_protection() {
		$spam   = new OnecomCheckSpam();
		$result = $spam->check_spam_protection();
		if ( $result[ $this->status_key ] === $this->flag_open ) {
			$theme_result  = $spam->is_onecom_theme();
			$result['fix'] = true;
			if ( $theme_result['onecom_theme'] && $theme_result['url'] !== '' ) {
				$result['fix_url']  = $theme_result['url'];
				$result['fix_text'] = __( 'Enable spam protection', 'onecom-wp' );
			}
		}

		$result['html'] = $this->get_html( 'spam_protection', $result );
		parent::save_result( 'spam_protection', $result['status'] );
		self::send_json( $result, 'spam_protection' );
	}

	public function login_attempts( $is_login_check = false ) {
		$login          = new OnecomCheckSpam();
		$result         = $login->check_login_attempts();
		$result['fix']  = true;
		$result['undo'] = true;
		$result['html'] = $this->get_html( 'login_attempts', $result );

		if ( isset( $_POST['action'] ) && ( ( $_POST['action'] === 'ocsh_check_login_attempts' ) || $is_login_check ) ) {
			parent::save_result( 'login_attempts', $result['status'] );
			self::send_json( $result, 'login_attempts' );
		} else {
			parent::save_result( 'spam_protection', $result['status'] );
			self::send_json( $result, 'spam_protection' );
		}
	}

	public function undo_login_attempts() {
		$login  = new OnecomCheckSpam();
		$result = $login->undo_spam_protection();
		$this->update_previous_scan( 'login_attempts', 1 );
		$this->push_stats( 'revert', 'login_attempts' );
		wp_send_json( $result );
	}

	/**
	 * @todo remove this unused function reset_failed_login
	 */
	public function reset_failed_login() {
		$login = new OnecomCheckLogin();
		wp_send_json( $login->reset_failed_login_data() );
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
		parent::save_result( 'login_recaptcha', $result['status'] );
		self::send_json( $result, 'login_recaptcha' );
	}

	public function undo_login_recaptcha() {
		$login = new OnecomCheckLogin();
		$this->push_stats( 'revert', 'login_recaptcha' );
		wp_send_json( $login->undo_login_recaptcha() );
	}

	public function asset_minification() {
		$minification   = new OnecomCheckAssetMinification();
		$result         = $minification->check_minification();
		$result['html'] = $this->get_html( 'asset_minification', $result );
		parent::save_result( 'asset_minification', $result['status'] );
		self::send_json( $result, 'asset_minification' );
	}

	public function error_reporting() {
		$err            = new OnecomDebugMode();
		$result         = $err->check_error_reporting();
		$result['html'] = $this->get_html( 'error_reporting', $result );
		parent::save_result( 'error_reporting', $result['status'] );
		self::send_json( $result, 'error_reporting' );
	}

	public function debug_enabled() {
		$err            = new OnecomDebugMode();
		$result         = $err->check_debug_enabled();
		$result['html'] = $this->get_html( 'debug_enabled', $result );
		parent::save_result( 'debug_enabled', $result['status'] );
		self::send_json( $result, 'debug_enabled' );
	}

	public function debug_log_size() {
		$err            = new OnecomDebugMode();
		$result         = $err->check_debug_log_size();
		$result['fix']  = true;
		$result['html'] = $this->get_html( 'debug_log_size', $result );
		parent::save_result( 'debug_log_size', $result['status'] );
		self::send_json( $result, 'debug_log_size' );
	}

	public function user_enumeration() {
		$usr            = new OnecomCheckUsername();
		$result         = $usr->check_user_enumeration();
		$result['fix']  = true;
		$result['html'] = $this->get_html( 'user_enumeration', $result );
		parent::save_result( 'user_enumeration', $result['status'] );
		self::send_json( $result, 'user_enumeration' );
	}

	public function optimize_uploaded_images() {
		$plugin         = new OnecomCheckPlugins();
		$result         = $plugin->is_imagify_setup();
		$result['fix']  = true;
		$result['html'] = $this->get_html( 'optimize_uploaded_images', $result );
		parent::save_result( 'optimize_uploaded_images', $result['status'] );
		self::send_json( $result, 'optimize_uploaded_images' );
	}

	public function enable_cdn() {
		$plugins        = new OnecomCheckPlugins();
		$result         = $plugins->check_cdn();
		$result['fix']  = true;
		$result['undo'] = true;
		if ( isset( $result['activate_plugin'] ) && $result['activate_plugin'] ) {
			$result['fix_text'] =  'Activate Performance cache';
			$result['fix_url']  = admin_url( 'plugins.php?plugin_status=inactive' );
		}
		$result['html'] = $this->get_html( 'enable_cdn', $result );
		parent::save_result( 'enable_cdn', $result['status'] );
		self::send_json( $result, 'enable_cdn' );
	}

	public function undo_enable_cdn() {
		$pc     = new OnecomCheckPlugins();
		$result = $pc->undo_check_performance_cdn();
		$this->update_previous_scan( 'enable_cdn', 1 );
		$this->push_stats( 'revert', 'enable_cdn' );
		wp_send_json( $result );
	}

	/**
	 * @param $check
	 * function to remove the check from ignored list
	 * @return void
	 */
	public function remove_ignored_check( $check ) {
		$check              = str_replace( array( 'ocsh_fix_', 'ocsh_fix_check_', 'check_' ), '', $check );
		$marked_as_resolved = $this->ignored;
		if ( empty( $marked_as_resolved ) ) {
			$marked_as_resolved = array();
		}
		if ( ( $key = array_search( $check, $marked_as_resolved ) ) !== false ) {
			unset( $marked_as_resolved[ $key ] );
		}
		update_option( 'oc_marked_resolved', $marked_as_resolved, 'no' );
	}
}
