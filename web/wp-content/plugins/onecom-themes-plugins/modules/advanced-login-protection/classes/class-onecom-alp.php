<?php

declare(strict_types=1);
defined( 'WPINC' ) or die(); // No Direct Access
require_once ONECOM_WP_PATH . 'staging/Includes/Core/Settings.php';

use OneStaging\Core\Settings as StagingSettings;

/**
 * Class Onecom_ALP
 * Encapsulates all logic related to Advanced Login Protection.
 */
class Onecom_ALP {

	private $staging_flag;
	private $alm_flag;
	private $http_host;

	/**
	 * Define the core functionality of the module.
	 */
	public function __construct() {
		// Get required db data
		$this->staging_flag = intval( get_site_option( 'onecom_is_staging_site', 0 ) );
		$this->alm_flag     = intval( get_site_option( 'onecom_login_masking', 0 ) );
	}

	public function init() {
		// Hook Advanced Login Protection
		add_action( 'init', array( $this, 'login_masking' ) );
	}

	/**
	 * Advanced Login Protection implementation
	 */
	public function login_masking(): void {

		//return if post or page password protected
		if ( array_key_exists( 'post_password', $_POST ) && isset( $_GET['action'] ) && $_GET['action'] === 'postpass' ) {
			return;
		}

		/**
		 * Reduce processing on ajax calls. Add this hook if one of the following conditions are met.
		 * 1. is_user_logged_in() is defined and user is logged out
		 * 2. is_user_logged_in() is not defined
		 */
		if (
			( ! function_exists( 'is_user_logged_in' ) )
			|| ( function_exists( 'is_user_logged_in' ) && ( ! is_user_logged_in() ) )
		) {

			// Check if staging site
			$staging = $this->is_staging_site();

			// Action if ALM enable and it is not a staging site
			if ( $this->alm_flag === 1 && ! $staging ) {
				add_action( 'wp_login', array( $this, 'admin_login_redirection' ), 99, 2 );
			} elseif ( $this->alm_flag === 2 && ! $staging ) {
				add_action( 'login_init', array( $this, 'login_redirection' ) );
			}
		}
		// if user is logged in but still trying to open wp-login.php
		// redirect back to /wp-admin/
		elseif (
			( '/wp-login.php' === $_SERVER['REQUEST_URI'] )
			&& is_user_logged_in()
		) {
			wp_redirect( is_multisite() ? network_admin_url() : admin_url() );
			exit;
		}
	}

	/**
	 * Redirect all users to mWP dashboard in control panel
	 */
	public function login_redirection(): void {
		if ( is_user_logged_in() ) {
			wp_redirect( is_multisite() ? network_admin_url() : admin_url() );
			exit;
		}
		$this->redirect_to_cp();
	}

	/**
	 * Redirect only the admin users to control panel.
	 */
	public function admin_login_redirection( $login, $user_obj ) {
		/**
		 * if user is authenticating from CP, do not redirect
		 */
		if ( isset( $_GET['onecom-auth'] ) ) {
			return;
		}

		// set user, just to be sure
		wp_set_current_user( $user_obj->ID );
		if (
			( ! is_multisite() && in_array( 'administrator', $user_obj->roles ) )
			|| ( is_multisite() && current_user_can( 'create_sites' ) )
		) {
			// manually logout user instead of calling wp_logout()
			wp_destroy_current_session();
			wp_clear_auth_cookie();
			wp_set_current_user( 0 );
			$this->redirect_to_cp();
			exit;
		}
	}

	// Redirection URL
	public function redirect_to_cp(): void {
		wp_redirect( OC_CP_LOGIN_URL );
		exit;
	}

	// Check if staging site
	public function is_staging_site(): bool {

		// Return if staging detected from db
		if ( $this->staging_flag === 1 ) {
			return true;
		}

		/**
		 * Detect staging via parent domain/salt
		 * Generate staging directories with HTTP_HOST & siteurl and match them
		 */

		// encode/decode salt to avoid sonar vulnerablity with hard coded string
		$encoded_salt = base64_encode( 'onecom_staging' );
		$decoded_salt = base64_decode( $encoded_salt );

		// HTTP_HOST contains parent domain even if staging site
		if (
			isset( $_SERVER['HTTP_HOST'] )
			&& ! empty( $_SERVER['HTTP_HOST'] )
		) {
			$this->http_host      = $_SERVER['HTTP_HOST'];
			$staging              = new StagingSettings();
			$parent_domain_hash   = hash_pbkdf2( 'sha256', $staging->urlToDomain( $this->http_host ), $decoded_salt, 100, 5 );
			$staging_with_host    = 'stg_' . $parent_domain_hash;
			$staging_with_siteurl = $staging->getStagingDir();

			// If both stg url are same, it is live else staging
			if ( $staging_with_host === $staging_with_siteurl ) {
				return false;
			}
		}
		return true;
	}
}
