<?php

/**
 * Render public functionals and views
 *
 * This class includes all actions that occur in the public area.
 *
 * @since      0.1.0
 * @package    Under_Construction
 * @subpackage OCUC_Render_Views
 */

// Exit if file accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OCUC_Render_Views {


	// Fetch under construction basic settings
	public static function get_uc_option() {
		return get_option( 'onecom_under_construction_info' );
	}

	// get uc info
	public function uc_status() {
		$uc_option = self::get_uc_option();
		return esc_html( $uc_option['uc_status'] );
	}

	// get uc headline
	public function uc_headline() {
		$uc_option = self::get_uc_option();
		return esc_html( $uc_option['uc_headline'] );
	}

	// Enquee timer js in head only if timer is enabled
	public function uc_enqueue_timer_js() {
		$uc_option = self::get_uc_option();
		if ( isset( $uc_option['uc_timer_switch'] ) && 'on' === $uc_option['uc_timer_switch'] ) {
			include_once ONECOM_UC_PLUGIN_URL . 'inc/modules/timer-js.php';
			$html = isset( $html ) ? $html : '';
		} else {
			$html = '';
		}
		return $html;
	}

	// get uc site title for meta title
	public function uc_site_title() {
		$site_title = get_bloginfo( 'name' );
		return esc_html( $site_title );
	}

	// get uc page background image
	public function uc_bg_image() {
		$uc_option        = self::get_uc_option();
		$uc_page_bg_image = isset( $uc_option['uc_page_bg_image'] ) && strlen( $uc_option['uc_page_bg_image'] ) ? $uc_option['uc_page_bg_image'] : '';
		return esc_html( $uc_page_bg_image );
	}

	// get uc page background color
	public function uc_bg_color() {
		$uc_option        = self::get_uc_option();
		$uc_page_bg_color = isset( $uc_option['uc_page_bg_color'] ) ? $uc_option['uc_page_bg_color'] : '';
		return esc_html( $uc_page_bg_color );
	}

	// get uc page description
	public function uc_description() {
		$uc_option      = self::get_uc_option();
		$uc_description = isset( $uc_option['uc_description'] ) ? $uc_option['uc_description'] : '';
		return nl2br( do_shortcode( $uc_description ) );
	}

	// get uc meta title
	public function uc_meta_title() {
		$uc_option    = self::get_uc_option();
		$uc_seo_title = isset( $uc_option['uc_seo_title'] ) ? $uc_option['uc_seo_title'] : '';
		return strip_tags( $uc_seo_title );
	}

	// get uc meta description
	public function uc_meta_description() {
		$uc_option          = self::get_uc_option();
		$uc_seo_description = isset( $uc_option['uc_seo_description'] ) ? $uc_option['uc_seo_description'] : '';
		return strip_tags( $uc_seo_description );
	}

	// get uc copyright text
	public function uc_copyright() {
		$uc_option    = self::get_uc_option();
		$uc_copyright = isset( $uc_option['uc_copyright'] ) ? $uc_option['uc_copyright'] : '';
		return nl2br( $uc_copyright );
	}

	// get uc logo
	public function uc_logo_title() {
		$uc_option = self::get_uc_option();
		if ( isset( $uc_option['uc_logo'] ) && strlen( $uc_option['uc_logo'] ) ) {
			$logo = $uc_option['uc_logo'];
			return sprintf( '<img src="%s" alt="%s" class="img-fluid" />', $logo, get_bloginfo( 'name' ) );
		} else {
			return '<h1>' . get_bloginfo( 'name' ) . '</h1>';
		}
	}

	// get uc logo
	public function uc_favicon() {
		$uc_option = self::get_uc_option();
		if ( isset( $uc_option['uc_favicon'] ) && strlen( $uc_option['uc_favicon'] ) ) {
			$uc_favicon_url = $uc_option['uc_favicon'];
			return sprintf( '<link rel="shortcut icon" href="%s" />', $uc_favicon_url );
		} else {
			return null;
		}
	}

	// get custom css
	public function uc_custom_css() {
		$uc_option     = self::get_uc_option();
		$uc_custom_css = isset( $uc_option['uc_custom_css'] ) ? $uc_option['uc_custom_css'] : '';
		return esc_html( $uc_custom_css );
	}

	// get analytics script
	public function uc_scripts() {
		$uc_option  = self::get_uc_option();
		$uc_scripts = isset( $uc_option['uc_scripts'] ) ? $uc_option['uc_scripts'] : '';
		return $uc_scripts;
	}

	// get footer analytics script
	public function uc_footer_scripts() {
		$uc_option         = self::get_uc_option();
		$uc_footer_scripts = isset( $uc_option['uc_footer_scripts'] ) ? $uc_option['uc_footer_scripts'] : '';
		return $uc_footer_scripts;
	}

	// get timer module view
	public function uc_timer() {
		include_once ONECOM_UC_PLUGIN_URL . '/inc/modules/timer.php';
		if ( isset( $html ) && strlen( $html ) ) {
			return $html;
		} else {
			return '';
		}
	}

	// get social icons
	public function uc_social_icons() {
		include_once ONECOM_UC_PLUGIN_URL . 'inc/classes/class-ocuc-social-icons.php';
		$uc_option    = self::get_uc_option();
		$social_icons = new OCUC_Social_Icons();
		return $social_icons->uc_get_social_icons( $uc_option );
	}
}

// load views
$html = new OCUC_Render_Views();
