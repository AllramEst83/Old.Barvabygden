<?php
$nonce     = '';
$onecom_sp = new OnecomSp();


$sp_options          = oc_get_sp_options( 'onecom_sp_protect_options' );
$spoption_checks     = $sp_options['checks'] ?? '';
$oc_spbadusragent    = $spoption_checks['oc_spbadusragent'] ?? '';
$oc_whitelistip      = $spoption_checks['oc_spwhitelistip'] ?? '';
$oc_sp_urlshort      = $spoption_checks['oc_sp_urlshort'] ?? '';
$oc_sp_proburl       = $spoption_checks['oc_sp_proburl'] ?? '';
$oc_sp_whitelistuser = $spoption_checks['oc_sp_whitelistuser'] ?? '';
$url_shorteners      = $sp_options['url-shortners'] ?? array();
$exploit_urls        = $sp_options['exploit-urls'] ?? array();
$whitelist_agents    = $sp_options['whitelist_agents'] ?? array();
$whitelist_ips       = $sp_options['whitelist_ips'] ?? array();
$whitelist_usernames = $sp_options['whitelist_usernames'] ?? array();

$oc_nonce = wp_create_nonce( 'one_sp_nonce' );

?>
<div class="notice notice-success advanced-settings oc_hidden">
	<p><?php _e( 'Advanced settings updated!', 'onecom-sp' ); ?></p></div>
