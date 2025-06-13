<?php
class OcSpBbcode {

	public function execute(
		&$sp_options = array(),
		&$oc_post = array()
	) {

		$bbcode_dict = array(
			'[php',
			'[url',
			'[link',
			'[img',
			'[include',
			'[script',
		);
		foreach ( $oc_post as $key => $val ) {
			foreach ( $bbcode_dict as $bbcode ) {
				if ( stripos( $val, $bbcode ) !== false ) {
					return " Detected BBCode $bbcode in $key";
				}
			}
		}
		return false;
	}
}
