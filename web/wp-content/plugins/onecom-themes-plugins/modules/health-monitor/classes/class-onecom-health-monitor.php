<?php
declare(strict_types=1);

/**
 * Deals with all the core features
 */
class OnecomHealthMonitor {

	use OnecomHMTexts;
	use OnecomCheckCategory;
	use OnecomChecksList;
	use OnecomLite;

	public $score_key  = 'score';
	public $status_key = 'status';
	public $title_key  = 'title';
	public $desc_key   = 'desc';
	public $module_path;
	public $checks              = array();
	public $flag_resolved       = 0;
	public $flag_open           = 1;
	public $flag_hidden         = 2;
	public $flag_open_critical  = 4;
	public $resolved_option     = 'oc_marked_resolved';
	public $active_plugins      = array();
	public $option_key          = 'onecom_hm_data';
	public $closing_li          = '</li>';
	public $ignored             = array();
	public $saved_critical_todo = 'saved_critical_todo';
	public $raw_list_key        = 'raw_list';

	public function __construct() {
		$this->init_trait();
		$this->init_trait_category();
		$all_checks           = $this->onecom_get_checks();
		$this->active_plugins = get_option( 'active_plugins' );
		$this->module_path    = ONECOM_WP_PATH . 'modules' . DIRECTORY_SEPARATOR . 'health-monitor' . DIRECTORY_SEPARATOR;
		if ( ! empty( $this->active_plugins ) && in_array( 'woocommerce/woocommerce.php', $this->active_plugins ) ) {
			$all_checks[] = 'woocommerce_sessions';
		}

		$this->ignored = get_option( $this->resolved_option, array() );
		if ( empty( $this->ignored ) || ! $this->onecom_is_premium() ) {
			$this->ignored = array();
		}
		$this->checks = apply_filters( 'onecom-checks-list', $all_checks );
		add_filter( 'onecom_if_premium', array( $this, 'onecom_premium_filter' ) );
		add_action( 'activated_plugin', array( $this, 'redirect_user_imagify_activation' ), 10, 2 );
		$this->remove_check_from_ignored_list();
	}

	public function log_entry( string $message, $single = 0 ) {

		$uploads_dir = wp_upload_dir();
		$log_dir     = $uploads_dir['basedir'] . DIRECTORY_SEPARATOR . 'onecom_logs/health_monitor';
		if (
			( ! ( ( WP_DEBUG_LOG || WP_DEBUG_LOG == 'true' ) || $single == 1 ) )
			||
			! file_exists( $log_dir )
			&& is_writable( $uploads_dir['basedir'] )
			&& ! mkdir( $log_dir )
		) {
			return false;
		}
		// return if log directory not reachable
		if ( ! is_writable( $log_dir ) ) {
			error_log( "Couldn't create onecom_logs directory for logging health monitor results. Insufficient file permissions for {$log_dir}" );

			return false;
		}
		// create health report log file

		$log_file = $log_dir . DIRECTORY_SEPARATOR . 'onecom-health-monitor.log';
		$time     = date( 'Y-m-d H:i:s' );

		// first lets check if it is writable
		return file_put_contents( $log_file, '[' . $time . '] ' . $message . "\n", FILE_APPEND );
	}

	public function save_result( $stage, $oc_hm_status, $finish = 0 ): bool {
		$result = get_site_transient( 'ocsh_site_scan_result' );

		$time = time();
		if ( ! $result ) {
			$result = array();
		}
		$removed_checks = array(
			'DB',
			'vulnerability_exists',
			'login_recaptcha',
			'asset_minification',
			'logout_duration',
			'login_protection',
		);
		foreach ( $result as $check => $status ) {
			if ( in_array( $check, $removed_checks ) ) {
				unset( $result[ $check ] );
			}
		}
		$result['time']   = $time;
		$result[ $stage ] = $oc_hm_status;
		$save             = set_site_transient( 'ocsh_site_scan_result', $result, 24 * HOUR_IN_SECONDS );

		if ( $finish == 1 ) {
			unset( $result['time'] );
			$health                     = array();
			$health['issues']           = $result;
			$health[ $this->score_key ] = round( $this->calculate_score( $result )[ $this->score_key ] );

			/* save health monitor result */
			$this->log_entry( '== one.com Health Monitor Scan ==' );
			$this->log_entry( json_encode( $health ), 1 );

			( class_exists( 'OCPushStats' ) ? OCPushStats::push_health_monitor_stats_request( 'scan', 'blog', OCPushStats::get_subdomain(), '1', $health ) : '' );
		}
		return $save;
	}

