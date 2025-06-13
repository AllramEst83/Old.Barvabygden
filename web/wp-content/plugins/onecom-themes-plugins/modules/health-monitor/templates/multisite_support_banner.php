<?php
$template   = new OnecomTemplate();
$is_premium = $template->onecom_is_premium( 'all_plugins' );
$is_mwp     = $template->onecom_is_premium();



?>
<div class="wrap ocsh-wrap">
	<div class="wrap-top-onecom-heading-desc">
		<h1 class="onecom-main-heading"><?php echo __( 'Health and Security Tools', 'onecom-wp' ); ?></h1>
		<p class="onecom-main-desc"><?php echo __( 'Monitor the essential security and performance checkpoints and fix them if needed.', 'onecom-wp' ); ?></p>
	</div>
	<div class="inner one_wrap bg_box_main_container oc_multisite_container">
		<div class="wrap_inner">

			<div class="onecom_head">
				<div class="onecom_head__inner onecom_head_left oc_left_multisite">
					<img class="oc-large-screen" src="<?php echo ONECOM_WP_URL; ?>modules/health-monitor/assets/images/Security3.svg" alt="" class="onecom-heading-icon">
					<img class="oc-small-screen" src="<?php echo ONECOM_WP_URL; ?>modules/health-monitor/assets/images/multisite-small.svg" alt="" class="onecom-heading-icon">
				</div>
				<div class="onecom_head__inner onecom_head_right_multisite">
					<h2 class="onecom_heading oc_multisite"><?php echo __( 'Not supported on Multisite', 'onecom-wp' ); ?></h2>
					<p class="oc-last-scan"><?php echo __( 'This feature is not available because your site is part of a Multisite installation.', 'onecom-wp' ); ?></p>

				</div>
			</div>

		</div>
	</div>