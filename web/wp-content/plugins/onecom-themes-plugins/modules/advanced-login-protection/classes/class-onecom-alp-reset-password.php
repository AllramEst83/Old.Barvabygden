<?php

defined( 'WPINC' ) or die(); // No Direct Access
/**
 * Class Onecom_ALP_Reset_Password
 * Trigger Password Reset mail when disable ALP (first time) & flag present
 */
class Onecom_ALP_Reset_Password {


	public function init() {
		// Reset password mail on ALP disable once
		add_action( 'disable_onecom_alp', array( $this, 'reset_password_mail' ), 10 );

		// Delete alp mail flag on password update
		add_action( 'after_password_reset', array( $this, 'disable_flag_password_reset' ), 10 );
		add_action( 'profile_update', array( $this, 'disable_flag_profile_update' ), 10 );
	}

	// Trigger Password Reset mail
	public function reset_password_mail() {
		// Check if ALM flag activated by installer
		$installer_alm = intval( get_site_option( 'onecom_alp_disable_mail', 0 ) );

		// Trigger mail to first user exists & ALM flag exist
		if ( $installer_alm === 1 ) {
			$user_info = get_userdata( 1 );
			if ( $user_info ) {
				$status = retrieve_password( $user_info->user_login );

				// If mail sent, remove flag
				if ( $status ) {
					delete_site_option( 'onecom_alp_disable_mail' );
				}
			}
		}
	}

	// Remove ALP mail flag, if password reset
	public function disable_flag_password_reset() {
		delete_site_option( 'onecom_alp_disable_mail' );
	}

	// Remove ALP mail flag, if password update via profile
	public function disable_flag_profile_update() {
		if ( isset( $_POST['pass1'] ) && $_POST['pass1'] !== '' ) {
			delete_site_option( 'onecom_alp_disable_mail' );
		}
	}
}