	public function calculate_score( $transient ) {
		if ( ! $transient || empty( $transient ) ) {
			return 0;
		}
		$time = $transient['time'];
		unset( $transient['time'] );

		$success = 0;

		foreach ( $transient as $score ) {
			if ( $score == OC_RESOLVED ) {
				$success++;
			}
		}
		$percent = round( ( ( $success * 100 ) / count( $transient ) ), 2 );
		if ( $percent == '100.00' ) {
			$percent = 100;
		}

		return array(
			$this->score_key => $percent,
			'time'           => $time,
		);
	}

	/**
	 * Prepare the response html for the provided check and the result.
	 *
	 * @param string $check
	 * @param array $result
	 *
	 * @return string
	 */
	public function get_html( string $check, array $result ) {
		$this->init_trait();
		$this->init_trait_category();
		$texts       = $this->get_text( $check );
		$caret       = $this->get_caret( $result );
		$list        = $this->get_list( $result, $check );
		$fix_button  = $this->get_fix_button( $result, $check, $texts );
		$undo_button = $this->get_undo_button( $check, $result );
		$upsell_text = $this->get_upsell_text( $check, $result, $texts );
		$status      = $this->get_status( $result, $list, $texts, $check );
		if ( $result[ $this->status_key ] !== $this->flag_resolved ) {
			$category_tag = $this->get_check_category( $check );
		} else {
			$category_tag = $this->get_check_category( $check, 1 );
		}

		$action_title = $texts[ $this->action_title ];
		$overview     = $category_tag . '<div class="onecom__overview">' . $texts[ $this->overview ] . '</div>';
		$desc         = $overview . $status;
		$undo_feature = ( isset( $result['undo'] ) && $result['undo'] ) ? 'data-undo="1"' : '';
		$template     = '<li ' . $undo_feature . ' id="ocsh-' . $check . '" class="ocsh-bullet ocsh-bullet-premium"><h4 class="ocsh-scan-title onecom__scan-title-bg">' . $action_title . $category_tag . '</h4>' . $caret;
		if ( $result[ $this->status_key ] !== $this->flag_resolved ) {
			$resolve_button = $this->get_resolve_button( $check );
		} else {
			$resolve_button = $undo_button;
		}
		if ( $resolve_button && $resolve_button !== '' ) {
			$template_button = '<div class="ocsh-actions">' . $resolve_button . $fix_button . $upsell_text . '</div>';
		} else {
			$template_button = '<div class="ocsh-actions">' . $fix_button . $upsell_text . '</div>';
		}
		$template .= '<div class="ocsh-desc-wrap hidden"><div class="osch-desc">' . $desc . '</div>' . $template_button . '</div>';

		return $template . $this->closing_li;
	}

	/**
	 * Accept scan outcome and format a result array to be returned.
	 *
	 * @param int $status status of current check 1,0
	 * @param string $title one liner description for current issue
	 * @param string $desc more detailed description for current issue
	 *
	 * @return array
	 */
	public function format_result( int $status = 1, string $title = '', string $desc = '' ): array {
		return array(
			$this->status_key => $status,
			$this->title_key  => __( $title, 'onecom-wp' ),
			$this->desc_key   => __( $desc, 'onecom-wp' ),
		);
	}

