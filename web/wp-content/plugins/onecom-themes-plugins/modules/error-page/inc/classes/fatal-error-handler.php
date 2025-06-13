<?php

/**
 * Class Onecom_Error_Handler
 * Plugin Name: one.com Error Handler
 * Plugin URI: https://www.one.com
 * Description: Display useful information if there is a problem on your site. This information will be visible only to the authenticated users.
 * Author: one.com
 * Author URI: https://one.com
 * Version: 0.0.1
 */
class Onecom_Error_Handler extends WP_Fatal_Error_Handler {

	/**
	 * Displays the default PHP error template.
	 *
	 * This method is called conditionally if no 'php-error.php' drop-in is available.
	 *
	 * It calls {@see wp_die()} with a message indicating that the site is experiencing technical difficulties and a
	 * login link to the admin backend. The {@see 'wp_php_error_message'} and {@see 'wp_php_error_args'} filters can
	 * be used to modify these parameters.
	 *
	 * @param array $error Error information retrieved from `error_get_last()`.
	 * @param true|WP_Error $handled Whether Recovery Mode handled the fatal error.
	 *
	 * @since 5.2.0
	 * @since 5.3.0 The `$handled` parameter was added.
	 *
	 */
	protected function display_default_error_template( $error, $handled ) {
		$current_locale = get_locale();
		if ( $current_locale === 'nb_NO' ) {
			load_textdomain( OC_PLUGIN_DOMAIN , __DIR__ . '/plugins/onecom-themes-plugins/languages/onecom-wp-no_NO.mo' );
		} elseif ( $current_locale === 'fi' ) {
			load_textdomain( OC_PLUGIN_DOMAIN , __DIR__ . '/plugins/onecom-themes-plugins/languages/onecom-wp-fi_FI.mo' );
		}
		if ( ! function_exists( '__' ) ) {
			wp_load_translations_early();
		}

		if ( ! function_exists( 'wp_die' ) ) {
			require_once ABSPATH . WPINC . '/functions.php';
		}

		if ( ! class_exists( 'WP_Error' ) ) {
			require_once ABSPATH . WPINC . '/class-wp-error.php';
		}

		if ( true === $handled && wp_is_recovery_mode() ) {
			$message = __( 'There has been a critical error on your website, putting it in recovery mode. Please check the Themes and Plugins screens for more details. If you just installed or updated a theme or plugin, check the relevant page for that first.', 'onecom-wp' );
		} elseif ( is_protected_endpoint() ) {
			$message = __( 'There has been a critical error on your website. Please check your site admin email inbox for instructions.', 'onecom-wp' );
		} else {

			$message = __( 'There has been a critical error on your website.', 'onecom-wp' );
		}
		$link      = '';
		$link_text = '';
		if ( ! function_exists( '' ) ) {
			require_once ABSPATH . WPINC . DIRECTORY_SEPARATOR . 'pluggable.php';
		}

		$message = sprintf(
			'<p>%s</p><p><a href="%s">%s</a></p>',
			$message,
			/* translators: Documentation explaining debugging in WordPress. */
			__( 'https://wordpress.org/support/article/debugging-in-wordpress/' ),
			__( 'Learn more about debugging in WordPress.', 'onecom-wp' )
		);
		$args = array(
			'response' => 500,
			'exit'     => false,
		);

		/**
		 * Filters the message that the default PHP error template displays.
		 *
		 * @param string $message HTML error message to display.
		 * @param array $error Error information retrieved from `error_get_last()`.
		 *
		 * @since 5.2.0
		 *
		 */
		$message = apply_filters( 'wp_php_error_message', $message, $error );

		/**
		 * Filters the arguments passed to {@see wp_die()} for the default PHP error template.
		 *
		 * @param array $args Associative array of arguments passed to `wp_die()`. By default these contain a
		 *                    'response' key, and optionally 'link_url' and 'link_text' keys.
		 * @param array $error Error information retrieved from `error_get_last()`.
		 *
		 * @since 5.2.0
		 *
		 */
		$args = apply_filters( 'wp_php_error_args', $args, $error );

		if ( is_user_logged_in() ) {
			$error_details    = $this->get_extension_for_error( $error );
			$extended_message = $this->get_extended_message( $error_details );
			$message         .= $extended_message;
		}
		$wp_error = new WP_Error(
			'internal_server_error',
			$message,
			array(
				'error' => $error,
			)
		);

		wp_die( $wp_error, '', $args );
	}

	protected function get_extension_for_error( $error ) {
		global $wp_theme_directories;

		if ( ! isset( $error['file'] ) ) {
			return false;
		}

		if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
			return false;
		}

		$error_file    = wp_normalize_path( $error['file'] );
		$wp_plugin_dir = wp_normalize_path( WP_PLUGIN_DIR );

		if ( 0 === strpos( $error_file, $wp_plugin_dir ) ) {
			$path  = str_replace( $wp_plugin_dir . '/', '', $error_file );
			$parts = explode( '/', $path );

			return array(
				'type' => 'plugin',
				'slug' => $parts[0],
			);
		}

		if ( empty( $wp_theme_directories ) ) {
			return false;
		}

