<?php

trait OnecomCheckCategory {

	public $category = array();

	public function init_trait_category( $skip_translations = 0 ) {
		$this->category = array(
			'security'    => ( 0 === $skip_translations ) ? __( 'Security', 'onecom-wp' ) : 'Security',
			'critical'    => ( 0 === $skip_translations ) ? __( 'Critical', 'onecom-wp' ) : 'Critical',
			'performance' => ( 0 === $skip_translations ) ? __( 'Performance', 'onecom-wp' ) : 'Performance',
		);
	}

	/**
	 * Get the category of a check
	 *
	 * @param string $check
	 *
	 * @return string
	 */
	public function get_check_category( $check = '', $hide_tag = 0, $html = 1 ): string {
		if ( empty( $check ) ) {
			return '';
		}
		$checks = array(
			'uploads_index'            => $this->category['critical'],
			'options_table_count'      => $this->category['critical'],
			'staging_time'             => $this->category['critical'],
			'backup_zip'               => $this->category['critical'],
			'wp_connection'            => $this->category['critical'],
			'dis_plugin'               => $this->category['critical'],
			'woocommerce_sessions'     => $this->category['critical'],
			'core_updates'             => $this->category['critical'],
			'performance_cache'        => $this->category['performance'],
			'updated_long_ago'         => $this->category['security'],
			'pingbacks'                => $this->category['security'],
			'logout_duration'          => $this->category['security'],
			'xmlrpc'                   => $this->category['security'],
			'spam_protection'          => $this->category['security'],
			'login_attempts'           => $this->category['security'],
			'login_recaptcha'          => $this->category['security'],
			'asset_minification'       => $this->category['performance'],
			'php_updates'              => $this->category['security'],
			'plugin_updates'           => $this->category['security'],
			'theme_updates'            => $this->category['security'],
			'wp_updates'               => $this->category['critical'],
			'ssl'                      => $this->category['security'],
			'file_execution'           => $this->category['security'],
			'file_permissions'         => $this->category['security'],
			'DB'                       => $this->category['security'],
			'file_edit'                => $this->category['security'],
			'usernames'                => $this->category['security'],
			'error_reporting'          => $this->category['security'],
			'enable_cdn'               => $this->category['performance'],
			//      'vulnerable_components' => $this->category['performance'],
				'user_enumeration'     => $this->category['security'],
			'optimize_uploaded_images' => $this->category['performance'],
			'login_protection'         => $this->category['security'],
			'debug_enabled'            => $this->category['security'],
			'inactive_plugins'         => $this->category['security'],
			'inactive_themes'          => $this->category['security'],
			'debug_log_size'           => $this->category['performance'],
		);
		$check  = str_replace( 'check_', '', $check );
		if ( ( ! array_key_exists( $check, $checks ) ) || empty( $checks[ $check ] ) ) {
			return '';
		}
		$class = 'onecom_tag ' . $checks[ $check ];

		if ( $hide_tag === 1 ) {
			$html = '<span class="oc_hide_tag ' . $class . '">' . $checks[ $check ] . '</span>';
		} else {
			$html = ( 1 === $html ) ? '<span class="' . $class . '">' . $checks[ $check ] . '</span>' : $checks[ $check ];
		}

		return $html;
	}
}
