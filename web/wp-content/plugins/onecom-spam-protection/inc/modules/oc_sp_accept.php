<?php
class OcSPAccept {


	public function execute(
		&$sp_options = array(),
		&$oc_post = array()
	) {
		if ( array_key_exists( 'HTTP_ACCEPT', $_SERVER ) ) {
			return false;
		}
		return __( 'HTTP_ACCEPT header', 'onecom-sp' ) . '&nbsp' . __( 'Not found', 'onecom-sp' ) . '.';
	}
}
