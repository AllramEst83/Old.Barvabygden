(function ($) {
	let HM = {
		ajaxurl: oc_constants.ajaxurl,
		checks: oc_constants.checks,
		counts: {
			todo: 0,
			done: 0,
			ignored: 0,
			critical: 0,
			hidden: 0
		},
		currentScreen: oc_constants.current_screen,
		emptyListMessages: oc_constants.empty_list_messages,
		howToFixKey: 'How to fix',
		revertKey: 'Revert',
		spinner: '<span class="components-spinner"></span>',
		text: oc_constants.text,
		totalChecks: oc_constants.checks.length,
		actionTaken: function (el) {
			return ($(el).parent().find('.components-spinner').length > 0);
		},
		calculateScore: function () {
			let that = this;
			let vulnCount = (! isNaN(parseInt($('#onecom_card_vulnerability_score').text())) && parseInt($('#onecom_card_vulnerability_score').text()) > 0) ? 1 : 0;
			let okCount = parseInt($('.done .ocsh-bullet').length) + parseInt($('.ocsh-bullet.ocsh-fix-success').length);
			let errCount = parseInt($('.todo .ocsh-bullet').length) - parseInt($('.ocsh-bullet.ocsh-fix-success').length);

			let ignoreCount = parseInt($('.ignored .ocsh-bullet').length);

			let healthPercent = Math.floor(((okCount + ignoreCount) * 100) / (that.totalChecks + vulnCount - that.counts.hidden  ));
			let scoreClass = 'poor';
			if (healthPercent == '100.00') {
				healthPercent = 100;
			}
			if (healthPercent > 75) {
				scoreClass = 'good';
			} else if (healthPercent <= 75 && healthPercent >= 50) {
				scoreClass = 'ok';
			}
			this.saveResult(healthPercent);
			that.counts.todo = errCount;
			return {
				score: `<span class="${scoreClass}">${healthPercent}%</span>`,
				todoCount: errCount
			};
		},
		deleteFile: function (el) {
			let that = this;
			let file = $(el).attr("data-file");
			if (! file) {
				return;
			}
			$(el).removeAttr("data-file");
			// showLoader(el.target);
			$.post(ajaxurl, {
				action: "ocsh_fix_backup_zip",
				file: file
			}, function (res) {
				$(el).text(res.title);
				$(el).addClass("ocsh-processed");
				// hideLoader(el.target);
				let remainingDeleteLinks = $(el).parents('.ocsh-desc-li').find('.ocsh-delete-link');

				if (remainingDeleteLinks.length === 1) {
					that.moveListItem($(el), $('ul.done'));
				}
			});
		},
		emptyListTemplate: function (text = '') {
			let html = `<div class="onecom_empty_list"><div class="onecom_empty_list__image"><img src="${oc_constants.asset_url}/modules/health-monitor/assets/images/wp-hosting.svg"></div><div><p class="onecom_empty_list__text">${text}</p></div>`;
			return html;
		},
		handleTabClick: function (el) {
			let target = $(el).attr('data-tab');
			$('.onecom_tabs_panel').fadeOut('fast');
			$(el).parent().find('.active').removeClass('active');
			$(el).addClass('active');
			$('.onecom_tabs_panels #' + target).fadeIn('fast');
		},
		hideLoader: function (el) {
			if (! el) {
				return;
			}
			$(el).parent().find('.components-spinner').remove();
		},
		isMobile: function () {
			return $(window).width() <= 774;
		},
		ignoreCheck: function (el) {
			let that = this;
			let check = $(el).data('check');
			that.counts.todo = ! isNaN(parseInt($('#todo_count').html()))? parseInt($('#todo_count').html()): 0;
			that.counts.ignored = ! isNaN(parseInt($('#ignored_count').html()))? parseInt($('#ignored_count').html()): 0;
			if ($(that).hasClass('ocsh-open-modal')) {
				that.showModal();
				return;
			}
			that.showLoader(el);
			$(that).removeClass("oc-mark-resolved");
			$.post(that.ajaxurl, {
				action: "ocsh_mark_resolved",
				check: check
			}, function (res) {
				that.hideLoader(el);
				$(that).text(res.title);
				$(el).parents('li.ocsh-bullet')
					.find('.oc-mark-resolved')
					.addClass('onecom_unignore')
					.removeClass('oc-mark-resolved')
					.text(that.text.unignore);
				let parentElement = $(el).parents('li.ocsh-bullet');
				parentElement.toggleClass('expanded');
				parentElement.find('.ocsh-desc-wrap').toggleClass('hidden');
				$(el).parents('li.ocsh-bullet').appendTo($('ul.ignored'));
				$('#ignored_count').html(++that.counts.ignored);
				$('#todo_count').html(--that.counts.todo);
				$('#onecom_card_todo_score').html(that.counts.todo);
				let score = that.calculateScore();
				$('#onecom_card_result').html(score.score);
				that.updateListStatus();
			});
		},
		moveListItem: function (el, targetList) {
			let parentElement;
			if (el.hasClass('ocsh-bullet')) {
				parentElement = el;
			} else {
				parentElement = $(el).parents('li.ocsh-bullet');
			}
			if(! el.hasClass('ocsh-delete-link')) {
				parentElement.toggleClass('expanded');
				parentElement.find('.ocsh-desc-wrap').toggleClass('hidden');
			}
			if (! targetList.hasClass('done')) {
				parentElement.appendTo(targetList);
			}
			this.counts.todo = parseInt($('.todo .ocsh-bullet').length) - parseInt($('.todo .ocsh-bullet.ocsh-fix-success').length);
			this.counts.done = parseInt($('.done .ocsh-bullet').length) + parseInt($('.ocsh-bullet.ocsh-fix-success').length);
			this.counts.ignored = parseInt($('.ignored .ocsh-bullet').length) - parseInt($('.ignored .ocsh-bullet.ocsh-fix-success').length);
			$('#todo_count').text(this.counts.todo);
			$('#onecom_card_todo_score').text(this.counts.todo);
			$('#done_count').text(this.counts.done);
			$('#ignored_count').text(this.counts.ignored);
			let score = this.calculateScore();
			$('#onecom_card_result').html(score.score);
			this.updateListStatus();
		},
		quickFix: function (el) {
			if (el.attr('href')) {
				return;
			}
			if (el.data('url') && el.data(url).length) {
				window.open(el.data('url'), 'CP');
				return;
			}
			let that = this;
			let inputs = {};
			let emptyInput = false;
			let inputArray = $(el).parents('.ocsh-bullet').find('input');
			if (inputArray.length) {
				$.each(inputArray, function (index, input) {
					$(input).text("").removeClass("ocsh-error-field");
					$(input).parent().find('.oc-error-message').fadeOut();
					if ($(input).val() == '') {

						let errorMessage = oc_constants.error_empty;
						if ($(input).attr('name') === 'oc_hm_site_key') {
							errorMessage = oc_constants.error_empty_sitekey;
						}
						$(input).parent().find('.oc-error-message').text(errorMessage).fadeIn();
						$(input).addClass("ocsh-error-field");
						emptyInput = true;
					} else if ($(input).val().length < 40) {
						$(input).parent().find('.oc-error-message').text(oc_constants.error_length).fadeIn();
						$(input).addClass("ocsh-error-field");
						emptyInput = true;
					}
					inputs[$(input).attr('name')] = $(input).val();
				});
			}
			if (emptyInput) {
				return;
			}
			let check = $(el).data("check");
			if (! check) {
				return;
			}
			that.showLoader(el);
			$.post(that.ajaxurl, {
				action: `ocsh_fix_${check}`,
				inputs: inputs
			}, function (response) {
				let parentElement = $(el).parents('.ocsh-bullet');
				if (response.status == '0') {
					$(el).attr('disabled', 'disabled');
					try {
						parentElement.find('.onecom__scan_content__wrap').html(response.desc);
						if (parentElement.data('undo') == '1') {
							parentElement.find('.ocsh-actions').html(`<a href="javascript:void(0)" class="onecom__revert_action" data-check="${check}">${that.revertKey}</a>`);
							let tag = parentElement.find('.onecom_tag').clone();
							let oldTitle = parentElement.find('.onecom__scan-title-bg').html();
							parentElement.data('old-title', oldTitle);
							parentElement.find('.onecom__scan-title-bg').html(response.desc);
							parentElement.find('.onecom__scan-title-bg').append(tag);
							parentElement.find('.onecom__scan-title-bg').append(`<a href="javascript:void(0)" class="onecom__revert_action" data-check="${check}"> ${that.revertKey}</a>`);
							parentElement.find('.onecom_tag').hide();
						} else {
							parentElement.find('.onecom_tag').hide();
							parentElement.find('.ocsh-actions').remove();
						}
						parentElement.find('.onecom__how_to_fix_wrap').remove();
						parentElement.addClass('ocsh-fix-success');
						that.moveListItem(parentElement, $('ul.done'));
					} catch (e) {
						console.info(e.message)
					}
				} else if (response.status == '1' && check === 'debug_log_size') {
					that.hideLoader(el);
					let errorMessageElement = parentElement.find('.oc-error-message');
					if (errorMessageElement.length === 0) {
						parentElement.find('.ocsh-actions').append('<p class="oc-error-message">' + response.title + '</p>')
					}
				}
			});
		},
		revert: function (el) {
			let that = this;
			let check = $(el).attr("data-check");
			if (! check) {
				return;
			}
			that.showLoader(el);
			$.post(that.ajaxurl, {
				action: "ocsh_undo_" + check
			}, function (response) {
				if (response.status == '0') {
					$(el).attr('disabled', 'disabled');
					try {
						let parentElement = $(el).parents('.ocsh-bullet');
						// parentElement.find('.onecom_tag').show();
						parentElement.find('.onecom__revert_action').remove();
						parentElement.find('.components-spinner').remove();
						parentElement.removeClass('ocsh-fix-success');
						parentElement.find('.onecom_tag').removeClass('oc_hide_tag');
						parentElement.find('.onecom__scan_content__wrap').html(response.desc);
						if (parentElement.data('old-title') != '') {
							parentElement.find('.onecom__scan-title-bg').html(parentElement.data('old-title'));
						}
						let howToFix = `<span class="onecom__how_to_fix_wrap"><h4 class="ocsh-scan-title onecom__fix_title">${that.howToFixKey}</h4>${response.how_to_fix}</span>`;
						let actions = `<div class="ocsh-actions"><span class="ocsh-resolve-wrap"><a class=" oc-mark-resolved" data-check="${check}">${response.ignore_text}</a></span><span class="ocsh-fix-wrap"><button class="oc-fix-button" data-check="${check}">${response.fix_button_text}</button></span></div>`;
						parentElement.find('.ocsh-actions').html(actions);
						parentElement.find('.osch-desc').append(howToFix);
						that.moveListItem(parentElement, $('ul.todo'));
					} catch (e) {
						console.info(e.message)
					}
				}
				that.hideLoader(el);
			});
		},
		runCheck: function (index) {
			let that = this;
			let data = {
				action: 'ocsh_check_' + this.checks[index]
			};

			if (this.checks[index] === 'error_reporting') {
				data.err = $('.onecom_tabs_container').data('error');
			}
			$.post(this.ajaxurl, data, function (response) {
				if (++index < that.checks.length) {
					that.runCheck(index);
				}
				try {
					switch (response.status) {
						case 0:
							$('ul.done').append(response.html);
							that.counts.done++;
							$('#done_count').text(that.counts.done);
							break;
						case 1:
							$('ul.todo').append(response.html);
							that.counts.todo++;
							$('#todo_count').text(that.counts.todo);
							break;
						case 2:
							// 2 means the factor we are checking for is not present and won't be displayed to the user
							that.counts.hidden++;
							break;
						case 3:
							// ignored status
							$('ul.ignored').append(response.html);
							that.counts.ignored++;
							$('#ignored_count').text(that.counts.ignored);
							break;
						case 4:
							// critical issues
							$('ul.critical').append(response.html);
							that.counts.todo++;
							$('#todo_count').text(that.counts.todo);
							break;
					}

				} catch (e) {
					console.info(e.message);
				}
				if (index === (that.checks.length)) {

					that.updateListStatus();
					let score = that.calculateScore();
					$('#onecom_card_result').html(score.score);
					$('#onecom_card_todo_score').text(that.counts.todo);
					if($('#onecom_card_result').find('.components-spinner').length <= 0) {
						$('.oc-trigger-hmscan').text('Scan now').removeClass('oc-disabled');
					}
				}
			});
		},
		saveResult: function (result) {
			result = 10;
			let color = '#4ab865';
			if (result < 85 && result >= 50) {
				color = '#ffb900';
			} else if (result < 50) {
				color = '#dc3232';
			}
			let score = '<i style="color:' + color + '">' + Number(result).toFixed(0) + '%</i> ';
			$.post(ajaxurl, {
				action: 'ocsh_save_result',
				osch_Result: result
			}, function (response) {
				let existingText = $('#ocsh-site-security').text();
				$('#ocsh-site-security').html(existingText + ' - ' + score + '<a class="button" href="' + oc_constants.ocsh_page_url + '">' + oc_constants.ocsh_scan_btn + '</a>');
				/* Show last time scan in UI */
				if(response && response.hasOwnProperty('last_scan_time')){
					$('#oc-last-scan-datetime').text(response.last_scan_time);
				}
			}, "json");
		},
		saveUser: function (el) {
			let that = this;
			let username = $(el).parent().find('.onecom__input_user').val();
			if (username == '') {
				$(el).parent().find('.onecom__input_user').addClass('onecom__error');
				return;
			}
			let oldUser = $(el).data('user');
			let nonce = $(el).data('nonce');
			that.showLoader($(el));
			$.post(that.ajaxurl, {
				action: 'ocsh_change_username',
				username: username,
				oldUser: oldUser,
				_ajax_nonce: nonce,
			}, function (response) {
				if (response.status == 1) {
					$(el).parent().find('.onecom__input_user').addClass('onecom__error');
				}
				if (response.status == '0') {
					let remainingActions = $(el).parents('.ocsh-desc-li').find('.onecom__input_user');
					if (remainingActions.length === 1) {
						that.moveListItem($(el), $('ul.done'));
					} else {
						$(remainingActions).slideUp().remove();
					}
				}
				that.hideLoader($(el));
			});
		},
		showFields: function (el) {
			$(el).parent().find('.oc_hidden').show();
		},
		showConfirmation: function (data) {
			// $('#oc_um_head h5').text(data.desc);
			$('#oc_um_body').html(data.title || data.desc || '');
			$('#oc_um_head').hide();
			$('#oc_phased-in').hide();
			$('.oc_up_btn').hide();
			$('#oc_um_overlay').show();
			$('#oc_um_wrapper').css('min-height', 'auto').css('min-width', 'auto');
			$('.oc_cancel_btn').css('margin', '0').addClass('oc_up_btn').text('Close');
			$('.oc_cancel_btn').show();
		},
		showLoader: function (el = null) {
			if (! el) {
				return;
			}
			if ($(el).parent().find('.components-spinner').length === 0) {
				$(el).parent().append(this.spinner);
			}
		},
		showModal: function (check) {
			$('#oc_um_overlay').show();
			$('body').addClass('oc-noscroll');
			let referrer = '';
			ocSetModalData({
				isPremium: true,
				feature: 'health_monitor',
				plugin: 'onecom-themes-plugin',
				featureAction: check,
				referrer: referrer
			});
		},
		startScan: function () {
			let that = this;
			if (! (this.checks && this.checks.length)) {
				return;
			}
			if (this.currentScreen !== '_page_onecom-wp-health-monitor') {
				return;
			}
			let count = 0;
			// remove recommendations from last scan (except for recommendations in vulnerabilities tab)
			$('.onecom_tabs_panel:not(.vulnerability)').find('li').remove();
			$('#onecom_card_result').html(that.spinner);
			$('#onecom_card_todo_score').html(that.spinner);
			// reset existing counts in HTML on the start of a new scan (except for counts in vulnerabilities tab)
			$('.onecom_tabs_container>.onecom_tab:not([data-tab="vulnerability"])>.count').text('0');
			that.counts.done = 0;
			that.counts.todo = 0;
			that.counts.ignored = 0;
			that.counts.critical = 0;
			that.counts.hidden = 0;
			this.runCheck(count);
		},
		scrollMenu: function () {
			/**
			 * Horizontal Scrolable/Drag menu
			 * https://codepen.io/thenutz/pen/VwYeYEE
			 */
			if ($('.h-parent').length) {
				const slider = document.querySelector('.h-parent');
				let isDown = false;
				let startX;
				let scrollLeft;

				slider.addEventListener('mousedown', (e) => {
					isDown = true;
					slider.classList.add('active');
					startX = e.pageX - slider.offsetLeft;
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
					if (! isDown) return;
					e.preventDefault();
					const x = e.pageX - slider.offsetLeft;
					const walk = (x - startX) * 3; //scroll-fast
					slider.scrollLeft = scrollLeft - walk;
					// console.log(walk);
				});
			}
		},
		toggleBullet: function (el, e = null) {
			if (! $(e.target).hasClass('onecom__scan-title-bg')) {
				return;
			}
			if ($(e.target).parents('.ocsh-bullet').hasClass('ocsh-fix-success')) {
				return;
			}
			$(el).toggleClass('expanded');
			$(el).find('.ocsh-desc-wrap').toggleClass('hidden');
		},
		unIgnore: function (el) {
			let that = this;
			let target = $('ul.todo');
			let check = $(el).data('check');
			that.counts.todo = ! isNaN(parseInt($('#todo_count').html()))? parseInt($('#todo_count').html()): 0;
			that.counts.ignored = ! isNaN(parseInt($('#ignored_count').html()))? parseInt($('#ignored_count').html()): 0;
			if (check == '') {
				return;
			}
			if ($(that).hasClass('ocsh-open-modal')) {
				that.showModal(check);
				return;
			}
			$(that).removeClass("onecom_unignore");
			that.showLoader(el);
			$.post(that.ajaxurl, {
				action: "onecom_unignore",
				check: check
			}, function (res) {
				that.hideLoader(el);
				$('ul.todo').append($(el).parents('li.ocsh-bullet'));
				let newText;
				if ($(el).data('priority') === 'critical') {
					newText = that.text.ignore_critical;
				} else {
					newText = that.text.ignore;
				}
				$(el).parents('li.ocsh-bullet')
					.find('.onecom_unignore')
					.addClass('oc-mark-resolved')
					.removeClass('onecom_unignore')
					.text(newText);
				let parentElement = $(el).parents('li.ocsh-bullet');
				parentElement.toggleClass('expanded');
				parentElement.find('.ocsh-desc-wrap').toggleClass('hidden');
				$(el).parents('li.ocsh-bullet').appendTo(target);
				$('#ignored_count').html(--that.counts.ignored);
				$('#todo_count').html(++that.counts.todo);
				$('#onecom_card_todo_score').html(that.counts.todo);
				let score = that.calculateScore();
				$('#onecom_card_result').html(score.score);
				that.updateListStatus();
			})
		},
		updatedCounts: function (data) {
			$('#onecom_card_result').html(data.score);
			$('#onecom_card_todo_score').html(data.todoCount);
		},
		updateListStatus: function () {
			let that = this;
			$(document).find('.onecom_empty_list').remove();
			if (($('ul.todo li').length === 0) && ($('#todo .onecom_empty_list').length === 0) && ($('ul.critical li').length === 0)) {
				$('#todo').append(that.emptyListTemplate(that.emptyListMessages.todo));
			}
			if (($('ul.done li').length === 0) && ($('#done .onecom_empty_list').length === 0)) {
				$('#done').append(that.emptyListTemplate(that.emptyListMessages.done));
			}
			if (($('ul.ignored li').length === 0) && ($('#ignored .onecom_empty_list').length === 0)) {
				$('#ignored').append(that.emptyListTemplate(that.emptyListMessages.ignored));
			}
		},
		validateInput: function (input) {
			if (input.val().length === 0) {
				$(input).parent().find('.oc-error-message').text(oc_constants.error_empty).fadeIn();
				$(input).addClass("ocsh-error-field");
			} else if (input.val().length < 40) {
				$(input).parent().find('.oc-error-message').text(oc_constants.error_length).fadeIn();
				$(input).addClass("ocsh-error-field");
			} else {
				$(input).parent().find('.oc-error-message').text("").fadeOut();
				$(input).removeClass("ocsh-error-field");
			}
		}
	}
	$(document).ready(function () {
		HM.scrollMenu();
		HM.updateListStatus();
		let noPrevScan = $('.ocsh-wrap').find('.oc-nps');
		if(noPrevScan.length > 0){
			$('.onecom_head').find('.oc-trigger-hmscan').text('Scanning').addClass( "oc-disabled" );
			$(document).find('.onecom_empty_list').remove();
			HM.startScan();
		}
		$('.onecom_tab').click(function (e) {
			HM.handleTabClick($(this));
		});
		$('.oc-trigger-hmscan').click(function (e){
			$(e.currentTarget).text('Scanning');
			$(e.currentTarget).addClass( "oc-disabled" );
			$(document).find('.onecom_empty_list').remove();
			HM.startScan();
		})
		// Load DataTables (VM history log)
		const trueForMobile = HM.isMobile();
		$('#vm-log-table').DataTable({
			"searching": false,
			scrollX: trueForMobile,
			orderClasses: false,
			"autoWidth": trueForMobile,
			pagingType: 'simple_numbers',
			"columnDefs": [
				{ "targets": 1, "orderable": false  },
				{ "targets": 5, "orderable": false },
				{ "targets": 6, "orderable": false }
			],
			order: [[0, 'desc']],
			"language": {
				"paginate": {
					"previous": "<i class='vm_page_left_arrow'></i>",
					"next": "<i class='vm_page_right_arrow'></i>"
				},
				"info": vm_log_strings.showing + " _START_ " + vm_log_strings.to + " _END_ " + vm_log_strings.of + " _TOTAL_ " + vm_log_strings.entries
			}
		} );

		// Wrap DataTables information and pagination in a container
		let infoContainer = $('<div class="info-paginate-container"></div>');
		$('.dataTables_info, .dataTables_paginate').appendTo(infoContainer);
		$('#vm-log-table_wrapper').append(infoContainer);

		// Append the container after the table

		$(document).on('click', '.ocsh-bullet', function (e) {
			HM.toggleBullet($(this), e);
		});
		$(document).on('click', '.oc-mark-resolved ', function (e) {
			if (HM.actionTaken($(this))) {
				return;
			}
			HM.ignoreCheck($(this));
		});
		$(document).on('click', '.onecom_unignore ', function (e) {
			if (HM.actionTaken($(this))) {
				return;
			}
			HM.unIgnore($(this));
		});
		$(document).on('click', '.onecom__open-modal', function (e) {
			let check = $(this).parents('li.ocsh-bullet').attr('id') || "ignore_list";
			check = check.replace("ocsh-", '');
			check = check.replace("check_", "");
			HM.showModal(check);
		});
		$(document).on('click', '.oc-fix-button', function (e) {
			if (HM.actionTaken($(this))) {
				return;
			}
			HM.quickFix($(this));
		});
		$(document).on('click', '.onecom__show_fields', function (e) {
			HM.showFields($(this));
		});
		$(document).on('click', '.onecom__save_user', function (e) {
			if (HM.actionTaken($(this))) {
				return;
			}
			HM.saveUser($(this));
		});
		$(document).on("click", ".ocsh-delete-link", function (e) {
			if (HM.actionTaken($(this))) {
				return;
			}
			HM.deleteFile($(this));
		});
		$(document).on("click", ".onecom__revert_action", function (e) {
			if (HM.actionTaken($(this))) {
				return;
			}
			HM.revert($(this));
		});
		$(document).on("click", ".oc_um_btn", function (e) {
			$('body').removeClass('oc-noscroll');
		});
		$(document).on('keyup', '#oc_hm_site_key, #oc_hm_site_secret', function (e) {
			HM.validateInput($(this));
		});
	});
})(jQuery)