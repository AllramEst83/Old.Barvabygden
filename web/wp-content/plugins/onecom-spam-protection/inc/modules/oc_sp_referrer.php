<?php

class OcSpReferrer {
	public function execute(
		&$sp_options = array(),
		&$oc_post = array()
	) {

		if ( ! $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			return false;
		}
		if ( strpos( $_SERVER['REQUEST_URI'], '/wc-api/WC_Gateway_Paypal' ) !== false ) {
			return false;
		}

		$referrer = '';

		if ( array_key_exists( 'HTTP_REFERER', $_SERVER ) ) {
			$referrer = $_SERVER['HTTP_REFERER'];
		}

		$user_agent = '';
		if ( array_key_exists( 'HTTP_USER_AGENT', $_SERVER ) ) {
			$user_agent = $_SERVER['HTTP_USER_AGENT'];
		}

		$host = $_SERVER['HTTP_HOST'];
		if ( empty( $referrer ) ) {
			return 'Missing HTTP_REFERER';
		}
		if ( empty( $host ) ) {
			return 'Missing HTTP_HOST';
		}

		if ( strpos( strtolower( $referrer ), strtolower( $host ) ) === false ) {

			return 'Invalid HTTP_REFERER';

		}
		return false;
	}
}
