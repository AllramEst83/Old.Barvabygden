<?php
$oc_vache = new OCVCaching();

$pc_checked               = '';
$performance_icon         = $oc_vache->oc_vc_uri . '/assets/images/pcache-icon.svg';
$varnish_caching          = get_site_option( OCVCaching::DEFAULTPREFIX . 'enable' );
$varnish_caching_ttl      = get_site_option( 'varnish_caching_ttl' );
$varnish_caching_ttl_unit = get_site_option( 'varnish_caching_ttl_unit' );

if ( $oc_vache->oc_premium() === true ) {
	$wrap_premium_class = 'oc-premium';
} else {
	$wrap_premium_class = 'oc-non-premium';
}

if ( 'true' === $varnish_caching ) {
	$pc_checked = 'checked';
}
$oc_nonce = wp_create_nonce( 'one_vcache_nonce' );
?>
<!-- Main Wrapper -->
<div class="<?php echo $wrap_premium_class; ?> gv-activated">
    <div class="vcache-wrap gv-p-fluid">
        <div class="oc-header-wrap">
            <h3><?php _e( 'Performance Cache', 'vcaching' ); ?> </h3>
            <div id="clear-cache-container-desktop">
                <button
                        class="gv-button gv-button-primary oc-clear-cache-cta gv-max-mob-hidden"
                        title="Clear Cache"
                        disabled
                >
					<?php _e('Clear cache', 'vcaching') ?>
                </button>
            </div>
        </div>
        <p class="gv-mt-sm gv-mb-fluid gv-text-sm oc-vcache-desc">
			<?php printf(
				__('The Performance Cache saves a copy of your website, which will then be shown to the next visitors of your site. This results in faster loading times and can improve your SEO ranking. %sLearn more%s', 'vcaching'),
				'<a class="ocwp_ocpc_cache_about_link_clicked_event" href="https://help.one.com/hc/en-us/articles/360000080458-How-to-use-the-Performance-Cache-plugin-for-WordPress" target="_blank" rel="noopener">',
				'</a>'
			); ?>
        </p>
        <div id="clear-cache-container-mobile" class="gv-mb-lg gv-mode-condensed">
            <button
                    class="gv-button gv-button-primary oc-clear-cache-cta gv-tab-hidden gv-desk-hidden"
                    title="Clear Cache"
                    disabled
            >
				<?php _e('Clear cache', 'vcaching') ?>
            </button>
        </div>
        <div id="notice-placeholder"></div>
        <div class="gv-content-container gv-p-lg  gv-stack-space-md" id="vcache-root">
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

                <div class="gv-skeleton skeleton-input gv-mb-md"></div>
                <div class="gv-skeleton skeleton-input gv-mb-md"></div>
                <div class="gv-skeleton skeleton-button gv-mb-md"></div>
            </div>
        </div>

        <input type="hidden" name="octracking" value="<?php echo $oc_nonce; ?>">

    </div>
</div>
<div class="clear"></div>