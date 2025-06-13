/*
* This 'Events Capture' script listens to click events on plugin and onboarding pages
* Item source is derived from plugin slug (or onboarding) based on page slug
* Item name is derived from feature name based on event class name
* Referrer is derived from HTTP referrer or Query strings
*/

// Step 1: Check self
// Step 2: Check immediate child if self does not match
// Step 3: Check immediate parent if still not found
document.addEventListener('click', function (event) {
	let target = event.target;

	// Function to check if an element has the target event class
	function hasEventClass(el) {
		return [...el.classList].find(cls => cls.startsWith('ocwp_') && cls.endsWith('_event')) || null;
	}

	// Step 1: Check self
	let matchedEventClass = hasEventClass(target);
	let matchedElement = matchedEventClass ? target : null;


	// Step 3: Check immediate parent if still not found
	if (!matchedEventClass && target.parentElement) {
		let parent = target.parentElement;
		matchedEventClass = hasEventClass(parent);
		matchedElement = matchedEventClass ? parent : null;

	}

	// Stop searching if a match is found
	if (matchedEventClass && matchedElement) {
		let itemSource = getItemSource();
		let itemName = getItemName(matchedEventClass);
		let refererName = getRefererName();

		const updatedEventName = matchedEventClass.replace(/_event$/, '');
		let args = {
			'event_action': updatedEventName,
			'item_category': 'misc',
			'item_name': itemName,
			'item_source': itemSource,
			'referrer': refererName
		};

		const additionalInfo = getAdditionalInfo(updatedEventName);

		//set additional info if having any value
		if(Object.keys(additionalInfo).length > 0) {
			args.additional_info = additionalInfo;
		}

		oc_push_stats_by_js(args);
	}
});

/**
 *  Modal dependent event add
 */
jQuery(document).on('click', '.oc-show-modal, #dev_mode_enable, #exclude_cdn_enable, .mm-radio, .onecom_multicheckbox', function (e) {
	//e.preventDefault();
	const upsellBtnEvent = jQuery(this).attr('data-upsell-btn-event');

	setTimeout(() => {
		jQuery('#oc_um_wrapper').find('#oc_um_footer .upgrade-plugin-dependent, #oc_um_footer a.oc_um_btn.oc_up_btn')
			.addClass(upsellBtnEvent);
	}, 1000); // Small delay to ensure modal content is added
});


/**
 * On upsell modal close remove the dynamic event class which is added on upsell link clicked
 */
jQuery(document).on('click', '.cancel-plugin-dependent, a.oc_um_btn.oc_cancel_btn', function (e) {
	e.preventDefault();
	jQuery('#oc_um_wrapper').find('#oc_um_footer .upgrade-plugin-dependent, #oc_um_footer a.oc_um_btn.oc_up_btn')
		.removeClass((index, className) => {
			return className.split(' ').filter(name => (name !== 'upgrade-plugin-dependent' && name !== 'oc_um_btn' && name !== 'oc_up_btn')).join(' ');
		});
});


/**
 * Get additional info during customer journey
 */
const getAdditionalInfo = (eventName) => {

	let additionalInfo = {}
	const buttonMappings = getButtonsMappings();

	// Welcome modal one-home
	if(eventName === 'ocwp_wpo_welcome_modal_closed' || eventName === 'ocwp_wpo_welcome_modal_tour_started')	 {
		const urlParams = new URLSearchParams(window.location.search);
		const onboardingFlow = urlParams.get('onboarding-flow');
		const page = urlParams.get('page');
		if(page === 'onecom-home'){
			const AIModal = (onboardingFlow === 'fast_track') ? 'fast_track' : 'customized';
			additionalInfo = {'onboarding-flow': AIModal};
		}
	} else if (eventName in buttonMappings) {
		additionalInfo = {'button': buttonMappings[eventName]};
	}
	
	// Common properites i.e path, screen size etc
	const fullPath = window.location.pathname + window.location.search + window.location.hash;
	if (fullPath) {
		additionalInfo['path'] = fullPath;
	}
	additionalInfo['screen_width'] = window.screen.width;
	additionalInfo['screen_height'] = window.screen.height;

	return additionalInfo
}

/**
 * Extract plugin (theme, onboarding) name as an Item source from current page slug
 */
function getItemSource() {
	let urlParams = new URLSearchParams(window.location.search);
	let currentPage = urlParams.get('page');

	// Define mappings for known plugin pages
	const ItemMappings = {
		'onecom-home': 						'onecom-themes-plugins',
		'onecom-wp-health-monitor':			'onecom-themes-plugins',
		'onecom-wp-staging': 				'onecom-themes-plugins',
		'onecom-wp-error-page': 			'onecom-themes-plugins',
		'onecom-wp-cookie-banner': 			'onecom-themes-plugins',
		'onecom-wp-themes': 				'onecom-themes-plugins',
		'onecom-wp-plugins': 				'onecom-themes-plugins',
		'onecom-wp-recommended-plugins': 	'onecom-themes-plugins',
		'onecom-wp-discouraged-plugins': 	'onecom-themes-plugins',
		'onecom-wp-rocket': 				'onecom-themes-plugins',
		'onecom-vcache-plugin': 			'onecom-vcache',
		'onecom-cdn': 						'onecom-vcache',
		'onecom-wp-under-construction': 	'onecom-under-construction',
		'onecom-wp-spam-protection': 		'onecom-spam-protection'
	};

	// Check if the URL ends with "install.php" and set page name to 'installer'
	let currentPath = window.location.pathname;
	if (currentPath.endsWith('install.php')) {
		return 'installer';
	}

	return ItemMappings[currentPage] || currentPage || 'unknown';
}

