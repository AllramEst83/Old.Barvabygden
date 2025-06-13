(function ($) {
	$("#oc_login_masking_overlay_wrap .oc_up_btn").click(function (){


		let args = {
			'event_action': 'click_control_panel_button',
			'item_category': 'blog',
			'item_name': 'auto_login_protection_modal'
		};

		oc_push_stats_by_js(args);


	})

})(jQuery);