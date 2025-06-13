<?php
$existing_live = get_option( 'onecom_staging_existing_live' );
?>
<div class="wrap_inner inner one_wrap" id="onecom-wrap">
	<div class="inner-wrap" id="stg-inner-wrap">
		<div class="one-card-old oc-row">
			<div class="one-card-inline-block one-card-align-left one-card-staging-content-old">
				<div class="one-staging-actions card-3">
					<div class="one-staging-site-info box one-card-staging-create-info">
						<div class="oc-column oc-left-column">
							<div class="oc-flex-center oc-icon-box">
								<img src="<?php echo ONECOM_WP_URL . 'assets/images/staging-icon.svg'; ?>" alt="One Staging" class="one-card-staging-create-icon" />
								<h2 class="main-heading"> <?php _e( 'Staging', 'onecom-wp' ); ?></h2>
							</div>
							<div class="stg-desc">
								<?php
								if ( ! empty( $existing_live ) && isset( $existing_live->directoryName ) ) {
									?>
									<p><?php _e( 'This is your staging website, a snapshot of your live website. Here you can test changes without affecting your live website.', 'onecom-wp' ); ?>
										<br>
										<?php _e( 'When you copy from staging to live, the live website will get replaced with a snapshot of this staging website.', 'onecom-wp' ); ?><br><br>
										<?php _e( 'Before you continue, please be aware of the following:', 'onecom-wp' ); ?><br>
									</p>
									<ol>
										<li><?php _e( 'It is not possible to reverse this process.', 'onecom-wp' ); ?></li>
										<li><?php _e( 'When you copy from staging to live, all data and files of your live website will be overwritten. This includes recently added posts, pages, media uploads and files.', 'onecom-wp' ); ?></li>
										<li><?php _e( 'It can take a couple of minutes for the copy to finish, so please do not close or navigate away from this page.', 'onecom-wp' ); ?></li>
									</ol>
									<?php
								} else {
									?>
									<p><?php _e( 'Live website details not found. Can not copy staging into live!', 'onecom-wp' ); ?></p>
								<?php } ?>
							</div>
						</div>
						<div class="oc-column oc-right-column align-center-btn">
							<div class="one-card-action-old">
								<?php if ( ! empty( $existing_live ) && isset( $existing_live->directoryName ) ) { ?>
								<div class="wrap-rgh-btn align-center">
									<button id="deploy_to_live" class="one-button btn button_1 one-button-copy-to-live ocwp_ocp_staging_copy_intiated_event" data-dialog-id="staging-copy-confirmation" data-title="<?php _e( 'Are you sure?', 'onecom-wp' ); ?>" data-width="500" data-height="320" data-live-id="<?php echo $existing_live->directoryName; ?>" data-cu-confirm-journey-event="ocwp_ocp_staging_copy_confirm_event"><?php _e( 'Copy staging to live', 'onecom-wp' ); ?></button>
								</div>
								<div class="wrap-rgh-btn align-center">
									<a href="<?php echo onecom_generic_locale_link( $request = 'staging_guide', get_locale() ); ?>" target="_blank" class="help_link ocwp_ocp_staging_help_guide_clicked_event"><img src="<?php echo ONECOM_WP_URL . 'assets/images/need-help.svg'; ?>" alt="Need help" class="action-rht-img" /><span><?php _e( 'Need help?', 'onecom-wp' ); ?></span></a>
								</div>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	</div> <!-- wrap_inner -->
	<div class="loading-overlay fullscreen-loader deploy-loader">
		<div class="loading-overlay-content">
			<h3 class="loading-overlay-message"><?php _e( 'Please wait, while we are copying staging into live.', 'onecom-wp' ); ?></h3>
			<div class="loader"></div>
		</div>

	</div><!-- loader -->
<style>
	#TB_window{max-height: 300px;    max-width: 600px;}
	/* #TB_ajaxContent.TB_modal {
		padding: 0 70px !important;
	} */
	#TB_ajaxContent p {
		padding: 0px!important;
	}
	#TB_ajaxContent{
		margin:0 auto !important;
		padding:0 !important;
		max-width:100%;
	}
	.one-logo .textleft span.one-entry-flag,
		h1 span.one-entry-flag {
		display: inline-block;
		padding: 3px 10px;
		font-size: 11px;
		color: #77a240;
		margin-left: 5px;
		vertical-align: 1px;
		border:1px solid #77a240;
		border-color: #77a240;
		font-weight: 500;
		letter-spacing: 1px;
		text-transform: uppercase;
	}
	.one-staging-entry.staging-entry{
		display: inline-block;
		padding: 12px 20px;
		border: 1px solid #e6e6e6;
		min-width: 320px;
		background-color: #fff;
		box-shadow: 0 0 8px #EFEFF0;
	}
	.one-staging-entry .entry-name h4{
		margin:0 0 4px 0;
		font-size:16px;
	}
	.one-staging-entry .entry-controls ul,
	.one-staging-entry .entry-controls ul li{
		list-style-type: none;
		margin:0;
	}
	.one-staging-entry .entry-link{
		color:#666;
		margin-top: 10px;
	}
	.one-staging-entry .entry-controls{
		margin: 21px 0 1px;
		font-size:12px;
	}
	.one-staging-entry .entry-controls ul li{
		display: inline-block;
		vertical-align: top;
		margin: 0 4px;
	}
	.one-staging-entry .entry-controls ul li:first-child{
		margin:0;
	}
	.one-staging-entry .entry-controls a{
		background-color:#fff;
		padding:3px 7px 2px 5px;
		border:1px solid #396fc9;
		color:#396fc9;
		cursor: pointer;
	}
	.one-staging-entry .entry-controls a.one-button-delete-staging{
		border-color:#ff6363;
		color: #ff6363;
	}
	.one-staging-entry .entry-controls a:hover{
		border-color:#396fc9;
		background-color:#396fc9;
		color:#fff;
	}
	.one-staging-entry .entry-controls a.one-button-delete-staging:hover{
		border-color:#ff6363;
		background-color:#ff6363;
		color:#fff;
	}
	.one-staging-entry .entry-controls .dashicons{
		font-size: 18px;
		vertical-align: -4px;
	}
	.loading-overlay.element-loader{
		min-height: 200px;
	}
</style>
<style>
	.stopwatch {
		background-color: #d2d2d2;
		font-size: 18px;
		line-height: 2;
		margin-top: 10px;
	}
	.stopwatch.done{
		color: #0085ba;
	}
	.stopwatch .dashicons {
		vertical-align: -3px;
	}
</style>
