<?php

$wp_rocket            = new Onecom_Wp_Rocket();
$wp_rocket_addon_info = $wp_rocket->wp_rocket_addon_info( true );

if ( ! defined( 'WP_ROCKET_BUTTON_LABEL' ) ) {
	define( 'WP_ROCKET_BUTTON_LABEL', 'Get WP Rocket' );
}

$wp_rocket_icon = ONECOM_WP_URL . 'modules/wp-rocket/assets/images/wp-rocket-icon.svg';
$checklist_icon = ONECOM_WP_URL . 'modules/wp-rocket/assets/images/check-list.svg';

?>
<!-- Main Wrapper -->
<div class="wrap" id="onecom-wrap">

	<!-- Important placeholder for one.com notifications -->
	<div class="onecom-notifier"></div>

	<!-- Page Header -->
	<div class="oc-page-header">
		<h1 class="main-heading">
			<?php _e( 'Performance Tools', 'onecom-wp' ); ?>
		</h1>

		<div class="page-description">
			<?php
			_e( 'Tools to help you improve your website’s performance', 'onecom-wp' );
			?>
		</div>
	</div>

	<!-- Main content -->
	<div class='inner-wrap'>
		<div class='oc-wp-rocket'>
			<?php
			if ( $wp_rocket->is_wp_rocket_addon_purchased()
				&& $wp_rocket->is_oc_wp_rocket_flag_exists()
				&& $wp_rocket->is_wp_rocket_active()
			) {
				?>
				<div class="oc-flex-center oc-icon-box">
					<div class="oc-flex-center">
						<img id="oc-performance-icon" width="48" height="48" src="<?php echo $wp_rocket_icon; ?>" alt="one.com" />
						<h2 class="main-heading"> <?php _e( 'WP Rocket is active', 'onecom-wp' ); ?> </h2>
					</div>
				</div>
				<div class="oc-main-content">
					<div class="wp-rocket-desc"><?php _e( 'WP Rocket was successfully activated on this installation.', 'onecom-wp' ); ?></div>
					<div class="wp-rocket-btn">
						<a href="<?php echo admin_url( 'options-general.php?page=wprocket' ); ?>" class="oc-btn oc-btn-primary">
							<?php _e( 'Go to WP Rocket plugin', 'onecom-wp' ); ?>
						</a>
					</div>
				</div>
				<!-- Case 3: WP Rocket is installed but not active -->
			<?php } elseif ( $wp_rocket->is_wp_rocket_installed() && ! $wp_rocket->is_wp_rocket_active() ) { ?>
				<div class="oc-flex-center oc-icon-box">
					<div class="oc-flex-center">
						<img id="oc-performance-icon" width="48" height="48" src="<?php echo $wp_rocket_icon; ?>" alt="one.com" />
						<h2 class="main-heading"> <?php _e( 'Activate WP Rocket', 'onecom-wp' ); ?> </h2>
					</div>
				</div>
				<div class="oc-main-content">
					<div class="wp-rocket-desc">
						<?php
							$message = printf( __( 'You purchased WP Rocket for %s, but it hasn’t been activated on this %s yet. Activate the plugin to boost your site’s performance.', 'onecom-wp' ), OC_DOMAIN_NAME, OC_HTTP_HOST );
						?>
					</div>
					<div class="wp-rocket-btn oc-flex-center">
						<button class="oc-btn oc-btn-primary oc-activate-wp-rocket-btn">
							<?php _e( 'Activate WP Rocket', 'onecom-wp' ); ?>
						</button>
						<span class="oc_cb_spinner spinner oc_activate_wp_rocket_spinner"></span>
					</div>
				</div>
				<!-- Case 4: WP Rocket is purchased but not installed -->
			<?php } elseif ( $wp_rocket->is_wp_rocket_addon_purchased() && ! $wp_rocket->is_wp_rocket_installed() ) { ?>
				<div class="oc-flex-center oc-icon-box">
					<div class="oc-flex-center">
						<img id="oc-performance-icon" width="48" height="48" src="<?php echo $wp_rocket_icon; ?>" alt="one.com" />
						<h2 class="main-heading"> <?php _e( 'WP Rocket plugin is missing', 'onecom-wp' ); ?> </h2>
					</div>
				</div>
				<div class="oc-main-content">
					<div class="wp-rocket-desc"><?php _e( 'To install and activate the WP Rocket go to one.com Control Panel.', 'onecom-wp' ); ?></div>
					<div class="wp-rocket-btn">
						<a href="<?php echo OC_CP_LOGIN_URL; ?>" target="_blank" class="oc-btn oc-btn-primary oc-wp-rocket-cp-link">
							<?php _e( 'Go to control panel', 'onecom-wp' ); ?>
						</a>
					</div>
				</div>
				<!-- Case 5: WP Rocket is purchased outside + active -->
				<?php
			} elseif ( ! $wp_rocket->is_wp_rocket_addon_purchased()
				&& ! $wp_rocket->is_oc_wp_rocket_flag_exists()
				&& $wp_rocket->is_wp_rocket_active() ) {
				?>
				<div class="oc-flex-center oc-icon-box">
					<div class="oc-flex-center">
						<img id="oc-performance-icon" width="48" height="48" src="<?php echo $wp_rocket_icon; ?>" alt="one.com" />
						<h2 class="main-heading"> <?php _e( 'WP Rocket', 'onecom-wp' ); ?> </h2>
					</div>
					<div>
						<span class="oc-discount-badge">
							-20% <?php _e( 'discount', 'onecom-wp' ); ?>
						</span>
					</div>
				</div>
				<div class="oc-main-content">
					<div class="wp-rocket-desc"><?php _e( 'We partnered up with WP Rocket to offer -20% off regular price to all one.com customers.', 'onecom-wp' ); ?></div>
					<div class="wp-rocket-btn wp-rocket-offer-link">
						<a href="<?php echo $wp_rocket->wp_rocket_translated_guide(); ?>" target="_blank" class="oc-btn oc-btn-primary">
							<?php _e( 'Read about the offer', 'onecom-wp' ); ?>
						</a>
					</div>
				</div>
				<!-- Default case 2 (or if WP Rocket is not purchased and not installed) -->
			<?php } else { ?>
				<div class="oc-flex-center oc-icon-box">
					<div class="oc-flex-center">
						<img id="oc-performance-icon" width="48" height="48" src="<?php echo $wp_rocket_icon; ?>" alt="one.com" />
						<h2 class="main-heading"> <?php _e( 'WP Rocket', 'onecom-wp' ); ?> </h2>
					</div>
					<div>
						<span class="oc-discount-badge">
							-20% <?php _e( 'discount', 'onecom-wp' ); ?>
						</span>
					</div>
				</div>
				<div class="oc-main-content">
					<div class="wp-rocket-desc"><?php _e( 'WP Rocket is the most powerful caching plugin in the world. Use it to improve the speed of your WordPress site, SEO ranking and conversions. No coding required.', 'onecom-wp' ); ?></div>
					<ul class="oc-list-icon">
						<li><?php _e( 'WP Rocket is a trusted one.com partner and works seamlessly with one.com plugin and service offering.', 'onecom-wp' ); ?></li>
						<li>
							<?php _e( 'WP Rocket instantly improves your site’s performance and Core Web Vitals scores.', 'onecom-wp' ); ?>
						</li>
						<li>
							<?php _e( 'WP Rocket automatically applies the 80% of web performance best practices.', 'onecom-wp' ); ?>
						</li>
					</ul>
					<div class="wp-rocket-btn">
						<a href="<?php echo OC_WPR_BUY_URL; ?>" target="_blank" class="oc-btn oc-btn-primary ocwp_ocpc_wpr_get_wp_rocket_cta_clicked_event">
							<?php _e( 'Get WP Rocket', 'onecom-wp' ); ?>
						</a>
					</div>
				</div>
			<?php } ?>
		</div>
	</div>
</div>
<div class="clear"> </div>