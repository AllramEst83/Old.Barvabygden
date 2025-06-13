<?php

/**
 * Deals with admin pages
 */
class OnecomAdminPages extends OnecomHealthMonitor {

	private $page_name = 'Health Monitor';

	public function init() {
		add_action( 'admin_menu', array( $this, 'report_page' ) );
		add_action( 'network_admin_menu', array( $this, 'report_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'page_scripts' ) );
		add_action( 'admin_menu', array( $this, 'onecom_remove_duplicate_menu' ), 20 );
	}

	public function report_page() {
		add_submenu_page(
			$this->text_domain,
			__( $this->page_name, 'onecom-wp' ),
			'<span id="onecom_health_monitor">' . __( $this->page_name, 'onecom-wp' ) . '</span>',
			'manage_options',
			'onecom-wp-health-monitor',
			array( $this, 'report_page_callback' ),
			0
		);
	}

	/**
	 * @return void
	 * function to remove duplicate HM menu entries in  case of outdated validator
	 */
	public function onecom_remove_duplicate_menu(): void {
		global $submenu;

		$parent_slug  = $this->text_domain;
		$submenu_slug = 'onecom-wp-health-monitor';

		if ( isset( $submenu[ $parent_slug ] ) ) {
			$submenu_items = &$submenu[ $parent_slug ];
			$found_count   = 0;

			foreach ( $submenu_items as $index => $menu_item ) {
				if ( $menu_item[2] === $submenu_slug ) {
					$found_count++;
					if ( $found_count > 1 ) {
						// Remove duplicate submenu
						unset( $submenu_items[ $index ] );
					}
				}
			}

			// Re-index the array to avoid issues with missing keys
			$submenu[ $parent_slug ] = array_values( $submenu_items );
		}
	}

	public function report_page_callback() {
		if ( is_multisite() ) {
			include_once $this->module_path . 'templates/multisite_support_banner.php';
		} else {
			include_once $this->module_path . 'templates/oc_sh_health_monitor.php';
		}
	}

	public function page_scripts( $hook_suffix ) {
		if ( $hook_suffix === '_page_onecom-wp-health-monitor' || $hook_suffix === 'admin_page_onecom-wp-health-monitor' || $hook_suffix === '_page_onecom-wp-staging-blocked' ) {
			if ( SCRIPT_DEBUG || SCRIPT_DEBUG == 'true' ) {
				$folder      = '';
				$extenstion  = '';
				$script_path = ONECOM_WP_URL . 'modules/health-monitor/assets/';
			} else {
				$folder      = 'min-';
				$extenstion  = '.min';
				$script_path = ONECOM_WP_URL . 'assets/';
			}
			wp_enqueue_script( 'updates' );
			wp_enqueue_style( 'oc_sh_fonts', ONECOM_WP_URL . 'assets/css/onecom-fonts.css' );
			wp_enqueue_style( 'oc_sh_css', $script_path . $folder . 'css/site-scanner' . $extenstion . '.css' );
			wp_enqueue_script(
				'oc_sh_js',
				$script_path . $folder . 'js/oc_sh_script' . $extenstion . '.js',
				array(
					'jquery',
					'wp-theme-plugin-editor',
				),
				null,
				true
			);
			$cm_settings['codeEditor'] = wp_enqueue_code_editor( array( 'type' => 'shell' ) );
			wp_enqueue_script( 'wp-theme-plugin-editor' );
			wp_enqueue_style( 'wp-codemirror' );
			wp_localize_script(
				'oc_sh_js',
				'oc_constants',
				array(
					'OC_RESOLVED'         => OC_RESOLVED,
					'OC_OPEN'             => OC_OPEN,
					'ocsh_page_url'       => menu_page_url( 'admin_page_onecom-wp-health-monitor', false ),
					'ocsh_scan_btn'       => __( 'Scan again', 'onecom-wp' ),
					'nonce'               => wp_create_nonce( HT_NONCE_STRING ),
					'nonce_error'         => __( 'An error occurred. Please reload the page and try again', 'onecom-wp' ),
					'cm_settings'         => $cm_settings,
					'resetHtaccess'       => base64_encode(
						'<FilesMatch "\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|html|htm|shtml|sh|cgi|suspected)$">
    deny from all
</FilesMatch>'
					),
					'checks'              => $this->checks,
					'error_empty'         => __( 'This field cannot be empty', 'onecom-wp' ),
					'error_empty_sitekey' => __( 'Please, enter your site key.', 'onecom-wp' ),
					'error_length'        => __( 'The entered value seems to be incomplete.', 'onecom-wp' ),
					'ajaxurl'             => $this->onecom_is_premium() ? add_query_arg(
						array(
							'premium' => 1,
						),
						admin_url( 'admin-ajax.php' )
					) : admin_url( 'admin-ajax.php' ),
					'asset_url'           => ONECOM_WP_URL,
					'empty_list_messages' => array(
						'todo'    => __( 'Awesome, you completed all recommendations!', 'onecom-wp' ),
						'done'    => __( 'You haven\'t completed any recommendations. See the <span data-target="todo">To do</span> section.', 'onecom-wp' ),
						'ignored' => __( 'You haven’t ignored any recommendations.', 'onecom-wp' ),
					),
					'text'                => array(
						'unignore'        => __( 'Unignore', 'onecom-wp' ),
						'ignore'          => __( 'Ignore from future scans', 'onecom-wp' ),
						'ignore_critical' => __( 'Ignore for 24 hours', 'onecom-wp' ),
					),
					'current_screen'      => get_current_screen()->base,
				)
			);
		}
	}
}
