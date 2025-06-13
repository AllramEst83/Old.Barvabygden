<?php

/**
 * Add shortcut to admin and frontend toolbar
 *
 * This class defines under-construction shortcut link in toolbar
 *
 * @since      0.2.0
 * @package    Under_Construction
 * @subpackage OCUC_Toolbar
 */

// Exit if file accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OCUC_Toolbar {


	public function __construct() {
		add_action( 'admin_bar_menu', array( $this, 'add_toolbar_link' ), 100 );
		add_action( 'admin_head', array( $this, 'add_ocuc_toolbar_css' ), 10 );
		add_action( 'wp_head', array( $this, 'add_ocuc_toolbar_css' ), 10 );
	}

	public function add_toolbar_link() {
		global $wp_admin_bar;

		if ( ! is_super_admin() || ! is_admin_bar_showing() ) {
			return;
		}

		$uc_option       = get_option( 'onecom_under_construction_info' );
		$uc_settings_url = admin_url( 'admin.php?page=onecom-wp-under-construction' );

		// Set toolbar shortcut text based on current uc status
		if ( isset( $uc_option['uc_status'] ) && 'on' === $uc_option['uc_status'] ) {
			$uc_status_class = 'ocuc_toggle_settings ocuc_setting_is_on ocwp_ocmm_toolbar_status_link_clicked_event';
			/* translators: %s a text wrapped in span */
			$uc_status = sprintf( __( 'Maintenance Mode is %1$sON%2$s', 'onecom-uc' ), '<span class="last_on">', '</span>' );
			/* translators: %s a text wrapped in span */
			$uc_meta_title = sprintf( __( 'Maintenance Mode is %1$sON%2$s', 'onecom-uc' ), '', '' );
		} else {
			$uc_status_class = 'ocuc_toggle_settings ocuc_setting_is_off ocwp_ocmm_toolbar_status_link_clicked_event';
			$uc_status       = __( 'Maintenance Mode is OFF', 'onecom-uc' );
			$uc_meta_title   = __( 'Maintenance Mode is OFF', 'onecom-uc' );
		}
		$wp_admin_bar->add_menu(
			array(
				'id'    => 'ocuc_options',
				'title' => $uc_status,
				'href'  => $uc_settings_url,
				'meta'  => array(
					'title' => $uc_meta_title,
					'class' => $uc_status_class,
				),
			)
		);
	}

	/**
	 * Add css and js for maintenance Mode ON/OFF
	 * @return void
	 */
	public function add_ocuc_toolbar_css() {
		if ( function_exists( 'is_admin_bar_showing' ) && is_admin_bar_showing() ) {
			?>
		<style>

			.ocuc_toggle_settings.ocuc_setting_is_on > a > span {
				color: #E85E0F;
			}

			.ocuc_toggle_settings.ocuc_setting_is_on > a:hover > span {
				color: #72AEE6;
			}

			.ocuc_toggle_settings.ocuc_setting_is_on > a::before {
				content: url(<?php echo ONECOM_UC_DIR_URL . 'assets/images/ocuc-on.svg'; ?>);
			}

			.ocuc_toggle_settings.ocuc_setting_is_on > a:hover::before {
				content: url(<?php echo ONECOM_UC_DIR_URL . 'assets/images/ocuc-hover.svg'; ?>);
			}

			.ocuc_toggle_settings.ocuc_setting_is_off > a::before {
				content: url(<?php echo ONECOM_UC_DIR_URL . 'assets/images/ocuc-off.svg'; ?>);
			}

			.ocuc_toggle_settings.ocuc_setting_is_off > a:hover::before {
				content: url(<?php echo ONECOM_UC_DIR_URL . 'assets/images/ocuc-hover.svg'; ?>);
			}

			.ocuc_toggle_settings.ocuc_setting_is_on > a::before,
			.ocuc_toggle_settings.ocuc_setting_is_off > a::before {
				margin-right: 10px !important;
				margin-top:2px;
				width:20px;
			}
		</style>
			<?php
		}
	}
}

$toolbar = new OCUC_Toolbar();
