<?php
// Prepare translated strings first
$title     = __( 'Your consent, your choice: your experience is important to us', 'onecom-wp' );
$info      = __( 'For delivering best customer experience, one.com wants to collect non-sensitive data from your website.', 'onecom-wp' );
$link_text = __( 'Which data is included?', 'onecom-wp' );

// Begin output buffering
ob_start();
?>
<div id="oc-data-consent-banner" class="gv-activated">
	<div class="gv-notice gv-notice-info gv-items-center">
		<gv-icon class="gv-notice-icon" src="<?php echo esc_url( ONECOM_WP_URL . '/modules/home/assets/icons/info.svg' ); ?>"></gv-icon>
		<div class="gv-flex-grow">
			<div class="gv-notice-content oc-consent-heading">
				<?php echo esc_html( $title ); ?>
			</div>
			<div class="gv-notice-content">
				<?php echo esc_html( $info ); ?>
				<a class="oc_consent_modal_show ocwp_ocp_consent_banner_modal_link_clicked_event" href="javascript:void(0);">
					<?php echo esc_html( $link_text ); ?>
				</a>
			</div>
		</div>

		<button type="button" class="oc-data-consent-decline gv-button gv-button-secondary gv-flex-shrink-0 ocwp_ocp_consent_banner_opted_out_event"><?php echo __( 'Opt out', 'onecom-wp' ); ?></button>
		<button type="button" class="oc-data-consent-accept gv-button gv-button-primary gv-flex-shrink-0 ocwp_ocp_consent_banner_accepted_event"><?php echo __( 'Accept', 'onecom-wp' ); ?></button>
	</div>
</div>
<?php
$consent_banner_html = ob_get_clean();
?>
<script>
	jQuery(function($) {
		const bannerHTML = <?php echo wp_json_encode( $consent_banner_html ); ?>;
		$('#wpcontent').append(bannerHTML);
	});
</script>