<form id="sp-advanced-settings" class="sp-blocked-lists" name="sp-advanced-settings" method="post">

	<input type="hidden" name="one_sp_nonce" value="<?php echo $oc_nonce; ?>"/>


	<label for="spwhitelistusername">

			<span class="oc_sp_switch">
			<input class="oc_sp_check" type="checkbox" id="spwhitelistusername" name="oc_sp_whitelistuser"
					value="true" 
					<?php
					if ( $oc_sp_whitelistuser == 'true' ) {
						echo OnecomSp::CHECKED;
					}
					?>
			>
				<span class="oc_sp_slider"></span>
			</span>

		<span><?php _e( 'Whitelist user names', 'onecom-sp' ); ?></span></label>
	<p class="description">
			<span class="prt-desc">

				<?php
				echo '<strong>' . sprintf( __( 'The registered usernames in the website are whitelisted by default. %sView Users%s', 'onecom-sp' ), '<a target="_blank" href="' . admin_url( 'users.php' ) . '">', '</a>' ) . '</strong><br/>';

				_e( 'Here you can add the usernames which you want to whitelist. These usernames will be skipped from the spam protection checks.', 'onecom-sp' )
				?>

			</span></p>
	<p class="description"><textarea name="oc_whitelist_usernames" class="oc_whitelist_usernames" cols="62" rows="8"><?php
	foreach ( $whitelist_usernames as $username ) {
		echo $username . "\r\n";
	}
	?></textarea></br><span><em><?php _e( OnecomSp::ONE_ENTRY_PER_LINE, 'onecom-sp' ); ?></em></span>
	</p></br>

	<label for="spbadusragent">
			<span class="oc_sp_switch">
			<input class="oc_sp_check" type="checkbox" id="spbadusragent" name="oc_spbadusragent"
					value="true" 
					<?php
					if ( 'true' == $oc_spbadusragent ) {
						echo 'checked="checked"';
					}
					?>
			>
				<span class="oc_sp_slider"></span>
			</span>
		<span><?php _e( 'Whitelist user agents', 'onecom-sp' ); ?></span></label>
	<p class="description">
		<span class="prt-desc"> <?php _e( 'Browsers always include a user agent string when they access a site. If you want to whitelist any user agents then you can add them here.', 'onecom-sp' ); ?></span>
	</p>

	<p class="description"><textarea name="oc_whitelist_useragent" class="oc-whitelist-useragent" cols="62" rows="8"><?php
	foreach ( $whitelist_agents as $agent ) {
		echo $agent . "\r\n";
	}
	?></textarea></br><span><em><?php _e( OnecomSp::ONE_ENTRY_PER_LINE, 'onecom-sp' ); ?></em></span>
	</p></br>


	<label for="spwhitelistIP">
			<span class="oc_sp_switch">
			<input class="oc_sp_check" type="checkbox" id="spwhitelistIP" name="oc_spwhitelistip"
					value="true" 
					<?php
					if ( 'true' == $oc_whitelistip ) {
						echo OnecomSp::CHECKED;
					}
					?>
			>
				<span class="oc_sp_slider"></span>
			</span>
		<span><?php _e( 'Whitelist IP address', 'onecom-sp' ); ?></span></label>
	<p class="description">
		<span class="prt-desc"> <?php _e( 'Here you can add specific IP addresses that you want to whitelist.', 'onecom-sp' ); ?></span>
	</p>

	<p class="description"><textarea name="oc_whitelist_ips" class="oc-whitelist-ip" cols="62" rows="8"><?php
	foreach ( $whitelist_ips as $ip ) {
		echo $ip . "\r\n";
	}
	?></textarea></br><span><em><?php _e( OnecomSp::ONE_ENTRY_PER_LINE, 'onecom-sp' ); ?></em></span>
	</p></br>


	<label for="spurlshort">
			<span class="oc_sp_switch">
			<input class="oc_sp_check" type="checkbox" id="spurlshort" name="oc_sp_urlshort"
					value="true" 
					<?php
					if ( 'true' == $oc_sp_urlshort ) {
						echo OnecomSp::CHECKED;
					}
					?>
			>
			<span class="oc_sp_slider"></span>
			</span>
		<span><?php _e( 'Block URL shortening services', 'onecom-sp' ); ?></span></label>
	<p class="description">
		<span class="prt-desc"><?php _e( 'This checks for URL shorteners listed below, in emails, author fields and comment body. Any form submission request having URL shortener will be blocked. You can add/delete shorteners from the list.', 'onecom-sp' ); ?></span>
	</p>

	<p class="description"><textarea name="oc_url_shorters" class="oc-url-shorters" cols="62" rows="8"><?php
	foreach ( $url_shorteners as $shortener ) {
		echo $shortener . "\r\n";
	}
	?></textarea></br><span><em><?php _e( OnecomSp::ONE_ENTRY_PER_LINE, 'onecom-sp' ); ?></em></span>
	</p></br>

	<label for="spprobchk">
			<span class="oc_sp_switch">
			<input class="oc_sp_check" type="checkbox" id="spprobchk" name="oc_sp_proburl"
					value="true" 
					<?php
					if ( 'true' == $oc_sp_proburl ) {
						echo OnecomSp::CHECKED;
					}
					?>
			>
				<span class="oc_sp_slider"></span>
			</span>
		<span><?php _e( 'Block 404 exploit probing', 'onecom-sp' ); ?></span></label>
	<p class="description">
		<span class="prt-desc">  <?php _e( 'Bots(automated programs) randomly search websites for exploitable files. If a URL is not found on the website(returns 404) and it is a match to a exploit URL from the list then the request will be blocked.', 'onecom-sp' ); ?></span>
	</p>

	<p class="description"><textarea name="oc_exploit_urls" class="oc-exploit-urls" cols="62" rows="8"><?php
	foreach ( $exploit_urls as $exploits ) {
		print $exploits . "\n";
	}

	?></textarea></br><span><em><?php _e( OnecomSp::ONE_ENTRY_PER_LINE, 'onecom-sp' ); ?></em></span></p></br>
	<p>
		<?php echo $onecom_sp->oc_generate_submit_button( 'regular' ); ?>
	</p>

	<div class="oc_sticky_footer">
		<p><?php echo $onecom_sp->oc_generate_submit_button( 'sticky' ); ?></p>
	</div>

	<p><?php echo $onecom_sp->oc_generate_submit_button( 'float' ); ?></p>
</form>
