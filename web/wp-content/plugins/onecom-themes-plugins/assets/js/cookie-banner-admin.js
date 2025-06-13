(function ($){

	bannerShow();
	$(window,document).resize(() => {
		bannerShow();
	});

	function bannerShow(){
		var bannerHtml = $('#backup-banner').html();
		if ($(window).width() > 1190) {
			$('#responsive-cookie-banner').html('');
			$('#nonresponsive-cookie-banner').html(bannerHtml);
		}else {
			$('#responsive-cookie-banner').html(bannerHtml);
			$('#nonresponsive-cookie-banner').html('');
		}
	}
	// disable all submit buttons until form data changed
	jQuery('.oc_cb_btn').prop('disabled', true);

	/* enable banner button */
	jQuery(document).on('change', '#cb_enable', function(){
		if ( ! jQuery(this).prop("checked")) {
			jQuery('.fieldset.cb_fields').removeClass('show');
			//on disable cb
			if ( ! oc_constants.isPremium) {
				jQuery('#banner_preview, #backup-banner > div').removeClass();
				jQuery('#oc_cb_config_form').submit();
				return false;
			}
		}
		else {
			//on enable cb
			if ( ! oc_constants.isPremium) {
				jQuery(this).prop("checked", false);
				jQuery('.fieldset.cb_fields').removeClass('show');
				check_upsell_view('onEnable');
				jQuery('#banner_preview, #backup-banner > div').removeClass();
				return false;
			}else {
				jQuery('.fieldset.cb_fields').removeClass('show').addClass('show');
			}
		}
	});

	/* enable policy link */
	jQuery(document).on('change', '#toggle_policy', function(){
		if ( ! jQuery(this).prop("checked")) {
			jQuery('.fieldset.policy_fields').removeClass('show');
		}
		else {
			jQuery('.fieldset.policy_fields').removeClass('show').addClass('show');
		}
	});

	// Store initial form data to match before enable/disable submit button
	jQuery(this).data('serialized', jQuery('#oc_cb_config_form').serialize());

	/* policy text remaining characters */
	jQuery(document).on('input', '#oc_cb_config_form input, #oc_cb_config_form textarea', function(){
		setTimeout(function(){
			oc_cb_validate_form();
		},200);
	});

	/* update preview based on focused field */
	jQuery(document).on('click', '#oc_cb_config_form input, #oc_cb_config_form textarea', function(){
		let status,fill,elm_class;

		// get bg fill color
		fill = jQuery('input[name="banner_style"]:checked').val();

		// check if banner disabled.
		status = jQuery('input[name="show"]:checked').val();
		if ( ! status) {
			jQuery('#banner_preview, #backup-banner > div').removeClass();
			return true;
		}


		// get the clicked element
		element = jQuery(this).attr('name');

		switch (element) {
			case 'banner_style':
				fill      = jQuery(this).val();
				elm_class = 'fill_' + fill;
				break;

			case 'banner_text':
				elm_class = 'text_' + fill;
				break;

			case 'policy_link':
			case 'policy_link_text':
			case 'policy_link_url':
				elm_class = 'link_' + fill;
				break;

			case 'button_text':
				elm_class = 'button_' + fill;
				break;

			default:
				elm_class = 'fill_' + fill;

		}
		jQuery('#banner_preview, #backup-banner > div').removeClass();
		jQuery('#banner_preview, #backup-banner > div').addClass(elm_class);

	});

	jQuery(document).on('submit', '#oc_cb_config_form', function(e){
		e.preventDefault();

		// validate fields
		if ( ! oc_cb_validate_form()) {
			return false;
		}

		// hide any previously shown errors from UI
		jQuery('#oc_cb_errors').removeClass('show');
		jQuery(".oc_cb_spinner").removeClass('success').addClass('is-active');

		// collect the form fields data
		let form_data = jQuery('#oc_cb_config_form').serialize();
		let data      = {
			action: 'oc_cb_settings',
			oc_cb_sec: oc_constants.oc_cb_token,
			settings: form_data
		};

		jQuery.ajax({
			url: ajaxurl,
			type: "POST",
			data: data,
			dataType: "JSON",
			error: function (xhr, textStatus, errorThrown) {
				console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);
				jQuery('#oc_cb_errors').html("").html("Failed to save settings. Please reload the page and try again.").addClass('show');
				jQuery(".oc_cb_spinner").removeClass('is-active').removeClass('success').addClass('error');
			},
			success: function (data) {
				if (data.error) {
					jQuery(".oc_cb_spinner").removeClass('is-active').removeClass('success').addClass('error');
					return false;
				}
				jQuery(".oc_cb_spinner").removeClass('is-active').addClass('success');

				/**
				 * After save, disable submit button, and
				 * store current form data to match later and enable submit button accordingly
				 */
				setTimeout(function() {
					jQuery(".oc_cb_spinner").removeClass('is-active').removeClass('success');
					jQuery('.oc_cb_btn').prop('disabled', true);
					jQuery(this).data('serialized', jQuery('#oc_cb_config_form').serialize());
				}, 2000);
			},
			statusCode: {
				200: function () {},
				404: function () {
					jQuery('#oc_cb_errors').html("").html("Failed to save settings. Please reload the page and try again.").addClass('show');
				},
				500: function () {
					jQuery('#oc_cb_errors').html("").html("Something went wrong; internal server error while processing the request!").addClass('show');
				}
			}
		});
		return false;
	});

	jQuery(document).ready(function () {
		// Show floating save button if regular button is not in viewport (via JS Observer API)
		let observer = new IntersectionObserver(function(entries) {
			// isIntersecting is true when element and viewport are overlapping else false
			if (entries[0].isIntersecting === true) {
				jQuery('.oc_cb_float_btn').hide();
				jQuery('.floating-spinner').hide();
			} else {
				jQuery('.oc_cb_float_btn').show();
				jQuery('.floating-spinner').show();
			}
		}, { threshold: [0] });

		// Observe/Float button only if element exists
		if ( jQuery('.oc_cb_regular_submit').length ) {
			observer.observe(document.querySelector(".oc_cb_regular_submit"));
		}

	});

	/* save settings */
	jQuery(document).on('click', '.oc_cb_btn', function(){
		jQuery('#oc_cb_config_form').submit();
	});

})(jQuery);

