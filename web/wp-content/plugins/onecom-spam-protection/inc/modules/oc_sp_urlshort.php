<?php

class OcSpUrlshort {

	public function execute(
		&$sp_options = array(),
		&$oc_post = array()
	) {

		$url_shorteners = $sp_options['url-shortners'];

		foreach ( $oc_post as $key => $post ) {
			if ( ! empty( $post ) ) {
				foreach ( $url_shorteners as $shortener ) {

					// checks redefined for better detection of url shortners in the post fields
					if ( stripos( $post, $shortener ) !== false
						&& ( stripos( $post, $shortener ) == 0
							|| substr( $post, stripos( $post, $shortener ) - 1, 1 ) == ' '
							|| substr( $post, stripos( $post, $shortener ) - 1, 1 ) == '/'
							|| substr( $post, stripos( $post, $shortener ) - 1, 1 ) == '@'
							|| substr( $post, stripos( $post, $shortener ) - 1, 1 ) == '.' ) ) {

						return "URL shortner detected: $shortener in $key";
					}
				}
			}
		}

		return false;
	}
}
