<!-- This is common section where WPAPI error needs to be shown such as one.com themes list, plugins list -->
<div class="wrap_inner inner one_wrap oc-api-error" id="onecom-wrap">
	<div class="inner-wrap">
		<div class="oc-flex-center oc-api-error-box">
			<div class="oc-api-error-icon">
				<img class="oc-desktop-icon" src="<?php echo ONECOM_WP_URL; ?>/assets/images/api-error-icon-desktop.svg" alt="Error" />
				<img class="oc-mobile-icon" src="<?php echo ONECOM_WP_URL; ?>/assets/images/api-error-icon-mobile.svg" alt="Error" />
			</div>
			<div>
				<h2 class="main-heading"><?php _e( 'Plugin error', 'onecom-wp' ); ?></h2>
				<div class="oc-api-error-message">
					<?php _e( 'We’re currently experiencing issues with our one.com plugin. This does not affect your site. <br />Please come back later.', 'onecom-wp' ); ?>
				</div>
			</div>
		</div>

	</div>
	<div class="clear"></div>
</div>