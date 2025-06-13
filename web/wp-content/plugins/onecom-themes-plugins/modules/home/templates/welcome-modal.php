<?php
if ( isset( $_GET['onboarding-flow'] ) ) {
	$onboarding_flow = sanitize_text_field( $_GET['onboarding-flow'] );
} else {
	$onboarding_flow = false;
}
$theme      = wp_get_theme();
$theme_name = $theme->get( 'Name' );

// Prepare the sentence with the theme name
$text_with_theme = sprintf(
	__( 'Your site was set up with the "%s" theme and the plugins you selected were installed and activated.', 'onecom-wp' ),
	esc_html( $theme_name )
);

if ( $onboarding_flow === 'oci-wp-install' ) {
	$image      = ONECOM_WP_URL . '/modules/home/assets/tiles/welcome-modal-two.svg';
	$text_below = __( 'We added custom, AI-generated texts to your website to help you get started.', 'onecom-wp' );
} elseif ( $onboarding_flow === 'demo_import' || $onboarding_flow === 'demo_import_classic' ) {
	$image      = ONECOM_WP_URL . '/modules/home/assets/tiles/welcome-modal.svg';
	$text_below = __( 'We added demo content to your website to help you get started.', 'onecom-wp' );
} elseif ( $onboarding_flow === 'fast_track' ) {
	$image           = ONECOM_WP_URL . '/modules/home/assets/tiles/welcome-modal.svg';
	$text_with_theme = sprintf(
		__( 'Your site was set up with the "%s" theme.', 'onecom-wp' ),
		esc_html( $theme_name )
	);
	$text_below      = '';
} else {
	$image      = ONECOM_WP_URL . '/modules/home/assets/tiles/welcome-modal.svg';
	$text_below = '';

}


if ( ! $onboarding_flow ) {
	return;
}
?>
<div id="oc_login_masking_overlay">
	<div id="oc_login_masking_overlay_wrap" class="gv-activated">

		<span class="oc_welcome_modal_close ocwp_wpo_welcome_modal_closed_event"><img src="<?php echo ONECOM_WP_URL . '/modules/home/assets/icons/close.svg'; ?>" /></span>
		<div class="oc-bg-wl-inner-wrap">

			<div class="oc-welcome-head">
				<h5 class="gv-mb-sm"><?php _e( 'Welcome to WP Admin - your new site is ready', 'onecom-wp' ); ?></h5>
			</div>
			<img src="<?php echo $image; ?>"/>
			<div id="oc_um_body_login_masking" class="gv-mt-sm gv-text-sm"> <?php echo $text_with_theme . ' ' . $text_below . '<br />' . __( 'Take our tour to learn more about WP Admin and our one.com features.', 'onecom-wp' ); ?>
			</div>
			<div class="gv-mt-lg">
				<a id="oc-start-tour" href="javascript:;" class="gv-button gv-button-primary ocwp_wpo_welcome_modal_tour_started_event"><?php echo __( 'Start tour', 'onecom-wp' ); ?></a>
			</div>
		</div>
	</div>
</div>
