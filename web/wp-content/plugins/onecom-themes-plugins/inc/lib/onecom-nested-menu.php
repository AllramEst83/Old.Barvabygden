<?php

declare(strict_types=1);
defined( 'WPINC' ) or die(); // No Direct Access

/**
 * Class Onecom_Nested_Menu
 * Nested admin menu with accordion
 */

if ( ! class_exists( 'Onecom_Nested_Menu' ) ) {
	final class Onecom_Nested_Menu {

		const ONECOM_MENU_SLUG = 'onecom-wp';
		public $oc_menu_logo;
		const ONECOM_PLUGIN_FILE  = 'onecom-themes-plugins/onecom-themes-plugins.php';
		const ONECOM_VCACHE_FILE  = 'onecom-vcache/vcaching.php';
		const ONECOM_WEBSHOP_FILE = 'onecom-webshop/webshop.php';
		const WP_ROCKET_TITLE     = 'WP Rocket';
		const WP_PCACHE_TITLE     = 'Performance Cache';


		public function __construct() {
			if ( class_exists( 'OneComCentralizedMenu' ) ) {
				$old_menu = new OneComCentralizedMenu();
				require __DIR__ . '/class-centralized-menu-decorator.php';
				new OneComCentralizedMenuDecorator( $old_menu );
			}
			$this->oc_menu_logo = sprintf( '<img class="onecom-menu-logo" src="%s" alt="%s" />', plugin_dir_url( __FILE__ ) . '/assets/images/one.com.black.svg', __( 'one.com', OC_VALIDATOR_DOMAIN ) );
		}

		public function init() {

			// remove menus registered from plugin
			add_action( 'admin_menu', array( $this, 'onecom_remove_menu' ), 14 );
			add_action( 'network_admin_menu', array( $this, 'onecom_remove_menu' ), 14 );

			// one.com admin menu
			add_action( 'admin_menu', array( $this, 'onecom_register_menu' ), 15 );
			add_action( 'network_admin_menu', array( $this, 'onecom_register_menu' ), 15 );

			// Enqueue CSS assets
			add_action( 'admin_head', array( $this, 'onecom_css_assets' ) );
			// Enqueue JS assets
			add_action( 'admin_print_footer_scripts', array( $this, 'onecom_js_assets' ), 100 );
		}

		// Register all menu and submenu
		public function onecom_register_menu() {
			global $submenu;

			$plugin_menu        = 'onecom-wp-plugins';
			$theme_menu         = 'onecom-wp-themes';
			$generic_plugin_ver = $this->oc_get_plugin_version( self::ONECOM_PLUGIN_FILE );

			// Return if multisite
			if ( ! is_network_admin() && is_multisite() ) {
				return false;
			}

			// Main menu
			$position = $this->onecom_get_free_menu_position( '2.1' );
			add_menu_page( __( 'One.com', self::ONECOM_MENU_SLUG ), $this->oc_menu_logo, 'manage_options', self::ONECOM_MENU_SLUG, '', 'dashicons-admin-generic', $position );
			if ( $this->onecom_plugin_activated() || is_plugin_active( 'onecom-spam-protection/onecom-spam-protection.php' ) ) {
				// Health and Security
				add_submenu_page(
					self::ONECOM_MENU_SLUG,
					'Health and Security',
					'<span id="onecom_health_security">Health and Security</span>',
					'manage_options',
					'onecom-health-security',
					array(
						$this,
						'onecom_menu_callback',
					),
					2
				);
				if ( $this->onecom_plugin_activated() ) {
					add_submenu_page( self::ONECOM_MENU_SLUG, __( 'Home', OC_VALIDATOR_DOMAIN ), '<span id="onecom_home">' . __( 'Home', OC_VALIDATOR_DOMAIN ) . '</span>', 'manage_options', 'onecom-home', '', -1 );

					add_submenu_page( self::ONECOM_MENU_SLUG, 'Health Monitor', 'Health Monitor', 'manage_options', 'onecom-wp-health-monitor', '' );
				}
				if ( is_plugin_active( 'onecom-spam-protection/onecom-spam-protection.php' ) ) {

					add_submenu_page( self::ONECOM_MENU_SLUG, 'Spam Protection', 'Spam Protection', 'manage_options', 'onecom-wp-spam-protection', '' );
				}
			}

			// Show 'Performance' nested menu heading if one.com (latest having WP-Rocket) or PCache plugin is active
			if ( is_plugin_active( self::ONECOM_VCACHE_FILE ) ||
				( $this->onecom_plugin_activated() && version_compare( $generic_plugin_ver, '4.0.0', '>=' ) ) ) {
				// Performance
				add_submenu_page(
					self::ONECOM_MENU_SLUG,
					'Performance',
					'<span id="onecom-performance-tools">Performance</span>',
					'manage_options',
					'onecom-performance-menu',
					array(
						$this,
						'onecom_menu_callback',
					),
					3
				);
			}

			// Show WP-Rocket page from one.com or PCache plugin based on version
			// @todo - Remove PCache page fallback after some time to remove redundant code
			if ( $this->onecom_plugin_activated() && version_compare( $generic_plugin_ver, '4.0.0', '>=' ) ) {
				add_submenu_page(
					self::ONECOM_MENU_SLUG,
					self::WP_ROCKET_TITLE,
					self::WP_ROCKET_TITLE,
					'manage_options',
					'onecom-wp-rocket',
					array(
						'Onecom_Wp_Rocket',
						'wp_rocket_page',
					),
					2
				);
			} elseif ( is_plugin_active( self::ONECOM_VCACHE_FILE ) &&
						version_compare( $this->oc_get_plugin_version( self::ONECOM_VCACHE_FILE ), '2.0', '>=' ) ) {
				add_submenu_page(
					self::ONECOM_MENU_SLUG,
					self::WP_ROCKET_TITLE,
					self::WP_ROCKET_TITLE,
					'manage_options',
					'onecom-wp-rocket',
					array(
						'OCVCaching',
						'wp_rocket_page',
					),
					2
				);
			}

			if ( is_plugin_active( self::ONECOM_VCACHE_FILE ) &&
				version_compare( $this->oc_get_plugin_version( self::ONECOM_VCACHE_FILE ), '2.0', '>=' ) ) {
				add_submenu_page(
					self::ONECOM_MENU_SLUG,
					self::WP_PCACHE_TITLE,
					self::WP_PCACHE_TITLE,
					'manage_options',
					'onecom-vcache-plugin',
					array(
						'OCVCaching',
						'cache_settings_page',
					),
					2
				);
				add_submenu_page(
					self::ONECOM_MENU_SLUG,
					'CDN',
					'CDN',
					'manage_options',
					'onecom-cdn',
					array(
						'OCVCaching',
						'cdn_settings_page',
					),
					2
				);
			} elseif ( is_plugin_active( self::ONECOM_VCACHE_FILE ) ) {
				add_submenu_page( self::ONECOM_MENU_SLUG, self::WP_PCACHE_TITLE, 'Performance Cache&nbsp;', 'manage_options', 'onecom-vcache-plugin', '', 2 );

			}

            //Staging feature not supported on multisite
            //or if the site and home url are different
            if ( $this->onecom_plugin_activated()
                && is_multisite() || ( get_option( 'siteurl' ) !== get_option( 'home' ) ) ) {
                add_submenu_page( self::ONECOM_MENU_SLUG, 'Staging blocked', '<span id="onecom_staging">Staging</span>', 'manage_options', 'onecom-wp-staging-blocked', '', 7 );
            } else if ( $this->onecom_plugin_activated()
                && ( isset( $submenu[ self::ONECOM_MENU_SLUG ] )
                    && ( ! is_multisite() )
                ) ) {
                add_submenu_page( self::ONECOM_MENU_SLUG, 'Staging', '<span id="onecom_staging">Staging</span>', 'manage_options', 'onecom-wp-staging', '', 7 );
            }

			if (
				is_plugin_active( self::ONECOM_WEBSHOP_FILE ) ||
				is_plugin_active( 'onecom-onephoto/onecom-onephoto.php' ) ||
				$this->onecom_plugin_activated() ||
				is_plugin_active( 'onecom-under-construction/onecom-under-construction.php' ) ||
				is_plugin_active( 'onecom-php-scanner/onecom-compatibility-scanner.php' )

			) {
				// Utility
				add_submenu_page(
					self::ONECOM_MENU_SLUG,
					'Utility',
					'<span id="onecom-utility">Utility</span>',
					'manage_options',
					'onecom-utility',
					array(
						$this,
						'onecom_menu_callback',
					),
					9
				);

				if ( is_plugin_active( self::ONECOM_WEBSHOP_FILE ) ) {
					include_once WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . self::ONECOM_WEBSHOP_FILE;
					add_submenu_page( self::ONECOM_MENU_SLUG, 'Online Shop', 'Online Shop', 'manage_options', 'one-webshop-settings', 'one_webshop_plugin_settings_page', 10 );

				}

				if ( is_plugin_active( 'onecom-onephoto/onecom-onephoto.php' ) ) {
					add_submenu_page( self::ONECOM_MENU_SLUG, 'One Photo', 'One Photo', 'manage_options', 'oc_onephoto', '', 11 );
				}

				if ( $this->onecom_plugin_activated() ) {
					add_submenu_page( self::ONECOM_MENU_SLUG, 'Advanced Error Page', 'Advanced Error Page', 'manage_options', 'onecom-wp-error-page', '', 12 );
					add_submenu_page( self::ONECOM_MENU_SLUG, 'Cookie Banner', 'Cookie Banner', 'manage_options', 'onecom-wp-cookie-banner', '', 13 );
				}
				if ( is_plugin_active( 'onecom-under-construction/onecom-under-construction.php' ) ) {
					add_submenu_page( self::ONECOM_MENU_SLUG, 'Maintenance Mode', 'Maintenance Mode', 'manage_options', 'onecom-wp-under-construction', '', 14 );
				}

				if ( is_plugin_active( 'onecom-php-scanner/onecom-compatibility-scanner.php' ) ) {
					add_submenu_page( self::ONECOM_MENU_SLUG, 'PHP Scanner', 'PHP Scanner', 'manage_options', 'onecom-php-compatibility-scanner', '', 15 );
				}

				// Themes & Plugins
				if ( $this->onecom_plugin_activated()
					&&
					( isset( $submenu[ self::ONECOM_MENU_SLUG ] ) ) &&
					! ( in_array( $plugin_menu, wp_list_pluck( $submenu[ self::ONECOM_MENU_SLUG ], 2 ) ) ||
						in_array( $theme_menu, wp_list_pluck( $submenu[ self::ONECOM_MENU_SLUG ], 2 ) ) )
				) {
					add_submenu_page( self::ONECOM_MENU_SLUG, 'Themes', '<span id="onecom_themes">Themes</span>', 'manage_options', 'onecom-wp-themes', '', 16 );
					add_submenu_page( self::ONECOM_MENU_SLUG, 'Plugins', '<span id="onecom_plugins">Plugins</span>', 'manage_options', 'onecom-wp-plugins', '', 17 );
				}
			}

			if ( ! is_network_admin() && is_multisite() ) {
				return false;
			}
		}


		public function onecom_remove_menu() {

			remove_menu_page( self::ONECOM_MENU_SLUG );

			remove_menu_page( 'onecom-vcache-plugin' );
			remove_menu_page( 'onecom-wp-under-construction' );
			remove_menu_page( 'onecom-wp-spam-protection' );
			remove_menu_page( 'onecom-php-compatibility-scanner' );
			remove_menu_page( 'vcaching-plugin' );
			remove_menu_page( 'one-webshop' );
			remove_menu_page( 'oc_onephoto' );
			remove_submenu_page( self::ONECOM_MENU_SLUG, 'onecom-wp-health-monitor' );
			remove_submenu_page( self::ONECOM_MENU_SLUG, 'onecom-vcache-plugin' );
			remove_submenu_page( self::ONECOM_MENU_SLUG, 'onecom-wp-error-page' );
			remove_submenu_page( self::ONECOM_MENU_SLUG, 'onecom-wp-cookie-banner' );
			remove_submenu_page( self::ONECOM_MENU_SLUG, 'onecom-wp-staging' );
			remove_submenu_page( self::ONECOM_MENU_SLUG, 'onecom-wp-staging-blocked' );
			remove_submenu_page( self::ONECOM_MENU_SLUG, self::ONECOM_MENU_SLUG );
			remove_submenu_page( self::ONECOM_MENU_SLUG, 'onecom-wp-plugins' );
			remove_submenu_page( self::ONECOM_MENU_SLUG, 'onecom-wp-themes' );

			if ( $this->onecom_plugin_activated() && version_compare( $this->oc_get_plugin_version( 'onecom-themes-plugins/onecom-themes-plugins.php' ), '3.0', '>=' ) ) {
				remove_submenu_page( self::ONECOM_MENU_SLUG, 'onecom-wp-vulnerability-monitor' );

			}
			if ( $this->onecom_plugin_activated() ) {
				remove_submenu_page( self::ONECOM_MENU_SLUG, 'onecom-wp-under-construction' );
			}
		}

		public function onecom_plugin_activated(): bool {
			return is_plugin_active( self::ONECOM_PLUGIN_FILE );
		}

		// Menu callback for non-functional menus //
		public function onecom_menu_callback() {
			wp_die( '<h2>' . __( 'Sorry, you are not allowed to access this page.' ) . '</h2>' );
		}

		public function onecom_get_free_menu_position( $start, $increment = 0.3 ) {
			foreach ( $GLOBALS['menu'] as $key => $menu ) {
				$menus_positions[] = $key;
			}

			if ( ! in_array( $start, $menus_positions ) ) {
				return $start;
			}

			/* the position is already reserved find the closet one */
			while ( in_array( $start, $menus_positions ) ) {
				$start += $increment;
			}

			return (string) $start;
		}



		/**
		 * @param $path
		 *
		 * @return false|string
		 * removed return type since string|bool is not supported in php 7.4
		 */
		public function oc_get_plugin_version( $path ) {

			if ( function_exists( 'get_file_data' ) ) {

				require_once ABSPATH . 'wp-admin/includes/plugin.php';
				$file_path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $path;
				if ( ! file_exists( $file_path ) ) {
					return false;
				}
				$plugin_data = get_file_data(
					$file_path,
					array(
						'Version' => 'Version',
					)
				);

				if ( ! empty( $plugin_data['Version'] ) ) {
					return $plugin_data['Version'];
				}
			}

			return false;
		}

		// Insert script & styles related to admin menu
		public function onecom_css_assets() {
			?>
			<style>
				li#toplevel_page_onecom-wp {
					font-family: 'Open Sans', sans-serif;
					font-size: 13px;
					letter-spacing: -0.4px;
				}

				li#toplevel_page_onecom-wp .wp-submenu-wrap li.accordion a {
					color: #B6BCC0;
					font-weight: 600;
					-webkit-font-smoothing: antialiased;
					cursor: pointer;
				}

				li#toplevel_page_onecom-wp .wp-submenu-wrap .panel li a,
				li#toplevel_page_onecom-wp .wp-submenu-wrap .panel li a:visited {
					font-size: 13px;
					color: #92989C;
					letter-spacing: -0.4px;
				}

				#adminmenu li#toplevel_page_onecom-wp .panel a:hover,
				#adminmenu li#toplevel_page_onecom-wp .panel a:focus {
					box-shadow: none;
				}

				li#toplevel_page_onecom-wp .accordion {
					transition: 0.4s;
				}

				li#toplevel_page_onecom-wp .accordion a:after {
					content: " ";
					background-image: url("data:image/svg+xml;charset=utf8,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0.5 0.75L5 5.25L9.5 0.75' stroke='%23B6BCC0'/%3E%3C/svg%3E");
					background-repeat: no-repeat;
					color: #777;
					float: right;
					height: 6px;
					width: 10px;
					margin-top: 7px;
				}

				li#toplevel_page_onecom-wp .accordion.active a:after {
					content: " ";
					background-image: url("data:image/svg+xml;charset=utf8,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M9.5 5.25L5 0.75L0.5 5.25' stroke='%23B6BCC0'/%3E%3C/svg%3E");
					background-repeat: no-repeat;
					height: 6px;
					width: 10px;
				}

				li#toplevel_page_onecom-wp .panel {
					padding: 0 0 0 27px;
					max-height: 0;
					overflow: hidden;
					/* transition: max-height 0.2s ease-out; */
					border: none;
					margin: 0;
					background: transparent;
				}

				li#toplevel_page_onecom-wp .panel li a:hover {
					box-shadow: none;
				}

				li#toplevel_page_onecom-wp.opensub .panel li a {
					padding-left: 0;
					padding-right: 0;
				}

				#adminmenu li#toplevel_page_onecom-wp ul > li > a,
				.folded #adminmenu li#toplevel_page_onecom-wp li.menu-top .wp-submenu>li>a {
					padding: 5px 10px 5px 12px;
				}

				/* Mobile menu works on visiblity hidden */
				@media screen and ( max-width: 782px ) {
					li#toplevel_page_onecom-wp ul.wp-submenu-wrap {
						visibility: hidden;
					}
					.auto-fold #adminmenu li#toplevel_page_onecom-wp a {
						font-family: 'Open Sans', sans-serif;
						font-size: 13px;
					}
				}

				/* Desktop menu works on display none */
				@media screen and ( min-width: 783px ) {
					li#toplevel_page_onecom-wp ul.wp-submenu-wrap {
						display: none;
					}
				}

				li#toplevel_page_onecom-wp ul.wp-submenu-wrap {
					visibility: hidden;
				}

				li#toplevel_page_onecom-wp .wp-first-item {
					display: none;
				}

				li#toplevel_page_onecom-wp .wp-submenu-wrap li.current a.current,
				li#toplevel_page_onecom-wp .wp-submenu-wrap .panel li a:hover {
					color: #ffffff;
				}

				li#toplevel_page_onecom-wp .wp-submenu-wrap li.current a,
				li#toplevel_page_onecom-wp .wp-submenu-wrap li.current a:hover {
					font-weight: normal;
				}
			</style>
			<style>
				[class*=\" icon-oc-\"],
				[class^=icon-oc-] {
					speak: none;
					font-style: normal;
					font-weight: 400;
					font-variant: normal;
					text-transform: none;
					line-height: 1;
					-webkit-font-smoothing: antialiased;
					-moz-osx-font-smoothing: grayscale
				}

				.icon-oc-one-com-white-32px-fill:before {
					content: \"\e901\"
				}

				.icon-oc-one-com:before {
					content: \"\e900\"
				}

				#one-com-icon,
				.toplevel_page_onecom-wp .wp-menu-image {
					speak: none;
					display: flex;
					align-items: center;
					justify-content: center;
					text-transform: none;
					line-height: 1;
					-webkit-font-smoothing: antialiased;
					-moz-osx-font-smoothing: grayscale
				}

				.onecom-wp-admin-bar-item>a,
				.toplevel_page_onecom-wp>.wp-menu-name {
					font-size: 16px;
					font-weight: 400;
					line-height: 1
				}

				#adminmenu .toplevel_page_onecom-wp>.wp-menu-name img.onecom-menu-logo {
					width: 69px;
					height: 9px;
					max-width: 100%;
				}

				#adminmenu .wp-submenu-wrap.wp-submenu>.wp-submenu-head>img.onecom-menu-logo {
					width: 88px;
					height: auto;
					max-width: 100%;
				}

				.onecom-wp-admin-bar-item>a img {
					height: 7px !important
				}

				.onecom-wp-admin-bar-item>a img,
				.toplevel_page_onecom-wp>.wp-menu-name img {
					opacity: .8
				}

				.onecom-wp-admin-bar-item.hover>a img,
				.toplevel_page_onecom-wp.wp-has-current-submenu>.wp-menu-name img,
				li.opensub>a.toplevel_page_onecom-wp>.wp-menu-name img {
					opacity: 1
				}

				#one-com-icon:before,
				.onecom-wp-admin-bar-item>a:before,
				.toplevel_page_onecom-wp>.wp-menu-image:before {
					content: '';
					position: static !important;
					background-color: rgba(240, 245, 250, .4);
					border-radius: 102px;
					width: 18px;
					height: 18px;
					padding: 0 !important
				}

				.onecom-wp-admin-bar-item>a:before {
					width: 14px;
					height: 14px
				}

				.onecom-wp-admin-bar-item.hover>a:before,
				.toplevel_page_onecom-wp.opensub>a>.wp-menu-image:before,
				.toplevel_page_onecom-wp.wp-has-current-submenu>.wp-menu-image:before {
					background-color: #76b82a
				}

				.onecom-wp-admin-bar-item>a {
					display: inline-flex !important;
					align-items: center;
					justify-content: center
				}

				#one-com-logo-wrapper {
					font-size: 4em
				}

				#one-com-icon {
					vertical-align: middle
				}

				.imagify-welcome {
					display: none !important;
				}
			</style>
			<?php
		}


		// Insert script & styles related to admin menu
		public function onecom_js_assets() {
			?>
			<script>
				// Group menu items to construct accordion based on href
				document.addEventListener("DOMContentLoaded", function() {

					// Prepare Health & Security accordion & panel
					jQuery(".wp-has-submenu a[href$='onecom-wp']").removeAttr("href")
					jQuery(".wp-submenu a[href$='onecom-health-security']").removeAttr("href").parent().addClass('accordion oc-health-security-accordion');
					jQuery("<div class='panel oc-health-security-panel'></div>").insertAfter(jQuery("li.oc-health-security-accordion"));
					// Add submenu items to Health & Security accordion panel
					jQuery(".wp-submenu a[href$='admin.php?page=onecom-wp-health-monitor']:not(.wp-first-item)").parent().appendTo(jQuery('.oc-health-security-panel'));
					jQuery('.wp-first-item span').removeAttr('id', 'onecom_home');
					jQuery(".wp-submenu a[href$='admin.php?page=onecom-wp-health-monitor']").parent().appendTo(jQuery('.oc-health-security-panel'));
					jQuery(".wp-submenu a[href$='admin.php?page=onecom-wp-vulnerability-monitor']").parent().appendTo(jQuery('.oc-health-security-panel'));
					jQuery(".wp-submenu a[href$='admin.php?page=onecom-wp-spam-protection']").parent().appendTo(jQuery('.oc-health-security-panel'));

					// Prepare Performance accordion & panel
					jQuery(".wp-submenu a[href$='onecom-performance-menu']").removeAttr("href").parent().addClass('accordion oc-performance-accordion');
					jQuery("<div class='panel oc-performance-panel'></div>").insertAfter(jQuery("li.oc-performance-accordion"));
					// Add submenu items to Performance accordion panel
					jQuery(".wp-submenu a[href$='admin.php?page=onecom-vcache-plugin']").parent().appendTo(jQuery('.oc-performance-panel'));
					jQuery(".wp-submenu a[href$='admin.php?page=onecom-cdn']").parent().appendTo(jQuery('.oc-performance-panel'));
					jQuery(".wp-submenu a[href$='admin.php?page=onecom-wp-rocket']").parent().appendTo(jQuery('.oc-performance-panel'));

					// Prepare Utility accordion & panel
					jQuery(".wp-submenu a[href$='onecom-utility']").removeAttr("href").parent().addClass('accordion oc-utility-accordion');
					jQuery("<div class='panel oc-utility-panel'></div>").insertAfter(jQuery("li.oc-utility-accordion"));
					// Add submenu items to Utility accordion panel
					jQuery(".wp-submenu a[href$='admin.php?page=onecom-online-shop']").parent().appendTo(jQuery('.oc-utility-panel'));
					jQuery(".wp-submenu a[href$='admin.php?page=one-webshop-settings']").parent().appendTo(jQuery('.oc-utility-panel'));
					jQuery(".wp-submenu a[href$='admin.php?page=onecom-wp-error-page']").parent().appendTo(jQuery('.oc-utility-panel'));
					jQuery(".wp-submenu a[href$='admin.php?page=oc_onephoto']").parent().appendTo(jQuery('.oc-utility-panel'));
					jQuery(".wp-submenu a[href$='admin.php?page=onecom-wp-cookie-banner']").parent().appendTo(jQuery('.oc-utility-panel'));
					jQuery(".wp-submenu a[href$='admin.php?page=onecom-wp-under-construction']").parent().appendTo(jQuery('.oc-utility-panel'));
					jQuery(".wp-submenu a[href$='admin.php?page=onecom-php-compatibility-scanner']").parent().appendTo(jQuery('.oc-utility-panel'));

					// Display was none for desktop, so display now
					let mediaQuery = window.matchMedia('(min-width: 783px)')
					if (mediaQuery.matches) {
						jQuery("li#toplevel_page_onecom-wp ul.wp-submenu-wrap").show();
					}

					/**
					 * Expand submenu for current active item (Desktop - when page loaded)
					 * This only works in desktop becuase in mobile menu is hidden (display:none) by default, and
					 * we cannot get height of element (.panel) inside hidden wrap (#adminmenuwrap)
					 */
					jQuery('li#toplevel_page_onecom-wp li.current').each(function() {
						let submenu_height = jQuery(this).parent(".panel").prop('scrollHeight') + "px";
						jQuery(this).parent(".panel").prev().addClass('active');
						jQuery(this).parent(".panel").css('max-height', submenu_height);
					});

					// Display menu only after submenu items arranged
					jQuery("li#toplevel_page_onecom-wp ul.wp-submenu-wrap").css('visibility', 'visible');

					/**
					 * Expand submenu for current active item (Mobile - when mobile menu opened/toggled)
					 * Expand (settings panel height) only works when mobile menu is visible and
					 * * it is hidden until clicked on #wp-admin-bar-menu-toggle.
					 * Hooking into #wp-admin-bar-menu-toggle also does not work becuase our code fires before submenu visible
					 * * therefore we used mutuation observer to detect dom change along with aria-expanded
					 * * It seems that aria-expanded is set to true after mobile menu is visible and somehow this works for us
					 */

						// Detect change in #wp-admin-bar-menu-toggle & aria-expanded is true, expand one.com current menu
					let mutationObserver = new MutationObserver(function(mutations) {
							mutations.forEach(function(mutation) {
								// console.log(mutation);
								if (jQuery('#wp-admin-bar-menu-toggle a').attr('aria-expanded') === "true") {
									jQuery('li#toplevel_page_onecom-wp li.current').each(function() {
										let submenu_height = jQuery(this).parent(".panel").prop('scrollHeight') + "px";
										jQuery(this).parent(".panel").prev().addClass('active');
										jQuery(this).parent(".panel").css('max-height', submenu_height);
									});
								}
							});
						});

					// Starts listening for changes in the HTML element (#wp-admin-bar-menu-toggle) of the page.
					if( jQuery('#wp-admin-bar-menu-toggle').length ) {
						mutationObserver.observe(document.querySelector("#wp-admin-bar-menu-toggle"), {
							attributes: true,
							characterData: true,
							childList: true,
							subtree: true,
							attributeOldValue: true,
							characterDataOldValue: true
						});
					}
					// accordion to expand/collapse menu items (when clicked .accordion)
					let acc = document.getElementsByClassName("accordion");
					let i;

					for (i = 0; i < acc.length; i++) {
						acc[i].addEventListener("click", function() {
							this.classList.toggle("active");
							let panel = this.nextElementSibling;
							if (panel.style.maxHeight) {
								panel.style.maxHeight = null;
							} else {
								panel.style.maxHeight = panel.scrollHeight + "px";
							}
						});
					}

				});
			</script>
			<?php
		}
	}
}