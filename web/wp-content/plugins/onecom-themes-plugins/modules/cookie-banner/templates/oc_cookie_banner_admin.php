<div class="wrap" id="onecom-ui">


	<h1 class="one-title"> <?php echo __( 'Utility Tools', 'onecom-wp' ); ?> </h1>
	<div class="page-subtitle">
		<?php _e( 'Helpful tools for building and maintaining your site.', 'onecom-wp' ); ?>
	</div>

	<?php
		$settings = get_site_option( 'oc_cb_configuration' );
	if ( empty( $settings ) || empty( $settings['config'] ) ) {
		$settings = array(
			'show_notice' => true,
			'config'      => array(
				'show'             => 0,
				'banner_text'      => __( 'This website uses cookies. By continuing to use this site, you accept our use of cookies.', 'onecom-wp' ),
				'policy_link'      => '',
				'policy_link_text' => __( OCCB_LEARN_MORE, 'onecom-wp' ),
				'policy_link_url'  => '',
				'button_text'      => __( 'Accept', 'onecom-wp' ),
				'banner_style'     => 'grey',
			),
		);
	}

		$settings = $settings['config'];

	if ( ! defined( 'OCCB_CHECKED' ) ) {
		define( 'OCCB_CHECKED', 'checked' );
	}

	if ( ! defined( 'OCCB_SHOW' ) ) {
		define( 'OCCB_SHOW', 'show' );
	}


		// get enabled status
		$show = $checked = '';
	if ( $settings['show'] ) {
		$show    = OCCB_SHOW;
		$checked = OCCB_CHECKED;
	}

		// get policy link status
		$pshow = $pchecked = '';
	if ( $settings['policy_link'] ) {
		$pshow    = OCCB_SHOW;
		$pchecked = OCCB_CHECKED;
	}

		// get banner_preview class
		$bp_class = '';
	if ( $settings['show'] ) {
		$bp_class = 'class="fill_' . $settings['banner_style'] . '"';
	}

	?>

	<div class="wrap_inner inner one_wrap oc_card cookie-banner">
		<div class="card-left settings-left">
			<div class="oc-flex-center oc-icon-box">
				<img src="<?php echo ONECOM_WP_URL; ?>/assets/images/Cookie.svg" alt="Cookie">
				<h2 class="main-heading"><?php echo __( 'Cookie banner', 'onecom-wp' ); ?></h2>
			</div>
			<p class="oc_desc indent"><?php echo __( 'Show a banner on your website to inform visitors about cookies and get their consent.', 'onecom-wp' ); ?></p>
			<div id="responsive-cookie-banner"></div>
			<form name="oc_cb_config_form" id="oc_cb_config_form" class="cookie-form">
				<div class="fieldset enable-cookie-banner">
					<label for="cb_enable">
						<span class="oc_cb_switch">
							<input type="checkbox" class="ocwp_ocp_cb_cookie_banner_status_toggled_event" id="cb_enable" name="show" value=1 <?php echo $checked; ?> />
							<span class="oc_cb_slider"></span>
						</span>
						<?php echo __( 'Enable cookie banner', 'onecom-wp' ); ?>
					</label>

				</div>

				<div class="fieldset cb_fields <?php echo $show; ?>">
					<label>
						<?php echo __( 'Banner text', 'onecom-wp' ); ?> <span id="occb_rem"></span>
						<textarea name="banner_text" id="banner_text" maxlength="500" placeholder="<?php echo __( 'Add a text asking your users to accept cookies.', 'onecom-wp' ); ?>"><?php echo $settings['banner_text']; ?></textarea>
					</label>
					<p class="oc_desc"><?php echo __( 'Cookie requirements vary. This default text is for general use but may not meet your particular legal requirements.', 'onecom-wp' ); ?> <a href="<?php echo onecom_generic_locale_link( 'cookie_guide', get_locale() ); ?>" rel="noopener noreferrer" target="_blank"><?php echo __( OCCB_LEARN_MORE, 'onecom-wp' ); ?></a></p>
				</div>

				<div class="fieldset cb_fields <?php echo $show; ?>">
					<label><?php echo __( 'Cookie policy link (Optional)', 'onecom-wp' ); ?></label>
					<span class="oc_gap"></span>
					<label class="oc_plain" for="toggle_policy">
						<input type="checkbox" id="toggle_policy" name="policy_link" value=1 <?php echo $pchecked; ?> /><?php echo __( 'Add a link to your cookie policy', 'onecom-wp' ); ?>
					</label>
					<p class="oc_desc indent" style="font-size: 14px;line-height: 22px;color: #8A8989;"><?php echo __( 'Your privacy policy can contain information about which cookies you use, so visitors can read more about what you collect information about.', 'onecom-wp' ); ?>
						<a href="<?php echo onecom_generic_locale_link( 'cookie_guide', get_locale() ); ?>" rel="noopener noreferrer" target="_blank"><?php echo __( 'What should be included in my cookie policy?', 'onecom-wp' ); ?></a>
					</p>

					<div class="fieldset policy_fields <?php echo $pshow; ?>">
						<label for="policy_link_text">
							<?php echo __( 'Link text', 'onecom-wp' ); ?>
							<input type="text" id="policy_link_text" name="policy_link_text" value="<?php echo $settings['policy_link_text']; ?>" maxlength="70" placeholder="<?php echo __( OCCB_LEARN_MORE, 'onecom-wp' ); ?>" />
						</label>
						<span class="oc_gap"></span><span class="oc_gap"></span>
						<label for="policy_link_url">
							<?php echo __( 'Link URL', 'onecom-wp' ); ?>
							<input type="text" name="policy_link_url" id="policy_link_url" value="<?php echo $settings['policy_link_url']; ?>" placeholder="e.g. https://www.example.com/cookiepolicy" />
						</label>
						<p class="oc_desc"><?php echo __( 'You can link to any page on your website or even add an external link.', 'onecom-wp' ); ?></p>
					</div>

				</div>

				<div class="fieldset cb_fields <?php echo $show; ?>">
					<label for="button_text">
						<?php echo __( 'Button text', 'onecom-wp' ); ?>
						<input type="text" id="button_text" name="button_text" placeholder="<?php echo __( 'Accept', 'onecom-wp' ); ?>" value="<?php echo $settings['button_text']; ?>" maxlength="60" />
					</label>
					<p class="oc_desc"><?php echo __( 'This is the text that appears on the button that activates cookies.', 'onecom-wp' ); ?></p>
				</div>

				<div class="fieldset cb_fields <?php echo $show; ?>">
					<label><?php echo __( 'Banner style', 'onecom-wp' ); ?></label>
					<span class="oc_gap"></span>
					<label class="block-fields" for="oc_cb_fill_grey"><input type="radio" value="grey" name="banner_style" id="oc_cb_fill_grey" data-preview="fill_grey" <?php echo ( $settings['banner_style'] == 'grey' ) ? 'checked' : ''; ?> /><?php echo __( 'Grey', 'onecom-wp' ); ?></label>
					<label class="block-fields" for="oc_cb_fill_black"><input type="radio" value="black" name="banner_style" id="oc_cb_fill_black" data-preview="fill_black" <?php echo ( $settings['banner_style'] == 'black' ) ? 'checked' : ''; ?> /><?php echo __( 'Black', 'onecom-wp' ); ?></label>
					<label class="block-fields" for="oc_cb_fill_white"><input type="radio" value="white" name="banner_style" id="oc_cb_fill_white" data-preview="fill_white" <?php echo ( $settings['banner_style'] == 'white' ) ? 'checked' : ''; ?> /><?php echo __( 'White', 'onecom-wp' ); ?></label>
				</div>
			</form>
			<div class="tip-wrap">
				<span class="idea_icon"></span>
				<div class="wrap-link-pc">
					<strong><?php echo __( 'Tip', 'onecom-wp' ); ?>: </strong><?php echo __( 'Remember to delete cache in case you are using any caching plugin.', 'onecom-wp' ); ?>
					<?php if ( class_exists( 'VCachingOC' ) ) : ?>
						<a href="<?php echo wp_nonce_url( add_query_arg( 'purge_varnish_cache', 1 ), 'vcaching' ); ?>"><?php echo __( 'Purge Performance Cache', 'onecom-wp' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
			<div class="btn-wrap-reg">
				<!-- Regular save button -->
				<button class="oc_cb_btn btn button_1 oc_cb_regular_submit ocwp_ocp_cb_cookie_banner_settings_saved_event" name="oc_cb_btn" disabled="disabled" id="" type="submit"><?php echo __( 'Save', 'onecom-wp' ); ?></button>
				<span class="oc_cb_spinner spinner"></span>

				<!-- Floating save button -->
				<button class="oc_cb_btn btn button_1 oc_cb_float_btn ocwp_ocp_cb_cookie_banner_settings_saved_event" name="oc_cb_btn" disabled="disabled" id="" type="submit"><?php echo __( 'Save', 'onecom-wp' ); ?></button>
				<span class="oc_cb_spinner floating-spinner spinner"></span>

				<!-- Sticky footer save button -->
				<div class="oc_cb_sticky_footer">
					<div class="wrap-sticky-spinner">
					<span class="oc_cb_spinner spinner"></span>
					<button class="oc_cb_btn btn button_1 ocwp_ocp_cb_cookie_banner_settings_saved_event" name="oc_cb_btn" disabled="disabled" id="" type="submit"><?php echo __( 'Save', 'onecom-wp' ); ?></button>
					</div>
				</div>
				<div id="oc_cb_errors"></div>
			</div>
			<span class="oc_gap"></span>
		</div>
		<div class="card-right settings-right">
			<div id="nonresponsive-cookie-banner">

			</div>
		</div>
	</div>
	<div id="backup-banner" style="display:none;">
		<div id="banner_preview" <?php echo $bp_class; ?>></div>
	</div>
</div>