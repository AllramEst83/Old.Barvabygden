<div id="onestaging-clonepage-wrapper">

	<!-- Page Header -->
	<?php require_once $this->path . 'views/includes/header.php'; ?>

	<div class="wrap ocsh-wrap staging-multisite">
		<div class="inner one_wrap bg_box_main_container oc_multisite_container">
			<div class="wrap_inner">

				<div class="onecom_head">
					<div class="onecom_head__inner onecom_head_left oc_left_multisite">
						<img class="oc-large-screen" src="<?php echo ONECOM_WP_URL; ?>modules/health-monitor/assets/images/Security3.svg" alt="" class="onecom-heading-icon">
						<img class="oc-small-screen" src="<?php echo ONECOM_WP_URL; ?>modules/health-monitor/assets/images/multisite-small.svg" alt="" class="onecom-heading-icon">
					</div>
					<div class="onecom_head__inner onecom_head_right_multisite">
						<h3 class="onecom_heading oc_multisite"><?php _e( 'Staging feature not available', 'onecom-wp' ); ?></h3>
						<p class="oc-last-scan"><?php _e( 'The one.com Staging feature is not available for WordPress websites that:' ); ?></p>
						<ul>
							<li>Are part of a Multisite installation</li>
							<li>Have their files stored on a subdirectory but are running from the root domain</li>
						</ul>

					</div>
				</div>

			</div>
		</div>
	</div>
</div>