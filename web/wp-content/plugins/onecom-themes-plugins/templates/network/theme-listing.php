<?php wp_enqueue_style( 'oc_gravity-css', ONECOM_WP_URL . 'modules/home/css/one.min.css', null, ONECOM_WP_VERSION ); ?>
<div class="gv-activated">
	<div class="theme-notification top-notification hide">
		<div class="gv-notice gv-notice-warning">
			<img class="gv-notice-icon gv-icon" src="<?php echo ONECOM_WP_URL . '/assets/images/warning.svg'; ?>">
			<p class="gv-notice-content"><b><?php _e('Important', OC_PLUGIN_DOMAIN)?>:</b> <?php _e('Support for our one.com classic themes will end during 2025. If you choose a classic theme for your site, we won’t be able to provide you with customised content.', OC_PLUGIN_DOMAIN);?></p>
		</div>
	</div>
</div>
<div class="wrap  gv-activated">
	<div class="loading-overlay fullscreen-loader">
		<div class="loading-overlay-content">
			<div class="gv-loader-container">
				<img class="gv-loader" src="<?php echo ONECOM_WP_URL . 'assets/images/spinner.svg';?>">
				<p></p>
			</div>
		</div>
	</div>

	<?php
	$requsted_paged = ( isset( $_GET['paged'] ) ) ? (int) $_GET['paged'] : 1;
	global $theme_data;
	global $oci_theme_fetch, $isWPApiDown;
	$theme_data = onecom_fetch_themes($page = 1, true);
	$oci_theme_fetch = $theme_data->collection;
	$config     = onecom_themes_listing_config();


	$response = array();

	//merge one.com themes and wp.org themes
	$themes = merge_classic_wp_themes($oci_theme_fetch);



	$html = array();
	if( !empty( $themes ) ) {
		$themes = onecom_filter_hidden_themes($themes);
		foreach ($themes as $key => $theme) :
			$is_installed = onecom_is_theme_installed( $theme->slug );
			$tags         = $theme->tags;
			$tags         = implode( ' ', $tags );
			$hidden_class = $key > ( $config['item_count'] - 1 ) ? 'hidden_theme' : '';
			$is_premium = ( ( is_array( $theme->tags ) && in_array( 'premium', $theme->tags ) ) ? 1 : 0 );
			$page_class = ceil( ( $key + 1 ) / $config['item_count'] );
			//prepare theme block
			$html[] = prepare_theme_block($theme, $key);
		endforeach;
	}else{
		$isWPApiDown = true;
	}

	// load error section if WPAPI is having error
	if ( $isWPApiDown ) {
		load_template( dirname(__DIR__) . '/wpapi-themes-error.php' );
	} else {
		?>
		<div class="oci-wrap-center">
			<div class="oci-category-desc gv-pr-md gv-pb-fluid">
				<h5 class="gv-mb-sm cat-heading"><?php _e( 'Choose a theme', OC_PLUGIN_DOMAIN ); ?></h5>
				<p class="gv-text-md"><?php _e( 'Select a theme that fits your business or project.', OC_PLUGIN_DOMAIN ); ?> </p>
			</div>
			<?php $categoriesAndIcons = json_decode(file_get_contents(dirname(__FILE__,2).'/assets/themes-categories.json'), true); ?>
			<div role="tablist" class="gv-tab-list">
				<?php
				$class = '';
				foreach ( $categoriesAndIcons as $category ) {
					if($category['slug'] === 'all'){
						$class = 'gv-tab-active';
					}else{
						$class ='';
					}
					?>
					<button role="tab" class="gv-tab oc-themes-tab <?php echo $class ?> <?php echo $category["class"];?>" data-category="<?php echo $category['slug']?>" aria-selected="true">
						<span class="gv-tab-content"><?php _e($category['name'], OC_PLUGIN_DOMAIN);?></span>
					</button>
				<?php } ?>
			</div>
		</div>
		<div class="gv-modal gv-hidden">
			<div class="gv-modal-content">
				<button class="gv-modal-close oc-modal-close">
					<img class="gv-icon" src="<?php echo ONECOM_WP_URL . '/modules/home/assets/icons/close.svg'; ?>" />
				</button>
				<div class="gv-modal-body">
					<h2 class="gv-modal-title"><?php _e('Activate theme with demo content?',OC_PLUGIN_DOMAIN)?></h2>
					<p class="gv-text-sm"><?php _e('You can activate the theme with or without demo content.',OC_PLUGIN_DOMAIN)?></p>
					<div class="gv-content-container gv-p-md gv-flex-row-sm gv-items-center">
						<div class="gv-mode-condensed">
							<input type="radio" name="radio-group-name" class="gv-radio" value="without-demo-content"/>
						</div>
						<div class="gv-content">
							<p class="gv-text-sm gv-text-bold"><?php _e('Without demo content (Recommended)',OC_PLUGIN_DOMAIN)?></p>
							<p class="gv-caption-lg gv-text-on-alternative"><?php _e('Best if you already have a website and want to keep your current content (posts, pages and images) as it is.',OC_PLUGIN_DOMAIN)?></p>
						</div>
					</div>
					<div class="gv-content-container">
						<div class="gv-flex-row-sm gv-items-center gv-p-md">
							<div class="gv-mode-condensed">
								<input type="radio" name="radio-group-name"  class="gv-radio" value="with-demo-content"/>
							</div>
							<div class="gv-content">
								<p class="gv-text-sm gv-text-bold"><?php _e('With demo content',OC_PLUGIN_DOMAIN)?></p>
								<p class="gv-caption-lg gv-text-on-alternative"><?php _e('Includes demo posts, pages, images and theme settings. No existing content will be deleted or changed.',OC_PLUGIN_DOMAIN)?></p>

							</div>
						</div>
						<div class="gv-notice gv-notice-warning gv-mode-condensed oc-modal-warning">
							<img class="gv-icon" src="<?php echo ONECOM_WP_URL . 'assets/images/warning.svg'; ?>" />
							<p class="gv-mode-condensed"><?php _e('We only recommend demo content for new websites . If you already have a website , the demo content will mix up with your existing content .',OC_PLUGIN_DOMAIN)?></p>
						</div>
					</div>
				</div>
				<div class="gv-button-group">
					<button type="button" class="gv-button gv-button-cancel oc-modal-close"><?php _e('Cancel',OC_PLUGIN_DOMAIN) ?></button>
					<button id="one-activate-theme" type="button" class="gv-button gv-button-primary"><?php _e('Continue',OC_PLUGIN_DOMAIN) ?></button>
				</div>
			</div>
		</div>

		<div class="right-content gv-flex oci-theme-preview-screen-right">
			<div class="gv-toast-container">
				<div class="gv-toast gv-toast-success gv-invisible">
					<p class="gv-toast-content"></p>
					<button class="gv-toast-close">
						<img class="gv-icon" src="<?php echo ONECOM_WP_URL . '/modules/home/assets/icons/close.svg'; ?>" />
					</button>
				</div>
				<div class="gv-toast gv-toast-alert">
					<p class="gv-toast-content"><?php _e('Couldn’t import demo content.',OC_PLUGIN_DOMAIN) ?></p>
					<button class="gv-toast-close">
						<img class="gv-icon" src="<?php echo ONECOM_WP_URL . '/modules/home/assets/icons/close.svg'; ?>" />
					</button>
				</div>
			</div>

			<div class="theme-preview-wrap gv-w-full">
				<div class="theme-notification hide">
					<div class="gv-notice gv-notice-warning">
						<img class="gv-notice-icon gv-icon" src="<?php echo ONECOM_WP_URL . '/assets/images/warning.svg'; ?>>
					<p class="gv-notice-content"><b><?php _e('Important', OC_PLUGIN_DOMAIN)?>:</b> <?php _e('Support for our one.com classic themes will end during 2025. If you choose a classic theme for your site, we won’t be able to provide you with customised content.', OC_PLUGIN_DOMAIN);?></p>
					</div>
				</div>
				<div class="oci-themes">
					<?php $tab1 = '';
					$tab2 = '';
					foreach ($html as $theme){
						if ( str_contains( $theme , 'classic-theme' ) ) {
							// Element has the classic-theme class
							$tab1 .= $theme;
						} else {
							// Element does not have the classic-theme class
							$tab2 .= $theme;
						}
					}
					echo '<div role="tabpanel" class="gv-tab-panel gv-panel-active all">' . $tab2 . '</div>';
					echo '<div role="tabpanel" class="gv-tab-panel classic-theme">' . $tab1 . '</div>';
					?>
				</div>
			</div>
		</div>
	<?php } ?>

	<?php add_thickbox(); ?>

	<div id="thickbox_preview"  style="display:none">
		<div id="preview_box" class="gv-activated">

			<div class="preview-container keep-open">
				<div class="desktop-content text-center preview">
					<div class="theme-notification-preview hide keep-open">
						<div class="gv-notice gv-notice-warning keep-open">
							<img class="gv-notice-icon gv-icon keep-open" src="<?php echo ONECOM_WP_URL . '/assets/images/warning.svg'; ?>">
							<p class="gv-notice-content keep-open"><b><?php _e('Important', OC_PLUGIN_DOMAIN)?>:</b> <?php _e('Support for our one.com classic themes will end during 2025. If you choose a classic theme for your site, we won’t be able to provide you with customised content.', OC_PLUGIN_DOMAIN);?></p>
							<button class="gv-notice-close keep-open close-preview-notification">
								<img class="close-notification gv-icon keep-open" src="<?php echo ONECOM_WP_URL . '/assets/images/close.svg'; ?>">
							</button>
						</div>
					</div>
					<iframe title="Preview"></iframe>
					<!-- Set any default iframe source, else it renders current (theme listing) page although hidden -->
					<iframe src='https://wpaddon-static.cdn-one.com/themes/onecom-acupuncture-clinic/preview?ver=0.5.1' title="Preview"></iframe>
				</div>
			</div>
			<div class="header_btn_bar keep-open">
				<div class="bottom-header keep-open">
					<div class="left-section-preview keep-open">
						<a href="javascript:void(0);" class="gv-button gv-button-cancel close_btn"><?php _e('Back', OC_PLUGIN_DOMAIN);?></a>
						<a href="javascript:void(0);" class="gv-button gv-button-primary select-preview-theme"><?php _e('Install theme', OC_PLUGIN_DOMAIN);?></a>
					</div>
					<div class="right-section-preview keep-open">
						<div class="gv-button-toggle-group keep-open">
							<button type="button" class="gv-button-toggle gv-active btn button_2 view-icon keep-open alternative" id="desktop">
								<img class="gv-icon keep-open" src="<?php echo ONECOM_WP_URL . '/assets/images/desktop-icon.svg'; ?>">
							</button>
							<button type="button" class="gv-button-toggle btn button_2 view-icon keep-open alternative" id="tablet">
								<img class="gv-icon keep-open"  src="<?php echo ONECOM_WP_URL . '/assets/images/icon-tablet.svg'; ?>">
							</button>
							<button type="button" class="gv-button-toggle btn button_2 view-icon keep-open alternative" id="mobile">
								<img class="gv-icon keep-open" src="<?php echo ONECOM_WP_URL . '/assets/images/icon-smartphone.svg'; ?>">
							</button>
						</div>
					</div>
					<div class="empty keep-open"></div>
				</div>
			</div>
		</div>
	</div>
</div>