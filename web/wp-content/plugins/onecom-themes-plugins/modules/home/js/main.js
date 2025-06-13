(function ($) {
	$(document).ready(function () {
		$('#oc-restart-tour').click(function () {
			console.info('Restart tour')
		})
		$('.gv-notice-close.restart-tour').click(function () {
			const el = $(this);
			$.post(oc_home_ajax_obj.ajax_url, {
					_ajax_nonce: oc_home_ajax_obj.nonce,
					action: "oc_home_silence_tour",
					title: this.value
				}, function (data) {
					if (data.status === 'success') {
						el.parents('.gv-notice').fadeOut()
					}
				}
			);
		})


		$("#oc-start-tour, #oc_login_masking_overlay_wrap .oc_welcome_modal_close").on('click', function (e) {
			e.preventDefault();
			$("#oc_login_masking_overlay").hide();
			$(".loading-overlay.fullscreen-loader").removeClass('show');
			let redirect = true;
			console.log($(this));
			if($(this).hasClass('oc_welcome_modal_close')){
				redirect = false;
			}
			const nonce = 'asdsadsad';

			$.post(oc_home_ajax_obj.ajax_url, {
				'action': 'oc_close_welcome_modal',
				'nonce': nonce
			})
				.done(function (response) {
					if (response && redirect) {
						window.location.href = oc_home_ajax_obj.home_url;
					}else{
						console.log('modal closed');
					}
				})
				.fail(function () {
					console.error("Failed to close the welcome modal.");
				});
		});

		// Show data consent modal
		$(".oc_consent_modal_show").on('click', function (e) {
			e.preventDefault();
			$("#oc_data_consent_overlay").show();
		});

		// Hide data consent modal
		$("#oc-consent-modal-close, #oc_data_consent_overlay .oc_welcome_modal_close").on('click', function (e) {
			e.preventDefault();
			$("#oc_data_consent_overlay").hide();
			$(".loading-overlay.fullscreen-loader").removeClass('show');
		});

		// Update data consent status based on actions
		function ocUpdateConsentStatus(status) {
			const data = {
				action: 'oc_update_consent_status',
				consent_status: status
			};

			fetch(oc_home_ajax_obj.ajax_url, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams(data),
			})
				.then(response => response.json())
				.then(result => {
					if (result.success) {
						$(".oc-consent-toast-container").show();
						$('#oc-consent-toast-success .gv-toast').addClass('gv-visible');
						setTimeout(function() {
							$('#oc-consent-toast-success .gv-toast').removeClass('gv-visible');
						}, 5000);

						$("#oc-data-consent-banner").hide();
						if (status === 1) {
							$('#oc-data-consent-toggle').prop('checked', true);
						}
					} else {
						$(".oc-consent-toast-container").show();
						$('#oc-consent-toast-failure .gv-toast').addClass('gv-visible');
						setTimeout(function() {
							$('#oc-consent-toast-failure .gv-toast').removeClass('gv-visible');
						}, 5000);
					}
				})
				.catch(error => {
					$(".oc-consent-toast-container").show();
					$('#oc-consent-toast-failure .gv-toast').addClass('gv-visible');
					setTimeout(function() {
						$('#oc-consent-toast-failure .gv-toast').removeClass('gv-visible');
					}, 5000);
				});
		}
		$("#oc-consent-settings input[type='checkbox']").on('click', function () {
			const status = $(this).is(':checked') ? 1 : 0;
			ocUpdateConsentStatus(status);
		});
		$('.oc-data-consent-decline').on('click', function () {
			ocUpdateConsentStatus(0);
		});
		$('.oc-data-consent-accept').on('click', function () {
			ocUpdateConsentStatus(1);
		});
		$('.oc-consent-toast-container .gv-toast-close').on('click', function () {
			$('.oc-consent-toast-container .gv-toast').removeClass('gv-visible');
		});

	});
})(jQuery)