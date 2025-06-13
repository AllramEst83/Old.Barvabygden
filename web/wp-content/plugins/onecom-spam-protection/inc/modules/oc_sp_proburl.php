<?php
class OcSpProburl {

	public function execute(
		&$sp_options = array(),
		&$oc_post = array()
	) {
		$url = OnecomSp::oc_get_spam_url();
		foreach ( $sp_options['exploit-urls'] as $exploits ) {

			if ( strpos( $url, $exploits ) !== false ) {

				return __( '404 exploit probing detected!', 'onecom-sp' ) . ': ' . $url;
			}
		}

		return false;
	}
}
