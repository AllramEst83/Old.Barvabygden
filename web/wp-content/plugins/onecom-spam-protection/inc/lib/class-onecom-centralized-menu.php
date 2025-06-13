<?php

declare(strict_types=1);

if ( ! class_exists( 'OneComCentralizedMenu' ) ) {


	final class OneComCentralizedMenu {

		const ONECOM_MENU_SLUG = 'onecom-wp';
		public $oc_inline_logo;



		public function __construct() {
			add_action( 'admin_menu', array( $this, 'onecom_register_menu' ), -1 );
			add_action( 'network_admin_menu', array( $this, 'onecom_register_menu' ), -1 );
			add_action( 'admin_head', array( $this, 'add_onecom_branding_css' ), 1 );
			add_action( 'admin_menu', array( $this, 'onecom_remove_menu' ), 12 );
			$this->oc_inline_logo = sprintf( '<img src="%s" alt="%s" />', plugin_dir_url( __FILE__ ) . '/assets/images/one.com.black.svg', __( 'One.com', OC_VALIDATOR_DOMAIN ) );
		}

		public function onecom_remove_menu() {

			remove_menu_page( 'onecom-vcache-plugin' );
			remove_menu_page( 'onecom-wp-under-construction' );
			remove_submenu_page( self::ONECOM_MENU_SLUG, self::ONECOM_MENU_SLUG );
		}


		public function onecom_register_menu() {

			global $submenu;

			$plugin_menu = 'onecom-wp-plugins';
			$theme_menu  = 'onecom-wp-themes';

			if ( is_plugin_active( 'onecom-vcache/vcaching.php' ) ) {

				add_submenu_page( self::ONECOM_MENU_SLUG, __( 'Performance Cache', OC_VALIDATOR_DOMAIN ), __( 'Performance Cache&nbsp;', OC_VALIDATOR_DOMAIN ), 'manage_options', 'onecom-vcache-plugin', '', 2 );
			}

			if ( is_plugin_active( 'onecom-under-construction/onecom-under-construction.php' ) ) {

				add_submenu_page( self::ONECOM_MENU_SLUG, __( 'Maintenance Mode', OC_VALIDATOR_DOMAIN ), __( 'Maintenance Mode', OC_VALIDATOR_DOMAIN ), 'manage_options', 'onecom-wp-under-construction', '', 2 );
			}

			if ( $this->onecom_plugin_activated() && ( isset( $submenu[ self::ONECOM_MENU_SLUG ] )

				) ) {

				if ( ! ( in_array( $plugin_menu, wp_list_pluck( $submenu[ self::ONECOM_MENU_SLUG ], 2 ) )
					|| in_array( $theme_menu, wp_list_pluck( $submenu[ self::ONECOM_MENU_SLUG ], 2 ) ) ) ) {

					add_submenu_page( self::ONECOM_MENU_SLUG, __( 'Themes', OC_PLUGIN_DOMAIN ), '<span id="onecom_themes">' . __( 'Themes', OC_PLUGIN_DOMAIN ) . '</span>', 'manage_options', 'onecom-wp-themes', '', 14 );
					add_submenu_page( self::ONECOM_MENU_SLUG, __( 'Plugins', OC_PLUGIN_DOMAIN ), '<span id="onecom_plugins">' . __( 'Plugins', OC_PLUGIN_DOMAIN ) . '</span>', 'manage_options', 'onecom-wp-plugins', '', 15 );

				}
			}

			if ( ! is_network_admin() && is_multisite() ) {
				return false;
			}

			if ( ! ( $this->onecom_plugin_activated() ) ) {
				$position = $this->onecom_get_free_menu_position( '2.1' );

				add_menu_page(
					$page_title = __( 'One.com', OC_VALIDATOR_DOMAIN ),
					$menu_title = $this->oc_inline_logo,
					$capability = 'manage_options',
					$menu_slug  = self::ONECOM_MENU_SLUG,
					$function   = '',
					$icon_url   = 'dashicons-admin-generic',
					$position
				);

			}
		}


		public function add_onecom_branding_css() {
			echo "<style>[class*=\" icon-oc-\"],[class^=icon-oc-]{speak:none;font-style:normal;font-weight:400;font-variant:normal;text-transform:none;line-height:1;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}.icon-oc-one-com-white-32px-fill:before{content:\"\e901\"}.icon-oc-one-com:before{content:\"\e900\"}#one-com-icon,.toplevel_page_onecom-wp .wp-menu-image{speak:none;display:flex;align-items:center;justify-content:center;text-transform:none;line-height:1;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}.onecom-wp-admin-bar-item>a,.toplevel_page_onecom-wp>.wp-menu-name{font-size:16px;font-weight:400;line-height:1}.toplevel_page_onecom-wp>.wp-menu-name img{width:69px;height:9px;}.wp-submenu-wrap.wp-submenu>.wp-submenu-head>img{width:88px;height:auto}.onecom-wp-admin-bar-item>a img{height:7px!important}.onecom-wp-admin-bar-item>a img,.toplevel_page_onecom-wp>.wp-menu-name img{opacity:.8}.onecom-wp-admin-bar-item.hover>a img,.toplevel_page_onecom-wp.wp-has-current-submenu>.wp-menu-name img,li.opensub>a.toplevel_page_onecom-wp>.wp-menu-name img{opacity:1}#one-com-icon:before,.onecom-wp-admin-bar-item>a:before,.toplevel_page_onecom-wp>.wp-menu-image:before{content:'';position:static!important;background-color:rgba(240,245,250,.4);border-radius:102px;width:18px;height:18px;padding:0!important}.onecom-wp-admin-bar-item>a:before{width:14px;height:14px}.onecom-wp-admin-bar-item.hover>a:before,.toplevel_page_onecom-wp.opensub>a>.wp-menu-image:before,.toplevel_page_onecom-wp.wp-has-current-submenu>.wp-menu-image:before{background-color:#76b82a}.onecom-wp-admin-bar-item>a{display:inline-flex!important;align-items:center;justify-content:center}#one-com-logo-wrapper{font-size:4em}#one-com-icon{vertical-align:middle}.imagify-welcome{display:none !important;}</style>";
		}

		public function onecom_plugin_activated(): bool {

			return ( is_plugin_active( 'onecom-themes-plugins/onecom-themes-plugins.php' ) );
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
	}
}
