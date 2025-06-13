jQuery(document).ready(function () {
	jQuery('.oc-activate-wp-rocket-btn').click(function(){
		oc_activate_wp_rocket();
	});

	// Capture WP-Rocket stats from WP-Rocket page & plugins entry
	jQuery("#onecom-wrap .oc-wp-rocket-cp-link").click(function (){
		let args = { 'event_action': 'click_cp_activate_button', 'item_category': 'plugin', 'item_name': 'wp_rocket' };
		oc_push_stats_by_js(args);
	})
	jQuery("#onecom-ui .oc-wp-rocket-cp-link").click(function (){
		let args = { 'event_action': 'click_cp_activate_button', 'item_category': 'plugin', 'item_name': 'wp_rocket' };
		oc_push_stats_by_js(args);
	})
	jQuery("#onecom-ui .wp-rocket-guide-link").click(function (){
		let args = { 'event_action': 'click_learn_more_button', 'item_category': 'plugin', 'item_name': 'wp_rocket' };
		oc_push_stats_by_js(args);
	})
	jQuery("#onecom-wrap .wp-rocket-offer-link").click(function (){
		let args = { 'event_action': 'click_wp_rocket_offer_link', 'item_category': 'plugin', 'item_name': 'wp_rocket' };
		oc_push_stats_by_js(args);
	})
});

// activate wp rocket button action
function oc_activate_wp_rocket(){
	jQuery('.oc_activate_wp_rocket_spinner').removeClass('success').addClass('is_active');
	jQuery.post(ajaxurl, {
		action: 'activate_oc_wp_rocket'
	}, function(response){
		jQuery('.oc_activate_wp_rocket_spinner').removeClass('is_active');
		if (response.status === true) {
			// Push stats
			let args = { 'event_action': 'click_wp_activate_button', 'item_category': 'plugin', 'item_name': 'wp_rocket' };
			jQuery('.oc_activate_wp_rocket_spinner').addClass('success');
			oc_push_stats_by_js(args);
			window.location.href = "options-general.php?page=wprocket";
		} else {
			console.log("Error: Could not activate plugin")
		}
	});
}