	/**
	 * Remove the parent directory path from the provided file path.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function format_path( string $path ): string {
		return ( str_replace( ABSPATH, '', $path ) );
	}

	/**
	 * get_list()
	 * Get list of items causing the issue
	 *
	 * @param array $result
	 *
	 * @return string
	 */
	private function get_list( array $result, string $check ): string|bool {

		if ( ! ( isset( $result['list'] ) || isset( $result['file-list'] ) || isset( $result[ $this->raw_list_key ] ) ) ) {
			return '';
		}
		if ( ( $check === 'inactive_plugins' || $check === 'inactive_themes' ) && count( $result['list'] ) > 10 ) {
			return '';
		}
		$list = '<ul class="ocsh-desc-li">';
		if ( isset( $result[ $this->raw_list_key ] ) && ( ! empty( $result[ $this->raw_list_key ] ) ) ) {
			$translated_text = __('Change user name', 'onecom-wp');
			$result[ $this->raw_list_key ] = str_replace('Change user name', $translated_text, $result[ $this->raw_list_key ]);
			return $list . $result[ $this->raw_list_key ] . '</ul>';
		}

		if ( isset( $result['file-list'] ) ) {
			foreach ( $result['file-list'] as $key => $item ) {
				$path        = $this->format_path( $key );
				$delete_link = '';
				if ( isset( $result['delete-link'] ) && $result['delete-link'] ) {
					$delete_link = '<a href="javascript:void(0)" class="ocsh-delete-link" data-file="' . $path . '">' . __( 'Delete', 'onecom-wp' ) . '</a>';
				}
				$list .= '<li>' . $path . ' <span class="ocsh-file-list-item">(' . $item . ')</span> ' . apply_filters( 'onecom_if_premium', $delete_link ) . $this->closing_li;
			}
		}

		if ( isset( $result['list'] ) ) {
			foreach ( $result['list'] as $key => $item ) {
				$delete_link = '';
				$path        = $this->format_path( $item );
				if ( isset( $result['delete-link'] ) && $result['delete-link'] ) {
					$delete_link = '<a href="javascript:void(0)" class="ocsh-delete-link" data-file="' . $path . '">' . __( 'Delete', 'onecom-wp' ) . '</a>';
				}
				$list .= '<li>' . $this->format_path( $item ) . apply_filters( 'onecom_if_premium', $delete_link ) . $this->closing_li;
			}
		}
		if ( str_contains( $list, '<li>' ) ) {
			return $list . '</ul>';

		} else {
			return false;
		}
	}


	/**
	 * get_fix_button()
	 * Get HTML for Fix button
	 *
	 * @param $result
	 * @param $check
	 *
	 * @return string
	 */
	private function get_fix_button( $result, $check, $text = array() ): string {

		if ( ! ( isset( $result['fix'] ) && $result[ $this->status_key ] === $this->flag_open ) ) {
			return '';
		}
		$fix_url = '';
		$class   = 'ocsh-open-modal';
		// stg check is added to navigate the user to staging page on click of fix button
		if ( $this->onecom_is_premium() || $check === 'check_performance_cache' || $check === 'enable_cdn' || $check === 'check_staging_time' ) {
			$fix_url = $result['fix_url'] ?? '';
			$class   = 'oc-fix-button';
		} else {
			$check = '';
		}
		$fix_text = $result['fix_text'] ?? $text[ $this->fix_button_text ];
		// stg check is added to navigate the user to staging page on click of fix button
		if ( $check === 'check_staging_time' || $check === 'inactive_plugins' || $check === 'inactive_themes' ) {
			$button_html = '<span class="ocsh-fix-wrap"><a target="_blank" href="' . $fix_url . '" class="' . $class . '"  data-check="' . $check . '">' . __($fix_text, $this->text_domain) . '</a></span>';
		}elseif ( $check === 'optimize_uploaded_images' && ! file_exists(WP_PLUGIN_DIR . '/imagify/imagify.php') ) {

			$button_html = '<form  method="post" class="ocsh-fix-wrap plugin-card-imagify"><a href="' . wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=imagify' ), 'install-plugin_imagify' ) . '" title="' . __( 'Install Imagify ', '' ) . '" id="" class="install-now oc-fix-button" data-slug="imagify" >' . __( 'Install Imagify', $this->text_domain) . '</a></form>';

		} elseif ( $check === 'optimize_uploaded_images' && ! is_plugin_active( 'imagify/imagify.php' ) ) {

			$activate_url = add_query_arg(
				array(
					'_wpnonce' => wp_create_nonce( 'activate-plugin_' . 'imagify/imagify.php' ),
					'action'   => 'activate',
					'plugin'   => 'imagify/imagify.php',
				),
				admin_url( 'plugins.php' )
			);

			$button_html = '<span class="ocsh-fix-wrap"><a href=' . $activate_url . ' title=' . __( 'Activate' ) . '  class="activate-now oc-fix-button"  data-slug="imagify">' . __( 'Activate Imagify', $this->text_domain ) . '</a></span>';

		} elseif ( $check === 'optimize_uploaded_images' && is_plugin_active( 'imagify/imagify.php' ) ) {
			$button_html = '<span class="ocsh-fix-wrap"><a href=' . admin_url( 'options-general.php?page=imagify' ) . ' title="' . __( 'Imagify Settings' ) . '"  class="oc-fix-button">' . __( 'Imagify Settings', $this->text_domain ) . '</a></span>';

		}else {
			$button_html = '<span class="ocsh-fix-wrap"><button class="' . $class . '"  data-check="' . $check . '">' . __($fix_text, $this->text_domain) . '</button></span>';
		}

		if ( $check === 'check_performance_cache' || $check === 'enable_cdn' ) {
			return $button_html;
		} else {
			return apply_filters( 'onecom_if_premium', $button_html );
		}
	}

