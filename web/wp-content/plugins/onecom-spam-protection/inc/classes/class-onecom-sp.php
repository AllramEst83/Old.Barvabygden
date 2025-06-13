<?php

/**
 * Base class for spam protection
 */


class OnecomSp {


	/**
	 * @var string
	 */
	public $text_domain         = 'onecom-sp';
	public $sub_page_protection = ' Protection Settings';
	public $sub_page_blocklist  = 'Advanced settings';
	public $sub_page_logs       = 'Spam Logs';
	public $is_premium;
	public $sp_protection_options;
	public $sub_page_diagnostics = 'Spam Protection Diagnostics';
	const THEAD_CLOSE            = '</th>';
	const TCOLUMN_CLOSE          = '</td>';
	const LI_CLOSE               = '</li>';
	const LI_START               = '<li>';
	const SPAM_URL_SHORTNERS     = '/spam/url-shortners';
	const SPAM_EXPLOIT_URLS      = '/spam/exploit-urls';
	const CHECKED                = 'checked="checked"';
	const ONE_ENTRY_PER_LINE     = 'one entry per line';



	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action( 'wp_ajax_oc_get_summary', array( $this, 'oc_get_spam_summary' ) );
		add_action( 'wp_ajax_nopriv_oc_get_summary', array( $this, 'oc_get_spam_summary' ) );
		add_action( 'wp_ajax_oc_save_settings', array( $this, 'oc_save_settings' ) );
		add_action( 'wp_ajax_nopriv_oc_save_settings', array( $this, 'oc_save_settings' ) );
		add_action( 'wp_ajax_oc_save_advanced_settings', array( $this, 'oc_save_advanced_settings' ) );
		add_action( 'wp_ajax_nopriv_oc_save_advanced_settings', array( $this, 'oc_save_advanced_settings' ) );
		add_action( 'wp_ajax_oc_check_spam_diagnostics', array( $this, 'oc_check_spam_diagnostics' ) );
		add_action( 'wp_ajax_nopriv_oc_check_spam_diagnostics', array( $this, 'oc_check_spam_diagnostics' ) );
		add_action( 'wp_ajax_oc_clear_spam_logs', array( $this, 'oc_clear_spam_logs' ) );
		add_action( 'wp_ajax_nopriv_oc_clear_spam_logs', array( $this, 'oc_clear_spam_logs' ) );
		add_action( 'init', array( $this, 'ocsp_wp_load_textdomain' ), -1 );
		$this->sp_protection_options = oc_get_sp_options( 'onecom_sp_protect_options' );
		$this->oc_auto_clear_logs();
	}


	// load text domain for language translation
	public function ocsp_wp_load_textdomain() {
		$current_locale           = get_locale();
		$locales_with_translation = array(
			'da_DK',
			'de_DE',
			'es_ES',
			'fr_FR',
			'it_IT',
			'pt_PT',
			'nl_NL',
			'sv_SE',
		);

		// Locales fallback and load english translations [as] if selected unsupported language in WP-Admin
		if ( $current_locale === 'fi' ) {
			load_textdomain( 'onecom-sp', ONECOM_PLUGIN_PATH . '/languages/onecom-sp-fi_FI.mo' );
		} elseif ( $current_locale === 'nb_NO' ) {
			load_textdomain( 'onecom-sp', ONECOM_PLUGIN_PATH . '/languages/onecom-sp-no_NO.mo' );
		} if ( in_array( get_locale(), $locales_with_translation ) ) {
			load_plugin_textdomain( 'onecom-sp', false, ONECOM_SP_PLUGIN_SLUG . '/languages' );
		} else {
			load_textdomain( 'onecom-sp', ONECOM_PLUGIN_PATH . '/languages/onecom-sp-en_GB.mo' );
		}
	}


	/**
	 * For enqueuing CSS & JS only on the required pages.
	 */
	public function admin_styles() {

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		$bodylist     = array(
			__( 'Protects the website from spam comments, spam registration, spambots, and other spammers.', 'onecom-sp' ),
			__( 'Customizable protection settings, spam logs, and other options.', 'onecom-sp' ),
		);
		$popupContent = array(
			'title'       => sprintf( __( 'Get Spam Protection plugin with %s Managed WordPress', 'onecom-sp' ), "\n" ),
			'top-desc'    => __( 'Spend less time worrying about your site’s safety and more time growing your business with one.com Managed WordPress.', 'onecom-sp' ),
			'footer-desc' => __( 'Managed WordPress includes other great features such as Vulnerability Monitor and Upgrade Manager', 'onecom-sp' ),
			'bodylist'    => $bodylist,
		);

		if ( SCRIPT_DEBUG || SCRIPT_DEBUG == 'true' ) {
			$folder     = '';
			$extenstion = '';
		} else {
			$folder     = 'min-';
			$extenstion = '.min';
		}
		$script_path = ONECOM_SP_WP_URL . '/assets/';

		wp_register_style( 'onecom_sp_admin_styles', $script_path . $folder . 'css/admin' . $extenstion . '.css', array(), ONECOM_SP_VERSION );
		wp_register_script( 'onecom_sp_admin_script', $script_path . $folder . 'js/oc-sp-admin' . $extenstion . '.js', array(), ONECOM_SP_VERSION );

		if ( in_array( $screen_id, $this->onecom_sp_allowed_screens() ) ) {
			wp_enqueue_style( 'onecom_sp_admin_styles' );
		}
		if ( in_array( $screen_id, $this->onecom_sp_allowed_screens() ) ) {
			wp_enqueue_script( 'onecom_sp_admin_script' );
			wp_localize_script(
				'onecom_sp_admin_script',
				'onespnotice',
				array(
					'oc_notice'                   => __( 'All the fields can\'t be left empty at least one input is required for diagnosis.', 'onecom-sp' ),
					'popupContent'                => $popupContent,
					'generic_validation_msg'      => __( "The input doesn't match the allowed format for this field. Please check and try again.", 'onecom-sp' ),
					'failed_login_validation_msg' => __( 'The number of failed login attempts must be between 1-10. The time in seconds should have a value between 10 and 900. Please check the numbers you entered and try again.', 'onecom-sp' ),
				)
			);
		}
	}


	/**
	 * @return mixed
	 * returns USER IP
	 */
	public function get_user_ip() {

		$ip         = $_SERVER['REMOTE_ADDR'] ?? '';
		$sp_options = $this->sp_protection_options;
		if ( isset( $sp_options['checks']['oc_spwhitelistip'] ) &&
			$sp_options['checks']['oc_spwhitelistip'] == 'true' &&
			isset( $sp_options['whitelist_ips'] ) &&
			is_array( $sp_options['whitelist_ips'] ) &&
			in_array( $ip, $sp_options['whitelist_ips'] ) ) {
			$ip = '';
		}

		return $ip;
	}

	/**
	 * @return mixed|null
	 * returns user agent
	 */
	public function oc_get_user_agent() {
		if ( array_key_exists( 'HTTP_USER_AGENT', $_SERVER ) ) {
			return $_SERVER['HTTP_USER_AGENT'];
		}
		return null;
	}


	/**
	 * @param $page_title
	 * @param $description
	 * This generates the header for admin pages of plugin.
	 */
	public static function sp_admin_head( $page_title, $description ) {
		$html = '';

		$html = '<h2 class="one-title">' . $page_title . '</h2>';

		return $html;
	}

	/**
	 * @return string[]
	 * returns array of protect options
	 */
	public static function sp_protect_options() {

		$protect_options = array(
			'oc_sp_accept',
			'oc_sp_referrer',
			'oc_sp_long',
			'oc_sp_short',
			'oc_sp_bbcode',
			'oc_sp_exploit',
			'oc_sp_urlshort',

		);
		return $protect_options;
	}


	/**
	 * @return array[]
	 * list of screens where resources should be enqueued
	 */
	public function onecom_sp_allowed_screens(): array {
		return array(
			'toplevel_page_onecom-wp-spam-protection',
			'toplevel_page_onecom-wp-spam-protection-network',
			'spam-protection_page_onecom-wp-protection-settings',
			'spam-protection_page_onecom-wp-advanced-settings',
			'spam-protection_page_onecom-wp-spam-logs',
			'spam-protection_page_onecom-wp-spam-diagnostics',
		);
	}

	/**
	 * @param $ip
	 * @param $email
	 * @param null $user_agent
	 * @param null $username
	 * @return array|mixed
	 *
	 * Interacts with WP API endpoint and returns the response received
	 */
	public function sp_api_check( $ip, $email, $user_agent = null, $username = null ) {

		//check for whitelisted user agents
		$user_agent = $this->oc_get_whitelisted_useragents( $user_agent );

		//check for the whitelisted users
		$username = $this->oc_get_whitelisted_users( $username );
		$payload  = array(
			'user_ip'    => $ip,
			'user_agent' => $user_agent,
			'user_email' => $email,
			'username'   => $username,
		);

		$curl = curl_init();
		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => MIDDLEWARE_URL . '/spam/check',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_VERBOSE        => false,
				CURLOPT_TIMEOUT        => 0,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => 'POST',
				CURLOPT_POSTFIELDS     => json_encode( $payload ),
				CURLOPT_HTTPHEADER     => array(
					'Content-Type: text/plain',
				),
			)
		);

		// silent call
		$response = curl_exec( $curl );
		$response = json_decode( $response, true );
		$err      = curl_error( $curl );

		// Close request to clear up some resources
		curl_close( $curl );

		if ( $err ) {
			return array(
				'data'    => null,
				'error'   => __( 'Some error occurred, please reload the page and try again.', 'validator' ),
				'success' => false,
			);
		} elseif ( ! $response['success'] ) {
			return $response;
		} else {
			return $response['data'];

		}
	}

	/**
	 * @return mixed|string
	 *
	 * Gets the URI of current page for logging in the spam logs
	 */
	public static function oc_get_spam_url() {
		// gets the module name from the URL address line
		$spam_url = '';
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$spam_url = $_SERVER['REQUEST_URI'];
		}
		if ( empty( $spam_url ) && isset( $_SERVER['REQUEST_URI'] ) ) {
			$spam_url = $_SERVER['SCRIPT_NAME'];
			if ( isset( $_SERVER['QUERY_STRING'] ) ) {
				$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
			}
		}
		if ( empty( $spam_url ) ) {
			$spam_url = '';
		}

		return $spam_url;
	}

	/**
	 * @param $url
	 * @param array $existing_options
	 * @return array|mixed
	 *
	 * gets the list from WP API endpoints for exploit-urls & url-shorteners
	 */
	public static function oc_get_values_from_api( $url, $existing_options = array() ) {
		global $wp_version;

		$fetch_list = $url;

		$args = array(
			'timeout' => 5,
		);

		$response = wp_remote_get( $fetch_list, $args );
		$get_list = array();

		if ( ! is_wp_error( $response && is_array( $response ) ) ) {

			$body = wp_remote_retrieve_body( $response );
			$body = json_decode( $body );
			if ( ! empty( $body ) && $body->success ) {
				$get_list = $body->data;
			} else {

				error_log( print_r( $body, true ) );
			}
		} else {
			$errorMessage = '(' . wp_remote_retrieve_response_code( $response ) . ') ' . wp_remote_retrieve_response_message( $response );

			error_log( print_r( $errorMessage, true ) );

		}
		if ( ! empty( $get_list ) && ! empty( $existing_options ) ) {
			//moved here to avoid recursion in case of restore plugin data
			$sp_options = oc_get_sp_options( 'onecom_sp_protect_options' );
			$sp_options = array_values( array_unique( array_merge( $get_list, $existing_options ) ) );

		} elseif ( ! empty( $get_list ) && empty( $existing_options ) ) {

			$sp_options = $get_list;

		}
		return $sp_options;
	}

	/**
	 * callback for the ajax request of spam summary block filter
	 */
	public function oc_get_spam_summary() {
		$duration  = $_POST['duration'];
		$spam_logs = oc_get_sp_options( 'onecom_sp_spam_logs' );
		$sorted    = $this->oc_get_sorted_list( strtotime( "-$duration" ), current_time( 'timestamp' ), $spam_logs['records'], 'summary' );

		if ( is_array( $sorted ) && ! empty( $sorted ) ) {

			$comments     = array();
			$registration = array();
			$failed_login = array();
			$other        = array();
			foreach ( $sorted as $record ) {

				if ( strpos( $record[3], 'wp-comments-post.php' ) !== false ) {

					$comments[] = $record[3];
					unset( $record[3] );

				} elseif ( strpos( $record[3], 'action=register' ) !== false ) {
					$registration[] = $record[3];
					unset( $record[3] );

				} elseif ( strcmp( '/wp-login.php', $record[3] ) == 0 ) {

					$failed_login[] = $record[3];
					unset( $record[3] );
				} else {
					$other[] = $record[3];

				}
			}

			$response = array(
				'total_count'        => count( $sorted ),
				'comments_count'     => count( $comments ),
				'registration_count' => count( $registration ),
				'failed_login'       => count( $failed_login ),
				'other_count'        => count( $other ),
			);

		} else {
			$response = array(
				'total_count'        => 0,
				'comments_count'     => 0,
				'registration_count' => 0,
				'failed_login'       => 0,
				'other_count'        => 0,
			);
		}
		wp_send_json_success( $response );
		return false;
	}

	/**
	 * @param $check_type
	 * @param $check_array
	 * @return string
	 *
	 * generates the table for spam diagnostics page based on check type & input array
	 */
	public function oc_generate_table( $check_type, $check_array ) {
		$html  = '';
		$class = '';
		$html .= "<table class='oc-diagnostics'>";
		$html .= '<thead><tr>';
		$html .= '<th>' . __( 'Check', 'onecom-sp' ) . self::THEAD_CLOSE;
		$html .= '<th>' . __( 'Result', OC_SP_TEXTDOMAIN ) . self::THEAD_CLOSE;

		$html .= $this->oc_get_dynamic_th( $check_type );
		$html .= '</thead></tr>';

		if ( $check_type !== 'website-check' ) {

			if ( $check_array['status'] ) {
				$class = 'oc-sp-error';
			}

			$html .= '<tr><td>' . __( 'one.com spam detection' ) . self::TCOLUMN_CLOSE;
			$html .= "<td><span class='" . $class . "'></span></td>";
			$html .= '<td>' . $check_array['reason'] . '</td></tr>';

		} else {
			$spam_detected     = array();
			$disabled_settings = array();
			foreach ( $check_array as $check => $value ) {
				if ( ! $value['result'] ) {
					$result = '<span class=\'oc-sp-success\'></span>';

				} else {
					$spam_detected[ $check ] = $value['result'];
					$result                  = $value['result'];
				}
				if ( $value['status'] !== 'true' ) {
					$status              = 'oc-sp-warning';
					$disabled_settings[] = $check;

				} else {
					$status = 'oc-sp-success';
				}

				$html .= '<tr><td>' . __( $value['description'], OC_SP_TEXTDOMAIN ) . self::TCOLUMN_CLOSE;
				$html .= '<td>' . $result . self::TCOLUMN_CLOSE;
				$html .= "<td><span class='" . $status . "'></span></td></tr>";

			}

			$final_result      = $this->oc_get_final_dg_status( $spam_detected );
			$settings_disabled = $this->oc_get_web_settings_msg( $disabled_settings );

			$html .= '<tr><td><b>' . __( 'Final Result', OC_SP_TEXTDOMAIN ) . '</b></td>';
			$html .= "<td class='" . $final_result['border'] . "'> " . $final_result['final_result'] . " </td><td>$settings_disabled</td></tr>";

		}

		$html .= '</table>';

		$html .= $this->oc_get_table_description( $check_type );

		return $html;
	}


	/**
	 * @param $check_type
	 * @return string
	 * returns the table header based on the type of check
	 */
	public function oc_get_dynamic_th( $check_type ) {

		if ( $check_type !== 'website-check' ) {
			return '<th>' . __( 'Reason', OC_SP_TEXTDOMAIN ) . self::THEAD_CLOSE;

		} else {
			return '<th>' . __( 'Protection Setting', OC_SP_TEXTDOMAIN ) . self::THEAD_CLOSE;
		}
	}


	/**
	 * @param $disabled_settings
	 * @return string
	 * returns final message for the protection settings column in diagnostics table
	 */
	public function oc_get_web_settings_msg( $disabled_settings ) {
		if ( ! empty( $disabled_settings ) ) {

			$settings_disabled = '<p>' . __( 'It is recommended to use default settings of plugin in case you are seeing spams not getting blocked effectively.', OC_SP_TEXTDOMAIN ) . ' <form method="post"><input type="submit" class="oc-save" value="Restore settings" name="oc-reset-settings"/></form></p>';

		} else {

			$settings_disabled = '<span class=\'oc-sp-success\'></span>';
		}

		return $settings_disabled;
	}

	/**
	 * @param $spam_detected
	 * @return array
	 * returns final status for the checks column in diagnostics table
	 */
	public function oc_get_final_dg_status( $spam_detected ) {
		$result = array();
		if ( ! empty( $spam_detected ) ) {
			$result['final_result'] = __( 'The details provided by you are detected as spam', OC_SP_TEXTDOMAIN );
			$result['border']       = 'border-spam';

		} else {
			$result['final_result'] = __( 'No spam Detected', OC_SP_TEXTDOMAIN );
			$result['border']       = 'border-nospam';

		}

		return $result;
	}

	/**
	 * @return bool
	 * auto clears spam logs stored in the wp_options table.
	 * if the records exceed 500 or the logs are older than 30 days.
	 */
	public function oc_auto_clear_logs() {

		$spam_logs       = oc_get_sp_options( 'onecom_sp_spam_logs' );
		$spam_count      = 0;
		$additional_info = array(
			'additional_info' => json_encode(
				array(
					'logs_cleared_by' => 'auto_cleared',
					'blocked_spams'   => $spam_logs['spam_count'] ?? '',

				)
			),
		);

		if ( $spam_logs && isset( $spam_logs['records'] ) && ! empty( $spam_logs['records'] ) ) {
			$spam_count = count( $spam_logs['records'] );
			$sorted     = $this->oc_get_sorted_list( strtotime( '-30 days' ), current_time( 'timestamp' ), $spam_logs['records'], 'clearLog' );

		}

		if ( $spam_count >= 500 ) {

			$spam_logs['records']    = array_slice( $spam_logs['records'], 500 );
			$spam_logs['spam_count'] = count( $spam_logs['records'] );

		} elseif ( ! empty( $sorted ) ) {

			foreach ( $sorted as $key => $value ) {
				unset( $spam_logs['records'][ $key ] );
			}

			$spam_logs['spam_count'] = count( $spam_logs['records'] );

		} else {
			return false;
		}

		( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_performance_cache( 'delete', 'setting', 'logs', ONECOM_SP_PLUGIN_SLUG, $additional_info ) : '' );

		oc_save_sp_options( $spam_logs, 'onecom_sp_spam_logs' );
	}

	/**
	 * @param $start_date
	 * @param $end_date
	 * @param $unsorted_arr
	 * @param $case
	 * @return array
	 *
	 * generates a sorted array based on start & end dates.
	 */
	public function oc_get_sorted_list( $start_date, $end_date, $unsorted_arr, $case ) {

		$sorted = array();
		if ( is_array( $unsorted_arr ) ) {
			if ( $case == 'clearLog' ) {
				foreach ( $unsorted_arr as $date => $value ) {

					if ( strtotime( $date ) <= $start_date ) {

						$sorted[ $date ] = $value;

					} elseif ( strtotime( $date ) > $end_date ) {
						break;
					}
				}
			} elseif ( $case == 'summary' ) {
				foreach ( $unsorted_arr as $date => $value ) {

					if ( strtotime( $date ) >= $start_date ) {

						$sorted[ $date ] = $value;

					} elseif ( strtotime( $date ) > $end_date ) {
						break;
					}
				}
			}
		}
		return $sorted;
	}

	/**
	 * @param $check
	 * @return string
	 * generates the description which appears before table on spam diagnostics page.
	 */
	public function oc_get_table_description( $check ) {
		$desc = '';

		$desc .= "<p class='table_desc'><strong>" . __( 'The diagnostics table consists of:', OC_SP_TEXTDOMAIN ) . '</strong></p>';
		$desc .= '<ul><li>' . __( 'The list of checks which are performed on the provided input.', OC_SP_TEXTDOMAIN ) . self::LI_CLOSE;
		$desc .= self::LI_START . __( 'Results of the checks which have been performed.', OC_SP_TEXTDOMAIN ) . self::LI_CLOSE;
		if ( $check == 'website-check' ) {
			$desc .= self::LI_START . __( 'The status of spam protection settings.', OC_SP_TEXTDOMAIN ) . self::LI_CLOSE;
		} else {
			$desc .= self::LI_START . __( 'The reason due which this has been diagnosed as spam.', OC_SP_TEXTDOMAIN ) . '</li></ul>';
		}
		return $desc;
	}


	/**
	 * @param $user_agent
	 * @return mixed|string
	 * checks for the provided useragents against the whitelisted user agents
	 */
	public function oc_get_whitelisted_useragents( $user_agent ) {

		if ( isset( $this->sp_protection_options['whitelist_agents'] )
			&& ! empty(
				$this->sp_protection_options['whitelist_agents']
				&& $this->sp_protection_options['checks']['oc_spbadusragent']
			) == 'true' ) {
			foreach ( $this->sp_protection_options['whitelist_agents'] as $agent ) {
				if ( stripos( $user_agent, $agent ) !== false ) {
					$user_agent = '';
					break;
				}
			}
		}
		return $user_agent;
	}


	/**
	 * @param $username
	 * @return mixed|string
	 * checks for the username provided against the registered and white listed user names.
	 */
	public function oc_get_whitelisted_users( $username ) {
		$whitelisted_users = array();
		$wp_reg_users      = get_users( array( 'fields' => array( 'user_login' ) ) );

		foreach ( $wp_reg_users as $user ) {
			$whitelisted_users[] = $user->user_login;
		}
		if (
			isset( $this->sp_protection_options['whitelist_usernames'] )
				&& ! empty( $this->sp_protection_options['whitelist_usernames'] )
				&& ( $this->sp_protection_options['checks']['oc_sp_whitelistuser'] == 'true' )
		) {
			foreach ( $this->sp_protection_options['whitelist_usernames'] as $user_name ) {

				$whitelisted_users[] = $user_name;
			}
		}
		foreach ( $whitelisted_users as $whitelisted_user ) {
			if ( strcasecmp( $whitelisted_user, $username ) == 0 ) {
				$username = '';
				break;
			}
		}
		return $username;
	}

	/**
	 * @param string $type
	 * @return string
	 * returns the submit button for settings page
	 */
	public function oc_generate_submit_button( $type = 'regular' ) {

		if ( $type == 'regular' ) {
			return '<input class="oc-save oc-regular-btn" type="submit" disabled name="one_sp_submit" value="' . __( 'Save', OC_SP_TEXTDOMAIN ) . '"/><span id="oc_sp_spinner" class="oc_cb_spinner spinner"></span>';
		} elseif ( $type == 'float' ) {
			return '<input class="oc-save oc-sp-float-btn" type="submit" disabled name="one_sp_submit" value="' . __( 'Save', OC_SP_TEXTDOMAIN ) . '"/><span id="oc_sp_spinner" class="oc_cb_spinner spinner float-spinner"></span>';
		} else {
			return '<span id="oc_sp_spinner" class="oc_cb_spinner spinner"></span><input class="oc-save" type="submit" disabled name="one_sp_submit" value="' . __( 'Save', OC_SP_TEXTDOMAIN ) . '"/>';

		}
	}

	public function oc_save_settings() {
		if (
			! empty( $_POST ) &&
			isset( $_POST['checks']['one_sp_nonce'] ) &&
			wp_verify_nonce( $_POST['checks']['one_sp_nonce'], 'one_sp_nonce' )
		) {
			$sp_options = oc_get_sp_options( 'onecom_sp_protect_options' );
			unset( $_POST['action'] );
			unset( $_POST['one_sp_nonce'] );

			$merged_options = array_replace_recursive( $sp_options, $_POST );
			unset( $merged_options['checks']['one_sp_nonce'] );
			$additional_info = array( 'settings_status' => json_encode( $merged_options['checks'] ) );
			oc_save_sp_options( $merged_options, 'onecom_sp_protect_options' );
			( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_performance_cache( 'save', 'setting', 'setting', ONECOM_SP_PLUGIN_SLUG, $additional_info ) : '' );

			wp_send_json_success(
				__( 'Protection settings updated!' )
			);
			return false;
		}
		wp_send_json_error();
	}

	public function oc_save_advanced_settings() {
		if (
			! empty( $_POST ) &&
			isset( $_POST['one_sp_nonce'] ) &&
			wp_verify_nonce( $_POST['one_sp_nonce'], 'one_sp_nonce' )
		) {
			$sp_options = oc_get_sp_options( 'onecom_sp_protect_options' );
			unset( $_POST['action'] );
			unset( $_POST['one_sp_nonce'] );

			$advance_options = array(
				'oc_spbadusragent',
				'oc_sp_urlshort',
				'oc_sp_proburl',
				'oc_sp_whitelistuser',
				'oc_spwhitelistip',
			);

			foreach ( $advance_options as $option ) {

				$option_val = 'false';
				if ( array_key_exists( $option, $_POST ) ) {
					$option_val = $_POST[ $option ];

				}
				$sp_options['checks'][ $option ] = $option_val;
			}
			$sp_options['whitelist_usernames'] = $this->oc_sanitise_options( 'whitelist_usernames', $_POST, 'whitelist_usernames', $sp_options );
			$sp_options['whitelist_ips']       = $this->oc_sanitise_options( 'whitelist_ips', $_POST, 'whitelist_ips', $sp_options );
			$sp_options['exploit-urls']        = $this->oc_sanitise_options( 'exploit_urls', $_POST, 'exploit-urls', $sp_options );
			$sp_options['url-shortners']       = $this->oc_sanitise_options( 'url_shorteners', $_POST, 'url-shortners', $sp_options );
			$sp_options['whitelist_agents']    = $this->oc_sanitise_options( 'whitelist_agents', $_POST, 'whitelist_agents', $sp_options );
			oc_save_sp_options( $sp_options, 'onecom_sp_protect_options' );
			wp_send_json_success(
				__( 'Advanced settings updated!' )
			);
			return false;
		}
		wp_send_json_error();
	}

	public function oc_sanitise_options( $post_variable, $post_array, $option_name, $sp_options ) {
		if ( array_key_exists( $post_variable, $post_array ) && is_array( $post_array[ $post_variable ] ) ) {

			$option_list = array();
			foreach ( $post_array[ $post_variable ] as $variable ) {
				$variable = trim( stripslashes( $variable ) );
				if ( ! empty( $variable ) ) {
					$option_list[] = $variable;
				}
			}

			return $option_list;
		} elseif ( array_key_exists( $post_variable, $post_array ) && ! is_array( $post_array[ $post_variable ] ) ) {
			return array();
		} elseif ( isset( $sp_options[ $option_name ] ) ) {
			return $sp_options[ $option_name ];
		} else {
			return array();
		}
	}

	public function oc_check_spam_diagnostics() {
		if (
			! empty( $_POST ) &&
			isset( $_POST['one_sp_nonce'] ) &&
			wp_verify_nonce( $_POST['one_sp_nonce'], 'one_sp_nonce' )
		) {

			$user_ip      = isset( $_POST['oc_validate_ip'] ) ? $_POST['oc_validate_ip'] : '';
			$user_name    = isset( $_POST['oc_validate_user'] ) ? $_POST['oc_validate_user'] : '';
			$user_email   = isset( $_POST['oc_validate_email'] ) ? $_POST['oc_validate_email'] : '';
			$user_agent   = isset( $_POST['oc_validate_user_agent'] ) ? $_POST['oc_validate_user_agent'] : '';
			$user_content = isset( $_POST['oc_validate_content'] ) ? $_POST['oc_validate_content'] : '';

			if ( strlen( $user_email ) > 80 ) {
				$user_email = substr( $user_email, 0, 77 ) . '...';
			}
			if ( strlen( $user_name ) > 80 ) {
				$user_name = substr( $user_name, 0, 77 ) . '...';
			}

			$api_check        = $this->sp_api_check( $user_ip, $user_email, $user_agent, '' );
			$api_check_result = array();

			if ( isset( $api_check['is_spam'] ) && $api_check['is_spam'] ) {

				$api_check_result['status'] = $api_check['is_spam'];
				$api_check_result['reason'] = $api_check['reason'];

				$table = $this->oc_generate_table( 'api-check', $api_check_result );
			} elseif ( isset( $api_check['is_spam'] ) && ! $api_check['is_spam'] ) {

				$web     = new OnecomSpWebsiteCheck();
				$oc_post = array(
					'email'   => $user_email,
					'author'  => $user_name,
					'comment' => $user_content,
				);

				$webiste_check = $web->oc_sp_diagnostics( $user_ip, $oc_post );

				$table = $this->oc_generate_table( 'website-check', $webiste_check );

			}
			if ( $table && $table !== '' ) {

				wp_send_json_success( $table );
				return false;
			}
		}
		wp_send_json_error();
		return false;
	}

	public function oc_clear_spam_logs() {
		if (
			isset( $_POST['one_sp_nonce'] ) &&
			wp_verify_nonce( $_POST['one_sp_nonce'], 'one_sp_nonce' )
		) {
			$spam            = oc_get_sp_options( 'onecom_sp_spam_logs' );
			$additional_info = array(
				'additional_info' => json_encode(
					array(
						'logs_cleared_by' => 'manually_cleared',
						'blocked_spams'   => $spam['spam_count'] ?? '',

					)
				),
			);

			( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_performance_cache( 'delete', 'setting', 'logs', ONECOM_SP_PLUGIN_SLUG, $additional_info ) : '' );

			$spam['records']    = array();
			$spam['spam_count'] = 0;

			oc_save_sp_options( $spam, 'onecom_sp_spam_logs' );
			wp_send_json_success( '<p>' . __( 'No logs found!', OC_SP_TEXTDOMAIN ) . '</p></div></div>' );
			return false;
		}
		wp_send_json_error();
	}
}
