<?php

trait OnecomLite {

	public function onecom_is_premium( $call_for = null ) {
		$features = oc_set_premi_flag();
		if ( $call_for === null ) {
			if ( isset( $features['data'] ) && ( ! empty( $features['data'] ) ) && ( in_array( 'MWP_ADDON', $features['data'] ) ) ) {
				return true;
			}
			return false;
		} elseif ( $call_for === 'all_plugins' ) {
			if (
				( isset( $features['data'] ) && empty( $features['data'] ) )
				|| (
					in_array( 'ONE_CLICK_INSTALL', (array) $features['data'] )
					|| in_array( 'MWP_ADDON', (array) $features['data'] )
				)
			) {
				return true;
			}
			return false;
		}
	}

	public function onecom_premium_filter( $subtitle ) {
		if ( ! $this->onecom_is_premium() ) {
			return '';
		}

		return $subtitle;
	}
}
