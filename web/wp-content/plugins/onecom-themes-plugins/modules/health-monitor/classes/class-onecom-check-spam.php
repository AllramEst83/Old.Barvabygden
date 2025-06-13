<?php

/**
 * Class OnecomCheckSpam
 * Deals with spam related checks and fixes
 */
declare(strict_types=1);

class OnecomCheckSpam extends OnecomHealthMonitor {

	private $plugin_list_url     = MIDDLEWARE_URL . '/antispam-plugins';
	private $plugin              = 'onecom-spam-protection/onecom-spam-protection.php';
	private $plugin_download_url = MIDDLEWARE_URL . '/plugins/onecom-spam-protection/download';

	/**
	 * Check if spam protection is enabled using any of the commonly used plugins.
	 * @return array
	 */
	public function check_spam_protection( $is_login_check = false ): array {
		/**
		 * when $is_login_check is empty string, it gives a false positive in tests below.
		 */
		$is_login_check     = (bool) $is_login_check;
		$plugin_object      = new OnecomCheckPlugins();
		$response           = wp_remote_get( $this->plugin_list_url );
		$list               = wp_remote_retrieve_body( $response );
		$list_decoded       = json_decode( $list, true );
		$remote_plugin_list = array();
		if ( isset( $list_decoded['data'] ) ) {
			$remote_plugin_list = $list_decoded['data'];
		}
		$remote_plugin_list[] = 'onecom-spam-protection';
		$active_plugins       = get_option( 'active_plugins' );
		$active_plugin_slugs  = array();
		if ( empty( $remote_plugin_list ) ) {
			$remote_plugin_list = array();
		}
		foreach ( $active_plugins as $plugin ) {
			$active_plugin_slugs[] = $plugin_object->plugin_slug( $plugin );
		}
		$plugin_intersection = array_intersect( $active_plugin_slugs, $remote_plugin_list );

		// if this is not a login_attempts check, the value of theme mod doesn't matter
		if ( isset( $_POST['action'] ) ) {
			$login_condition = ( $_POST['action'] === 'ocsh_check_login_attempts' || $is_login_check );
		} else {
			$login_condition = false;
		}

		if ( empty( $plugin_intersection ) && ( $login_condition || ( ! get_theme_mod( 'oc_checkbox' ) ) ) ) {
			return $this->format_result( $this->flag_open );
		} else {
			return $this->format_result( $this->flag_resolved );
		}
	}

	public function check_login_attempts() {
		// if any plugin from this array is active then this audit will be passed.
		$plugins_to_check = array(
			'login-lockdown/loginlockdown.php',
			'limit-login-attempts-reloaded/limit-login-attempts-reloaded.php',
			'limit-login-attempts/limit-login-attempts.php',
		);

		foreach ( $plugins_to_check as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				return $this->format_result( $this->flag_resolved );
			}
		}

		if ( is_plugin_active( 'onecom-spam-protection/onecom-spam-protection.php' ) ) {
			$sp_options = json_decode( get_option( 'onecom_sp_protect_options' ), true );
			// Check for JSON decoding errors
			$json_rrror    = json_last_error();
			$login_enabled = ( isset( $sp_options['checks']['oc_sp_quickres'] ) && $sp_options['checks']['oc_sp_quickres'] !== null ) ? $sp_options['checks']['oc_sp_quickres'] : null;
			if ( $json_rrror === JSON_ERROR_NONE && filter_var( $login_enabled, FILTER_VALIDATE_BOOLEAN ) === true ) {
				return $this->format_result( $this->flag_resolved );
			} elseif ( $json_rrror !== JSON_ERROR_NONE ) {
				error_log( 'JSON decoding error for spam protection options' );
				return $this->format_result( $this->flag_open );
			} else {
				return $this->format_result( $this->flag_open );
			}
		}
		return $this->format_result( $this->flag_open );
	}

	/**
	 * @return array
	 */
	public function is_onecom_theme(): array {
		$theme  = wp_get_theme();
		$author = $theme->get( 'Author' );
		if ( in_array( $author, array( 'one.com', 'onecom' ) ) ) {
			return array(
				'onecom_theme' => true,
				'url'          => admin_url( '/customize.php?autofocus[section]=oc_spam_checkbox' ),
			);
		}

		return array(
			'onecom_theme' => false,
			'url'          => '',
		);
	}

	/**
	 * Install the spam protection plugin
	 * @return array
	 */
	public function fix_spam_protection(): array {
		$check = 'spam_protection';
		if ( $_POST['action'] === 'ocsh_fix_login_attempts' ) {
			$check = 'login_attempts';
		}
		if ( is_plugin_active( 'onecom-spam-protection/onecom-spam-protection.php' ) ) {
			$this->update_spam_protection_setting();
			return $this->format_result(
				$this->flag_resolved,
				$this->text[ $check ][ $this->fix_confirmation ],
				$this->text[ $check ][ $this->status_desc ][ $this->status_resolved ]
			);
		} elseif ( $this->install_plugin() ) {
			$this->update_spam_protection_setting();
			return $this->format_result(
				$this->flag_resolved,
				$this->text[ $check ][ $this->fix_confirmation ],
				$this->text[ $check ][ $this->status_desc ][ $this->status_resolved ]
			);
		} else {
			return $this->format_result( $this->flag_open );
		}
	}

	public function update_spam_protection_setting( $status = true ) {
		$sp_options                             = json_decode( get_option( 'onecom_sp_protect_options' ), true );
		$sp_options['checks']['oc_sp_quickres'] = $status;
		update_option( 'onecom_sp_protect_options', json_encode( $sp_options ), false );
	}

	public function undo_spam_protection(): array {
		$check = 'spam_protection';
		if ( $_POST['action'] === 'ocsh_undo_login_attempts' ) {
			$check = 'login_attempts';
			if ( is_plugin_active( 'onecom-spam-protection/onecom-spam-protection.php' ) ) {
				$this->update_spam_protection_setting( false );
				return array(
					$this->status_key      => $this->flag_resolved,
					$this->fix_button_text => $this->text[ $check ][ $this->fix_button_text ],
					$this->desc_key        => $this->text[ $check ][ $this->status_desc ][ $this->status_open ],
					$this->how_to_fix      => $this->text[ $check ][ $this->how_to_fix ],
					'ignore_text'          => $this->ignore_text,
				);
			}
		}

		deactivate_plugins( $this->plugin );

		return array(
			$this->status_key      => $this->flag_resolved,
			$this->fix_button_text => $this->text[ $check ][ $this->fix_button_text ],
			$this->desc_key        => $this->text[ $check ][ $this->status_desc ][ $this->status_open ],
			$this->how_to_fix      => $this->text[ $check ][ $this->how_to_fix ],
			'ignore_text'          => $this->ignore_text,
		);
	}

	/**
	 * Install a plguin
	 * @return bool
	 * @todo    remove hardcoded values
	 */
	private function install_plugin(): bool {
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
		require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';

		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$upgrader->install( $this->plugin_download_url );
		return is_null( activate_plugin( $this->plugin ) );
	}
}
