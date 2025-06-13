jQuery(document).ready(function () {

	// Disable premium fields for non-premium (or downgraded package)
	jQuery(".oc-non-premium #dev_mode_duration").prop('disabled', true);
	jQuery(".oc-non-premium #oc_dev_duration_save").prop('disabled', true);
	jQuery(".oc-non-premium #exclude_cdn_data").prop('disabled', true);
	jQuery(".oc-non-premium .oc_cdn_data_save").prop('disabled', true);

	// enable disable save button based on cdn switches state
	// oc_cdn_save_state_change();

	jQuery('#pc_enable').change(function () {
		ocSetVCState();
	});
	jQuery('.oc_ttl_save').click(function(){
		if (oc_validate_ttl()) {
			oc_update_ttl();
		}
	});
	jQuery('.oc_cdn_data_save').click(function(){
		if (oc_validate_cdn_data()) {
			oc_update_cdn_data();
		}
	});

	jQuery("#pc_enable_settings .oc_vcache_ttl").keypress(function(event) {
		jQuery(this).removeClass('oc_error');
		jQuery('#pc_enable_settings .oc-ttl-error-msg').hide();
	});

	jQuery("#dev_mode_enable_settings #dev_mode_duration").keypress(function(event) {
		jQuery(this).removeClass('oc_error');
		jQuery('#dev_mode_enable_settings .oc-ttl-error-msg').hide();
	});

	jQuery("#exclude_cdn_enable_settings #exclude_cdn_data").keypress(function(event) {
		jQuery(this).removeClass('oc_error');
		jQuery('#exclude_cdn_enable_settings .oc-ttl-error-msg').hide();
	});

	jQuery('.oc-activate-wp-rocket-btn').click(function(){
		oc_activate_wp_rocket();
	});

	jQuery('#cdn_enable').change(function (){
		ocSetCdnState();
	});
	jQuery('#dev_mode_enable').change(function (){
		jQuery('#dev_mode_duration').removeClass('oc_error');
		ocSetDevMode();
	});
	jQuery('#exclude_cdn_enable').change(function (){
		jQuery('#exclude_cdn_data').removeClass('oc_error');
		ocExcludeCDNState();
	});

	// disable all submit buttons until form changed
	jQuery('#pc_enable_settings form button.oc_ttl_save').attr('disabled', true);

	// Enable save button when form changed
	let settingsForm = jQuery('#pc_enable_settings form');
	settingsForm.each(function () {
		jQuery(this).data('serialized', jQuery(this).serialize());
	}).on('change keyup paste', function () {
		jQuery(this)
			.find('button.oc_ttl_save')
			.attr('disabled', jQuery(this).serialize() == jQuery(this).data('serialized'));
	})

	// disable CDN setting submit button until form changed
	jQuery('#cdn_settings button.oc_cdn_data_save').attr('disabled', true);

	// Enable save button when form changed
	let cdnSettingsForm = jQuery('#cdn_settings form');
	cdnSettingsForm.each(function () {
		jQuery(this).data('cdnSerialized', jQuery(this).serialize());
	}).on('change keyup paste', function () {
		jQuery(this)
			.find('button.oc_cdn_data_save')
			.attr('disabled', jQuery(this).serialize() == jQuery(this).data('cdnSerialized'));
	})

});

function oc_toggle_state(element) {
	var current_icon = element.attr('src');
	var new_icon     = element.attr('data-alt-image');
	element.attr({
		'src': new_icon,
		'data-alt-image': current_icon
	});
}


function oc_change_cdn_icon(){
	if (jQuery('#cdn_enable').prop('checked')) {
		jQuery('#oc-cdn-icon-active').show();
		jQuery('#oc-cdn-icon').hide();
		jQuery('.oc-cdn-feature-box').show();
		// Remove sub features success classes else spinner animate on each switch
		jQuery('.oc-cdn-feature-box .oc_cb_spinner').removeClass('success');
	} else {
		jQuery('#oc-cdn-icon').show();
		jQuery('#oc-cdn-icon-active').hide();
		jQuery('.oc-cdn-feature-box').hide();
		// Remove sub features success classes else spinner animate on each switch
		jQuery('.oc-cdn-feature-box .oc_cb_spinner').removeClass('success');
	}
}

// activate wp rocket button action
function oc_activate_wp_rocket(){
	jQuery('.oc_activate_wp_rocket_spinner').removeClass('success').addClass('is_active');
	jQuery.post(ajaxurl, {
		action: 'oc_activate_wp_rocket'
	}, function(response){
		jQuery('.oc_activate_wp_rocket_spinner').removeClass('is_active');
		if (response.status === true) {
			jQuery('.oc_activate_wp_rocket_spinner').addClass('success');
			window.location.href = "options-general.php?page=wprocket";
		} else {
			console.log("Error: Could not activate plugin")
		}
	});
}

function oc_show_more_less(){
	if (jQuery(".oc-hidden-content").css('display') === 'none') {
		jQuery(".oc-show-hide a").text("Show less");
		jQuery(".oc-hidden-content").show();
	} else {
		jQuery(".oc-show-hide a").text("Show more");
		jQuery(".oc-hidden-content").hide();
	}
}