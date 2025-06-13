/**********  Newsletter JS with WP Ajax *********/
(function ($) {
	$(document).ready(function () {

		$('.oc-newsletter-form').submit(function (e) {
			e.preventDefault();
			var el = $(this);
			// el.find('.oc-spinner').removeClass('d-none');
			el.find('.oc-newsleter-submit').attr('disabled', 'disabled');

			$.post(oc_ajax.ajaxurl, {
				'action': 'oc_newsleter_sub',
				'email': el.find('.oc-newsletter-input').val(),
				'oc_cpt': el.find('input[name="oc_cpt"]').val(),
				'oc_captcha_val': el.find('.oc-captcha-val').val(),
				'oc_csrf_token': el.find('.oc_csrf_token').val(),
				'oc-newsletter-nonce': el.find('input[name="oc-newsletter-nonce"]').val()
			}, function (res) {
				console.log(res);
				el.parent().find('.oc-message').text(res.text).slideDown();
				el.find('.oc-newsleter-submit').removeAttr('disabled');
				// el.find('.oc-spinner').addClass('d-none');
				if (res.status == 'success') {
					el.slideUp();
				}
			});
		});
	});

})(jQuery)