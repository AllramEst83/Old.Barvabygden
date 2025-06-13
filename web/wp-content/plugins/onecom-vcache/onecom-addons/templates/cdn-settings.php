<?php
$oc_vache = new OCVCaching();

$dev_mode_checked      = '';
$exclude_cdn_checked   = '';
$dev_mode_duration     = $oc_vache->oc_json_get_option( 'onecom_vcache_info', 'dev_mode_duration' );
$oc_dev_mode_status    = $oc_vache->oc_json_get_option( 'onecom_vcache_info', 'oc_dev_mode_enabled' );
$oc_exclude_cdn_data   = $oc_vache->oc_json_get_option( 'onecom_vcache_info', 'oc_exclude_cdn_data' );
$oc_exclude_cdn_status = $oc_vache->oc_json_get_option( 'onecom_vcache_info', 'oc_exclude_cdn_enabled' );
$premium_inline_msg    = apply_filters( 'onecom_premium_inline_badge', '', __( 'Premium feature', 'vcaching' ), 'mwp' );

if ( $oc_vache->oc_premium() === true ) {
	$wrap_premium_class = 'oc-premium';
} else {
	$wrap_premium_class = 'oc-non-premium';
}

if ( 'true' === $oc_dev_mode_status ) {
	$dev_mode_checked = 'checked';
} else {
	$dev_mode_checked = '';
}

if ( 'true' === $oc_exclude_cdn_status ) {
	$exclude_cdn_checked = 'checked';
} else {
	$exclude_cdn_checked = '';
}

$cdn_enabled = get_site_option( 'oc_cdn_enabled' );

$cdn_icon = $oc_vache->oc_vc_uri . '/assets/images/cdn-icon.svg';

?>
<!-- Main Wrapper -->
<div class="wrap <?php echo $wrap_premium_class; ?> gv-activated">
    <div class="vcache-wrap gv-p-fluid">
        <!-- Page Header -->
        <div class="oc-header-wrap">
            <h3><?php _e( 'CDN', 'vcaching' ); ?> </h3>
            <div id="clear-cdn-container-desktop">
                <button
                        class="gv-button gv-button-primary oc-clear-cache-cta gv-max-mob-hidden"
                        title="Clear CDN cache"
                        disabled
                >
					<?php _e('Clear CDN cache', 'vcaching') ?>
                </button>
            </div>
        </div>

        <p class="gv-mt-sm gv-mb-fluid gv-text-sm oc-vcache-desc">
			<?php
			_e( 'A content delivery network (CDN) is a network of servers in multiple locations that save copies of your website closer to users’ location. This means that your website data has to travel a shorter distance, making your site load quicker. A CDN is especially useful if you have a lot of visitors spread across the globe.', 'vcaching' );
			?>
        <div id="clear-cdn-container-mobile" class="gv-mb-lg gv-mode-condensed">
            <button
                    class="gv-button gv-button-primary oc-clear-cache-cta gv-tab-hidden gv-desk-hidden"
                    title="Clear Cache"
                    disabled
            >
				<?php _e('Clear CDN cache', 'vcaching') ?>
            </button>
        </div>
        <div id="occdn-notice-placeholder"></div>
        <div id="onecom-cdn-root" class="gv-content-container gv-p-lg  gv-stack-space-md">
            <div class="skeleton-form">
                <div class="gv-skeleton gv-mb-md heading-skeleton"></div>
                <div class="gv-flex gv-items-center gv-mb-md">
                    <span class="skeleton skeleton-radio "></span>
                    <span class="gv-skeleton skeleton-label"></span>
                </div>
                <div class="gv-flex gv-items-center gv-mb-md">
                    <span class="skeleton skeleton-radio"></span>
                    <span class="gv-skeleton skeleton-label"></span>
                </div>
                <div class="gv-skeleton gv-mb-md heading-skeleton"></div>
                <div class="gv-flex gv-items-center gv-mb-md">
                    <span class="skeleton skeleton-radio"></span>
                    <span class="gv-skeleton skeleton-label"></span>
                </div>
                <div class="gv-skeleton gv-mb-md heading-skeleton gv-ml-lg"></div>
                <div class="gv-skeleton skeleton-button gv-mb-md gv-ml-lg"></div>
                <div class="gv-flex gv-items-center gv-mb-md">
                    <span class="skeleton skeleton-radio"></span>
                    <span class="gv-skeleton skeleton-label"></span>
                </div>
                <div class="gv-skeleton gv-mb-md heading-skeleton gv-ml-lg"></div>
                <div class="gv-skeleton gv-textarea-skeleton gv-mb-md gv-ml-lg"></div>
                <div class="gv-skeleton skeleton-button"></div>
            </div>
        </div>
    </div>
</div>



<div class="clear"></div>