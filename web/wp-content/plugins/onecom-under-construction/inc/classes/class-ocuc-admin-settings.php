<?php

/**
 * Defines admin settings functions (sections and fields)
 *
 * @since      0.1.0
 * @package    Under_Construction
 * @subpackage OCUC_Admin_Settings
 */

// Exit if file accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OCUC_Admin_Settings {

	private $settings_api;

	public function init_admin_settings() {
		$this->settings_api = new OCUC_Admin_Settings_API();
		add_action( 'admin_init', array( $this, 'uc_settings_init_fn' ) );
		add_action( 'admin_menu', array( $this, 'uc_add_page_fn' ) );
		add_action( 'network_admin_menu', array( $this, 'uc_add_page_fn' ) );
		add_action( 'admin_init', array( $this->settings_api, 'settings_init' ) );
		add_action( 'admin_head', array( $this, 'uc_menu_icon_css_fn' ) );
	}

	// Add sections/groups for different fields
	public function get_settings_sections() {
		$sections = array(
			array(
				'id'       => 'onecom_under_construction_settings',
				'title'    => __( 'General settings', 'onecom-uc' ),
				'desc'     => '',
				'callback' => 'callback_section',
                'event_track_class' => 'ocwp_ocmm_settings_tab_viewed_event'
			),
			array(
				'id'       => 'onecom_under_construction_content',
				'title'    => __( 'Content', 'onecom-uc' ),
				'callback' => 'callback_section',
                'event_track_class' => 'ocwp_ocmm_content_tab_viewed_event'
			),
			array(
				'id'       => 'onecom_under_construction_customization',
				'title'    => __( 'Customization', 'onecom-uc' ),
				'callback' => 'callback_section',
                'event_track_class' => 'ocwp_ocmm_customization_tab_viewed_event'
			),
		);
		return $sections;
	}

	/**
	 * Returns all the settings fields for above sections
	 *
	 * @return array settings fields
	 */
	public function get_settings_fields() {
		// prepare users array to whitelist via multicheck option
		$role_info  = wp_roles();
		$users_list = $role_info->role_names;

		$settings_fields = array(
			'onecom_under_construction_settings'      => array(

				array(
					'name'  => 'uc_status',
					'label' => __( 'Status', 'onecom-uc' ),
					'type'  => 'checkbox',
					'desc'  => __( 'Enable Maintenance Mode on your website', 'onecom-uc' ),
                    'event_track_class' => 'ocwp_ocmm_maintenance_mode_status_toggled_event',

				),

				array(
					'name'    => 'uc_theme',
					'label'   => __( 'Select design', 'onecom-uc' ),
					'desc'    => __( 'Choose a design for the Maintenance Mode page', 'onecom-uc' ),
					'type'    => 'radio_image',
					'options' => array(
						'theme-1' => 'design-1.jpg',
						'theme-2' => 'design-2.jpg',
						'theme-3' => 'design-3.jpg',
						'theme-4' => 'design-4.jpg',
						'theme-5' => 'design-5.jpg',
						'theme-6' => 'design-6.jpg',
					),
					'srcset'  => array(
						'theme-1' => 'design-1-194w.jpg',
						'theme-2' => 'design-2-194w.jpg',
						'theme-3' => 'design-3-194w.jpg',
						'theme-4' => 'design-4-194w.jpg',
						'theme-5' => 'design-5-194w.jpg',
						'theme-6' => 'design-6-194w.jpg',
					),
                    'event_track_class' => 'ocwp_ocmm_design_mwp_upgrade_initiated_event',
                    'modal_event_class' => 'ocwp_ocmm_design_mwp_upgrade_confirmed_event',
				),

				array(
					'name'    => 'uc_http_mode',
					'label'   => __( 'Mode', 'onecom-uc' ),
					'desc'    => '',
					'type'    => 'radio',
					'options' => array(
						'200' => __( 'Coming soon', 'onecom-uc' ) .
							' <p class="description" style="margin-bottom:6px;padding-left:31px;">' .
							__( 'Returns standard 200 HTTP OK response code to indexing robots', 'onecom-uc' ) .
							'</p>',
						'503' => __( 'Maintenance mode', 'onecom-uc' ) .
							' <p class="description" style="margin-bottom:6px;padding-left:31px;">' .
							__( 'Returns 503 HTTP Service unavailable code to indexing robots', 'onecom-uc' ) .
							'</p>',
					),
				),

				array(
					'name'  => 'uc_timer_switch',
					'label' => __( 'Countdown timer', 'onecom-uc' ),
					'desc'  => __( 'Would you like to show countdown timer?', 'onecom-uc' ),
					'type'  => 'checkbox',
				),

				array(
					'name'              => 'uc_timer',
					'label'             => '',
					'type'              => 'datetime',
					'placeholder'       => __( 'Select date', 'onecom-uc' ),
					'desc'              => __( 'Set countdown timer. Current Wordpress time: ', 'onecom-uc' ) .
						current_time( 'Y-m-d H:i' ) . '. <a href="' . admin_url( 'options-general.php' ) . '" target="_blank">' .
						__( 'Change timezone', 'onecom-uc' ) .
						'</a>',
					'sanitize_callback' => 'sanitize_text_field',
				),

				array(
					'name'    => 'uc_timer_action',
					'label'   => __( 'Countdown action', 'onecom-uc' ),
					'type'    => 'select',
					'options' => array(
						'no-action' => __( 'No action', 'onecom-uc' ),
						'hide'      => __( 'Hide countdown timer', 'onecom-uc' ),
						'disable'   => __( 'Disable the Maintenance Mode and show your website', 'onecom-uc' ),
					),
					'desc'    => __( 'Select action after countdown ends', 'onecom-uc' ),
                    'event_track_class' => 'ocwp_ocmm_countdown_mwp_upgrade_clicked_event'
				),

				array(
					'name'  => 'uc_subscribe_form',
					'label' => __( 'Subscribe form', 'onecom-uc' ),
					'desc'  => __( 'Would you like to show subscription form?', 'onecom-uc' ),
					'type'  => 'checkbox',
				),

				array(
					'name'    => 'uc_whitelisted_roles',
					'label'   => __( 'Whitelisted user roles', 'onecom-uc' ),
					'desc'    => __( 'Selected user roles will see the normal site, instead of the Maintenance Mode.', 'onecom-uc' ),
					'type'    => 'multicheck',
					'options' => $users_list,
                    'event_track_class' => 'ocwp_ocmm_role_toggle_mwp_upgrade_initiated_event',
                    'modal_event_class' => 'ocwp_ocmm_role_toggle_mwp_upgrade_confirmed_event',
				),

				array(
					'name'    => 'uc_exclude_pages',
					'label'   => __( 'Exclude pages', 'onecom-uc' ),
					'type'    => 'exclude_multiselect',
					'options' => array(),
					'desc'    => __( 'Select the page(s) to be excluded by maintenance mode such as "WooCommerce Lost password" or any custom login page. To avoid performance issues on sites, we show only the first 250 entries for each post type (post, page, product etc).', 'onecom-uc' ),
                    'event_track_class' => 'ocwp_ocmm_always_visible_upgrade_clicked_event'
				),

				array(
					'name'  => 'uc_submit',
					'label' => '',
					'type'  => 'submit',
					'id'    => 'oc_submit',
				),
			),

			/* Design Settings */
			'onecom_under_construction_content'       => array(
				array(
					'name'              => 'uc_logo',
					'label'             => __( 'Logo', 'onecom-uc' ),
					'type'              => 'file',
					'default'           => '',
					'options'           => array(
						'button_label' => __( 'Select Image', 'onecom-uc' ),
					),
					'desc'              => __( 'Site title will be displayed if no image uploaded.', 'onecom-uc' ) . ' ' . __( 'Site title', 'onecom-uc' ) . ': ' . get_bloginfo( 'blogname' ),
					'sanitize_callback' => 'sanitize_text_field',

				),

				array(
					'name'              => 'uc_favicon',
					'label'             => __( 'Site icon', 'onecom-uc' ),
					'type'              => 'file',
					'default'           => '',
					'options'           => array(
						'button_label' => __( 'Select Image', 'onecom-uc' ),
					),
					'desc'              => __( 'Site Icons are what you see in browser tabs, bookmark bars, and within the WordPress mobile apps.', 'onecom-uc' ) . ' ' . __( 'Site Icons should be square and at least 512 × 512 pixels.', 'onecom-uc' ),
					'sanitize_callback' => 'sanitize_text_field',
                    'event_track_class' => 'ocwp_ocmm_site_icon_mwp_upgrade_clicked_event'
				),

				array(
					'name'              => 'uc_headline',
					'label'             => __( 'Headline', 'onecom-uc' ),
					'type'              => 'text',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				),

				array(
					'name'    => 'uc_description',
					'label'   => __( 'Description', 'onecom-uc' ),
					'desc'    => '',
					'type'    => 'wysiwyg',
					'default' => '',
				),

				array(
					'name'              => 'uc_copyright',
					'label'             => __( 'Copyright Text', 'onecom-uc' ),
					'type'              => 'text',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				),

				array(
					'name'              => 'uc_facebook_url',
					'label'             => __( 'Facebook', 'onecom-uc' ),
					'type'              => 'url',
					'sanitize_callback' => 'sanitize_text_field',
					'placeholder'       => 'https://facebook.com/profile',
				),
				array(
					'name'              => 'uc_twitter_url',
					'label'             => __( 'Twitter', 'onecom-uc' ),
					'type'              => 'url',
					'sanitize_callback' => 'sanitize_text_field',
					'placeholder'       => 'https://twitter.com/profile',
				),

				array(
					'name'              => 'uc_instagram_url',
					'label'             => __( 'Instagram', 'onecom-uc' ),
					'type'              => 'url',
					'sanitize_callback' => 'sanitize_text_field',
					'placeholder'       => 'https://instagram.com/profile',
				),

				array(
					'name'              => 'uc_linkedin_url',
					'label'             => __( 'LinkedIn', 'onecom-uc' ),
					'type'              => 'url',
					'sanitize_callback' => 'sanitize_text_field',
					'placeholder'       => 'https://linkedin.com/profile',
				),

				array(
					'name'              => 'uc_youtube_url',
					'label'             => __( 'YouTube', 'onecom-uc' ),
					'type'              => 'url',
					'sanitize_callback' => 'sanitize_text_field',
					'placeholder'       => 'https://youtube.com/profile',
				),

				array(
					'name'              => 'uc_seo_title',
					'label'             => __( 'SEO title', 'onecom-uc' ),
					'type'              => 'text',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
					'desc'              => __( 'Search engines displays the 50 to 65 characters of a title tag on search engine results pages.', 'onecom-uc' ),
                    'event_track_class' => 'ocwp_ocmm_seo_title_mwp_upgrade_clicked_event'
				),

				array(
					'name'  => 'uc_seo_description',
					'label' => __( 'SEO description', 'onecom-uc' ),
					'type'  => 'textarea',
					'desc'  => __( 'SEO meta description length is recommended between 120 to 160 characters.', 'onecom-uc' ),
                    'event_track_class' => 'ocwp_ocmm_seo_description_mwp_upgrade_clicked_event'
				),

				array(
					'name'  => 'uc_submit',
					'label' => '',
					'type'  => 'submit',
				),

			),

			'onecom_under_construction_customization' => array(
				array(
					'name'    => 'uc_page_bg_color',
					'label'   => __( 'Background Color', 'onecom-uc' ),
					'desc'    => '',
					'type'    => 'color',
					'default' => '',
				),

				array(
					'name'    => 'uc_primary_color',
					'label'   => __( 'Primary color', 'onecom-uc' ),
					'desc'    => '',
					'type'    => 'color',
					'default' => '',
					'desc'    => __( 'Set color for site title and button', 'onecom-uc' ),
				),

				array(
					'name'              => 'uc_page_bg_image',
					'label'             => __( 'Background image', 'onecom-uc' ),
					'desc'              => __( 'Choose between having a solid color background or uploading an image. By default images will cover the entire background.', 'onecom-uc' ),
					'type'              => 'file',
					'default'           => '',
					'options'           => array(
						'button_label' => __( 'Select Image', 'onecom-uc' ),
					),
					'sanitize_callback' => 'sanitize_text_field',
				),

				array(
					'name'        => 'uc_custom_css',
					'label'       => __( 'Custom CSS', 'onecom-uc' ),
					'placeholder' => '.selector { property-name: property-value; }',
					'desc'        => __( 'Add custom CSS code', 'onecom-uc' ),
					'type'        => 'textarea',
				),

				array(
					'name'        => 'uc_scripts',
					'label'       => __( 'Header scripts', 'onecom-uc' ),
					'placeholder' => '&lt;script&gt;
  &lt;!-- Analytics code --&gt;
&lt;/script&gt;',
					'desc'        => __( 'Paste in your universal or classic google analytics code in header', 'onecom-uc' ),
					'type'        => 'textarea',
				),

				array(
					'name'        => 'uc_footer_scripts',
					'label'       => __( 'Footer scripts', 'onecom-uc' ),
					'placeholder' => '&lt;script&gt;
  &lt;!-- Analytics code --&gt;
&lt;/script&gt;',
					'desc'        => __( 'Paste in your analytics or custom scripts in footer', 'onecom-uc' ),
					'type'        => 'textarea',
                    'event_track_class' => 'ocwp_ocmm_footer_scripts_mwp_upgrade_clicked_event'
				),

				array(
					'name'  => 'uc_submit',
					'label' => '',
					'type'  => 'submit',
				),
			),

		);

		return $settings_fields;
	}

	/**
	 * Initialize and registers the settings sections and fileds to WordPress
	 *
	 * Usually this should be called at `admin_init` hook.
	 *
	 * This function gets the initiated settings sections and fields. Then
	 * registers them to WordPress and ready for use.
	 */

	public function uc_settings_init_fn() {
		//set the settings
		$this->settings_api->set_sections( $this->get_settings_sections() );
		$this->settings_api->set_fields( $this->get_settings_fields() );
	}

	// Add sub page to the Settings Menu
	public function uc_add_page_fn() {
		// @later-todo - move out as public var if getting used at multiple places
		$menu_title = __( 'Maintenance Mode', 'onecom-uc' );
		add_menu_page( $menu_title, $menu_title, 'manage_options', 'onecom-wp-under-construction', array( $this, 'uc_page_fx' ), 'dashicons-admin-generic' );
	}

	// add uc settings menu icon
	public function uc_menu_icon_css_fn() {
		define( 'OCUC_MENU_ICON_GREY', ONECOM_UC_DIR_URL . 'assets/images/uc-menu-icon-grey.svg' );
		define( 'OCUC_MENU_ICON_BLUE', ONECOM_UC_DIR_URL . 'assets/images/uc-menu-icon-blue.svg' );

		echo "<style>.toplevel_page_onecom-wp-under-construction > .wp-menu-image{display:flex !important;align-items: center;justify-content: center;}.toplevel_page_onecom-wp-under-construction > .wp-menu-image:before{content:'';background-image:url('" . OCUC_MENU_ICON_GREY . "');font-family: sans-serif !important;background-repeat: no-repeat;background-position: center center;background-size: 18px 18px;background-color:#fff;border-radius: 100px;padding:0 !important;width:18px;height: 18px;}.toplevel_page_onecom-wp-under-construction.current > .wp-menu-image:before{background-size: 16px 16px; background-image:url('" . OCUC_MENU_ICON_BLUE . "');}.ab-top-menu #wp-admin-bar-purge-all-varnish-cache .ab-icon:before,#wpadminbar>#wp-toolbar>#wp-admin-bar-root-default>#wp-admin-bar-onecom-wp .ab-item:before, .ab-top-menu #wp-admin-bar-onecom-staging .ab-item .ab-icon:before{top: 2px;}a.current.menu-top.toplevel_page_onecom-wp-under-construction.menu-top-last{word-spacing: 10px;}@media only screen and (max-width: 960px){.auto-fold #adminmenu a.menu-top.toplevel_page_onecom-wp-under-construction{height: 55px;}}</style>";
		return true;
	}

	// Display the admin options page
	public function uc_page_fx() {
		if ( is_multisite() ) {
			include_once ONECOM_UC_PLUGIN_URL . 'inc/multisite-support-banner.php';
		} else {
			$premium_class = $this->settings_api->oc_premium() ? 'oc-premium' : 'oc-non-premium';
			?>
		<div class="wrap one_uc_wrap" id="onecom-ui">
			<?php
			$this->uc_admin_head();
				// Show message after settings save
				settings_errors();
			?>
			<div class="ocuc-setting-wrap <?php echo $premium_class; ?>" id="responsiveTabsDemo" style="visibility: hidden;">
				<div class="wrap-head-desc">
					<div class="oc-flex-center oc-icon-box">
						<img width="48" height="48" src="<?php echo ONECOM_UC_DIR_URL . '/assets/images/mm-icon.svg'; ?>" alt="one.com">
						<h2 class="main-heading">
							<?php
							echo __( 'Maintenance Mode', 'onecom-uc' );
							?>
							<span class='oc-heading-label'>
							<?php echo __( 'Pro', 'onecom-uc' ); ?>
						</span>
						</h2>
					</div>
					<p>
						<?php
						if ( $this->settings_api->oc_premium() === true ) {
							echo __( 'Make your website private when editing it. Maintenance Mode tells the visitors that your website is under construction.', 'onecom-uc' ) . ' ' . __( 'With the Pro version, you get more customization options.', 'onecom-uc' );
						} else {
							echo __( 'Make your website private when editing it. Maintenance Mode tells the visitors that your website is under construction.', 'onecom-uc' );
						}
						?>
					</p>
				</div>
				<?php
					$this->uc_show_navigation();
				?>
				<form method="post" action="options.php" id="uc-form">
					<?php settings_fields( ONECOM_UC_OPTION_FIELD ); ?>
					<div class="onecom_tabs_panels">
					<?php
					$loop  = 0;
					$class = '';
					foreach ( $this->get_settings_sections() as $form ) {
						$class = 'onecom_tabs_panel ' . $form['id'];
						if ( $loop > 0 ) {
							$class = 'onecom_tabs_panel oc_hidden ' . $form['id'];
						}
						++$loop;
						?>
						<div class="<?php echo $class; ?>" id="<?php echo $form['id']; ?>">

							<?php
							do_action( 'wsa_form_top_' . $form['id'], $form );

							do_settings_sections( $form['id'] );
							do_action( 'wsa_form_bottom_' . $form['id'], $form );
							?>
						</div>
					<?php } ?>
					</div>
				</form>
			</div>
			<?php
			$this->settings_api->script();
		}
	}

	public function uc_admin_head() {
		?>
			<h1 class="one-title"> <?php echo __( 'Utility Tools', 'onecom-uc' ); ?>
			</h1>
			<div class="page-subtitle">
				<?php
				echo __( 'Helpful tools for building and maintaining your site.', 'onecom-uc' );
				?>
			</div>
		<?php
	}

	/**
	 * All three navigation
	 * @return void
	 */
	public function uc_show_navigation2() {
		$count = count( $this->get_settings_sections() );

		// don't show the navigation if only one section exists
		if ( 1 === $count ) {
			return;
		}

		$html = '<ul>';
		foreach ( $this->get_settings_sections() as $tab ) {
			$html .= sprintf( '<li><a href="#%1$s" id="%1$s-tab">%2$s</a></li>', $tab['id'], $tab['title'] );
		}
		$html .= '</ul>';
		echo $html;
		?>
		<?php
	}
	/**
	 * All three navigation
	 * @return void
	 */
	public function uc_show_navigation() {
		$count = count( $this->get_settings_sections() );

		// don't show the navigation if only one section exists
		if ( 1 === $count ) {
			return;
		}

		$settings      = (array) get_site_option( 'onecom_under_construction_info' );
		$disable_class = '';
		if (
			empty( $settings ) ||
			'' === $settings ||
			! array_key_exists( 'uc_status', $settings ) ||
			'on' !== $settings['uc_status']
		) {
			$disable_class = ' disabled-tab';
		}
		$loop  = 0;
		$class = 'onecom_tab active ocwp_ocmm_settings_tab_viewed_event';
		$html  = '<div class="h-parent-wrap"><div class="h-parent"><div class="h-child"><div class="onecom_tabs_container">';
		foreach ( $this->get_settings_sections() as $tab ) {

			if ( $loop > 0 ) {
				$class = 'onecom_tab' . $disable_class. ' '. $tab['event_track_class'];
			}
			++$loop;
			$html .= sprintf( '<div class="%3$s" data-tab="%1$s">%2$s</div>', $tab['id'], $tab['title'], $class );
		}
		$html .= '</div></div></div></div>';
		echo $html;
		?>
		<?php
	}
	public function uc_forms() {
		?>


		<?php
	}
}
