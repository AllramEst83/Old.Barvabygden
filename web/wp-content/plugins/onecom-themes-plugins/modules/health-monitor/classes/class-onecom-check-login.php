<?php
declare(strict_types=1);

class OnecomCheckLogin extends OnecomHealthMonitor {


	public $hm_data           = array();
	public $login_masking_key = 'onecom_login_masking';

	public function __construct() {

		parent::__construct();
		$this->hm_data = get_option( $this->option_key );
	}

	public function init() {
		add_action( 'wp_login_failed', array( $this, 'log_failed_login' ) );
		if (
			$this->hm_data
			&& isset(
				$this->hm_data['login_recaptcha'],
				$this->hm_data['recaptcha_keys'],
				$this->hm_data['recaptcha_keys']['oc_hm_site_key'],
				$this->hm_data['recaptcha_keys']['oc_hm_site_secret']
			)
			&& $this->hm_data['login_recaptcha']
			&& $this->hm_data['recaptcha_keys']
			&& $this->hm_data['recaptcha_keys']['oc_hm_site_key']
			&& $this->hm_data['recaptcha_keys']['oc_hm_site_secret']
		) {
			add_action( 'login_form', array( $this, 'login_form' ) );
			add_filter( 'wp_authenticate_user', array( $this, 'verify_login_form' ), 10, 3 );
		}

		$expiry = get_option( 'oc_modified_cookie_expiry', false );
		if ( ! $expiry ) {
			return;
		}
		add_filter( 'auth_cookie_expiration', array( $this, 'modify_logout_duration' ), 10, 3 );
	}

	public function check_logout_time() {
		$logout_time = get_option( 'oc_modified_cookie_expiry', false );
		if ( ! $logout_time ) {
			return $this->format_result( $this->flag_open, __( 'You are using the default login expiration.', 'onecom-wp' ), __( 'Current settings logout users after 48 hours. Consider shortening this duration.', 'onecom-wp' ) );
		}

		return $this->format_result( $this->flag_resolved, __( 'You are using optimal logout duration.', 'onecom-wp' ) );
	}

	public function fix_check_logout_time() {
		if ( update_option( 'oc_modified_cookie_expiry', true, 'no' ) ) {
			return $this->format_result(
				$this->flag_resolved,
				$this->text['logout_duration'][ $this->fix_confirmation ],
				$this->text['logout_duration'][ $this->status_desc ][ $this->status_resolved ]
			);
		} else {
			return $this->format_result( $this->flag_open, __( 'Failed to fix logout duration', 'onecom-wp' ) );
		}
	}

	public function undo_check_logout_time() {
		if ( update_option( 'oc_modified_cookie_expiry', false, 'no' ) ) {
			$check = 'logout_duration';

			return array(
				$this->status_key      => $this->flag_resolved,
				$this->fix_button_text => $this->text[ $check ][ $this->fix_button_text ],
				$this->desc_key        => $this->text[ $check ][ $this->status_desc ][ $this->status_open ],
				$this->how_to_fix      => $this->text[ $check ][ $this->how_to_fix ],
				'ignore_text'          => $this->ignore_text,
			);
		} else {
			return $this->format_result( $this->flag_open, __( 'Failed to rollback', 'onecom-wp' ) );
		}
	}

	public function modify_logout_duration( $expiry, $user_id, $remember ) {
		$expiry;
		$user_id;
		$remember;

		return 28800;
	}

	/**
	 * store usernames and emails for which login attempts failed.
	 *
	 * @param $username
	 */
	public function log_failed_login( $username ) {
		if ( ! ( username_exists( $username ) || email_exists( $username ) ) ) {
			return;
		}
		$failed_hm_login = get_option( $this->option_key, array() );
		if ( ! isset( $failed_hm_login['failed_logins'] ) ) {
			$failed_hm_login['failed_logins'] = array();
		}

		$failed_hm_login['failed_logins'][ $username ] = date( 'Y-m-d H:i' );
		update_option( $this->option_key, $failed_hm_login, 'no' );
	}