	/**
	 * get_resolve_button()
	 * Get HTML for resolve button
	 *
	 * @param $check
	 *
	 * @return string
	 */
	private function get_resolve_button( string $check ): string {
		$class    = ( $this->onecom_is_premium() ) ? '' : 'ocsh-open-modal';
		$ignored  = $this->is_ignored( $check );
		$priority = 'normal';
		if ( $ignored ) {
			$link_text = $this->unignore_text;
			$class    .= ' onecom_unignore';
		} else {
			$link_text = $this->ignore_text;
			$class    .= ' oc-mark-resolved';
		}

		// override text for critical issues
		if ( $this->get_check_category( $check ) === '<span class="onecom_tag">' . $this->category['critical'] . '</span>' ) {
			if ( ! $ignored ) {
				$link_text = $this->ignore_critical_text;
			}
			$priority = 'critical';
		}

		$button_html = '<span class="ocsh-resolve-wrap"><a class="' . $class . '" data-check="' . $check . '" data-priority="' . $priority . '">' . $link_text . '</a></span>';

		return apply_filters( 'onecom_if_premium', $button_html );
	}

	/**
	 * get_undo_button()
	 * Get undo button
	 *
	 * @param $check
	 * @param $result
	 *
	 * @return string
	 */
	private function get_undo_button( string $check, array $result ): string {
		if ( ! ( $result['status'] == $this->flag_resolved && isset( $result['undo'] ) && $result['undo'] ) ) {
			return '';
		}
		if ( $this->onecom_is_premium() ) {
			return '<a class="onecom__revert_action" data-check="' . $check . '">' . $this->revert_text . '</a>';
		}

		return '';
	}

	/**
	 * @param string $check
	 * @param array $result
	 * @param array $texts
	 *
	 * @return string
	 */
	private function get_upsell_text( string $check, array $result, array $texts ): string {
		if ( ( $result[ $this->status_key ] === $this->flag_resolved )
			|| empty( $texts['upsell_text'] )
			|| $this->onecom_is_premium()
		) {
			return '';
		}

		return '<div class="onecom_ignored"><img src="' . ONECOM_WP_URL . '/assets/images/lock.svg" alt="one.com lock"><p>' . $texts['upsell_text'] . '</p></div>';
	}

	/**
	 * get_input_fields()
	 * Get HTML for input fields
	 *
	 * @param $result
	 *
	 * @return string
	 */
	private function get_input_fields( $result ): string {
		if ( ( ! ( isset( $result['input_fields'] ) && $result['input_fields'] ) ) || $result[ $this->status_key ] === $this->flag_resolved ) {
			return '';
		}
		$fields  = '';
		$hm_data = get_option( $this->option_key );
		$inputs  = $hm_data['recaptcha_keys'] ?? array();
		foreach ( $result['input_fields'] as $field ) {
			$value   = $inputs[ $field['name'] ] ?? '';
			$fields .= ' <div class="ocsh_input-fields">
<label> ' . $field['label'] . ' </label>
<input value = "' . $value . '" type = "text" name = "' . $field['name'] . '" id = "' . $field['name'] . '"><p class="oc-error-message"></p>
</div> ';
		}

		return apply_filters( 'onecom_if_premium', $fields );
	}

