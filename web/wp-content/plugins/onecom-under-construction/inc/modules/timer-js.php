<?php

/**
 * Defines countdown timer
 * Scripts included in PHP because need to access PHP data into JS code and WordPress localize (and other) hooks cannot used
 */

// Exit if file accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$html            = new OCUC_Render_Views();
$uc_option       = self::get_uc_option();
$uc_timer_action = isset( $uc_option['uc_timer_action'] ) ? $uc_option['uc_timer_action'] : '';
$uc_timer        = isset( $uc_option['uc_timer'] ) ? $uc_option['uc_timer'] : '';
$uc_timer_switch = isset( $uc_option['uc_timer_switch'] ) ? $uc_option['uc_timer_switch'] : '';

ob_start();
/**
 * Countdown timer JS event only if
 * * if timer is on
 * * AND a valid future
 * * OR past date with no action
 */
if (
	false !== strtotime( $uc_timer ) &&
	'on' === $uc_timer_switch &&
	( strtotime( $uc_timer ) >= strtotime( current_time( 'Y-m-d H:i:s' ) ) ||
		( strtotime( $uc_timer ) < strtotime( current_time( 'Y-m-d H:i:s' ) ) &&
			'no-action' === $uc_timer_action ) )
) {
	?>
	<script>
		(function($) {
			$(document).ready(function() {
				var currentTime = Number(<?php echo strtotime( current_time( 'Y-m-d H:i:s' ) ); ?>);
				// Update the countdown every 1 second
				var x = setInterval(function() {
					// Update the countdown every 1 second
					//var currentTime = Number(<?php echo strtotime( current_time( 'Y-m-d H:i:s' ) ); ?>);

					// Ajax call to get latest timestamp from WordPress
					$.ajax({
						url: oc_ajax.ajaxurl,
						type: "POST",
						data: {
							'action': 'ocuc_wp_time'
						},
						success: function(data) {
							// return current timestamp based on WordPress settings
							// console.log(data);
							currentTime = data;
						},
						error: function(errorThrown) {
							currentTime = data;
						}
					});

					// Timer end date
					var endDate = <?php echo strtotime( $uc_timer ); ?>;

					// Find the interval between now and the countdown date
					var interval = endDate - currentTime;

					// Time calculations for days, hours, minutes and seconds
					var seconds = Number(interval);
					var days = Math.floor(seconds / (3600 * 24));
					days = String("00" + days).slice(-2); // keep it in 2 digit always
					var hours = Math.floor(seconds % (3600 * 24) / 3600);
					hours = String("00" + hours).slice(-2);
					var minutes = Math.floor(seconds % 3600 / 60);
					minutes = String("00" + minutes).slice(-2);
					var seconds = Math.floor(seconds % 60);
					seconds = String("00" + seconds).slice(-2);

					// Show timer have future date
					if (interval > 0) {
						document.getElementById("counter-day").innerHTML = days;
						document.getElementById("counter-hour").innerHTML = hours;
						document.getElementById("counter-minute").innerHTML = minutes;
						document.getElementById("counter-second").innerHTML = seconds;
					}
					// If the timer is finished, set it 00
					else {
						clearInterval(x);
						document.getElementById("counter-day").innerHTML = "00";
						document.getElementById("counter-hour").innerHTML = "00";
						document.getElementById("counter-minute").innerHTML = "00";
						document.getElementById("counter-second").innerHTML = "00";
					}
				}, 1000);
			});

		})(jQuery)
	</script>
	<?php
}
$html = ob_get_clean();
