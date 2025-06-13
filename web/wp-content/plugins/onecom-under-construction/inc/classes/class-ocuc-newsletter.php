<?php

/**
 * Defines newsletter with captcha
 *
 * @since      0.1.0
 * @package    Under_Construction
 * @subpackage OCUC_Newsletter
 */

// Exit if file accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OCUC_Newsletter extends OCUC_Captcha {

	public $captcha;
	const EMAIL      = 'email';
	const ERROR      = 'error';
	const OC_STATUS  = 'status';
	const OC_SUCCESS = 'success';

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		$this->captcha = new OCUC_Captcha();
		// Ajax handles is working if out of class
		//add_action('wp_ajax_oc_newsleter_sub', array($this, 'newsletter_cb'));
		//add_action('wp_ajax_nopriv_oc_newsleter_sub', array($this, 'newsletter_cb'));
	}

	/**
	 * Front-end display of widget.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Saved values from database.
	 *
	 * @see WP_Widget::widget()
	 *
	 */
	public function subscriber_form() {
		$uc_data          = new OCUC_Render_Views();
		$uc_option        = $uc_data->get_uc_option();
		$any_one_register = get_option( 'users_can_register' );
		if ( 'off' === $uc_option['uc_subscribe_form'] ) {
			return;
		}

		if ( ! $any_one_register ) {
			echo $this->signup_message();
		} else {
			?>
			<div class="newsletter">
				<div class="row">
					<div class="col-sm-12">
						<div class="content">
							<form class="form oc-newsletter-form" action="">
								<div class="input-group">
									<input type="email" class="form-control oc-newsletter-input"
										   placeholder="<?php _e('Please enter your email.', 'onecom-uc'); ?>"
										   required="required"/>
									<?php
									wp_nonce_field( 'newsletter-submit', 'oc-newsletter-nonce' );
									echo $this->captcha->captcha_fields();
									?>
									<span class="input-group-btn">
										<button type="submit" class="btn oc-newsletter-submit">
											<?php
											_e( 'Submit', 'onecom-uc' );
											?>
										</button>
										<i aria-hidden="true" class="fas fa-circle-notch fa-spin oc-spinner d-none ml-2 mt-2"></i>
									</span>

								</div>
							</form>
							<p class="oc-message mt-4 mb-0"></p>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
	}

	public function signup_message() {
		$message = '<div class="newsletter"><p>' . __( 'To enable newsletter subscriptions user registrations must be allowed.', 'onecom-uc' );

		if ( is_user_logged_in() ) {
			$message .= sprintf( '</p><a target="_blank" href="%s"><u>' . __( 'Anyone can register', 'onecom-uc' ) . '?' . '</u></a>', admin_url( 'options-general.php' ) );
		}

		$message .= '</div>';
		return $message;
	}

	public static function newsletter_cb() {

		/* Check Nonce */
		if ( ! wp_verify_nonce( $_POST['oc-newsletter-nonce'], 'newsletter-submit' ) ) {

			wp_send_json(
				array(
					'type' => self::ERROR,
					'text' => __( 'Invalid security token, please reload the page and try again.', 'onecom-uc' ),
				)
			);
		}
		$captcha = new OCUC_Captcha();
		$captcha->secure_form();
		/* Check Length of the parameters being received from POST request */
		$output = array();
		if ( ! ( strlen( trim( $_POST[ self::EMAIL ] ) ) && filter_var( $_POST[ self::EMAIL ], FILTER_VALIDATE_EMAIL ) ) ) {
			$output = array(
				'type' => self::ERROR,
				'text' => __( 'Email entered is not valid or empty.', 'onecom-uc' ),
			);
			wp_send_json( $output );
		}
		if ( 200 < mb_strlen( $_POST[ self::EMAIL ], '8bit' ) ) {
			$output = array(
				'type' => self::ERROR,
				'text' => __( 'Email is too large, please use a valid email.', 'onecom-uc' ),
			);
			wp_send_json( $output );
		}

		$email = filter_var( mb_strtolower( $_POST['email'], 'UTF-8' ), FILTER_SANITIZE_EMAIL );

		if ( email_exists( $email ) ) {
			$output = array(
				'type' => self::ERROR,
				'text' => __( 'This email is already used. Please use a different email.', 'onecom-uc' ),
			);
			wp_send_json( $output );
		}

		/* Make user login from email id */
		$user_login = explode( '@', $email );
		if ( ! empty( $user_login ) ) {
			$user_login = $user_login[0];
		}
		/* avoid duplicates by adding extra digits in the username : day and month */
		$user_login .= gmdate( '_d_m' );

		/* register user */
		$register_subscriber = register_new_user( $email, $email );

		/* if error */
		if ( is_wp_error( $register_subscriber ) ) {
			$output = array(
				'type' => self::ERROR,
				'text' => $register_subscriber->get_error_message(),
			);
		} else {
			/* success */
			$output = array(
				self::OC_STATUS => self::OC_SUCCESS,
				'text'          => 'Subscribed successfully.',
				'id'            => $register_subscriber,
			);
		}
		if ( empty( $output ) ) {
			$output = array(
				self::OC_STATUS => self::ERROR,
				'text'          => __( 'Some error occurred, please reload the page and try again.', 'onecom-uc' ),
			);
		}
		wp_send_json( $output );
	}
}