		foreach ( $wp_theme_directories as $theme_directory ) {
			$theme_directory = wp_normalize_path( $theme_directory );

			if ( 0 === strpos( $error_file, $theme_directory ) ) {
				$path  = str_replace( $theme_directory . '/', '', $error_file );
				$parts = explode( '/', $path );

				return array(
					'type' => 'theme',
					'slug' => $parts[0],
				);
			}
		}

		return false;
	}

	private function get_extended_message( $error ) {
		$details       = '<style>.xdebug-error{display:none;}.onecom-error-logo-wrap {position: absolute;right: 0;bottom: 0;}.wp-die-message{position: relative;}</style>';
		$details      .= '<p>' . $this->get_cause( $error ) . '</p>';
		$details      .= $this->get_tips( $error );
		$branding      = '<div style="margin:50px 0 20px 0; background: none; border-bottom: 3px solid #76B82A; height: 1px; font-size: 1px;"></div>';
		$branding_logo = '<div class="onecom-error-logo-wrap"><a href="https://www.one.com" target="_blank"><img alt="small logo" id="oneComWebmail-svg-header-img" src="https://www-static.cdn-one.com/images/mail/logo/one.com.green.dot.svg" width="48" height="48" border="0" style="border: 0; outline: none; text-decoration: none; display: block;"></a></div><div style="clear:both;"></div>';

		return $branding . $details . $branding_logo;
	}

	/**
	 * Gets the description indicating the possible cause for the error.
	 *
	 * @param array $extension The extension that caused the error.
	 *
	 * @return string Message about which extension caused the error.
	 * @since 5.2.0
	 *
	 */
	private function get_cause( $extension ) {

		if ( 'plugin' === $extension['type'] ) {
			$plugin = $this->get_plugin( $extension );

			if ( false === $plugin ) {
				$name = $extension['slug'];
			} else {
				$name = $plugin['Name'];
			}

			/* translators: %s: Plugin name. */
			$cause = sprintf( __( 'WordPress has detected an issue with your plugin %s.', 'onecom-wp' ), $name );
		} else {
			$theme = wp_get_theme( $extension['slug'] );
			$name  = $theme->exists() ? $theme->display( 'Name' ) : $extension['slug'];

			/* translators: %s: Theme name. */
			$cause = sprintf( __( 'WordPress has detected an issue with your theme %s.',  'onecom-wp' ), $name );
		}

		return $cause;
	}

	private function get_tips( $error ) {
		$message  = '<p>' . __( 'Here are some possible reasons for this error:','onecom-wp' ) . '</p>';
		$message .= '<ul>';
		if ( $error['type'] === 'theme' ) {
			$message .= '<li><a href="https://help.one.com/hc/en-us/articles/115005585909-Change-your-WordPress-theme-from-the-database" target="_blank">' . __( 'A recent theme update', 'onecom-wp' ) . '</a></li>';
			$message .= '<li><a href="https://help.one.com/hc/en-us/articles/115005585909-Change-your-WordPress-theme-from-the-database" target="_blank">' . __( 'Activation of a child theme', 'onecom-wp' ) . '</a></li>';

		} else {
			$message .= '<li><a href="https://help.one.com/hc/en-us/articles/115005593985" target="_blank">' . __( 'A recent plugin update', 'onecom-wp' ) . '</a></li>';
		}
		$message .= '<li><a href="https://help.one.com/hc/en-us/articles/115005593985-Disable-WordPress-plugins-in-phpMyAdmin" target="_blank">' . __( 'Deactivation of a required plugin', 'onecom-wp' ) . '</a></li>';
		$message .= '<li><a href="https://help.one.com/hc/en-us/articles/115005585989-Update-WordPress-manually" target="_blank">' . __( 'WordPress update', 'onecom-wp' ) . '</a></li>';
		$message .= '<li><a href="https://help.one.com/hc/en-us/articles/360000518537-How-do-I-fix-my-broken-WordPress-site-after-updating-PHP-" target="_blank">' . __( 'PHP version update', 'onecom-wp' ) . '</a></li>';

		$message .= '</ul>';
		$link     = 'https://help.one.com/hc/en-us/categories/360002171377-WordPress';
		$message .= '<p>' . sprintf( __( 'For further information, you can visit our <a href="%s" target="_blank"> help center</a>', 'onecom-wp' ), $link ) . '</p>';

		return $message;
	}

	/**
	 * Return the details for a single plugin based on the extension data from an error.
	 *
	 * @param array $extension The extension that caused the error.
	 *
	 * @return bool|array A plugin array {@see get_plugins()} or `false` if no plugin was found.
	 * @since 5.3.0
	 *
	 */
	private function get_plugin( $extension ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();

		// Assume plugin main file name first since it is a common convention.
		if ( isset( $plugins[ "{$extension['slug']}/{$extension['slug']}.php" ] ) ) {
			return $plugins[ "{$extension['slug']}/{$extension['slug']}.php" ];
		} else {
			foreach ( $plugins as $file => $plugin_data ) {
				if ( 0 === strpos( $file, "{$extension['slug']}/" ) || $file === $extension['slug'] ) {
					return $plugin_data;
				}
			}
		}

		return false;
	}
}

return new Onecom_Error_Handler();