	/**
	 * check if there was any failed login attempt for any username that matches
	 * any of the existing users
	 */
	public function check_failed_login(): array {
		$logins = get_option( $this->option_key );
		if ( ! isset( $logins['failed_logins'] ) ) {
			return $this->format_result( $this->flag_resolved, __( 'There were no failed login attempts', 'onecom-wp' ) );
		}
		$result = $this->format_result( $this->flag_open, __( 'There were some failed login attempts.', 'onecom-wp' ), __( 'There were some of the failed login attempts for existing users. Consider changing the username for following users.', 'onecom-wp' ) );
		if ( $logins['failed_logins'] ) {
			$result['file-list'] = $logins['failed_logins'];
		}

		return $result;
	}

	/**
	 * Reset the log of failed login attempts.
	 * @return array
	 */
	public function reset_failed_login_data(): array {
		$hm_data_obj = get_option( $this->option_key );
		unset( $hm_data_obj['failed_logins'] );
		if ( update_option( $this->option_key, $hm_data_obj, 'no' ) ) {
			return $this->format_result( $this->flag_resolved, __( 'Failed login data reset', 'onecom-wp' ) );
		}

		return $this->format_result( $this->flag_open, __( 'Unable to reset login attempts', 'onecom-wp' ) );
	}

	public function login_recaptcha(): array {
		$hm_data_obj = get_option( $this->option_key );
		if ( isset( $hm_data_obj['login_recaptcha'] ) && $hm_data_obj['login_recaptcha'] ) {
			return $this->format_result( $this->flag_resolved );
		}

		return $this->format_result( $this->flag_open );
	}

	public function fix_login_recaptcha( $data ) {
		$hm_data_obj                    = get_option( $this->option_key );
		$hm_data_obj['recaptcha_keys']  = $data['inputs'];
		$hm_data_obj['login_recaptcha'] = true;
		update_option( $this->option_key, $hm_data_obj, 'no' );

		return $this->format_result(
			$this->flag_resolved,
			$this->text['login_recaptcha'][ $this->fix_confirmation ],
			$this->text['login_recaptcha'][ $this->status_desc ][ $this->status_resolved ]
		);
	}

	public function undo_login_recaptcha() {
		$hm_data_obj                    = get_option( $this->option_key );
		$hm_data_obj['login_recaptcha'] = false;

		if ( update_option( $this->option_key, $hm_data_obj, 'no' ) ) {
			$check = 'login_recaptcha';

			return array(
				$this->status_key      => $this->flag_resolved,
				$this->fix_button_text => $this->text[ $check ][ $this->fix_button_text ],
				$this->desc_key        => $this->text[ $check ][ $this->status_desc ][ $this->status_open ],
				$this->how_to_fix      => $this->text[ $check ][ $this->how_to_fix ],
				'ignore_text'          => $this->ignore_text,
			);

		} else {
			return $this->format_result( $this->status_open );
		}
	}

	public function login_form() {
		wp_enqueue_script( 'oc-google-recaptcha', 'https://www.google.com/recaptcha/api.js' );
		?>
		<style>
			.g-recaptcha {
				transform: scale(.9);
				-webkit-transform: scale(.9);
				transform-origin: 0 0;
				-webkit-transform-origin: 0 0;
			}
		</style>
		<p>
			<label for="recaptcha"><br/>
		<div class="g-recaptcha" data-sitekey="<?php echo $this->hm_data['recaptcha_keys']['oc_hm_site_key']; ?>"></div>
		</label>
		</p>
		<?php
	}

	public function verify_login_form( $user, $password ) {
		$password;
		$secretkey = $this->hm_data['recaptcha_keys']['oc_hm_site_secret'];
		if ( isset( $_POST['g-recaptcha-response'] ) ) {
			$response = wp_remote_get( 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secretkey . '&response=' . $_POST['g-recaptcha-response'] );
			$response = json_decode( $response['body'], true );
			if ( $response['success'] ) {
				return $user;
			} else {
				return new WP_Error( 'Captcha Invalid', '<strong>' . __( 'Invalid captcha value', 'onecom-wp' ) . '</strong>' );
			}
		} else {
			return new WP_Error( 'Captcha Invalid', '<strong>' . __( 'Invalid captcha value', 'onecom-wp' ) . '</strong>' );
		}
	}
}