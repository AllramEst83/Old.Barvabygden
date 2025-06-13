<?php

/**
 * Fired at different events/actions in the plugin
 *
 * This class push analytics stats & logs
 *
 * @since      0.2.0
 * @package    Under_Construction
 * @subpackage OCUC_Stats
 */

// Exit if file accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OCUC_Stats {


	// Constructor
	public function __construct() {
		// update_option_name fires when options actually updated with new value
		add_action( 'update_option_onecom_under_construction_info', array( $this, 'uc_stats_info' ), 10, 2 );
	}

	/**
	 * UC enable/disable stats
	 */
	public function uc_stats_info( $old_uc_data, $new_uc_data ) {
		global $pagenow;
		if ( 'options.php' === $pagenow ) {
			$referrer = 'plugins_page';
		} else {
			$referrer = 'install_wizard';
		}

		// prepare information for log/stats push
		$new_uc_data['uc_logo']              = isset( $new_uc_data['uc_logo'] ) && ! empty( $new_uc_data['uc_logo'] ) ? '1' : '0';
		$new_uc_data['uc_favicon']           = isset( $new_uc_data['uc_favicon'] ) && ! empty( $new_uc_data['uc_favicon'] ) ? '1' : '0';
		$new_uc_data['uc_headline']          = isset( $new_uc_data['uc_headline'] ) && ! empty( $new_uc_data['uc_headline'] ) ? '1' : '0';
		$new_uc_data['uc_description']       = isset( $new_uc_data['uc_description'] ) && ! empty( $new_uc_data['uc_description'] ) ? '1' : '0';
		$new_uc_data['uc_seo_title']         = isset( $new_uc_data['uc_seo_title'] ) && ! empty( $new_uc_data['uc_seo_title'] ) ? '1' : '0';
		$new_uc_data['uc_seo_description']   = isset( $new_uc_data['uc_seo_description'] ) && ! empty( $new_uc_data['uc_seo_description'] ) ? '1' : '0';
		$new_uc_data['uc_custom_css']        = isset( $new_uc_data['uc_custom_css'] ) && ! empty( $new_uc_data['uc_custom_css'] ) ? '1' : '0';
		$new_uc_data['uc_scripts']           = isset( $new_uc_data['uc_scripts'] ) && ! empty( $new_uc_data['uc_scripts'] ) ? '1' : '0';
		$new_uc_data['uc_footer_scripts']    = isset( $new_uc_data['uc_footer_scripts'] ) && ! empty( $new_uc_data['uc_footer_scripts'] ) ? '1' : '0';
		$new_uc_data['uc_copyright']         = isset( $new_uc_data['uc_copyright'] ) && ! empty( $new_uc_data['uc_copyright'] ) ? '1' : '0';
		$new_uc_data['uc_facebook_url']      = isset( $new_uc_data['uc_facebook_url'] ) && ! empty( $new_uc_data['uc_facebook_url'] ) ? '1' : '0';
		$new_uc_data['uc_twitter_url']       = isset( $new_uc_data['uc_twitter_url'] ) && ! empty( $new_uc_data['uc_twitter_url'] ) ? '1' : '0';
		$new_uc_data['uc_instagram_url']     = isset( $new_uc_data['uc_instagram_url'] ) && ! empty( $new_uc_data['uc_instagram_url'] ) ? '1' : '0';
		$new_uc_data['uc_linkedin_url']      = isset( $new_uc_data['uc_linkedin_url'] ) && ! empty( $new_uc_data['uc_linkedin_url'] ) ? '1' : '0';
		$new_uc_data['uc_youtube_url']       = isset( $new_uc_data['uc_youtube_url'] ) && ! empty( $new_uc_data['uc_youtube_url'] ) ? '1' : '0';
		$new_uc_data['uc_page_bg_image']     = isset( $new_uc_data['uc_page_bg_image'] ) && ! empty( $new_uc_data['uc_page_bg_image'] ) ? '1' : '0';
		$new_uc_data['uc_page_bg_color']     = isset( $new_uc_data['uc_page_bg_color'] ) && ! empty( $new_uc_data['uc_page_bg_color'] ) ? '1' : '0';
		$new_uc_data['uc_primary_color']     = isset( $new_uc_data['uc_primary_color'] ) && ! empty( $new_uc_data['uc_primary_color'] ) ? '1' : '0';
		$new_uc_data['uc_whitelisted_roles'] = isset( $new_uc_data['uc_whitelisted_roles'] ) && ! empty( $new_uc_data['uc_whitelisted_roles'] ) ? '1' : '0';
		$new_uc_data['uc_exclude_pages']     = isset( $new_uc_data['uc_exclude_pages'] ) && ! empty( $new_uc_data['uc_exclude_pages'] ) ? '1' : '0';
		$uc_status                           = isset( $new_uc_data['uc_status'] ) ? $new_uc_data['uc_status'] : '';

		// prevent some information from log/stats push
		unset( $new_uc_data['uc_timer'] );

		// trigger plugin enable/disable stats
		if ( 'on' === $uc_status ) {
			$this->ocuc_stats_push( 'enable', 'setting', ONECOM_UC_PLUGIN_SLUG, $referrer, $new_uc_data );
		} elseif ( 'off' === $uc_status ) {
			$this->ocuc_stats_push( 'disable', 'setting', ONECOM_UC_PLUGIN_SLUG, $referrer, $new_uc_data );
		}
	}

	/**
	 * Function to push stats for events of under-construction
	 */
	public static function ocuc_stats_push(
		$event_action,
		$item_category = null,
		$item_name = null,
		$referrer = null,
		$additional_info = array()
	) {
		if ( class_exists( 'OCPushStats' ) ) {
			$result         = oc_set_premi_flag();
			$item_avail     = (int) oc_pm_features( 'ins', $result['data'] );
			$base_params    = \OCPushStats::stats_base_parametres();
			$dynamic_params = array(
				\OCPushStats::HIT_TYPE      => \OCPushStats::EVENT,
				\OCPushStats::EVENT_ACTION  => $event_action,
				\OCPushStats::ITEM_CATEGORY => $item_category,
				\OCPushStats::ITEM_NAME     => $item_name,
				\OCPushStats::REFERRER      => $referrer,
				\OCPushStats::ITEM_AVAIL    => "$item_avail",
			);

			$dynamic_params = array_filter(
				$dynamic_params,
				function ( $value ) {
					return ! is_null( $value ) && '' !== $value;
				}
			);

			if ( ! empty( $additional_info ) ) {
				$dynamic_params = array_merge( $dynamic_params, $additional_info );
			}
			$payload = json_encode( array_merge( $base_params, $dynamic_params ) );

			return \OCPushStats::curl_request( $payload );
		}
	}
}

$stats = new OCUC_Stats();
