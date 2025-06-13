<?php

/**
 * Captcha implementation for newsletter
 *
 * @since      0.1.0
 * @package    Under_Construction
 * @subpackage OCUC_Captcha
 */

// Exit if file accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OCUC_Captcha {

	private $key = '1ASD2A4D2AA4DA15A';

	/**
	 * Function captcha_fields
	 * Return HTML string contaning the fields that will be used in forms to track
	 * token etc.
	 *
	 * @param void
	 *
	 * @return string
	 */
	public function captcha_fields() {
		$oc_token = $this->captcha_string();
		$fields   = '<div class="oc-captcha-wrap"><label class="d-block">' . __( 'Type in the answer:', 'onecom-uc' ) . '</label><span class="d-inline-block oc-cap-container"><img alt="Please reload" src="' . ONECOM_UC_DIR_URL . '/inc/modules/captcha-image.php?i=' . $oc_token . '" />'
			. '<input type="text" name="oc_captcha_val" class="oc-captcha-val"  placeholder="?" maxlength="3" required="required" /></span>'
			. '<input type="hidden" name="oc_cpt" value="' . $oc_token . '" />'
			. '<input type="text" name="oc_csrf_token" value="" class="oc_csrf_token" /></div>';

		return $fields;
	}

	/**
	 * Function oc_get_captcha_string
	 * Generate a token to be used to add value in captcha
	 *
	 * @param void
	 *
	 * @return string
	 */
	public function captcha_string( $echo_display = false ) {
		$num1  = rand( 0, 10 );
		$num2  = rand( 1, 10 );
		$token = $this->key . base64_encode( $num1 . '#' . $num2 );
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && $echo_display ) {
			wp_send_json(
				array(
					'token' => $token,
					'image' => ONECOM_UC_DIR_URL . '/inc/modules/captcha-image.php?i=' . $token,
				)
			);
			wp_die();
		}

		return $token;
	}

	/**
	 * Function secure_form
	 * Secure form submission, try to block spams by using captcha and honeypot
	 *
	 * @param void
	 *
	 * @return void
	 */
	public function secure_form() {
		/* Check Captcha */
		if (
			! isset( $_POST['oc_cpt'] ) || ! isset( $_POST['oc_captcha_val'] ) || ! $_POST['oc_captcha_val']
			|| ! $_POST['oc_cpt'] || ! $this->validate_captcha( $_POST['oc_captcha_val'], $_POST['oc_cpt'] )
		) {
			wp_send_json(
				array(
					'type' => 'error',
					'text' => __( 'Invalid answer, please try again.', 'onecom-uc' ),
				)
			);
		}

		/** Check Honey Pot field */

		if ( ! isset( $_POST['oc_csrf_token'] ) || '' !== $_POST['oc_csrf_token'] ) {

			wp_send_json(
				array(
					'status' => 'error',
					'text'   => __( 'Some error occurred, please reload the page and try again.', 'onecom-uc' ),
				)
			);
		}
	}
	/**
	 * Function validate_captcha
	 * Check if incoming value of captcha is valid
	 *
	 * @param $value , string that user entered as captcha solution
	 * @param $encrypted_val , string the token that was used to generate captcha
	 *
	 * @return string
	 */
	public function validate_captcha( $value, $encrypted_val ) {
		$decrypted_value = base64_decode( str_replace( $this->key, '', $encrypted_val ) );
		if ( ! $decrypted_value ) {
			return false;
		}
		$exploded = explode( '#', $decrypted_value );

		if ( count( $exploded ) < 2 ) {
			return false;
		}

		return ( intval( $exploded[0] ) + intval( $exploded[1] ) ) === intval( $value );
	}
}
