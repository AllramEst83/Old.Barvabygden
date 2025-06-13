	<?php

	$sp_options = oc_get_sp_options( 'onecom_sp_protect_options' );

	$onecom_sp = new OnecomSp();

	$spoption_checks  = $sp_options['checks'] ?? '';
	$oc_sp_accept     = $spoption_checks['oc_sp_accept'] ?? '';
	$oc_sp_referrer   = $spoption_checks['oc_sp_referrer'] ?? '';
	$oc_sp_long       = $spoption_checks['oc_sp_long'] ?? '';
	$oc_sp_short      = $spoption_checks['oc_sp_short'] ?? '';
	$oc_sp_bbcode     = $spoption_checks['oc_sp_bbcode'] ?? '';
	$oc_sp_exploit    = $spoption_checks['oc_sp_exploit'] ?? '';
	$oc_sp_quickres   = $spoption_checks['oc_sp_quickres'] ?? '';
	$oc_max_login_val = $spoption_checks['oc_max_login_val'] ?? '';
	$oc_block_time    = $spoption_checks['oc_block_time'] ?? '';


	?>


	<?php
	$oc_nonce = wp_create_nonce( 'one_sp_nonce' );
	if ( ! empty( $success_notice ) ) {
		echo "$success_notice";
	}
	?>


	<div id="oc-sp-success" class="notice notice-success oc_hidden">
		<p><?php _e( 'Protection settings updated!', 'onecom-sp' ); ?></p>
	</div>
	<form id="sp-settings" class="sp-protect-options" method="post" name="sp-protect-options">
		<input type="hidden" name="one_sp_nonce" value="<?php echo $oc_nonce; ?>"/>

		<label for="spaccept">
						<span class="oc_sp_switch">
			<input class="oc_sp_check" type="checkbox" id="spaccept" name="oc_sp_accept"
					value="" 
					<?php
					if ( 'true' == $oc_sp_accept ) {
						echo OnecomSp::CHECKED;
					}
					?>
			>
			<span class="oc_sp_slider"></span>
						</span>
			<span><?php _e( 'Block requests without HTTP_ACCEPT header', 'onecom-sp' ); ?></span>
		</label>
			<p class="description">
				<span class="prt-desc"><?php _e( 'Blocks users who have a missing or incomplete HTTP_ACCEPT header. All browsers provide this header.If a request is missing the HTTP_ACCEPT header it is because a poorly written bot is trying access your site.', 'onecom-sp' ); ?></span>
			</p>



		<label for="spreferrer">
			<span class="oc_sp_switch">
			<input class="oc_sp_check" type="checkbox" id="spreferrer" name="oc_sp_referrer"
					value="true" 
					<?php
					if ( 'true' == $oc_sp_referrer ) {
						echo OnecomSp::CHECKED;
					}
					?>
			>
				<span class="oc_sp_slider"></span>
						</span>
			<span><?php _e( 'Block requests coming from invalid HTTP_REFERER', 'onecom-sp' ); ?></span>
		</label>
		<p class="description">
		<span class="prt-desc"><?php _e( 'When a form is submitted, all browsers generally provide origin of that submission i.e., HTTP_REFERER header. If the HTTP_REFERER header is missing or does not match your website then that submission is probably not made by a human. Note: In some cases, you may want to disable this check if you are noticing incorrect spam detections from visitors who are accessing your site via mobile/tablet devices.', 'onecom-sp' ); ?></span>
		</p>


		<label for="splongchk">
			<span class="oc_sp_switch">
			<input class="oc_sp_check" type="checkbox" id="splongchk" name="oc_sp_long"
					value="true" 
					<?php
					if ( 'true' == $oc_sp_long ) {
						echo OnecomSp::CHECKED;
					}
					?>
			>
			<span class="oc_sp_slider"></span>
			</span>
			<span><?php _e( 'Disable lengthy emails and author names', 'onecom-sp' ); ?></span>
		</label>
			<p class="description">
				<span class="prt-desc"><?php _e( 'Spammers mostly use unusually long names and emails. This check rejects the submissions having names and emails over 64 characters.', 'onecom-sp' ); ?></span>
			</p>


		<label for="spshrtchk">
			<span class="oc_sp_switch">
			<input class="oc_sp_check" type="checkbox" id="spshrtchk" name="oc_sp_short"
					value="true" 
					<?php
					if ( 'true' == $oc_sp_short ) {
						echo OnecomSp::CHECKED;
					}
					?>
			>
				<span class="oc_sp_slider"></span>
			</span>
			<span><?php _e( 'Disable too short emails and author names', 'onecom-sp' ); ?></span>
		</label>
			<p class="description">
				<span class="prt-desc"><?php _e( 'Spammers often  use short usernames or emails. This blocks requests with emails less than 5 characters & usernames less than 3 characters.', 'onecom-sp' ); ?></span>
			</p>



		<label for="spbbcode">
			<span class="oc_sp_switch">
			<input class="oc_sp_check" type="checkbox" id="spbbcode" name="oc_sp_bbcode"
					value="true" 
					<?php
					if ( 'true' == $oc_sp_bbcode ) {
						echo OnecomSp::CHECKED;
					}
					?>
			>
			<span class="oc_sp_slider"></span>
			</span>
			<span><?php _e( 'Mark comments having BBCodes as spam', 'onecom-sp' ); ?></span>
		</label>
			<p class="description">
				<span class="prt-desc"><?php _e( 'BBCodes are codes like [url] that spammers like to place in comments. WordPress does not support BBCodes without a plugin.  If you have a BBCode plugin then uncheck this. This check will mark any comment that has BBCodes as spam.', 'onecom-sp' ); ?></span>
			</p>


		<label for="spexploit">
			<span class="oc_sp_switch">
			<input class="oc_sp_check" type="checkbox" id="spexploit" name="oc_sp_exploit"
					value="true" 
					<?php
					if ( 'true' == $oc_sp_exploit ) {
						echo OnecomSp::CHECKED;
					}
					?>
			>
				<span class="oc_sp_slider"></span>
			</span>
			<span><?php _e( 'Analyse input fields text for exploits', 'onecom-sp' ); ?></span>
		</label>
			<p class="description">
				<span class="prt-desc"><?php _e( 'This checks for the PHP `eval` function and typical SQL injection strings in comments text and login attempts. It also checks for JavaScript that may potentially be used for cross domain exploits.', 'onecom-sp' ); ?></span>
			</p>


		<label for="spquickres">
			<span class="oc_sp_switch">
			<input class="oc_sp_check" type="checkbox" id="spquickres" name="oc_sp_quickres"
					value="true" 
					<?php
					if ( 'true' == $oc_sp_quickres ) {
						echo OnecomSp::CHECKED;
					}
					?>
			>
			<span class="oc_sp_slider"></span>
			</span>
			<span><?php _e( 'Block users with consecutive failed login attempts', 'onecom-sp' ); ?></span></label>
			<p class="description">
				<span class="prt-desc"> <?php _e( 'On finding maximum number of failed login attempts in a fixed time duration from an IP address, the login form is temporarily blocked for that IP for duration as specified below.', 'onecom-sp' ); ?></span></p>


		<label class="sp-sub-points"><?php _e( 'Maximum no of failed login attempts', 'onecom-sp' ); ?>: <input
					type="number" required class="oc_max_login_val" name="oc_max_login_val"
					value="<?php echo $oc_max_login_val; ?>" min="1" max="10"></label>
		<label class="sp-sub-points last-form-item"><?php _e( 'Time for which user will be temporarily blocked(in seconds)', 'onecom-sp' ); ?>
			: <input
					type="number" required name="oc_block_time" class="oc_block_time"
					value="<?php echo $oc_block_time; ?>" min="10" max="900"></label>


		<p>
			<?php echo $onecom_sp->oc_generate_submit_button( 'regular' ); ?>

		</p>

		<div class="oc_sticky_footer">
			<p><?php echo $onecom_sp->oc_generate_submit_button( 'sticky' ); ?></p>
		</div>

		<p><?php echo $onecom_sp->oc_generate_submit_button( 'float' ); ?></p>

	</form>

