<?php
class OcSpLong {

	public function execute(
		&$sp_options = array(),
		&$oc_post = array()
	) {

		if ( array_key_exists( 'email', $oc_post ) ) {
			$user_email = $oc_post['email'];
			if ( ! empty( $user_email ) && strlen( $user_email ) > 64 ) {
				return '' . __( 'Email exceeds the allowed character limit', 'onecom-sp' ) . " : $user_email";
			}
		}
		if ( array_key_exists( 'author', $oc_post ) && ! empty( $oc_post['author'] ) ) {
			$username = $oc_post['author'];
			if ( strlen( $oc_post['author'] ) > 64 ) {
				return '' . __( 'Username exceeds the allowed character limit', 'onecom-sp' ) . " : $username";
			}
		}

		return false;
	}
}
