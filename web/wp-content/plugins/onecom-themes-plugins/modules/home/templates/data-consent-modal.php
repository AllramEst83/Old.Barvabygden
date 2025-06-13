<!-- This file contains consent modal and toast messages which are required on all one.com plugin pages, so added in this common file -->

<!-- Consent banner modal -->
<div id="oc_data_consent_overlay" style="display:none;">
	<div id="oc_login_masking_overlay_wrap" class="gv-activated">

		<span class="oc_welcome_modal_close ocwp_ocp_home_data_modal_closed_event"><img src="<?php echo ONECOM_WP_URL . '/modules/home/assets/icons/close.svg'; ?>" /></span>
		<div class="oc-bg-wl-inner-wrap">

			<div class="oc-welcome-head">
				<h2 class="gv-mb-sm gv-heading-sm gv-mr-fluid"><?php _e( 'Your consent, your choice - allow us to collect data', 'onecom-wp' ); ?></h2>
			</div>

			<div class="gv-notice gv-notice-info">
				<gv-icon class="gv-notice-icon" src="<?php echo ONECOM_WP_URL . '/modules/home/assets/icons/info.svg'; ?>"></gv-icon>
				<p class="gv-notice-content">
					<?php echo __( 'To deliver the best customer experience, one.com would like to collect non-sensitive data from your website.', 'onecom-wp' ); ?> <?php echo __( 'You can opt out any time.', 'onecom-wp' ); ?>
				</p>
			</div>

			<h3 class="gv-text-sm gv-mt-sm">
				<?php echo __( 'We would like to collect the following information:', 'onecom-wp' ); ?>
			</h3>
			<ul class="gv-list-items gv-text-sm gv-mt-sm gv-mode-condensed gv-list-bullet">
				<li><?php echo __( 'Installed plugins and themes', 'onecom-wp' ); ?></li>
				<li><?php echo __( 'Number of: posts, pages, media, products, comments and users', 'onecom-wp' ); ?></li>
				<li><?php echo __( 'Use of one.com plugins and features', 'onecom-wp' ); ?></li>
				<li><?php echo __( 'Staging and multisite creation', 'onecom-wp' ); ?></li>
				<li><?php echo __( 'Help centre article visits', 'onecom-wp' ); ?></li>
				<li><?php echo __( 'Feature access and actions taken in the interface', 'onecom-wp' ); ?></li>
				<li><?php echo __( 'Upgrade start and completion', 'onecom-wp' ); ?></li>
			</ul>

			<div class="gv-mt-lg">
				<a id="oc-consent-modal-close" href="javascript:;" class="gv-button gv-button-primary ocwp_ocp_home_data_modal_closed_event"><?php echo __( 'Got it', 'onecom-wp' ); ?></a>
			</div>
		</div>
	</div>
</div>

<!-- Consent banner toast messages upon consent update -->
<div class="gv-activated oc-consent-toast-container">
	<div id="oc-consent-toast-success" class="gv-toast-container">
		<div class="gv-toast gv-toast-success" >
			<div class="gv-toast-content">
				<div><?php echo __( 'Your preferences were saved.', 'onecom-wp' ); ?></div>
			</div>
			<button class="gv-toast-close">
				<gv-icon src="<?php echo ONECOM_WP_URL; ?>/modules/home/assets/icons/close.svg"></gv-icon>
			</button>
		</div>
	</div>

	<div id="oc-consent-toast-failure" class="gv-toast-container">
		<div class="gv-toast gv-toast-alert">
			<div class="gv-toast-content">
				<div><?php echo __( 'Couldn’t save your preferences.', 'onecom-wp' ); ?></div>
			</div>
			<button class="gv-toast-close">
				<gv-icon src="<?php echo ONECOM_WP_URL; ?>/modules/home/assets/icons/close.svg"></gv-icon>
			</button>
		</div>
	</div>
</div>