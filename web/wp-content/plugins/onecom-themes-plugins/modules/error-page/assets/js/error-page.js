(function ($) {

	errorssShow();
	$(window,document).resize(() => {
		errorssShow();
	});

	function errorssShow(){
		if ($(window).width() > 1190) {
			$('.show-on-mobile .onecom-error-preview').attr('id','onecom-error-preview-mobile');
		}else {
			$('.show-on-mobile .onecom-error-preview').attr('id','onecom-error-preview');
		}
	}

	$("#onecom_ep_enable").click(function () {

		if (parseInt(LocalizeObj.isPremium)) {
			$(this).find('.oc-failed')
			{
				$('.oc-failed').removeClass("oc-failed");
			}
			let data = {
				action: 'onecom-error-pages',
				type: $(this).prop("checked") ? 'enable' : 'disable'
			};
			$('.spinner.error-page').addClass('is-active');
			ajaxUpdate(data);
		}else {
			//show modal for premium
			checkPremium($(this));
		}
	});

	function failButton() {
		if ($("#onecom_ep_enable").prop("checked")) {
			$('.oc_cb_slider').addClass("oc-failed");
			$("#onecom_ep_enable").prop("checked", false);
		} else {
			$('.oc_cb_slider').addClass("oc-success");
			$("#onecom_ep_enable").prop("checked", true);
		}
	}

	function checkPremiumSwitch(){

		if ( ! parseInt(LocalizeObj.isPremium)) {

			let data = {
				action: 'onecom-error-pages',
				type: 'disable'
			};

			ajaxUpdate(data);
		}

	}

	function ajaxUpdate(data){
		$.post(ajaxurl, data, function (res) {
			$('.spinner.error-page').removeClass('is-active');
			$('.spinner.error-page').addClass('success');
			setTimeout(function() {
				$('.spinner.error-page').removeClass('success');
			}, 2000);
			if (res.status === 'success') {
				$('#onecom-error-preview').toggleClass("onecom-error-preview onecom-error-extended");
				$('#onecom-status-message').slideUp();
			} else {
				$('#onecom-status-message').text(res.message);
				$('#onecom-status-message').slideDown();
				failButton();
			}
		});
	}

	function checkPremium(thisObj){
		if ( ! parseInt(LocalizeObj.isPremium)) {
			//check status
			if (thisObj.is(':checked')) {
				thisObj.prop('checked', false);
				let referrer = location.search;
				$('#oc_um_overlay').show();
				ocSetModalData({
					isPremium: LocalizeObj.isPremium,
					feature: 'advanced_error_page',
					featureAction: 'settings',
					referrer: referrer
				});
			} else {
				checkPremiumSwitch();
				thisObj.prop('checked', false);
			}
			return false;
		}
	}
})(jQuery);