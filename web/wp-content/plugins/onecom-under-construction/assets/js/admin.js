(function ($) {
	$(document).ready(function () {
		/**
		 *  flatpickr datepicker init
		 *  disable past date by minDate
		 */
		$("input.picker-datetime").flatpickr({
			enableTime: true,
			dateFormat: "Y-m-d H:i",
			time_24hr: true,
			minDate: "today"
		});

		$('.onecom_tab.disabled-tab').off('click');
		//Load slick slider on mobile devices
		showSlick();
		jQuery(window).on('resize',function(){
			showSlick();
		});

		function showSlick(){
			const viewportWidth = jQuery(window).width();

			if (viewportWidth < 481)
			{
				$('.ocp-radio-image').not('.slick-initialized').slick({
					dots: true,
					slidesToShow: 1,
					slidesToScroll: 1,
					arrows: false,
					draggable: true,
					infinite: false,
					adaptiveHeight: true,
					mobileFirst: true,
				   // centerMode: true,
					variableWidth: true,
					responsive: [
						{
							breakpoint: 1024,
							settings: "unslick"
						},
						{
							breakpoint: 600,
							settings: "unslick"
						},
						{
							breakpoint: 481,
							settings: {
								slidesToShow: 1,
								slidesToScroll: 1
							}
						}
					]
				});
			}
		}

		// Multiselect (via select2 library) for exclude pages
		$('.oc-select2-multi').select2();

		// Show spinner and hide UC settings, untill document ready
		$('.uc_spinner').hide();

		// disable all submit buttons until form changed
		$('#uc-form').find('input:submit').attr('disabled', true);

		// Form will be used many times
		let settingsForm = $('#uc-form');

		$('#responsiveTabsDemo').css("visibility", "visible");

		// Expand textarea according to content
		function textAreaExpand() {
			var element = document.getElementById("onecom_under_construction_info[uc_scripts]");

			element.style.height = "1px";
			element.style.height = (30 + element.scrollHeight) + "px";

			var element = document.getElementById("onecom_under_construction_info[uc_footer_scripts]");

			element.style.height = "1px";
			element.style.height = (30 + element.scrollHeight) + "px";

			var element = document.getElementById("onecom_under_construction_info[uc_custom_css]");

			element.style.height = "1px";
			element.style.height = (30 + element.scrollHeight) + "px";
		}
		//textAreaExpand();

		/**
		 *  Show/Clear image thumb after image change
		 */
		$("input.wpsa-url").change(function () {
			var img_src = $(this).val();
			if (img_src.trim() != '') {
				// clear existing image, and show uploaded image
				$(this).siblings(".img-box").empty();
				$(this).siblings(".img-box").html("<img class='image-thumb' src='' /><button class='img-delete' title='Remove image' type='button'>x</button>");
				$(this).siblings(".img-box").find("img").attr("src", img_src);
				// Trigger form change, to enable/disable save button
				settingsForm.change();
			} else {
				// else keep image box empty
				$(this).siblings(".img-box").empty();
				// Trigger form change, to enable/disable save button
				settingsForm.change();
			}
		});

		// On image delete, clear input box and make image box empty
		$('body').on('click', '.img-delete', function () {
			$(this).closest("td").find("input:text").val("");
			$(this).parent('.img-box').empty();
			// Trigger form change, to enable/disable save button
			settingsForm.change();
		});

		/**
		 * Handle premium feature enable/disable conditions
		 * Allow non-premium cu to modify selection, if they had set it earlier
		 */

		// Disable timer action options for non-premium users (except first & existing selected)
		$(".uc_timer_action option:first").removeAttr('disabled');
		$(".uc_timer_action option:selected").removeAttr('disabled');
		// Once selected first option, disable others
		$('.oc-non-premium .uc_timer_action select').change(function () {
			if ($(this).children('option:first-child').is(':selected')) {
				$(".oc-non-premium .uc_timer_action select option").not(':first-child').each(function () {
					$(this).prop('disabled', true);
				});
			}
		});

		// Whitelisted Users: Make it read-only for non-premium users (except first & existing selected)
		$(".uc_whitelisted_roles td label:first input").prop('readonly', false);
		$(".uc_whitelisted_roles input:checked").prop('readonly', false);
		// Once disabled premium option, do now allow to enable again
		$('.oc-non-premium .uc_whitelisted_roles td label:not(:eq(0)) input').change(function (event) {
			if ($(this).prop('checked') == false) {
				$(this).prop('readonly', true);
			}
		});

		// Implement read-only behaviour on checkboxes
		$(document.body).delegate('[type="checkbox"][readonly="readonly"]', 'click', function (e) {
			// Prevent any action (work like disabled except it submit values)
			e.preventDefault();

			// Show upgrade modal
			jQuery('#oc_um_overlay').show();
			// todo - modal stats feature
			$('.loading-overlay.fullscreen-loader').removeClass('show');
		});

		/** 
		 * Set current selected theme background image
		 * * If not bg image exists 
		 * * Or other theme's default bg image exists
		 */
		$('.uc_theme input[type=radio]').change(function () {
			if (this.checked) {
				var themeName = $(this).attr('value');
				var currentBg = $(".uc_page_bg_image input[type=text]").val();
				var assetsPath = "onecom-under-construction/assets/images";

				if (themeName == 'theme-1' &&
					(currentBg == '' ||
						currentBg.indexOf(assetsPath) != -1)
				) {
					var bgImage = theme_info_obj.theme_directory_uri + '/design-1-bg.jpeg';
					$(".uc_page_bg_image input[type=text]").val(bgImage);
				} else if (themeName == 'theme-2' &&
					(currentBg == '' ||
						currentBg.indexOf(assetsPath) != -1)) {
					var bgImage = theme_info_obj.theme_directory_uri + '/design-2-bg.jpeg';
					$(".uc_page_bg_image input[type=text]").val(bgImage);
				} else if (themeName == 'theme-3' &&
					(currentBg == '' ||
						currentBg.indexOf(assetsPath) != -1)) {
					var bgImage = theme_info_obj.theme_directory_uri + '/design-3-bg.jpeg';
					$(".uc_page_bg_image input[type=text]").val(bgImage);
				} else if (themeName == 'theme-4' &&
					(currentBg == '' ||
						currentBg.indexOf(assetsPath) != -1)) {
					$(".uc_page_bg_image input[type=text]").val('');
				} else if (themeName == 'theme-5' &&
					(currentBg == '' ||
						currentBg.indexOf(assetsPath) != -1)) {
					$(".uc_page_bg_image input[type=text]").val('');
				} else if (themeName == 'theme-6' &&
					(currentBg == '' ||
						currentBg.indexOf(assetsPath) != -1)) {
					var bgImage = theme_info_obj.theme_directory_uri + '/design-6-bg.jpeg';
					$(".uc_page_bg_image input[type=text]").val(bgImage);
				}
			}
		});

		$('.uc_theme input:disabled').next().click(function () {

			jQuery('#oc_um_overlay').show();
			$('.loading-overlay.fullscreen-loader').removeClass('show');
		});

		/* Hide all components on switch disable */
		if (!$('.uc_status input[type=checkbox]').prop('checked')) {
			$('#responsiveTabsDemo .onecom_tabs_container .onecom_tab:nth-child(2)').prop('disabled',true);
			$('#responsiveTabsDemo .onecom_tabs_container .onecom_tab:nth-child(3)').prop('disabled',true);
			$('tr[class^="uc_"]:not(.uc_status):not(.uc_submit)').addClass('uc_hide');
			$('.form-table:nth-of-type(2)').addClass('uc_hide');
			$('.postbox-header:nth-of-type(2)').addClass('uc_hide');
			$('.form-table:nth-of-type(3)').addClass('uc_hide');
			$('.postbox-header:nth-of-type(3)').addClass('uc_hide');
		}

		/* Hide/Show all components on switch change */
		$(document).on('change', '.uc_status input[type=checkbox]', function () {

			if (!$(this).prop("checked")) {
				//check premium ondisable
				if(!theme_info_obj.isPremium) {
					jQuery(this).prop("checked", false);
					jQuery('#submit').trigger('click');
				}

				$('#responsiveTabsDemo .h-parent-wrap .onecom_tabs_container .onecom_tab:nth-child(2)').addClass('disabled-tab');
				$('#responsiveTabsDemo .h-parent-wrap .onecom_tabs_container .onecom_tab:nth-child(3)').addClass('disabled-tab');
				$('tr[class^="uc_"]:not(.uc_status):not(.uc_submit)').addClass('uc_hide');
				$('.form-table:nth-of-type(2)').addClass('uc_hide');
				$('.postbox-header:nth-of-type(2)').addClass('uc_hide');
				$('.form-table:nth-of-type(3)').addClass('uc_hide');
				$('.postbox-header:nth-of-type(3)').addClass('uc_hide');
			} else {

				//check premium on enable
				if(!theme_info_obj.isPremium) {
					jQuery(this).prop("checked", false);
					jQuery('#submit').prop('disabled','disabled');
					show_upsell_view('onEnable');
					return false;
				}

				/**
				 * When maintenance mode switch enabled, check timer date
				 * * If past date/time, set timer to today+1 week
				 */
				let timer_date = new Date($('.uc_timer .picker-datetime').val());
				var now = new Date();

				if (timer_date < now) {
					$("input.picker-datetime").flatpickr({
						enableTime: true,
						dateFormat: "Y-m-d H:i",
						time_24hr: true,
						minDate: "today",
						defaultDate: now.fp_incr(7) // 7 days for now
					});
				}

				$('#responsiveTabsDemo .h-parent-wrap .onecom_tabs_container .onecom_tab:nth-child(2)').removeClass('disabled-tab');
				$('#responsiveTabsDemo .h-parent-wrap .onecom_tabs_container .onecom_tab:nth-child(3)').removeClass('disabled-tab');
				$('tr[class^="uc_"]:not(.uc_status)').removeClass('uc_hide');
				$('.form-table:nth-of-type(2)').removeClass('uc_hide');
				$('.postbox-header:nth-of-type(2)').removeClass('uc_hide');
				$('.form-table:nth-of-type(3)').removeClass('uc_hide');
				$('.postbox-header:nth-of-type(3)').removeClass('uc_hide');
			}
		});

		// Show floating save button if regular button is not in viewport (via JS Observer API)
		var observer = new IntersectionObserver(function (entries) {
			// isIntersecting is true when element and viewport are overlapping else false
			if (entries[0].isIntersecting === true) {
				$('.oc-uc-float-btn').hide();
			} else {
				$('.oc-uc-float-btn').show();
			}
		}, { threshold: [0] });

		observer.observe(document.querySelector("#onecom_under_construction_settings .uc-submit-button"));
		observer.observe(document.querySelector("#onecom_under_construction_content .uc-submit-button"));
		observer.observe(document.querySelector("#onecom_under_construction_customization .uc-submit-button"));

		/* Disable timer field if switched off */
		if (!$('.uc_timer_switch input[type=checkbox]').prop('checked')) {
			$('.uc_timer').addClass('uc_hide');
			$('.uc_timer_action').addClass('uc_hide');
		}

		$(document).on('change', '.uc_timer_switch input[type=checkbox]', function () {
			if (!$(this).prop("checked")) {
				$('.uc_timer').addClass('uc_hide');
				$('.uc_timer_action').addClass('uc_hide');
			} else {
				$('.uc_timer').removeClass('uc_hide');
				$('.uc_timer_action').removeClass('uc_hide');
			}
		});
		
		// Enable floating/sticky submit button when form changed
		settingsForm.each(function () {
			$(this).data('serialized', $(this).serialize());
		}).on('change keyup paste', function () {
			$(this)
				.find('input:submit')
				.attr('disabled', $(this).serialize() == $(this).data('serialized'));
		})
		
		// Show 'Saving' on all submit button once clicked
		$('.uc-submit-button').click(function () {
			$("#uc-form input:submit").val(theme_info_obj.savingLabel);
		});

		/**
		 * Tab click
		 */
		$('#responsiveTabsDemo .onecom_tab').click(function(e){
			if($(this).hasClass('disabled-tab')){
				return false;
			}
			let target = $(this).attr('data-tab');
			$('.onecom_tabs_panel').fadeOut('fast');
			$(this).parent().find('.active').removeClass('active');
			$(this).addClass('active');
			$('.onecom_tabs_panels #' + target).fadeIn('fast');
		});

		/**
		 * Check premium
		 * @param $checkStatus
		 */
		function show_upsell_view(checkStatus){

			if(checkStatus === 'onEnable'){
				let referrer = location.search;
				jQuery('#oc_um_overlay').show();
				ocSetModalData({
					isPremium: theme_info_obj.isPremium,
					feature: 'maintenance_mode',
					featureAction: 'settings',
					referrer: referrer
				});
			}
		}
		change_labelColor_for_nonmWP();
		function change_labelColor_for_nonmWP(){
			//change label color for nonWP
			$('p.tag_badge.nmWP-badge').parent().css('color','#BBBBBB');

			//change for role selection
			const status = $('tr.uc_whitelisted_roles th').find('.tag_badge').hasClass('nmWP-badge');
			if(status){
				$('tr.uc_whitelisted_roles td fieldset .oc_switch_label').each(function(index,val){
					if(index > 0){
						$(this).css('color','#BBBBBB');
					}
				});

				//Change exclude pages color
				$('.uc_exclude_pages label').css('color','#BBBBBB');
				$('.uc_exclude_pages .select2-container--default .select2-selection--multiple').css('background-color','#F7F7F7');
			}


		}

	});
})(jQuery)