<?php

class OneHomeSections {

	public function __construct() {
		add_action( 'wp_ajax_oc_close_welcome_modal', array( $this, 'oc_close_welcome_modal' ) );
	}
	function get_cards(): array {
		return array(
			array(
				'title'    => __( 'Health and Security', 'onecom-wp' ),
				'subtitle' => __( 'Keep your site secure.', 'onecom-wp' ),
				'url'      => admin_url( 'admin.php?page=onecom-wp-health-monitor' ),
				'icon'     => 'health-monitor',
				'event_track_class'    => 'ocwp_ocp_home_health_security_clicked_event'
			),
			array(
				'title'    => __( 'Performance', 'onecom-wp' ),
				'subtitle' => __( 'Make sure your website loads fast.', 'onecom-wp' ),
				'url'      => $this->get_performance_plugin_url(),
				'icon'     => 'external-server',
				'event_track_class'    => 'ocwp_ocp_home_performance_link_clicked_event'
			),
			array(
				'title'    => __( 'Staging', 'onecom-wp' ),
				'subtitle' => __( 'Test changes in staging.', 'onecom-wp' ),
				'url'      => admin_url( 'admin.php?page=onecom-wp-staging' ),
				'icon'     => 'staging',
				'event_track_class'    => 'ocwp_ocp_home_staging_link_clicked_event'
			),
		);
	}

	function get_help_cards(): array {
		return array(
			array(
				'title'    => __( 'Help Centre', 'onecom-wp' ),
				'subtitle' => __( 'Find answers quickly in our Help Centre.', 'onecom-wp' ),
				'url'      => 'https://help.one.com',
				'icon'     => 'help',
				'event_track_class'    => 'ocwp_ocp_home_help_centre_link_clicked_event'
			),
			array(
				'title'    => __( 'Email support', 'onecom-wp' ),
				'subtitle' => __( 'We will respond within 24 hours, all year round.', 'onecom-wp' ),
				'url'      => 'https://help.one.com/hc/en-us/requests/new',
				'icon'     => 'library_books',
				'event_track_class'    => 'ocwp_ocp_home_email_support_link_clicked_event'
			),
		);
	}

	function get_articles_mwp(): array {
		return array(
			array(
				'title' => __( 'How to build your WordPress website', 'onecom-wp' ),
				'url'   => 'https://help.one.com/hc/en-us/articles/360001788897',
				'event_track_class' => 'ocwp_ocp_home_build_wp_site_link_clicked_event'
			),
			array(
				'title' => __( 'What is the one.com plugin?', 'onecom-wp' ),
				'url'   => 'https://help.one.com/hc/en-us/articles/115005593945',
				'event_track_class' => 'ocwp_ocp_home_what_is_ocp_plugin_link_clicked_event'
			),
			array(
				'title' => __( 'What is Maintenance Mode', 'onecom-wp' ),
				'url'   => 'https://help.one.com/hc/en-us/articles/8096988382353',
				'event_track_class' => 'ocwp_ocp_home_what_is_mm_link_clicked_event'
			),
			array(
				'title' => __( 'Using the one.com Staging feature for WordPress', 'onecom-wp' ),
				'url'   => 'https://help.one.com/hc/en-us/articles/360000020617',
				'event_track_class' => 'ocwp_ocp_home_using_staging_link_clicked_event'
			),
			array(
				'title' => __( 'How to use the Performance Cache plugin for WordPress', 'onecom-wp' ),
				'url'   => 'https://help.one.com/hc/en-us/articles/360000080458',
				'event_track_class' => 'ocwp_ocp_home_using_pcache_link_clicked_event'
			),
			array(
				'title' => __( 'How can I improve the speed of my WordPress site', 'onecom-wp' ),
				'url'   => 'https://help.one.com/hc/en-us/articles/6555011842705-How-can-I-improve-the-speed-of-my-WordPress-site',
				'event_track_class' => 'ocwp_ocp_home_special_mwp_support_link_clicked_event'
			),
		);
	}

	function get_articles_basic(): array {
		return array(
			array(
				'title' => __( 'How to build your WordPress website', 'onecom-wp' ),
				'url'   => 'https://help.one.com/hc/en-us/articles/360001788897',
				'event_track_class' => 'ocwp_ocp_home_build_wp_site_link_clicked_event'
			),
			array(
				'title' => __( 'What is the one.com plugin?', 'onecom-wp' ),
				'url'   => 'https://help.one.com/hc/en-us/articles/115005593945',
				'event_track_class' => 'ocwp_ocp_home_what_is_ocp_plugin_link_clicked_event'
			),
			array(
				'title' => __( "What is one.com's Managed WordPress", 'onecom-wp' ),
				'url'   => 'https://help.one.com/hc/en-us/articles/360020315097',
				'event_track_class' => 'ocwp_ocp_home_what_is_ocp_mwp_link_clicked_event'
			),
			array(
				'title' => __( 'What is Maintenance Mode', 'onecom-wp' ),
				'url'   => 'https://help.one.com/hc/en-us/articles/8096988382353',
				'event_track_class' => 'ocwp_ocp_home_what_is_mm_link_clicked_event'
			),
			array(
				'title' => __( 'How to use the Performance Cache plugin for WordPress', 'onecom-wp' ),
				'url'   => 'https://help.one.com/hc/en-us/articles/360000080458',
				'event_track_class' => 'ocwp_ocp_home_using_pcache_link_clicked_event'
			),
			array(
				'title' => __( 'What is WP Rocket', 'onecom-wp' ),
				'url'   => 'https://help.one.com/hc/en-us/articles/5927991871761',
				'event_track_class' => 'ocwp_ocp_home_what_is_wpr_link_clicked_event'
			),
		);
	}


	function get_cp_url() {
		$domain = $_SERVER['HTTP_X_GROUPONE_HOST'] ?? '';
		return esc_url( 'https://www.one.com/admin/managedwp/' . $domain . '/managed-wp-dashboard.do' );
	}

	function get_performance_plugin_url() {
		if ( in_array( 'onecom-vcache/vcaching.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			return admin_url( 'admin.php?page=onecom-vcache-plugin' );
		} else {
			return admin_url( 'admin.php?page=onecom-wp-rocket' );
		}
	}

	function oc_close_welcome_modal() {

		$user_id = get_current_user_id();

		if ( $user_id ) {
			// Update the user meta for the currently logged-in user
			$update_meta = update_user_meta( $user_id, 'oc-welcome-modal-closed', true );

			if ( $update_meta || is_integer( $update_meta ) ) {
				// Send a success response
				wp_send_json_success( array( 'message' => 'Welcome modal successfully closed' ) );
			} else {
				// Send a failure response
				wp_send_json_error( array( 'message' => 'Failed to update the welcome modal user meta' ) );
			}
		} else {
			// Send an error response if no user is logged in
			wp_send_json_error( array( 'message' => 'User not logged in' ) );
		}
	}
}
