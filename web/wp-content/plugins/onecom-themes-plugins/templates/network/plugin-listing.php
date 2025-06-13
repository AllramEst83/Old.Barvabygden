<div class="gv-activated " id="onecom-plugins-ui">
    <div class="gv-p-fluid">
        <div class="loading-overlay fullscreen-loader">
            <div class="loading-overlay-content">
                <div class="loader"></div>
            </div>
        </div><!-- loader -->
        <div class="onecom-notifier"></div>

		<?php
		if ( ! ismWP() && function_exists( 'onecom_premium_theme_admin_notice' ) ) {
			onecom_premium_theme_admin_notice();
		}
		?>
        <div id="oc-toast-content" class="gv-toast-container"></div>


        <h3> <?php _e( 'Plugins', 'onecom-wp' ); ?> </h3>

        <div class="page-subtitle gv-pb-lg">
			<?php _e( 'Plugins to maintain, secure, and optimise your website.', 'onecom-wp' ); ?>
        </div>
        <div id="oc-plugins-root" class="gv-activated"></div>

		<?php
		// Get plugins data
		$plugins = onecom_fetch_plugins();

		if ( is_wp_error( $plugins ) ) {
			load_template( __DIR__ . '/wpapi-error.php' );
		} else {
			// filter out the plugins with property hidden=true
			$plugins = array_filter(
				$plugins ?? array(),
				function ( $p ) {
					return ! $p->hidden;
				}
			);

			$plugin_count = onecom_plugins_count();
			?>

		<?php } ?>
    </div>
</div>
<?php add_thickbox(); ?>
<span class="dashicons dashicons-arrow-up-alt onecom-move-up"></span>