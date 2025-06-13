<?php

class OnecomFixes extends OnecomHealthMonitor {


	public function init() {
		add_action( 'wp_ajax_ocsh_fix_check_performance_cache', array( $this, 'fix_check_performance_cache' ) );
		add_action( 'wp_ajax_ocsh_fix_woocommerce_sessions', array( $this, 'fix_woocommerce_sessions' ) );
		add_action( 'wp_ajax_ocsh_fix_staging_check', array( $this, 'fix_staging' ) );
		add_action( 'wp_ajax_ocsh_fix_check_pingbacks', array( $this, 'fix_check_pingbacks' ) );
		add_action( 'wp_ajax_ocsh_fix_enable_cdn', array( $this, 'fix_check_performance_cdn' ) );
		add_action( 'wp_ajax_ocsh_fix_backup_zip', array( $this, 'fix_check_backup_zips' ) );
		add_action( 'wp_ajax_ocsh_fix_logout_duration', array( $this, 'fix_check_logout_duration' ) );
		add_action( 'wp_ajax_ocsh_fix_xmlrpc', array( $this, 'fix_xmlrpc' ) );
		add_action( 'wp_ajax_ocsh_fix_login_attempts', array( $this, 'fix_spam_protection' ) );
		add_action( 'wp_ajax_ocsh_fix_login_recaptcha', array( $this, 'fix_login_recaptcha' ) );
		add_action( 'wp_ajax_ocsh_fix_file_execution', array( $this, 'fix_file_execution' ) );
		add_action( 'wp_ajax_ocsh_change_username', array( $this, 'fix_usernames' ) );
		add_action( 'wp_ajax_ocsh_fix_dis_plugin', array( $this, 'fix_dis_plugin' ) );
		add_action( 'wp_ajax_ocsh_fix_user_enumeration', array( $this, 'fix_user_enumeration' ) );
		add_action( 'wp_ajax_ocsh_fix_spam_protection', array( $this, 'fix_spam_protection' ) );
		add_action( 'wp_ajax_ocsh_fix_debug_log_size', array( $this, 'fix_debug_log_size' ) );
	}

	public function fix_woocommerce_sessions() {
		$db     = new OnecomCheckDB();
		$result = $db->fix_woocommerce_sessions();
		$this->update_previous_scan( 'woocommerce_sessions', $result['status'] );
		$this->remove_from_ignore();
		wp_send_json( $result );
	}

	public function fix_staging() {
		$stg    = new OnecomCheckStaging();
		$result = $stg->fix_staging();
		$this->remove_from_ignore();
		wp_send_json( $result );
	}

	public function fix_check_pingbacks() {
		$ping           = new OnecomPingback();
		$result         = $ping->fix_pingback();
		$result['undo'] = true;
		$this->update_previous_scan( 'check_pingbacks', $result['status'] );
		$this->remove_from_ignore();
		wp_send_json( $result );
	}

	public function fix_check_performance_cache() {
		$pc             = new OnecomCheckPlugins();
		$result         = $pc->fix_performance_cache();
		$result['undo'] = true;
		$this->update_previous_scan( 'performance_cache', $result['status'] );
		$this->remove_from_ignore();
		wp_send_json( $result );
	}

	public function fix_check_performance_cdn() {
		$pc             = new OnecomCheckPlugins();
		$result         = $pc->fix_performance_cdn();
		$result['undo'] = true;
		$this->update_previous_scan( 'enable_cdn', $result['status'] );
		$this->remove_from_ignore();
		wp_send_json( $result );
	}

	public function fix_check_backup_zips() {
		$fs     = new OnecomCheckFiles();
		$file   = strip_tags( $_POST['file'] );
		$result = $fs->fix_backup_zips( $file );
		$this->update_previous_scan( 'check_backup_zip', $result['status'], $file );
		$this->remove_from_ignore();
		wp_send_json( $result );
	}


	public function fix_check_logout_duration() {
		$logout = new OnecomCheckLogin();
		$result = $logout->fix_check_logout_time();
		$this->remove_from_ignore();
		wp_send_json( $result );
	}

	public function fix_xmlrpc() {
		$xmlrpc         = new OnecomXmlRpc();
		$result         = $xmlrpc->fix_check_xmlrpc();
		$result['undo'] = true;
		$this->update_previous_scan( 'xmlrpc', $result['status'] );
		$this->remove_from_ignore();
		wp_send_json( $result );
	}

	public function fix_login_recaptcha() {
		$login = new OnecomCheckLogin();
		$this->remove_from_ignore();
		wp_send_json( $login->fix_login_recaptcha( $_POST ) );
	}

	public function fix_file_execution() {
		$file = new OnecomFileSecurity();
		$file->get_htaccess();
		$result = $file->oc_save_ht_cb();
		$this->update_previous_scan( 'file_execution', $result['status'] );
		$this->remove_from_ignore();
		self::send_json( $result );
	}

	public function fix_usernames() {
		$username = new OnecomCheckUsername();
		$result   = $username->fix_usernames();
		$this->update_previous_scan( 'usernames', $result['status'] );
		$this->remove_from_ignore();
		wp_send_json( $result );
	}

	public function fix_dis_plugin() {
		$plugin = new OnecomCheckPlugins();
		$result = $plugin->fix_dis_plugin();
		$this->update_previous_scan( 'dis_plugin', $result['status'] );
		$this->remove_from_ignore();
		wp_send_json( $result );
	}

	public function fix_user_enumeration() {
		$user   = new OnecomCheckUsername();
		$result = $user->fix_user_enumeration();
		$this->update_previous_scan( 'user_enumeration', $result['status'] );
		$this->remove_from_ignore();
		wp_send_json( $result );
	}

	public function fix_spam_protection() {
		$plugin         = new OnecomCheckSpam();
		$result         = $plugin->fix_spam_protection();
		$result['undo'] = true;
		$this->update_previous_scan( 'login_attempts', $result['status'] );
		$this->remove_from_ignore();
		wp_send_json( $result );
	}

	public function fix_debug_log_size() {
		$plugin = new OnecomDebugMode();
		$result = $plugin->fix_debug_log_size();
		$this->update_previous_scan( 'debug_log_size', $result['status'] );
		$this->remove_from_ignore();
		wp_send_json( $result );
	}

	private function remove_from_ignore(): void {

		$check              = strip_tags( $_POST['action'] );
		$check              = str_replace( array( 'ocsh_fix_', 'ocsh_fix_check_', 'check_' ), '', $check );
		$marked_as_resolved = $this->ignored;
		if ( empty( $marked_as_resolved ) ) {
			$marked_as_resolved = array();
		}
		if ( ( $key = array_search( $check, $marked_as_resolved ) ) !== false ) {
			unset( $marked_as_resolved[ $key ] );
		}
		$this->push_stats( 'quick_fix', $check );
		update_option( 'oc_marked_resolved', $marked_as_resolved, 'no' );
	}
}
