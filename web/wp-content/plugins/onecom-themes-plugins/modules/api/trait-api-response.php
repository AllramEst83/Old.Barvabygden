<?php
trait OCAPIResponseTrait {
	/**
	 * Return a sample health-monitor response to API calls
	 * when health-monitor's scan data is not present already
	 * @return array
	 */
	public function sampleResponse(): array {
		$res = array(
			'time'                     => time(),
			'uploads_index'            => 0,
			'options_table_count'      => 0,
			'check_staging_time'       => 0,
			'check_backup_zip'         => 0,
			'performance_cache'        => 0,
			'enable_cdn'               => 0,
			'check_updated_long_ago'   => 0,
			'check_pingbacks'          => 0,
			'xmlrpc'                   => 0,
			'spam_protection'          => 0,
			'user_enumeration'         => 0,
			'optimize_uploaded_images' => 0,
			'error_reporting'          => 0,
			'usernames'                => 0,
			'php_updates'              => 0,
			'plugin_updates'           => 0,
			'theme_updates'            => 0,
			'wp_updates'               => 0,
			'wp_connection'            => 0,
			'core_updates'             => 0,
			'ssl'                      => 0,
			'file_execution'           => 0,
			'file_permissions'         => 0,
			'file_edit'                => 0,
			'dis_plugin'               => 0,
		);
		return $res;
	}

	/**
	 * @return array
	 * Return a list of the checks for which the banner should appear in CP if they needs action
	 */
	public function get_issues_appearing_cp(): array {
		return array(
			'dis_plugin',
			'wp_connection',
			'wp_updates',
			'woocommerce_sessions',
			'staging_time',
			'options_table_count',
			'backup_zip',
			'uploads_index',
			'vulnerability_exists',
		);
	}
}
