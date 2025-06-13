<?php
declare(strict_types=1);
defined( 'WPINC' ) or die(); // No Direct Access

/**
 * Class Onecom_ALP_Notice
 * Advanced Login Protection notice for new users
 */
class Onecom_ALP_Notice {

	public $onecom_login_url  = OC_CP_LOGIN_URL;
	public $onecom_guest_user = 'https://help.one.com/hc/en-us/articles/115005584729-How-do-I-create-a-guest-user-';

	public function init() {
		// ALP admin notice for new users
		add_action( 'admin_init', array( $this, 'new_user_masking_notice' ) );
	}

	// Login protection notice for new users
	public function new_user_masking_notice() {
		global $pagenow;
		$flag = get_site_option( 'onecom_login_masking', 0 );

		// Admin notice based on ALP settings
		if ( $pagenow === 'user-new.php' && intval( $flag ) === 1 ) {
			add_action( 'admin_notices', array( $this, 'admin_masking_notice' ) );
		} elseif ( $pagenow === 'user-new.php' && intval( $flag ) === 2 ) {
			add_action( 'admin_notices', array( $this, 'all_masking_notice' ) );
		}
	}

	// Notice content if ALP enabled for admin users
	public function admin_masking_notice() {
		$class   = 'notice notice-info onecom-notice';
		$message = sprintf( __( 'You have the one.com Advanced Login Protection enabled for administrators. If you want to allow another user to login as administrator, create a guest user in the %sone.com control panel%s.', 'onecom-wp' ), '<a href="' . $this->onecom_guest_user . '" target="_blank">', '</a>', '<a href="' . $this->onecom_login_url . '" target="_blank">', '</a>' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
	}

	// Notice content if ALP enabled for all
	public function all_masking_notice() {
		$class   = 'notice notice-error onecom-notice';
		$message = sprintf( __( 'You have the one.com Advanced Login Protection enabled which means that only users with one.com credentials can login to your site. You can invite other users to login as administrator by creating a Guest user in the %sone.com control panel%s.', 'onecom-wp' ), '<a href="' . $this->onecom_guest_user . '" target="_blank">', '</a>', '<a href="' . $this->onecom_login_url . '" target="_blank">', '</a>' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
	}
}