	private function get_caret( $result ): string {
		return $result[ $this->status_key ] === $this->flag_open ? '<span class="oc-caret"></span> ' : '';
	}

	/**
	 * Push stats
	 *
	 * @param array $additional_info
	 */
	public function push_stats( string $event_action, string $check_name ): void {
		if ( ! class_exists( 'OCPushStats' ) ) {
			return;
		}
		\OCPushStats::push_health_monitor_stats_request( $event_action, 'blog', OCPushStats::get_subdomain(), '1', array( 'check_name' => $check_name ), array( 'item_source' => 'health_monitor' ) );
	}

	/**
	 * @param $url
	 *
	 * @return array
	 */
	public function get_curl_header( $url ) {
		$headers = array();
		$ch      = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 1 );
		curl_setopt(
			$ch,
			CURLOPT_HEADERFUNCTION,
			function ( $curl, $header ) use ( &$headers ) {
				@$curl;
				$len    = strlen( $header );
				$header = explode( ':', $header, 2 );
				if ( count( $header ) < 2 ) { // ignore invalid headers
					return $len;
				}
				$name = strtolower( trim( $header[0] ) );
				if ( ! array_key_exists( $name, $headers ) ) {
					$headers[ $name ] = array( trim( $header[1] ) );
				} else {
					$headers[ $name ][] = trim( $header[1] );
				}

				return $len;
			}
		);
		curl_exec( $ch );

