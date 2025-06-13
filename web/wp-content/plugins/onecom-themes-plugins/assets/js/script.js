let selectedTheme = '';
let selectedThemeName = '';
let activateButton;
let installButton;
let themeType;

// for adding lazy loading on the themes listing page
document.addEventListener("DOMContentLoaded", () => {
	const lazyBackgrounds = document.querySelectorAll(".theme-screen-bg.lazyload");

	const backgroundObserver = new IntersectionObserver((entries, observer) => {
		entries.forEach((entry) => {
			if (entry.isIntersecting) {
				const bgElement = entry.target;
				const bgUrl = bgElement.dataset.bg;
				if (bgUrl) {
					bgElement.style.backgroundImage = `url(${bgUrl})`;
					bgElement.classList.add("loaded");
					observer.unobserve(bgElement);
				}
			}
		});
	});

	lazyBackgrounds.forEach((bg) => backgroundObserver.observe(bg));
});
// lazy loading ends //

(function($){
	$(document).ready(function(){


		screenshotPreview();
		// Click event back to top
		if ( $('.onecom-move-up').length > 0 ) {
			$('.onecom-move-up').click( function(){
				$("html, body").animate({ scrollTop: 0 }, "slow");
				return false;
			} );
		}

		/**
		 *  Hide right side fading when themes scroll reaches to end, else show it
		 *  Since we cannot directly hide a pseudo-element using jQuery, we will use temp addClass/removeClass
		 */
		let observer = new IntersectionObserver(function (entries) {
			if (entries[0].isIntersecting === true) {
				$(".h-parent-wrap").addClass("oct-hide-after");
			} else {
				$(".h-parent-wrap").removeClass("oct-hide-after");
			}
		}, { threshold: [0] });
		if ( $('#oc_theme_filter li:last-child span').length ) {
			observer.observe(document.querySelector("#oc_theme_filter li:last-child span"));
		}


		/* themes screen */
		/**
		 * Install / Activate theme
		 */
		function oc_install_theme(that){
			let button        = $(that);
			let theme_wrapper = $(that).parents('.one-theme:first');

			$(theme_wrapper).addClass('active');

			let name       = $(that).attr('data-name');
			let theme_slug = $(that).attr('data-theme_slug');
			const redirect   = $(that).attr('data-redirect');
			let themeName = $(that).parent().find('.themeName-preview').text();
			let downloadLink = $(that).attr('data-download');
			var network    = onecom_vars.network;

			if ( typeof name == 'undefined' || name == '' ) {
				return;
			}

			if ( typeof network == 'undefined' ) {
				network = false;
			}

			var data = {
				'action' : name,
				'theme_slug' : theme_slug,
				'redirect' : redirect,
				'network' : network,
				'template': downloadLink
			}

			$(theme_wrapper).addClass('active');
			$.post(ajaxurl, data, function( response ) {

				let result = $.parseJSON(response);

				if ( typeof result.type != 'undefined' && result.type == 'redirect' ) {
					window.location = result.url;
				} else {
					let time_to_show_message = 5000;
					if ( result.type == 'success' ) {
						$(button).removeClass('ocwp_ocp_themes_page_theme_selected_event');
						toggleLoader(false);
						let newmsg = onecom_vars.installMsg.replace("%s%", themeName);
						$('.gv-toast-content').text(newmsg);
						$('.gv-toast-success').removeClass('gv-invisible');
						$('.gv-toast-success').addClass('gv-visible');
						$(theme_wrapper).addClass('installed');
						$(button).removeClass('one-install').addClass('one-installed');
						$(button).find('.action-text').remove();
						$(button).text(result.button_html);
						$(button).attr('data-name', 'onecom_activate_theme');
						$(button).blur();
						$(button).closest('.one-theme').find('.status-badge').removeClass('gv-hidden').text('Installed');
						if(result.parentThemeSlug !== false ){
							// Assuming `parentThemeSlug` contains the parent theme's slug
							const parentThemeSlug = result.parentThemeSlug;

							let parentThemeTile = $('[data-tslug="' + parentThemeSlug + '"]');
							const themeBtn = parentThemeTile.find('a.select_theme');
							parentThemeTile.addClass('installed');
							parentThemeTile.find('.status-badge').removeClass('gv-hidden').text('Installed');
							$(themeBtn).removeClass('one-install').addClass('one-installed');
							$(themeBtn).text(result.button_html);
							$(themeBtn).attr('data-name', 'onecom_activate_theme');
							$(themeBtn).blur();

						}

					}else{
						toggleLoader(false);
						showToast('alert',result.message);
					}
					setTimeout( function(){
						toggleLoader(false);
						$('.gv-toast-success').removeClass('gv-visible');
						$('.gv-toast-success').addClass('gv-invisible');
						hideToast('alert');
					}, time_to_show_message );
				}

			} );

		}

		/**
		 * Handles theme activation
		 **/
		$(document).on( 'click', '#one-activate-theme', function(){


			if($(this).data('theme-type') === 'all'){
				console.log('other theme selected');
			}else {
				// Get the selected radio button
				let that = $(this);
				const selectedOption = $('input[name="radio-group-name"]:checked');

				if (selectedOption.length === 0) {
					alert("Please select an option before continuing.");
					return;
				}
				toggleLoader('show');
				let msg = onecom_vars.activateProgress.replace("%s%", selectedThemeName);

				$('.gv-loader-container').find('p').text(msg);

				// Execute different functions based on the selected value
				if (selectedOption.val() === "without-demo-content") {
					activateWithoutDemoContent(that);
				} else if (selectedOption.val() === "with-demo-content") {
					activateWithDemoContent();
				}
			}
		});

		function activateThemeAjaxCall(theme_slug, selectedThemeName, network = false) {
			return new Promise((resolve, reject) => {
				if (!theme_slug) {
					resolve({ success: false, error: "Theme slug is missing." });
					return;
				}

				// Perform AJAX request
				$.post(ajaxurl, {
					action: 'onecom_activate_theme',
					theme_slug: theme_slug,
					network: network
				})
					.done(response => {
						try {
							const result = $.parseJSON(response);
							resolve(result);
						} catch (error) {
							console.error("Failed to parse JSON response:", error);
							reject("Invalid JSON response");
						}
					})
					.fail((xhr, status, error) => {
						console.error("AJAX request failed:", status, error);
						reject(status);
					});
			});
		}




		// Function for activating without demo content
		async function activateWithoutDemoContent(that) {
			try {
				// Show loader
				toggleLoader(true);

				// Find the theme wrapper
				let theme_wrapper = that.parents('.one-theme:first');
				$(theme_wrapper).addClass('active');

				// Extract data attributes
				let theme_slug = selectedTheme;
				let network = onecom_vars.network || false;

				// Check if the necessary data is available
				if (!theme_slug) {
					throw new Error("Theme slug is undefined.");
				}

				// Call the AJAX function
				const result = await activateThemeAjaxCall(theme_slug, network);

				if (result.success) {
					let message = onecom_vars.activationMsg.replace("%s%", selectedThemeName);
					// Show success toast
					showToast('success', message);

					// Update theme status and button text
					updateThemeStatus('Customise', 'Activate', 'one-installed', result);

					// Update status badge
					updateStatusBadge('Active', 'Installed', activateButton);
					$(activateButton).text(result.install_text);
					$(activateButton).attr('href', result.link);
					$(activateButton).closest('.one-theme').find('.status-badge').text('Active').removeClass('gv-badge-generic').addClass('gv-badge-success');

				} else {
					// Show error toast
					let msg = onecom_vars.themeActivationErr.replace("%s%", selectedThemeName);
					showToast('alert', msg);
					console.error("Theme activation failed:", result);
					setTimeout(() => hideToast('alert'), 5000);
				}

				// Hide success message after 5 seconds
				setTimeout(() => {
					hideToast('success')
				}, 5000);
				$('.gv-content-container .gv-radio').prop('checked', false);
			} catch (error) {
				// Global error handling
				console.error("Error activating theme:", error);
				let msg = onecom_vars.themeActivationErr.replace("%s%", selectedThemeName);
				showToast('alert', msg);
			} finally {
				// Hide loader
				activateButton= null;
				toggleLoader(false);
				closeModal();
				setTimeout(() => hideToast('alert'), 5000);

			}
		}

		// Function for activating with demo content
		async function activateWithDemoContent() {
			// Show loader
			toggleLoader(true);

			try {
				// Activate theme
				const result = await activateThemeAjaxCall(selectedTheme, onecom_vars.network || false);

				if (!result.success) {
					let msg = onecom_vars.themeActivationErr.replace("%s%", selectedThemeName);
					showToast('alert', msg);
					return;
				}

				// Handle classic theme type
				if (themeType === 'classic-theme') {
					try {
						const importResult = await handleDemoAjaxRequest({
							action: 'ocdi_import_demo_data',
							security: onecom_vars.nonce,
							selected: ''
						});

						if (importResult === 'success') {
							let message = onecom_vars.activationMsg.replace("%s%", selectedThemeName);
							// Show success toast
							showToast('success', message);
							updateThemeStatus('Customise','Activate','one-installed',result);
							updateStatusBadge('Active', 'Installed', activateButton );
							$(activateButton).closest('.one-theme').find('.status-badge').text('Active').removeClass('gv-badge-generic').addClass('gv-badge-success');
							closeModal();
							$('.gv-content-container .gv-radio').prop('checked', false);
						} else {
							let message = onecom_vars.democontentErr.replace("%s%", selectedThemeName);
							showToast('alert',msg);
						}
					} catch (error) {
						console.error('Demo import failed:', error);
						showToast('alert','Demo import failed.');
					}finally {
						setTimeout(function () {
							$('.gv-toast').removeClass('gv-visible');
							$('.gv-toast').addClass('gv-invisible');
						},2500)
					}
				}else{
					showToast('alert','Couldn’t import demo content.');
				}
			} catch (error) {
				console.error('Theme activation failed:', error);
				showToast('alert',`Couldn’t activate theme ${selectedThemeName}.`);
			} finally {
				// Hide loader in both success and error cases
				toggleLoader(false);
				setTimeout(function () {
					$('.gv-toast').removeClass('gv-visible');
					$('.gv-toast').addClass('gv-invisible');
				},5000)

			}
		}

		const toggleLoader = (show) => {
			const loader = $('.loading-overlay.fullscreen-loader');
			if (show) {
				loader.removeClass('hide').addClass('show');
			} else {
				loader.removeClass('show').addClass('hide');
			}
		};

		const showToast = (type, message) => {
			const toast = $(`.gv-toast-${type}`);
			toast.find('.gv-toast-content').html(message);
			toast.removeClass('gv-invisible').addClass('gv-visible');
		};

		const hideToast = (type) => {
			$(`.gv-toast-${type}`).removeClass('gv-visible').addClass('gv-invisible');
		};

		const closeModal = () => {
			$('.gv-modal').addClass('gv-hidden');
			selectedTheme = '';
			selectedThemeName = '';
			activateButton = '';
			themeType = '';
		};

		const updateThemeStatus = (currentText, newText, newClass, result) => {
			$('.select_theme')
				.filter(function () {
					return $(this).text().trim() === currentText;
				})
				.text(newText)
				.addClass(newClass)
				.removeAttr('href');

			$(activateButton).text(result.install_text).attr('href', result.link).removeClass(newClass);
		};

		const updateStatusBadge = (currentText, newText, button,badgeClass='generic') => {
			let oldClass,newClass;
			if(badgeClass === 'generic'){
				oldClass = 'gv-badge-success';
				newClass = 'gv-badge-generic';
			}else{
				newClass = 'gv-badge-success';
				oldClass = 'gv-badge-generic';
			}
			$('.status-badge')
				.filter(function () {
					return $(this).text().trim() === currentText;
				})
				.text(newText).removeClass(oldClass).addClass(newClass);

			$(button).closest('.one-theme').find('.status-badge').text(newText);
		};



		$(document).on('click','.gv-toast-close', function (){
			hideToast('success');
			hideToast('alert');
		});

		$(document).on('click', 'a.oc_theme_filter_select', function () {

			$('.oc_theme_filter_select').removeClass('active-category');
			$(this).addClass('active-category');

			let filterVal = $(this).attr('data-category-filter');
			//show warning only for classic theme
			(filterVal === 'classic-theme') ? $('.theme-notification').removeClass('hide').addClass('show') : $('.theme-notification').removeClass('show').addClass('hide');
			let checkRow = $('.oci-theme-preview-screen-right .one-theme.oci-theme-box-nw');
			let counter = 0;

			checkRow.css('display', 'none');
			/* loop per row for class check */
			checkRow.each(function (index, val) {

				let innerObj = $(this);
				if (innerObj.hasClass(filterVal.toLowerCase())) {

					if (filterVal != 'classic-theme' && innerObj.hasClass('classic-theme')) {
						innerObj.css('display', 'none');
						return;
					}
					innerObj.css('display', 'block');
					counter++;
				} else {
					innerObj.css('display', 'none');
				}
			});

			if ( window.innerWidth <= 1023 ) {
				if ($('#oc-wizard .tab-content .tab-pane .one-theme.oci-theme-box-nw' + '.' + filterVal + ':hidden').length === 0) {
					$('.mobile_loader').css('display', 'none');
				} else {
					$('.mobile_loader').css('display', 'block');
				}
			}
		});

		$(document).on('click','.oc-themes-tab', function (){
			let cat = $(this).data('category');
			$('.oc-themes-tab').removeClass('gv-tab-active');

			$(this).addClass('gv-tab-active');
			// Hide all tab panels
			$('.gv-tab-panel').removeClass('gv-panel-active').hide();

			// Show the panel with the matching class
			$(`.${cat}`).addClass('gv-panel-active').show();

			if(cat === 'classic-theme'){
				$('.top-notification').removeClass('hide').addClass('show');
			}else {
				$('.top-notification').removeClass('show').addClass('hide');
			}


		})

		const httpRequest = async (options) => {

			return await $.ajax(options)
				.done((res) => {
					return Promise.resolve(res);
				})
				.catch(async (error) => {

						return Promise.reject(error);
				});
		};
		const handleDemoAjaxRequest = async (ajaxData) => {
			const startTime = performance.now();

			const performAjaxRequest = async (data) => {

				const options = {
					url: ajaxurl,
					method: 'POST',
					data,
					dataType: 'json'
				};

				try {
					const response = await httpRequest(options);

					if (!response || typeof response.status === 'undefined') {
						throw new Error('Invalid response format');
					}

					switch (response.status) {
						case 'newAJAX':
							return await performAjaxRequest(ajaxData);

						case 'customizerAJAX': {
							const customizerData = {
								action: 'ocdi_import_customizer_data',
								security: onecom_vars.nonce
							};
							return await performAjaxRequest(customizerData);
						}

						case 'afterAllImportAJAX': {
							const finalData = {
								action: 'ocdi_after_import_data',
								security: onecom_vars.nonce
							};
							const finalResponse = await httpRequest({
								url: ajaxurl,
								method: 'POST',
								data: finalData,
								dataType: 'json'
							});

							if (finalResponse.message && finalResponse.message.includes('notice-success')) {
								const endTime = performance.now();
								console.log(`Demo import completed in ${(endTime - startTime) / 1000} seconds`);
								return 'success';
							} else {
								throw new Error('Demo import not completed');
							}
						}

						default:
							throw new Error(`Unexpected status: ${response.status}`);
					}
				} catch (error) {
					console.error('Error during demo import:', error.message);
					throw error; // Re-throw the error to propagate it to higher levels
				}
			};

			try {
				return await performAjaxRequest(ajaxData);
			} catch (error) {
				console.error('Demo import failed:', error.message);
				throw error; // Propagate the failure to the caller
			}
		};

		/* themes screen end */


		/**
		 * Handles modal open
		 **/
		$(document).on( 'click', '.one-installed', function(e){
			e.stopPropagation();
			handleThemeActivation($(this)); // Delegate to a reusable function
		});

// Handler for the new button (e.g., `.other-button`)
		$(document).on('click', '.activate-preview-theme', function (e) {
			e.stopPropagation();
			tb_remove();
			handleThemeActivation(activateButton);
		});

// Reusable function for handling theme activation logic
		function handleThemeActivation(button) {
			selectedTheme = button.attr('data-theme_slug');
			selectedThemeName = button.parent().find('.themeName-preview').text();
			activateButton = button;
			let msg = onecom_vars.activateProgress.replace("%s%", selectedThemeName);
			$('.gv-loader-container').find('p').text(msg);

			if (button.data('theme-type') === 'classic-theme') {
				themeType = 'classic-theme';
				$('.gv-modal').removeClass('gv-hidden');
			} else {
				themeType = 'other';
				activateWithoutDemoContent(button);
			}
			console.log(selectedThemeName);
		}

		/**
		 * Handles modal close
		 **/
		$(document).on( 'click', '.oc-modal-close', function(){
		closeModal();

		});
		// Close the modal if clicking outside of it
		$(document).on('click', function (e) {
			const $modal = $('.gv-modal'); // Replace with your modal selector if different
			if (!$modal.hasClass('gv-hidden') && !$(e.target).closest('.gv-modal-content').length) {
				closeModal();
			}
		});

		$(document).on('click','.wp-themes-error-btn', function (e) {
			location.reload();
		})


		/**
		 * Handles theme installation
		 **/
		$(document).on( 'click', '.one-install', function(){
			$('.loading-overlay.fullscreen-loader').removeClass('hide').addClass('show');
			selectedThemeName = $(this).parent().find('.themeName-preview').text();
			let newmsg = onecom_vars.installProgress.replace("%s%", selectedThemeName);
			$('.gv-loader-container').find('p').text(newmsg);
			let that     = $(this);

			oc_install_theme(that);

		});

		/**
		 * Handles plugin installation
		 **/
		$(document).on( 'click', '.install-now, .activate-plugin-ajax', function(event){
			event.preventDefault();
			var button      = $(this);
			var plugin_card = $(this).parents('.one-plugin-card:first');

			var download_url = $(this).attr('data-download_url');
			var plugin_slug  = $(this).attr('data-slug');
			var plugin_name  = $(this).attr('data-name');
			var action       = $(this).attr('data-action');
			var redirect     = $(this).attr('data-redirect');
			var plugin_type  = ( typeof( $(this).attr('data-plugin_type') ) != 'undefined' ) ? $(this).attr('data-plugin_type') : '';

			$('.loading-overlay.fullscreen-loader').removeClass('hide').addClass('show');

			var data = {
				action : action,
				plugin_slug : plugin_slug,
				plugin_name : plugin_name,
				download_url : download_url,
				plugin_type : plugin_type,
				redirect : redirect
			}

			$.post(ajaxurl, data, function( response ) {
				var result = $.parseJSON(response);

				console.log(result);

				if ( typeof result.type != 'undefined' && result.type == 'redirect' ) {
					window.location = result.url;
				} else {
					$('.onecom-notifier').html(result.message).attr('type', result.type).addClass('show');
					var time_to_show_message = 5000;
					if ( result.type == 'success' ) {
						//if( typeof result.status.activateUrl != 'undefined' && result.status.activateUrl != '' ) {
						$(plugin_card).addClass('activate');
						$(button).after(result.button_html);
						$(button).remove();

						time_to_show_message = 2500;
					}
					setTimeout( function(){
						$('.onecom-notifier').removeClass('show');
						$('.loading-overlay.fullscreen-loader').removeClass('show').addClass('hide');
					}, time_to_show_message );
				}
			});

		});

		/**
		 * Handle pagination events
		 **/
		$('.pagination-item').click( function( event ) {
			event.preventDefault();
			if ( $(this).is('.current') ) {
				return;
			}
			$('.pagination-item').removeClass('current');
			$(this).addClass('current');
			ocPaginateFilter(event);
			return;
		} );




		/**
		 * Confirmation for deactivating of a plugin
		 **/
		var $info      = $("#one-confirmation");
		var yes_string = $info.attr('data-yes_string');
		var no_string  = $info.attr('data-no_string');
		$info.dialog({
			'dialogClass'   : 'wp-dialog wp-one-dialog',
			'modal'         : true,
			'autoOpen'      : false,
			'closeOnEscape' : true,
			'width'         : '25%',
			hide: { effect: "explode", duration: 1000 },
			resizable: false,
			'buttons'       : [
				{
					text: no_string,
					"class" : "button",
					click: function() {
						$(this).dialog("close");
					}
				},
				{
					text: yes_string,
					"class" : "button button-primary",
					click: function() {
						var submit = $(this).data('element');
						var form   = $(submit).parents('form:first');
						$(form)[0].submit();
						$(this).dialog("close");
					}
				}
			]
		});
		$('.one-deactivate-plugin').click( function( event ) {
			event.preventDefault();
			$info.data('element', this).dialog('open');
		} );
		$('.discouraged-modal-close').click( function() {
			$("#one-confirmation").dialog('close');
		} );

		$('.one-theme').hover(
			function () {
				const element = $(this);
				element.addClass('active');

				// Enable buttons after a delay
				setTimeout(() => {
					element.find('.gv-button').css('pointer-events', 'auto');
				}, 200);
			},
			function () {
				const element = $(this);
				element.removeClass('active');

				// Disable buttons immediately
				element.find('.gv-button').css('pointer-events', 'none');
			}
		);

		$('#oc_theme_filter li').click(function(event){
			ocPaginateFilter(event);
		});

		// Click parent (as it has filter attr) when child theme-count is clicked
		$('#oc_theme_filter li span').click(function(event){
			event.currentTarget.parentElement.click();
		});

		$(document).on('click', '.select-preview-theme', function () {
			tb_remove();
			$('.loading-overlay.fullscreen-loader').removeClass('hide').addClass('show');
			let newmsg = onecom_vars.installProgress.replace("%s%", selectedThemeName);
			$('.gv-loader-container').find('p').text(newmsg);
			oc_install_theme(installButton);
		});
	});

	$(window).scroll( function(){
		onecom_move_up_toggle();
	} );

	$(window).load( function(){
		onecom_move_up_toggle();
	} );
	function ocPaginateFilter(event){
		var filterTerm = jQuery(event.target).attr('data-filter-key');
		if ( ! filterTerm) {
			filterTerm = $('#oc_theme_filter').find('.oc-active-filter').attr('data-filter-key')
		}
		var request_page  = $('.pagination-item.current').attr('data-request_page');
		var perPageItem   = $('.theme-browser').attr('data-item_count');
		var selectedItems = null;
		var pages         =  Math.ceil($('#theme-browser-page-1 .' + filterTerm).length / perPageItem);
		var start, end;

		//switch to initial page on category change

		if ($(event.target).parent().attr('id') === 'oc_theme_filter') {
			request_page = 1;
			ocAdjustPagination(pages);
			$('.oc-active-filter').removeClass('oc-active-filter');
			$(event.target).addClass('oc-active-filter');
		}
		removeItems($('.all'));
		if (filterTerm !== 'all') {
			removeItems(jQuery('.one-theme').not(jQuery('.' + filterTerm)));
			if ( pages > 1 ) {
				start         = (request_page - 1) * perPageItem;
				end           = (request_page * perPageItem);
				selectedItems = $('.' + filterTerm).slice(start, end);
			}else {
				selectedItems = $('.' + filterTerm);
			}
			showItems(jQuery(selectedItems));
		}else {
			showItems($('.page-' + request_page));
		}
	}

	function ocAdjustPagination(pages){
		$('.theme-browser-pagination .current').removeClass('current');
		$('.theme-browser-pagination .first').addClass('current');
		$('.theme-browser-pagination a').hide();
		if (pages > 1) {
			$('.theme-browser-pagination a').slice(0, pages).show();
		}
	}
	function showItems(elements){
		$(elements).removeClass('hidden_theme');
		$('.theme-browser-page').hide();
		$('.theme-browser-page-filtered').show().append($(elements).clone());
	}
	function removeItems(elements){
		$('.theme-browser-page-filtered').find(elements).remove();
	}
	/**
	 * Get query parameter value of current URL
	 **/
	function getQueryVariable(variable) {
		var query = window.location.search.substring(1);
		var vars  = query.split('&');
		for (var i = 0; i < vars.length; i++) {
			var pair = vars[i].split('=');
			if (pair[0] == variable) {
				return pair[1];
			}
		}
		return false;
	}

	/**
	 * Update URL if pagination event triggered, used to in history API
	 **/
	function onecomUpdateURL( request_page ) {
		var page    = getQueryVariable('page');
		var url     = window.location.href.split('?')[0];
		var params  = { 'page': page, 'paged':request_page };
		var new_url = url + '?' + $.param(params);
		history.pushState(params, null, new_url);
	}

	/**
	 * Function to toggle inline premium badge
	 */
	function oc_toggle_inline_badge(flag){

		// Check if premium theme
		if (flag && flag == 1) {
			$('.inline_badge').show();
		}
		else {
			$('.inline_badge').hide();
		}
	}

	/**
	 * It will help when user clicks on back forward button on browser
	 **/
	window.onpopstate = function(event) {
		if ( typeof event != 'undefined' && event.state != null ) {
			var paged = event.state.paged;
		} else {
			var paged = getQueryVariable('paged');
			if ( typeof paged == 'undefined' || paged == '' || paged == null ) {
				paged = 1;
			}
		}
		//var page_id = 'theme-browser-page-'+paged;
		$('.pagination-item').each( function( index, item ) {
			if ( $(item).attr('data-request_page') == paged ) {
				$(item).trigger('click');
				return;
			}
		} );
	};

	this.screenshotPreview = function(){
		/* CONFIG */

		xOffset = 10;
		yOffset = 30;

		// these 2 variable determine popup's distance from the cursor
		// you might want to adjust to get the right result

		/* END CONFIG */
		$(".one-screenshot").hover(function(e){
				this.t     = this.title;
				this.title = "";
				var c      = (this.t != "") ? "<br/>" + this.t : "";
				$("body").append("<p id='one-screenshot'><img src='" + $(this).attr('data-preview') + "' alt='url preview' />" + c + "</p>");
				$("#screenshot")
					.css("top",(e.pageY - xOffset) + "px")
					.css("left",(e.pageX + yOffset) + "px")
					.fadeIn("fast");
			},
			function(){
				this.title = this.t;
				$("#one-screenshot").remove();
			});
		$(".one-screenshot").mousemove(function(e){
			$("#one-screenshot")
				.css("top",(e.pageY - xOffset) + "px")
				.css("left",(e.pageX + yOffset) + "px");
		});
	};

	/**
	 * Snippet to handle thickbox full size
	 **/
	$(document).on( 'thickbox:iframe:loaded', function( e ) {
		// Small Snippet to hide install button
		$('#TB_iframeContent').contents().find('head').append($("<style type='text/css'> .plugin-install-php #plugin-information-footer {display:none !important;} </style>"));
	} );

	/* ==============  Theme preview JS with next/previous button events ==================== */
	$(document).on("click", ".preview_link", function(){

		installButton = $(this).siblings('.one-install');
		activateButton = $(this).siblings('.one-installed');

		if (installButton.length === 0 && activateButton.length === 0) {
			console.log('Both buttons are missing');
			$('.left-section-preview .gv-button-primary')
				.text('Customise theme')
				.removeClass('select-preview-theme')
				.removeClass('activate-preview-theme')
				.attr('href', onecom_vars.customizeURL);
		} else if (installButton.length === 0) {
			$('.left-section-preview .gv-button-primary')
				.text('Activate theme')
				.addClass('activate-preview-theme')
				.removeClass('select-preview-theme')
				.removeAttr('href');
		} else if (installButton.length > 0) {
			console.log('Install button exists');
			$('.left-section-preview .gv-button-primary')
				.text(onecom_vars.installTheme)
				.removeClass('activate-preview-theme')
				.addClass('select-preview-theme')
				.removeAttr('href');
		}
		//show warning notification on preview link for classic theme only
		$('.theme-notification-preview').removeClass('show').addClass('hide');
		if ($(this).closest('.one-theme.oci-theme-box-nw').hasClass('classic-theme')) {
			$('.theme-notification-preview').removeClass('hide').addClass('show');
		}
		// Toggle premium badge
		oc_toggle_inline_badge($(this).parents('.one-theme:first').attr('data-is-premium'));

		var theme_count = $(".theme-browser > div.one-theme").length;
		var current_demo_id = $(this).attr('data-id');
		// Set current theme demo url in iframe
		var url = $(this).attr("data-demo-url");
		if ($(this).hasClass('mobile-active')) {
			url = $(this).find('a.preview_link').attr("data-demo-url");
			current_demo_id = $(this).find('a.preview_link').attr('data-id');
		}
		$('iframe').attr('src', url);


		//Add demo id in theme preview select theme button
		$('a.select-preview-theme').attr('data-demo-id', current_demo_id);
		// Set next demo url id attribute
		var next_id = $(this).closest('.one-theme').nextAll('.one-theme:visible').find('.preview_link').attr("data-id");
		$('.header_btn_bar .next').attr('data-demo-id', next_id);
		// Set previous demo url id attribute
		var prev_id = $(this).closest('.one-theme').prevAll('.one-theme:visible:first').find('.preview_link').attr("data-id");
		$('.header_btn_bar .previous').attr('data-demo-id', prev_id);

		// Check theme count to manage previous/next action
		$('.header_btn_bar .theme-info').attr('data-theme-count', theme_count);
		// Set current theme id in data attribute
		$('.header_btn_bar .theme-info').attr('data-active-demo-id', current_demo_id);
		$('.header_btn_bar .preview-install-button').attr('data-active-demo-id', current_demo_id);
		// Reset Previous/Next Button Style
		$('.header_btn_bar .next').removeAttr('style');
		$('.header_btn_bar .previous').removeAttr('style');
		// If no (0) previous theme preview div available, disable previous button
		var demo_id = $(this).attr('data-id');
		var prev_theme_num = $('#demo-' + demo_id).closest('.one-theme').prevAll('.one-theme:visible:first').length;
		if (prev_theme_num === 0) {
			$('.header_btn_bar .previous').css({'opacity': '0.5', 'cursor': 'initial'});
			$('.header_btn_bar .previous').attr('data-demo-id', '0');
		}
		// If no (0) next theme preview div available, disable next button
		demo_id = $(this).attr('data-id');
		var next_theme_num = $('#demo-' + demo_id).closest('.one-theme').nextAll('.one-theme:visible').length;
		if (next_theme_num === 0) {
			$('.header_btn_bar .next').css({'opacity': '0.5', 'cursor': 'initial'});
			$('.header_btn_bar .next').attr('data-demo-id', '0');
		}

		// Load Preview Overlay after preview next theme information compilation
		tb_show("Preview Popup", "#TB_inline?width=full&height=full&inlineId=thickbox_preview&modal=true&class=thickbox", null);
		$('.preview-container').addClass('scroll');
		// Add preview page specific class to set page width/height to full page
		$('body').addClass("preview_page");
		var referrer = location.search;

	});


	$(document).on("click", ".close_btn", function(){
		// remove thickbox overlay
		tb_remove();
		// remove preview page specific class
		setTimeout( function(){
			$('body').removeClass("preview_page");
		}, 500 );
	});

	$(document).on("click", ".one-dialog-close", function(){
		tb_remove();
	});

	$(document).on( 'click', '.preview-install-button', function() {


		var current_demo_id = $(this).attr('data-active-demo-id');
		var item            = null;
		$('.one-theme').each( function( key, theme ) {
			var demo_id = $(theme).find('.preview_link').attr('data-id');
			if ( demo_id === current_demo_id ) {
				item = theme;
				return false;
			}
		} );
		if ( item != null ) {
			$('html, body').stop().animate({ scrollTop: ( $(item).offset().top - 64 ) }, 300);
			$('.close_btn').trigger('click');
			$(item).find('.one-install').trigger('click');
		}
	} );

	$(document).on("click", "#desktop", function(e){
		e.preventDefault();
		$(".preview-container .phone-content").removeClass("phone-content").addClass("desktop-content");
		$(".preview-container .tablet-content").removeClass("tablet-content").addClass("desktop-content");
		$(".preview-container .preview div").remove(".scrn-wrap");
		$(".preview-container").addClass("scroll");
		$(".preview-container iframe").removeClass("horizontal");
		$(".desktop-content").removeClass("horizontal");
		$("#desktop").addClass("gv-active").removeClass('alternative');
		$('#tablet').removeClass("gv-active").addClass('alternative');
		$("#mobile").removeClass("gv-active").addClass('alternative');
	});

	/**
	 * Preview desktop version template
	 */
	$(document).on("click", "#tablet", function (e) {
		e.preventDefault();
		$(".preview-container .phone-content").removeClass("phone-content").addClass("tablet-content");
		$('.preview-container .desktop-content').removeClass("desktop-content").addClass("tablet-content");
		$(".preview-container .preview div").remove(".scrn-wrap");
		$(".preview-container").addClass("scroll");
		$(".preview-container iframe").removeClass("horizontal");
		$(".desktop-content").removeClass("horizontal");

		$('#tablet').addClass("gv-active").removeClass('alternative');
		;
		$("#desktop").removeClass("gv-active").addClass('alternative');
		;
		$("#mobile").removeClass("gv-active").addClass('alternative');
		;
	});

	/**
	 * Preview mobile version template
	 */
	$(document).on("click", "#mobile", function (e) {
		e.preventDefault();
		$('.preview-container .desktop-content').removeClass("desktop-content").addClass("phone-content");
		$('.preview-container .tablet-content').removeClass("tablet-content").addClass("phone-content");
		$(".preview-container").addClass("scroll");
		$("#mobile").addClass("gv-active").removeClass('alternative');
		$("#desktop").removeClass("gv-active").addClass('alternative');
		;
		$("#tablet").removeClass("gv-active").addClass('alternative');
		;
	});

	$(document).on("click", ".screen-rotate", function(){
		$(".preview-container iframe").toggleClass("horizontal");
		$(".phone-content").toggleClass("horizontal");
	});

	$(document).on("click", ".header_btn_bar .next", function(){
		// Check if current preview theme is first, disable previous button
		var demo_id        = $(this).attr('data-demo-id');
		var active_demo_id = $('#preview_box .theme-info').attr('data-active-demo-id');
		var next_theme_num = $('#demo-' + demo_id).closest('.one-theme').next('.one-theme').length;
		$('.header_btn_bar .preview-install-button').attr('data-active-demo-id', demo_id);
		var referrer = location.search;
		oc_add_preview_log(demo_id, 'navigation',referrer);
		// Toggle premium badge
		oc_toggle_inline_badge($('[data-index="' + demo_id + '"]').attr('data-is-premium'));

		if (demo_id === '0') {
			// demo_id 0 means, you are already on last theme. No action needed
			event.stopPropagation();
		} else if (next_theme_num === 0) {
			// next_theme_num 0 means, next theme is last theme. Disable next button
			$(this).css({ 'opacity' : '0.5', 'cursor' : 'initial' });
			$(this).attr('data-demo-id', 0);
			$('.header_btn_bar .previous').attr('data-demo-id', active_demo_id);
			var url           = $('#demo-' + demo_id).attr('data-demo-url');
			var theme_wrapper = $('#demo-' + demo_id).parents('.one-theme:first');
			$('iframe').attr('src', url);
			$('.header_btn_bar .theme-info').attr('data-active-demo-id', demo_id);
		} else {
			// Common action for rest of the themes
			$('.header_btn_bar .previous').removeAttr('style');
			var url           = $('#demo-' + demo_id).attr("data-demo-url");
			var theme_wrapper = $('#demo-' + demo_id).parents('.one-theme:first');
			$('iframe').attr('src', url);
			var next_id = $('#demo-' + demo_id).closest('.one-theme').next('.one-theme').find('.preview_link').attr("data-id");
			$(this).attr('data-demo-id', next_id);
			$('.header_btn_bar .previous').attr('data-demo-id', active_demo_id);
			$('.header_btn_bar .theme-info').attr('data-active-demo-id', demo_id);
		}
		if ( $(theme_wrapper).hasClass('installed') ) {
			$('.header_btn_bar').find('.preview-install-button').hide();
		} else {
			$('.header_btn_bar').find('.preview-install-button').show();
		}
	});

	$(document).on("click", ".header_btn_bar .previous", function(){
		// Check if current preview theme is first, disable previous button
		var demo_id        = $(this).attr('data-demo-id');
		var active_demo_id = $('#preview_box .theme-info').attr('data-active-demo-id');
		var prev_theme_num = $('#demo-' + demo_id).closest('.one-theme').prev('.one-theme').length;
		$('.header_btn_bar .preview-install-button').attr('data-active-demo-id', demo_id);
		var referrer = location.search;
		oc_add_preview_log(demo_id, 'navigation',referrer);
		// Toggle premium badge
		oc_toggle_inline_badge($('[data-index="' + demo_id + '"]').attr('data-is-premium'));

		if (demo_id === '0') {
			// demo_id 0 means, no previous theme demo available
			event.stopPropagation();
		} else if (prev_theme_num === 0) {
			// prev_theme_num 0 means, it will switch to first theme and disable previous button
			$(this).css({ 'opacity' : '0.5', 'cursor' : 'initial' });
			$(this).attr('data-demo-id', 0);
			$('.header_btn_bar .next').attr('data-demo-id', active_demo_id);
			var url           = $('#demo-' + demo_id).attr('data-demo-url');
			var theme_wrapper = $('#demo-' + demo_id).parents('.one-theme:first');
			$('iframe').attr('src', url);
			// Assign previous demo id 0, as this is first theme
			$('.header_btn_bar .theme-info').attr('data-active-demo-id', demo_id);
		} else {
			$('.header_btn_bar .next').removeAttr('style');
			var url           = $('#demo-' + demo_id).attr("data-demo-url");
			var theme_wrapper = $('#demo-' + demo_id).parents('.one-theme:first');
			$('iframe').attr('src', url);
			var prev_id = $('#demo-' + demo_id).closest('.one-theme').prev('.one-theme').find('.preview_link').attr("data-id");
			$(this).attr('data-demo-id', prev_id);
			$('.header_btn_bar .next').attr('data-demo-id', active_demo_id);
			$('.header_btn_bar .theme-info').attr('data-active-demo-id', demo_id);
		}
		if ( $(theme_wrapper).hasClass('installed') ) {
			$('.header_btn_bar').find('.preview-install-button').hide();
		} else {
			$('.header_btn_bar').find('.preview-install-button').show();
		}
	});


	// Toggle back to top button
	this.onecom_move_up_toggle = function() {
		if ( $('.onecom-move-up').length == 0 ) {
			return false;
		}
		var window_height = $(window).height();
		var scrollTop     = $(window).scrollTop();
		if ( ( window_height / 2 ) <= scrollTop ) {
			$('.onecom-move-up').addClass('show');
		} else {
			$('.onecom-move-up').removeClass('show');
		}
	}

	function oc_add_preview_log(obj, section,referrer){
		var tn            = '', isPremium;
		var index         = obj;
		var targetElement = $('#theme-browser-page-1').find($('[data-index="' + index + '"]'));
		tn                = $(targetElement).find($('.theme-action')).find("[data-theme_slug]").data('theme_slug');
		isPremiumInt      = $(targetElement).data('is-premium') || '0';
		if (isPremiumInt == '0') {
			isPremium = 'false';
		}else if (isPremiumInt == '1') {
			isPremium = 'true';
		}
		if (tn) {
			themeName = tn.trim()
		}
		oc_trigger_log({
			actionType: 'wppremium_preview_theme',
			isPremium: isPremium,
			theme: tn ,
			referrer:referrer
		});
	}


	/**
	* Horizontal Scrolable/Drag menu
	* https://codepen.io/thenutz/pen/VwYeYEE
	*/
	if ($('.h-parent').length ) {
		const slider = document.querySelector('.h-parent');
		let isDown   = false;
		let startX;
		let scrollLeft;

		slider.addEventListener('mousedown', (e) => {
			isDown = true;
			slider.classList.add('active');
			startX     = e.pageX - slider.offsetLeft;
			scrollLeft = slider.scrollLeft;
		});
		slider.addEventListener('mouseleave', () => {
			isDown = false;
			slider.classList.remove('active');
		});
		slider.addEventListener('mouseup', () => {
			isDown = false;
			slider.classList.remove('active');
		});
		slider.addEventListener('mousemove', (e) => {
			if ( ! isDown) return;
			e.preventDefault();
			const x           = e.pageX - slider.offsetLeft;
			const walk        = (x - startX) * 3; //scroll-fast
			slider.scrollLeft = scrollLeft - walk;
			// console.log(walk);
		});
	}

	$(document).ready(function() {
		$('a.oc_theme_filter_select[data-category-filter="all"]').trigger('click');
	});
})(jQuery);