/**
 * Map item names based on event_action prefix.
 */
function getItemName(eventAction) {
	if (!eventAction || typeof eventAction !== 'string') {
		return 'unknown';
	}

	// Define known prefixes and their mappings
	const itemMappings = getItemMappings();

	// Find if any known prefix exists in the string
	for (let prefix in itemMappings) {
		if (eventAction.includes(prefix)) {
			return itemMappings[prefix]; // Return mapped value if prefix is found
		}
	}

	return 'unknown'; // Default if no known prefix is found
}

/**
 * Extract and map the HTTP referer.
 */
function getRefererName() {
	let referrerURL = document.referrer;

	if (!referrerURL) {
		return 'direct_access'; // No referrer means direct visit
	}

	let referrerPage = new URL(referrerURL).searchParams.get('page');

	// Define mappings for known referer pages
	const refererMappings = {
		'control_panel-page': 'control_panel',
		'dashboard': 'admin_dashboard'
	};

	return refererMappings[referrerPage] || referrerPage || 'external';
}

function getItemMappings(){
	return {
		'wpo_': 'wordpress_onboarding',
		'ocp_hm': 'health_monitor',
		'ocpc_cache': 'performance_cache', // Add more mappings as needed
		'ocpc_toolbar_cache': 'performance_cache',
		'ocpc_cdn': 'cdn',
		'ocpc_toolbar_cdn': 'cdn',
		'ocpc_wpr': 'wp-rocket',
		'ocsp_': 'spam_protection',
		'ocp_staging_': 'staging',
		'aep_advanced_': 'advance_error_page',
		'cb_cookie_':'cookie_banner',
		'ocp_themes': 'onecom_themes_page',
		'ocp_plugins': 'onecom_plugin_page',
		'ocp_vm': 'vulnerability_monitor',
		'ocp_home': 'onecom_home',
		'ocwp_ocmm': 'maintenance_mode',
		'ocp_menu': 'onecom_menu'
	};
}

// Capture button label in additional info
function getButtonsMappings(eventName){
	return {
		'ocwp_ocp_menu_plugins_clicked': 'Plugins',
		'ocwp_ocp_plugins_wp_rocket_learn_more_clicked': 'Learn more',
		'ocwp_ocpc_wpr_get_wp_rocket_cta_clicked': 'Get WP Rocket',
		'ocwp_ocp_menu_wpr_clicked': 'WP Rocket'
	};
}

// Adding events capture related classes to menu items
jQuery(document).ready(function($) {

	const menuClasses = [
		{ page: 'onecom-home', class: 'ocwp_ocp_menu_logo_clicked_event', submenuClass: 'ocwp_ocp_menu_home_clicked_event' },
		{ page: 'onecom-wp-health-monitor', class: 'ocwp_ocp_menu_hm_clicked_event' },
		{ page: 'onecom-wp-spam-protection', class: 'ocwp_ocp_menu_sp_clicked_event' },
		{ page: 'onecom-vcache-plugin', class: 'ocwp_ocp_menu_pc_clicked_event' },
		{ page: 'onecom-cdn', class: 'ocwp_ocp_menu_cdn_clicked_event' },
		{ page: 'onecom-wp-rocket', class: 'ocwp_ocp_menu_wpr_clicked_event' },
		{ page: 'onecom-wp-staging', class: 'ocwp_ocp_menu_staging_clicked_event' },
		{ page: 'onecom-wp-error-page', class: 'ocwp_ocp_menu_aep_clicked_event' },
		{ page: 'onecom-wp-cookie-banner', class: 'ocwp_ocp_menu_cb_clicked_event' },
		{ page: 'onecom-wp-under-construction', class: 'ocwp_ocp_menu_mm_clicked_event' },
		{ page: 'onecom-wp-themes', class: 'ocwp_ocp_menu_themes_clicked_event' },
		{ page: 'onecom-wp-plugins', class: 'ocwp_ocp_menu_plugins_clicked_event' }

	];

	// Add classes to main menu and submenu
	menuClasses.forEach(item => {
		$(`#toplevel_page_onecom-wp a[href="admin.php?page=${item.page}"]`).addClass(item.class);
		if (item.submenuClass) {
			$(`.wp-submenu a[href="admin.php?page=${item.page}"]`).addClass(item.submenuClass);
		}
	});

	// Toolbar events
	const toolbarClasses = [
		{ id: 'purge-onecom-cache-only', class: 'ocwp_ocpc_toolbar_cache_cleared_event' },
		{ id: 'purge-onecom-cdn-only', class: 'ocwp_ocpc_toolbar_cdn_cleared_event' }
	];

	toolbarClasses.forEach(item => {
		$(`#wp-admin-bar-${item.id} > a`).addClass(item.class);
	});
});