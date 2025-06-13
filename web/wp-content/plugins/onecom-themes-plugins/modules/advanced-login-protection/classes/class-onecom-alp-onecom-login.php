<?php

declare(strict_types=1);
defined( 'WPINC' ) or die(); // No Direct Access

/**
 * Class Onecom_ALP_Reset_Password
 * Show "Login with one.com" on WordPress login form
 */
class Onecom_ALP_Onecom_Login {

	public $onecom_login_url = OC_CP_LOGIN_URL;

	public function init() {
		// Hook login page button if ALP is enabled for admin
		$flag = get_site_option( 'onecom_login_masking', 0 );

		// Do not show button if staging
		$alp = new Onecom_ALP();
		// Check if staging site
		$staging = $alp->is_staging_site();

		if ( intval( $flag ) === 1 && ! $staging ) {
			add_action( 'login_footer', array( $this, 'onecom_login_button' ) );
			add_action( 'login_enqueue_scripts', array( $this, 'onecom_login_enqueue_style' ), 9 );
			add_action( 'login_enqueue_scripts', array( $this, 'onecom_login_enqueue_script' ), 10 );
		}
	}

	// Enqueue style on login page
	public function onecom_login_enqueue_style() {
		?>
		<style type="text/css">
			@import url('../assets/css/onecom-fonts.css');

			#onecom-login-button {
				display: none;
			}

			#login {
				width: 340px !important;
			}

			#loginform #onecom-login-button {
				display: block;
				-webkit-font-smoothing: antialiased;
				/* to match font weight exactly like figma in mac) */
			}

			#onecom-login-button .btn.button_1 {
				display: block;
				text-align: center;
				width: 100%;
				box-sizing: border-box;
				padding: 8px 12px;
				margin-bottom: 20px;
				margin-top: 23px;
				border-radius: 100px;
				font-family: Montserrat, sans-serif;
				font-weight: 600;
				font-size: 14px;
				text-decoration: none;
				line-height: 24px;
				cursor: pointer;
				-webkit-transition: all 0.2s ease-in-out;
				-moz-transition: all 0.2s ease-in-out;
				transition: all 0.2s ease-in-out;
				background-color: #0078C8;
				color: #ffffff;
			}

			#onecom-login-button .btn,
			#onecom-login-button .btn:focus,
			#onecom-login-button .btn:active {
				outline: none;
				box-shadow: none;
			}

			#onecom-login-button span {
				display: block;
				font-family: Roboto, sans-serif;
				font-size: 16px;
				line-height: 16px;
				text-align: center;
				margin-bottom: 20px;
			}

			/* Logout session popup - login form vertical bar remove */
			.interim-login #onecom-login-button .btn.button_1 {
				margin: 5px auto 28px auto;
			}

			.interim-login #onecom-login-button span {
				display: none;
			}
		</style>
		<?php
	}

	// Enqueue scripts on login page
	public function onecom_login_enqueue_script() {
		?>
		<script>
			document.addEventListener("DOMContentLoaded", function(event) {
				var login_button = document.getElementById("onecom-login-button");
				var login_form = document.getElementById("loginform");
				login_form.prepend(login_button);
			});
		</script>
		<?php
	}

	// Login button
	public function onecom_login_button() {
		?>
		<div id='onecom-login-button'>
			<a href="<?php echo $this->onecom_login_url; ?>" class="btn button_1">
				<?php _e( 'Admin sign in with one.com', 'onecom-wp' ); ?>
			</a>
			<span>or</span>
		</div>
		<?php
	}
}
