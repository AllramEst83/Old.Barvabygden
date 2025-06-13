<?php
// No Direct Access
defined( 'WPINC' ) or die(); // No Direct Access

if ( ! defined( 'SP_PLUGIN_SLUG' ) ) {
	define( 'SP_PLUGIN_SLUG', 'onecom-spam-protection/onecom-spam-protection.php' );
}

/*
 * Spam Protection Notice
 * */
if ( ! function_exists( 'onecom_fetch_antispam_plugins' ) ) {
	function onecom_fetch_antispam_plugins() {
		$fetch_plugins_url = MIDDLEWARE_URL . '/antispam-plugins';
		$get_plugins       = '';

		$args = array(
			'timeout'     => 5,
			'httpversion' => '1.0',
			'sslverify'   => true,
		);

		$response = wp_remote_get( $fetch_plugins_url, $args );
		if ( ! is_wp_error( $response && is_array( $response ) ) ) {
			$body = wp_remote_retrieve_body( $response );
			$body = json_decode( $body );
			if ( ! empty( $body ) && $body->success ) {
				$get_plugins = $body->data;
			} else {
				error_log( print_r( $body, true ) );
			}
		} else {
			$errorMessage = '(' . wp_remote_retrieve_response_code( $response ) . ') ' . wp_remote_retrieve_response_message( $response );
			error_log( 'Error in forms protection notice -->' . print_r( $errorMessage, true ) );
		}
		if ( is_array( $get_plugins ) && ! empty( $get_plugins ) ) {
			set_site_transient( 'onecom_fetched_plugins', $get_plugins, 10 * HOUR_IN_SECONDS );
			return $get_plugins;
		}
	}
}

if ( ! function_exists( 'onecom_spam_protection_notice' ) ) {
	function onecom_spam_protection_notice() {

		$screen = get_current_screen();

		$screens = array(
			'themes',
			'plugins',
			'options-general',
			'users',
			'edit-comments',
		);
		// return if screen not allowed or user has not the capability
		if ( ! in_array( $screen->base, $screens ) || ! current_user_can( 'deactivate_plugin' ) ) {
			return false;
		}

		$features = oc_set_premi_flag();

		// if spam protection plugin is active and the user is having mWP package then return
		if ( isset( $features['data'] ) && ! empty( $features['data'] ) &&
			is_plugin_active( SP_PLUGIN_SLUG ) &&
			in_array( 'MWP_ADDON', $features['data'] ) ) {
			return false;

		}
		// get active plugins
		$act_plugins    = get_site_option( 'active_plugins' );
		$display_notice = true;

		$activated_plugins_slug = array();
		foreach ( $act_plugins as $plg ) {
			$activated_plugins_slug[] = explode( '/', $plg )[0];
		}

		$get_plugins = get_site_transient( 'onecom_fetched_plugins' );
		if ( ! $get_plugins ) {
			$get_plugins = (array) onecom_fetch_antispam_plugins();
		}

		$active_spam_plugin = array_intersect( $get_plugins, $activated_plugins_slug );
		if ( get_site_option( 'dismiss-oc-spam-notice' ) && ( get_site_option( 'dismiss-oc-spam-notice' ) > strtotime( '-60 days' ) ) ) {
			$display_notice = false;
		}

		if ( $active_spam_plugin || ! $display_notice ) {
			return false;
		} else {
			// Display Spam protection warning
			$link = admin_url( 'plugin-install.php?s=anti-spam&tab=search&type=term' );
			if ( ! ismWP() ) {
				$text = __( 'Your website forms are not protected against spam and abuse. We recommend installing a captcha or spam protection plugin. &nbsp;<a href=' . $link . '>View recommended plugins</a> ', 'onecom-wp' );
			} elseif ( ismWP() && ! file_exists( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . SP_PLUGIN_SLUG ) ) {

				$text = __( 'Your website forms are not protected against spam and abuse. We recommend installing spam protection plugin.', 'onecom-wp' ) . '<form method="post"><input type="hidden" name="oc-install-sp"/> <button type="submit"  class="button">Install & Activate Plugin</button><span id="oc_switch_spinner" class="oc_cb_spinner spinner"></form>';
			} else {
				$text = __( 'Your website forms are not protected against spam and abuse. We recommend installing spam protection plugin.', 'onecom-wp' ) . '<form method="post"><input type="hidden" name="oc-activate-sp"/><button type="submit"  class="button">Activate Plugin</button><span id="oc_switch_spinner" class="oc_cb_spinner spinner"></form>';
			}
			echo "<div id='oc-spam-nt' class='notice notice-warning is-dismissible'><p> {$text}</p></div>";
		}
	}
}

add_action( 'admin_notices', 'onecom_spam_protection_notice', 2 );

add_action( 'admin_head', 'dismiss_notice_script' );
add_action( 'admin_head', 'oc_install_spam_protection' );
//add_action('activated_plugin', 'oc_redirect_to_spam_protection');

function oc_install_spam_protection() {
	$url = admin_url( 'admin.php?page=onecom-wp-spam-protection' );
	if ( ! empty( $_POST ) && isset( $_POST['oc-install-sp'] ) ) {
		$spam_protection = new OnecomCheckSpam();
		$install         = function () {
			return $this->install_plugin();
		};
		$install->call( $spam_protection );

		echo( "<script>location.href = '" . $url . "'</script>" );

	} elseif ( ! empty( $_POST ) && isset( $_POST['oc-activate-sp'] ) ) {

		activate_plugin( SP_PLUGIN_SLUG );
		echo( "<script>location.href = '" . $url . "'</script>" );
	}
}



if ( ! function_exists( 'dismiss_notice_script' ) ) {

	function dismiss_notice_script() {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				$(document).on('click', '#oc-spam-nt .notice-dismiss', function (event) {
					data = {
						action: 'display_oc_spam_admin_notice',
					};

					$.post(ajaxurl, data, function (response) {
						console.log(response, 'DONE!');
					});
				});

				$(document).on('submit', '#oc-spam-nt form', function (){
					$('#oc_switch_spinner').css('visibility', 'visible');
					$(this).find(':input[type=submit]').prop('disabled', true);
				})
			});
		</script>

		<style>
			#oc-spam-nt {
				display: flex;
				padding: 5px 15px;
				align-items: center;
			}
			#oc-spam-nt p{
				max-width: 67%;
				margin-right: 5px;
			}

			#oc-spam-nt .spinner {
				background: url(data:image/gif;base64,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) no-repeat;
				background-size: 20px 20px;
				display: inline-block;
				visibility: hidden;
				float: right;
				vertical-align: middle;
				opacity: .7;
				width: 20px;
				height: 20px;
				margin: 4px 10px 0;
			}
			@media only screen and (max-width : 768px) {
				#oc-spam-nt {
					align-items: flex-start;
					flex-direction: column;
				}
				#oc-spam-nt p{
					max-width:85%;
				}
			}
		</style>

		<?php
	}
}
add_action( 'wp_ajax_display_oc_spam_admin_notice', 'display_oc_spam_admin_notice' );

function display_oc_spam_admin_notice() {
	update_site_option( 'dismiss-oc-spam-notice', current_time( 'timestamp' ), 'no' );
	wp_die();
}