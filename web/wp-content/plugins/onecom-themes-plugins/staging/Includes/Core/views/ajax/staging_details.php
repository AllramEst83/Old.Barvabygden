<!--Staging entry box-->
<div id="staging_entry">
	<div class="one-staging-details card-2">
		<div class="one-staging-site-info box one-card-staging-create-info">
			<div class="oc-column oc-left-column">
				<div class="oc-flex-center oc-icon-box">
					<img src="<?php echo ONECOM_WP_URL . 'assets/images/staging-icon.svg'; ?>" alt="One Staging - Ready" class="one-card-staging-create-icon-old" />
					<h2 class="main-heading"><?php _e( 'Staging', 'onecom-wp' ); ?></h2>
				</div>
				<div class="stg-desc">
					<?php if ( isset( $cloneExists ) && $cloneExists ) : ?>
						<p><?php _e( 'The staging website is a copy of your live website, where you can test new plugins and themes without affecting your live website.', 'onecom-wp' ); ?> <br>
							<?php _e( 'Only one staging version can be created for each website. When you rebuild a staging website, any existing staging site will be replaced with a new snapshot of your live website.', 'onecom-wp' ); ?><br>
							<?php _e( 'The login details for the staging backend are the same as for the live website.', 'onecom-wp' ); ?><br><br>

							<?php echo sprintf( __( '%sCaution:%s Rebuilding will overwrite all files and the database of your existing staging website.', 'onecom-wp' ), '<strong>', '</strong>' ); ?>
						</p>
					<?php else : ?>
						<div>
							<p><strong><?php _e( 'Staging site broken', 'onecom-wp' ); ?></strong></p>
							<p><?php _e( 'We have detected that your staging site is broken due to missing database table(s) and/or directory(s).', 'onecom-wp' ); ?><br>
								<?php _e( 'Click on "Rebuild Staging" to regenerate your staging site.', 'onecom-wp' ); ?></p>
						</div>
					<?php endif; ?>
					<?php if ( ! isPremium() ) { ?>
						<div class="wrap-rgh-btn-desc preimum_badge"><?php echo apply_filters( 'oc_staging_button_delete', '', __( 'Premium feature', 'onecom-wp' ), 'stg' ); ?></div>
					<?php } ?>
				</div>
			</div>
			<div class="oc-column oc-right-column">
				<div class="one-card-action-old staging-details-created">
					<?php
					if ( ! empty( $clones ) ) :
						foreach ( $clones as $key => $clone ) :
							?>
							<div class="one-staging-entry staging-entry" id="entry_<?php echo $key; ?>" data-staging-id="<?php echo $key; ?>"></div>
							<?php if ( empty( $clones ) || $cloneExists ) { ?>
							<div class="wrap-rgh-btn"><a href="javascript:void(0);" data-loginUrl="<?php echo trailingslashit( $clone['url'] ); ?>wp-login.php" data-stgUrl="<?php echo trailingslashit( $clone['url'] ); ?>" class="one-button btn button_1 loginStaging ocwp_ocp_staging_logged_in_event" style="min-width: 62px;text-align: center;"><?php _e( 'Log in to staging', 'onecom-wp' ); ?></a></div>
							<div class="wrap-rgh-btn"><a href="<?php echo $clone['url']; ?>" target="_blank" class="viewStaging ocwp_ocp_staging_viewed_event"><img src="<?php echo ONECOM_WP_URL . 'assets/images/view-site.svg'; ?>" alt="View your site" class="action-rht-img" /><span><?php _e( 'View your site', 'onecom-wp' ); ?></span></a></div>
							<?php } ?>
							<?php
						endforeach;
					endif;
					if ( empty( $clones ) || $cloneExists ) {
						echo $rebuild_btn = '<div class="wrap-rgh-btn"><a href="javascript:void(0);" class="one-button btn one-button-update-staging ocwp_ocp_staging_rebuild_event" data-staging-id="" data-dialog-id="staging-update-confirmation" data-title="' . __( 'Are you sure?', 'onecom-wp' ) . '" data-width="500" data-height="300" data-cu-confirm-journey-event="ocwp_ocp_staging_rebuild_confirmed_event"><img src="' . ONECOM_WP_URL . 'assets/images/rebuild-staging.svg" alt="Rebuild staging" class="action-rht-img" /><span>' . __( 'Rebuild staging', 'onecom-wp' ) . '</span></a></div>';
					} else {
						echo $rebuild_btn = '<div class="wrap-rgh-btn"><a href="javascript:void(0);" class="one-button btn one-button-update-staging rebuild-btn ocwp_ocp_staging_rebuild_event" data-staging-id="" data-dialog-id="staging-update-confirmation" data-title="' . __( 'Are you sure?', 'onecom-wp' ) . '" data-width="500" data-height="300" data-cu-confirm-journey-event="ocwp_ocp_staging_rebuild_confirmed_event"><span>' . __( 'Rebuild staging', 'onecom-wp' ) . '</span></a></div>';
					}
					$delete_btn = '<div class="wrap-rgh-btn"><a href="javascript:;" class="staging-trash one-button-delete-staging ocwp_ocp_staging_deletion_initiated_event"  title="' . __( 'Delete Staging', 'onecom-wp' ) . '" data-title="' . __( 'Are you sure?', 'onecom-wp' ) . '" data-dialog-id="staging-delete" data-width="500" data-height="275" data-cu-confirm-journey-event="ocwp_ocp_staging_deletion_confirmed_event"><img src="' . ONECOM_WP_URL . 'assets/images/delete-staging.svg" alt="Delete staging" class="action-rht-img" /><span>' . __( 'Delete staging', 'onecom-wp' ) . '</span></a></div>';
					echo $delete_btn;
					?>
					<div class="wrap-rgh-btn"><a href="<?php echo onecom_generic_locale_link( $request = 'staging_guide', get_locale() ); ?>" target="_blank" class="help_link2 ocwp_ocp_staging_help_guide_clicked_event"><img src="<?php echo ONECOM_WP_URL . 'assets/images/need-help.svg'; ?>" alt="Need help" class="action-rht-img" /><span><?php _e( 'Need help?', 'onecom-wp' ); ?></span></a></div>
				</div>
			</div>
		</div>
	</div>
</div>