function oc_cb_validate_form(){

	let oc_cb_error  = false;
	let oc_cb_submit = ".oc_cb_btn";

	// hide any previously shown errors from UI
	jQuery('#oc_cb_errors').removeClass('show');
	jQuery(".oc_cb_spinner").removeClass('success').removeClass('is-active');

	/* check textarea */
	let cb_text           = "#banner_text";
	let cb_text_maxlength = jQuery(cb_text).attr('maxlength');
	let cb_rem            = "#occb_rem";

	jQuery(cb_rem).html((jQuery(cb_text).val().length) + " / " + cb_text_maxlength);

	// return false if no text entered
	if (jQuery(cb_text).val().length == 0) {
		jQuery(cb_text).addClass('occberror');
		oc_cb_error = true;
	}
	else if (jQuery(cb_text).val().length >= 490) {
		jQuery(cb_rem).css("display", "inline-block");
		jQuery(cb_text).removeClass('maxlimit');
		// jQuery(cb_text).addClass('occberror');
	}
	else {
		jQuery(cb_rem).css("display", "none");
		jQuery(cb_text).removeClass('occberror');
		jQuery(cb_text).removeClass('maxlimit');
	}


	/* check policy link text */
	let cb_link_text = "#policy_link_text";
	if (jQuery(cb_link_text + ':visible').length && jQuery(cb_link_text).val().length == 0) {
		jQuery(cb_link_text).addClass('occberror');
		oc_cb_error = true;
	}
	else {
		jQuery(cb_link_text).removeClass('occberror');
	}

	/* check policy link */
	let cb_policy_link = "#policy_link_url";
	let cb_link_ptrn   = new RegExp("(https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9-]+[a-zA-Z0-9-]\.[^\s]{2,}|https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9]\.[^\s]{2,}|www\.[a-zA-Z0-9]\.[^\s]{2,})");

	if ( jQuery(cb_policy_link + ':visible').length && ((jQuery(cb_policy_link).val().length == 0) || (cb_link_ptrn.test(jQuery(cb_policy_link).val()) == false))) {
		jQuery(cb_policy_link).addClass('occberror');
		oc_cb_error = true;
	}
	else {
		jQuery(cb_policy_link).removeClass('occberror');
	}



	/* check button text */
	let oc_cb_btn_text = "#button_text";
	if (jQuery(oc_cb_btn_text).val().length) {
		jQuery(oc_cb_btn_text).removeClass('occberror');
	}
	else {
		jQuery(oc_cb_btn_text).addClass('occberror');
		oc_cb_error = true;
	}

	if (oc_cb_error && jQuery('#cb_enable:checked').length) {
		jQuery(oc_cb_submit).attr("disabled", "disabled");
		return false;
	}

	// After CB validation, enable only if any change in form data
	jQuery('.oc_cb_btn').prop('disabled', jQuery('#oc_cb_config_form').serialize() == jQuery(this).data('serialized'));

	return true;

}

/**
 * Check premium
 * @param $checkStatus
 */
function check_upsell_view(checkStatus){

	if (checkStatus === 'onEnable') {
		let referrer = location.search;
		jQuery('#oc_um_overlay').show();
		ocSetModalData({
			isPremium: oc_constants.isPremium,
			feature: 'cookie_banner',
			featureAction: 'settings',
			referrer: referrer
		});
	}
}