		return $headers;
	}

	/**
	 * @param $content
	 *
	 * @return string
	 */
	private function get_status( $result, $list, $texts, $check ): string {
		if ( $result[ $this->status_key ] === $this->status_open && ( $check === 'inactive_plugins' || $check === 'inactive_themes' ) ) {
			$count = count( $result['list'] );

			if ( $count > 0 ) {
				$status_desc_open = $texts[ $this->status_desc ][ $this->status_open ];

				if ( $list !== '' ) {
					$status_desc_open = sprintf( '%s', $status_desc_open );
				}

				$texts[ $this->status_desc ][ $this->status_open ] = sprintf( $status_desc_open, $count );
			}
		}
		$key_status     = $result[ $this->status_key ] === $this->flag_resolved ? $this->status_resolved : $this->status_open;
		$how_to_fix_key = $this->onecom_is_premium() ? $this->how_to_fix : $this->how_to_fix_lite;
		if ( empty( $texts[ $how_to_fix_key ] ) ) {
			$how_to_fix_key = $this->how_to_fix;
		}
		$input_fields = $this->get_input_fields( $result );
		$status       = '<h4 class="ocsh-scan-title onecom__scan_status">' . $this->status_text . '</h4>';
		$status      .= '<div class="onecom__scan_content__wrap"> ';
		$status      .= $texts[ $this->status_desc ][ $key_status ];
		if ( $list ) {
			$status .= $list;
		}
		$status .= '</div> ';
		if ( ( $result[ $this->status_key ] !== $this->flag_resolved )
			&& (
				( $texts[ $how_to_fix_key ] != '' )
				|| ( ! empty( $texts['upsell_text'] ) )
			) ) {
			$status .= '<span class="onecom__how_to_fix_wrap"><h4 class="ocsh-scan-title onecom__fix_title"> ' . $this->fix_text . '</h4>';
		}

		if ( ( $result[ $this->status_key ] !== $this->flag_resolved ) && ( $texts[ $how_to_fix_key ] != '' ) ) {
			$status .= $texts[ $how_to_fix_key ];
		}

		return $status . $input_fields . '</span>';
	}

	/**
	 * Check if a check is ignored
	 *
	 * @param string $check
	 *
	 * @return bool
	 */
	public function is_ignored( $check = '' ): bool {
		$check = str_replace( 'check_', '', $check );

		return in_array( $check, $this->ignored );
	}

	/**
	 * Save the list of critical issues found in scan
	 *
	 * @param $check
	 *
	 * @return bool
	 */
	public function save_critical_todo( $check, $result ): bool {
		$hm_data = get_option( $this->option_key, array() );
		if ( empty( $hm_data ) ) {
			$hm_data = array();
		}
		if ( isset( $hm_data[ $this->saved_critical_todo ] ) && array_key_exists( $check, $hm_data[ $this->saved_critical_todo ] ) ) {
			return false;
		}
		$hm_data[ $this->saved_critical_todo ][ $check ] = $result['html'];

		return update_option( $this->option_key, $hm_data, 'no' );
	}


	/**
	 * Response format based on type of request
	 */
	public function send_json( array $result, string $check = '' ) {
		if ( ! defined( 'REST_REQUEST' ) ) {
			if ( $this->is_ignored( $check ) ) {
				$result[ $this->status_key ] = 3;
			}
			// add "html" key if not present
			if ( ! isset( $result['html'] ) ) {
				$result['html'] = $this->get_html( $check, $result );
			}
			if ( $result[ $this->status_key ] === $this->flag_open_critical ) {
				$this->save_critical_todo( $check, $result );
			}
			wp_send_json( $result );
		}

		return $result;
	}

	/**
	 * @param $plugin
	 * @param $network_activation
	 * redirects the user to imagify settings page after plugin activation
	 */
	public function redirect_user_imagify_activation( $plugin, $network_activation ) {
		if ( $plugin == 'imagify/imagify.php' ) {
			wp_redirect( admin_url( 'options-general.php?page=imagify' ) );
			exit();
		}
	}

	/**
	 * @return bool
	 * function for removing  optimize_uploaded_images check from the ignored list based on plugin activation
	 */
	public function remove_check_from_ignored_list(): bool {
		$marked_as_resolved = $this->ignored;
		$plugin_installed   = false;
		if ( empty( $marked_as_resolved || ! isset( $marked_as_resolved['optimize_uploaded_images'] ) ) ) {

			return false;
		}

		// Added the recommended function to check Imagify API key
		// added in Imagify v2.1.1
		if (
			in_array( 'imagify/imagify.php', $this->active_plugins )
			&& function_exists( 'imagify_is_api_key_valid' ) && imagify_is_api_key_valid() ) {
			$plugin_installed = true;

		} else {

			$optimisation_plugins = array(
				'wp-smushit/wp-smush.php',
				'ewww-image-optimizer/ewww-image-optimizer.php',
				'optimole-wp/optimole-wp.php',
				'shortpixel-image-optimiser/wp-shortpixel.php',

			);
			foreach ( $optimisation_plugins as $optimisation_plugin ) {
				if ( in_array( $optimisation_plugin, $this->active_plugins ) ) {
					$plugin_installed = true;
					break;
				}
			}
		}

		if ( ( $plugin_installed === true ) && ( $key = array_search( 'optimize_uploaded_images', $marked_as_resolved ) ) !== false ) {

			unset( $marked_as_resolved[ $key ] );
		}
		update_option( $this->resolved_option, $marked_as_resolved, 'no' );
		return true;
	}

	/**
	 * @param $check
	 * @param $status
	 * updates the values in previous scans based on the undo and fix actions
	 * @return void
	 */
	public function update_previous_scan( $check, $status, $file = null ): void {
		$prev_scan   = get_site_transient( 'ocsh_site_previous_scan' );
		$scan_result = get_site_transient( 'ocsh_site_scan_result' );
		if ( empty( $prev_scan ) ) {
			$prev_scan = array();
		}
		if ( empty( $scan_result ) ) {
			$scan_result = array();
		}
		if ( $check == 'check_backup_zip' && $status === 0 ) {
			if ( isset( $prev_scan[ $check ]['list'] ) && is_array( $prev_scan[ $check ]['list'] ) ) {
				foreach ( $prev_scan[ $check ]['list'] as $key => $value ) {
					if ( str_contains( $value, $file ) ) {
						unset( $prev_scan[ $check ]['list'][ $key ] );
					}
				}

				if ( empty( $prev_scan[ $check ]['list'] ) ) {
					$prev_scan[ $check ]['delete-link'] = 0;
				} else {
					$status = 1;
				}
			}
		}
		$prev_scan[ $check ]['status'] = $status;
		$scan_result[ $check ]         = $status;

		set_site_transient( 'ocsh_site_previous_scan', $prev_scan );
		set_site_transient( 'ocsh_site_scan_result', $scan_result );
	}
}