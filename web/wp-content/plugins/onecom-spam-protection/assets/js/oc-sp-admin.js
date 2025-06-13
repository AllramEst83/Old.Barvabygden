(function ($) {
	var currentTab;
	let SP = {
		handleTabClick: function (el) {
			let target = $(el).attr('data-tab');
			$('.onecom_tabs_panel').fadeOut('fast');
			$(el).parent().find('.active').removeClass('active');
			$(el).addClass('active');
			$('#' + target).fadeIn('fast');

		},
		formSubmission: function (formname) {
			$(formname).on('click', '.oc-save', function (e) {

				e.preventDefault();
				let formID = $(formname).attr('id');

				$(this).siblings('#oc_sp_spinner').removeClass('success').addClass('is_active');
				if (formID == 'sp-settings') {
					$(this).val('Saving');
					SP.protectionForm(formname, $(this));
				} else if (formID == 'sp-advanced-settings') {
					$(this).val('Saving');
					SP.advanceSettings(formname, $(this));
				} else if (formID == 'sp-diagnostics') {

					SP.spamDiagnostics(formname, $(this));
				} else if (formID == 'sp-clear-logs') {
					$(this).val('Clearing')
					SP.spamClearlogs(formname, $(this));
				}

			})
		},

		protectionForm: function (e, button) {

			let elements = document.forms["sp-protect-options"].elements,

				data = {
					action: 'oc_save_settings',
					checks:
						{
							oc_sp_accept: (elements["oc_sp_accept"]).checked,
							oc_sp_referrer: (elements["oc_sp_referrer"]).checked,
							oc_sp_long: (elements["oc_sp_long"]).checked,
							oc_sp_short: (elements["oc_sp_short"]).checked,
							oc_sp_bbcode: (elements["oc_sp_bbcode"]).checked,
							oc_sp_exploit: (elements["oc_sp_exploit"]).checked,
							oc_sp_quickres: (elements["oc_sp_quickres"]).checked,
							oc_max_login_val: (elements["oc_max_login_val"]).value,
							oc_block_time: (elements["oc_block_time"]).value,
							one_sp_nonce: elements["one_sp_nonce"].value,
						},
				}

				if ( ! SP.protectionFormValidate(elements)) {
                $(button).siblings('#oc_sp_spinner').removeClass('is_active');
                $(button).val('Save');
                return false;
				}

			$.post(ajaxurl, data, function (response) {
				$(button).siblings('#oc_sp_spinner').removeClass('is_active');

				if (response.success) {

					$(button).siblings('#oc_sp_spinner').addClass('success');
					$(e).parents().find('.notice-success').hide();
					$('#oc-sp-success').fadeIn('slow');
					$(e).find('input:submit').attr('disabled', true);
					$(button).val('Save');
					SP.disableSubmit();
					setTimeout(function () {
						$(button).siblings('#oc_sp_spinner').removeClass('success');
					}, 6000);

				}

			})

		},
		// Validate login attempts and blocking duration via Regex
		protectionFormValidate: function (elements) {
			let validationError;

			let maxLoginVal   = elements['oc_max_login_val'].value;
			let blockDuration = elements['oc_block_time'].value;
			if (maxLoginVal === null || maxLoginVal === "" || ! (/^([1-9]|10)$/.test(maxLoginVal.trim()))) {
				$("input[name=oc_max_login_val]").addClass('oc_input_error');
				validationError = 1;
			}

			if (blockDuration === null || maxLoginVal === "" || ! (/^(1[0-9]|[2-9][0-9]|[1-8][0-9]{2}|900)$/.test(blockDuration.trim()))) {
				$("input[name=oc_block_time]").addClass('oc_input_error');
				validationError = 1;
			}

			// If any error detected, return false, else true
			if (validationError === 1) {
				if ($('.sp-protect-options').find('.oc-dg-err').length === 0) {
					// Add notice if it's not already there
					$('.last-form-item').after('<div class="notice notice-error oc-dg-err"><p class="error">' + onespnotice.failed_login_validation_msg + '</p></div>');
				}
				return false;
			} else {
				return true;
			}
		},

		advanceSettings: function (e, button) {
			let elements         = document.forms['sp-advanced-settings'].elements,
				ocSpWhitelist    = elements['oc_sp_whitelistuser'].checked,
				ocSpWhitelistIP  = elements['oc_spwhitelistip'].checked,
				ocSpBaduseragent = elements['oc_spbadusragent'].checked,
				ocSpUrlshort     = elements['oc_sp_urlshort'].checked,
				ocSpProburl      = elements['oc_sp_proburl'].checked,

				advancedData = {
					action: 'oc_save_advanced_settings',
					oc_sp_whitelistuser: ocSpWhitelist,
					oc_spbadusragent: ocSpBaduseragent,
					oc_sp_urlshort: ocSpUrlshort,
					oc_sp_proburl: ocSpProburl,
					oc_spwhitelistip: ocSpWhitelistIP,
					one_sp_nonce: elements["one_sp_nonce"].value,

					whitelist_usernames: (elements['oc_whitelist_usernames'].value !== '') ? elements['oc_whitelist_usernames'].value.split("\n") : '',
					whitelist_ips: (elements['oc_whitelist_ips'].value !== '') ? elements['oc_whitelist_ips'].value.split("\n") : '',
					whitelist_agents: elements['oc_whitelist_useragent'].value !== '' ? elements['oc_whitelist_useragent'].value.split("\n") : '',
					url_shorteners: elements['oc_url_shorters'].value !== '' ? elements['oc_url_shorters'].value.split("\n") : '',
					exploit_urls: elements['oc_exploit_urls'].value !== '' ? elements['oc_exploit_urls'].value.split("\n") : '',
				};

			if ( ! ocSpWhitelist) {
				delete (advancedData.whitelist_usernames);
			}
			if ( ! ocSpWhitelistIP) {
				delete (advancedData.whitelist_ips);
			}
			if ( ! ocSpBaduseragent) {
				delete (advancedData.whitelist_agents);
			}
			if ( ! ocSpUrlshort) {
				delete (advancedData.url_shorteners);
			}
			if ( ! ocSpProburl) {
				delete (advancedData.exploit_urls);
			}

			SP.executeAjaxRequest(e, advancedData, button);

		},

		executeAjaxRequest: function (e, data, button) {

			$.post(ajaxurl, data, function (response) {
				$(button).siblings('#oc_sp_spinner').removeClass('is_active');

				if (response.success) {

					$(button).siblings('#oc_sp_spinner').addClass('success');
					$(e).parents().find('.notice-success').hide();
					$(e).parents().find('.advanced-settings').fadeIn('slow');
					$(e).find('input:submit').attr('disabled', true);
					$(button).val('Save');
					SP.disableSubmit();
					setTimeout(function () {
						$(button).siblings('#oc_sp_spinner').removeClass('success');
					}, 6000);

				}

			})

		},

		spamDiagnostics: function (e, button) {

			$(button).val('Checking');

			let elements       = document.forms['sp-diagnostics'].elements,
				$this          = $(e),
				validation_err = $this.parent().find('.oc-dg-err');

			let emptyFields = $this.find(":input").filter(function () {
				return $.trim(this.value) === "";
			});

			/**
			 * If all fields empty, show error to enter at-least one parameter
			 * Else if email or ip address is not valid, show validation message
			 * else submit data successfully
			 */
			if (
				(emptyFields.length) === 5
			) {
				$(e).parents().find('.oc-dg-err').remove();
				$this.parent().prepend('<div class="notice notice-error oc-dg-err"><p class="error">' + onespnotice.oc_notice + '</p></div>');
				$(button).siblings('#oc_sp_spinner').removeClass('is_active');
				$(button).val('Check for spam');
				return false;
			} else if ( ! SP.diagnosticsFormValidate(elements)) {
				$(e).parents().find('.oc-dg-err').remove();
				$this.parent().prepend('<div class="notice notice-error oc-dg-err"><p class="error">' + onespnotice.generic_validation_msg + '</p></div>');
				$(button).siblings('#oc_sp_spinner').removeClass('is_active');
				$(button).val('Check for spam');
				return false;

			} else {
				$(e).find('input:submit').attr('disabled', true);
				$(e).parents().find('.oc-dg-err').remove();

				let data = {
					action: 'oc_check_spam_diagnostics',
					oc_validate_ip: elements['oc_validate_ip'].value,
					oc_validate_user: elements['oc_validate_user'].value,
					oc_validate_email: elements['oc_validate_email'].value,
					oc_validate_user_agent: elements['oc_validate_user_agent'].value,
					oc_validate_content: elements['oc_validate_content'].value,
					one_sp_nonce: elements["one_sp_nonce"].value,
				}

				$.post(ajaxurl, data, function (response) {

					$(button).siblings('#oc_sp_spinner').removeClass('is_active');

					if (response.success) {

						$(button).siblings('#oc_sp_spinner').addClass('success');
						$(e).parents().find('.ocdg-results').html(response.data);
						$(e).find('input:submit').attr('disabled', false);
						$(e)[0].reset();
						$(button).val('Check for spam');
						setTimeout(function () {
							$(button).siblings('#oc_sp_spinner').removeClass('success');
						}, 6000);

					}

				})
			}

		},
		// Validate IP and Email address only if entered via Regex
		diagnosticsFormValidate: function (elements) {
			let validationError;
			let ipValue       = elements['oc_validate_ip'].value;
			let emailValue    = elements['oc_validate_email'].value;
			const ipv4Pattern = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
			const ipv6Pattern = /^(([0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){0,7}[0-9a-fA-F]{1,4}|(([0-9a-fA-F]{1,4}:)*[0-9a-fA-F]{1,4})?::(([0-9a-fA-F]{1,4}:)*[0-9a-fA-F]{1,4})?|([0-9a-fA-F]{1,4}:){1,7}:|:(:[0-9a-fA-F]{1,4}){1,7})(%.+)?$/;
			if (ipValue.length > 0 &&
				! (ipv4Pattern.test(ipValue) || ipv6Pattern.test(ipValue))) {
				$("#ocvalidateip").addClass('oc_input_error');
				validationError = 1;
			}

			if (emailValue.length > 0 &&
				! (/^\S+@\S+\.\S+$/.test(emailValue.trim()))) {
				$("#ocvalidateemail").addClass('oc_input_error');
				validationError = 1;
			}

			// If any error detected, return false, else true
			if (validationError === 1) {
				return false;
			} else {
				return true;
			}
		},

		spamClearlogs: function (e, button) {
			$(e).find('input:submit').attr('disabled', true);

			let data = {
				action: 'oc_clear_spam_logs',
				one_sp_nonce: $(e).find(".one_sp_nonce").val(),
			}

			$.post(ajaxurl, data, function (response) {

				$(button).siblings('#oc_sp_spinner').removeClass('is_active');

				if (response.success) {

					$(e).parents().find('.notice-success').hide();
					$(button).siblings('#oc_sp_spinner').addClass('success');
					$(e).parents().find('.one-sp-logs').html(response.data);
					$(e).find('input:submit').attr('disabled', false);

				}

			})

		},
		disableSubmit: function () {
			let arrForm = [
				$('form.sp-protect-options'),
				$('form.sp-blocked-lists')
			];
			let formObj = $.map(arrForm, function (el) {
				return el.get()
			});
			$(formObj).each(function () {
				$(this).data('serialized', $(this).serialize())
			})
				.on('change input', function () {
					$(this)
						.find('input:submit')
						.attr('disabled', $(this).serialize() == $(this).data('serialized'));

				})
				.find('input:submit, button:submit')
				.attr('disabled', true);
		},

		disableTextArea: function (checkboxEl, textareaEl, additionalInput = '') {
			if (checkboxEl && checkboxEl.prop('checked') === true) {
				textareaEl.prop('disabled', false).css('background', '#ffffff');

				if (additionalInput !== '') {
					additionalInput.prop('disabled', false).css('background', '#ffffff');
				}
			} else if (checkboxEl && checkboxEl.prop('checked') !== true) {
				textareaEl.prop('disabled', true).css('background', '#f0f0f1');

				if (additionalInput !== '') {
					additionalInput.prop('disabled', true).css('background', '#f0f0f1');
				}
			}
		},

		toggleOnChange: function (checkboxEl, textareaEl, additionalInput = '') {
			checkboxEl.on('change', function () {
				let checked = $(this).prop('checked');
				textareaEl.prop('disabled', ! checked);
				if (additionalInput !== '') {
					additionalInput.prop('disabled', ! checked);
				}
				if ( ! checked) {
					textareaEl.css('background', '#f0f0f1');
					if (additionalInput !== '') {
						additionalInput.css('background', '#f0f0f1');
					}
				} else {
					textareaEl.css('background', '#ffffff');
					if (additionalInput !== '') {
						additionalInput.css('background', '#ffffff');
					}
				}
			});
		}

	}

	$(document).ready(function () {

		// Remove any input field error on focus
		$('form.sp-diagnostics input').focus(function() {
			$('form.sp-diagnostics input').removeClass('oc_input_error');
		});
		$('form.sp-protect-options input').focus(function() {
			$('form.sp-protect-options input').removeClass('oc_input_error');
			$("form.sp-protect-options .oc-dg-err").remove();
		});

		$('.onecom_tab').click(function (e) {
			SP.handleTabClick($(this));
			currentTab = $('.onecom_tab.active').data('tab');
		});

		$('.oc-duration-filter').change(function (e) {
			const $this = $(this);
			if ($this.hasClass('disabled-section')) {
				return false;
			}
			$('.filter-summary ul li').removeClass('active');
			if ( ! $this.parent().hasClass('active')) {
				$this.parent().addClass('active');
			}
			// console.log($(this).data('duration'))
			let data = {
				action: 'oc_get_summary',
				duration: $('option:selected', this).data('duration')
			};
			$('span#oc_switch_spinner').css('visibility', 'visible');
			const total_count      = $('.oc-summary-body').find('.oc_total_count'),
				comment_count      = $('.oc-summary-body').find('.oc_comment_count'),
				registration_count = $('.oc-summary-body').find('.oc_registration_count'),
				failed_login_count = $('.oc-summary-body').find('.oc_failed_login_count'),
				other_count        = $('.oc-summary-body').find('.oc_other_count');

			$.post(ajaxurl, data, function (response) {
				total_count.html(response.total_count);
				comment_count.html(response.comments_count);
				registration_count.html(response.registration_count);
				failed_login_count.html(response.failed_login);
				other_count.html(response.other_count);
				$('#oc_switch_spinner').css('visibility', 'hidden');

			});
		});

		const blocked_lists        = $('.sp-blocked-lists'),
			whitelist              = blocked_lists.find('#spbadusragent'),
			urlshortener           = blocked_lists.find('#spurlshort'),
			proburl                = blocked_lists.find('#spprobchk'),
			whitelist_users        = blocked_lists.find('#spwhitelistusername'),
			whitelistIP            = blocked_lists.find('#spwhitelistIP'),
			username_textarea      = blocked_lists.find('.oc_whitelist_usernames'),
			IPtextarea             = blocked_lists.find('.oc-whitelist-ip'),
			useragent_textarea     = blocked_lists.find('.oc-whitelist-useragent'),
			urlshorteners_textarea = blocked_lists.find('.oc-url-shorters'),
			exploit_url_textarea   = blocked_lists.find('.oc-exploit-urls'),
			limitlogin             = $('#spquickres'),
			max_login_val          = $('.oc_max_login_val'),
			block_time             = $('.oc_block_time');

		// events trigger on page load
		SP.disableTextArea(whitelist_users, username_textarea);
		SP.disableTextArea(whitelistIP, IPtextarea);
		SP.disableTextArea(whitelist, useragent_textarea);
		SP.disableTextArea(urlshortener, urlshorteners_textarea);
		SP.disableTextArea(proburl, exploit_url_textarea);
		SP.disableTextArea(limitlogin, max_login_val, block_time);

		// page load events end //

        // events which triggers on change of the toggle switches //
		SP.toggleOnChange(whitelist_users, username_textarea);
		SP.toggleOnChange(whitelistIP, IPtextarea);
		SP.toggleOnChange(whitelist, useragent_textarea);
		SP.toggleOnChange(urlshortener, urlshorteners_textarea);
		SP.toggleOnChange(proburl, exploit_url_textarea);
		SP.toggleOnChange(limitlogin, max_login_val, block_time);

        // on change events end //

		$('.oc-show-modal').on('click', function (e) {

			e.preventDefault();

			jQuery.ajax({
				url: ajaxurl,//NOTE: Change ajaxurl as per your variable
				type: "POST",
				data: {
					action: 'show_plugin_dependent_popup',//NOTE:Same value required
					popupContent: onespnotice.popupContent//NOTE:Same value required
				},
				success: function (response) {
					var result = response;
					if (typeof result.success != 'undefined' && result.success === true) {
						//success message
						//NOTE: Change premium condition as per your variable
						// if(!parseInt(ocvmObj.isPremium)){
						$('#oc_um_overlay').html(result.data);
						var referrer = location.search;
						$('#oc_um_overlay').show();
						ocSetModalData({
							isPremium: true,//NOTE: Change premium condition as per your variable
							feature: 'spam_protection',//NOTE: Change feature value as per your plugin dependent call
							featureAction: 'getStarted',//NOTE: Change featureAction value as per your plugin dependent call
							referrer: referrer
						});
					}
				},
				error: function (xhr, textStatus, errorThrown) {
					//error log
				}
			});

		})

		const checkDisabled = document.getElementsByClassName('disabled-section');

		if (checkDisabled.length === 0) {

            // Show floating save button if regular button is not in viewport (via JS Observer API)
			let observer = new IntersectionObserver(function (entries) {
				// isIntersecting is true when element and viewport are overlapping else false
				if (entries[0].isIntersecting === true) {
					$('.oc-sp-float-btn').hide();
					$('.float-spinner').removeClass('success').hide();
				} else {
					$('.oc-sp-float-btn').show();
					$('.float-spinner').show();
				}
			}, {threshold: [0]});

			observer.observe(document.querySelector("#onecom-sp-ui #settings .oc-save"));
			observer.observe(document.querySelector("#onecom-sp-ui #advanced_settings .oc-save"));

		}
		// disable submit button if no change in settings form
		let settingsForm        = $('#onecom-sp-ui').find('form.sp-protect-options'),
			advanceSettingsForm = $('#onecom-sp-ui').find('form.sp-blocked-lists'),
			spamDiagnosticsForm = $('#onecom-sp-ui').find('form.sp-diagnostics'),
			spamClearLogs       = $('#onecom-sp-ui').find('form#sp-clear-logs');

		SP.formSubmission(settingsForm);
		SP.formSubmission(advanceSettingsForm);
		SP.formSubmission(spamDiagnosticsForm);
		SP.formSubmission(spamClearLogs);
		SP.disableSubmit();

	})

})(jQuery)



