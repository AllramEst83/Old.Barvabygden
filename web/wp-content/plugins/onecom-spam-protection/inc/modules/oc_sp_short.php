<?php
class OcSpShort {

	public function execute(
		&$sp_options = array(),
		&$oc_post = array()
	) {
		if ( array_key_exists( 'email', $oc_post ) ) {
			$user_email = $oc_post['email'];
			if ( ! empty( $user_email ) && strlen( $user_email ) >= 1 && strlen( $user_email ) < 5 ) {
				return "Email Too Short: $user_email";
			}
		}
		if ( array_key_exists( 'author', $oc_post ) ) {
			$user_name = $oc_post['author'];

			if ( ! empty( $user_name ) && strlen( $user_name ) >= 1 && strlen( $user_name ) < 3 ) {
				return "Author Too Short: $user_name";
			}
		}
		return false;
	}
}
