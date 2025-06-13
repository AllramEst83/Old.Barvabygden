<?php
	$spam = oc_get_sp_options( 'onecom_sp_spam_logs' );

	$nonce = '';
if ( array_key_exists( 'one_sp_nonce', $_POST ) ) {
	$nonce = $_POST['one_sp_nonce'];
}


if ( ! empty( $nonce ) && wp_verify_nonce( $nonce, 'one_sp_nonce' ) ) {

	$additional_info = array(
		'additional_info' => json_encode(
			array(
				'logs_cleared_by' => 'manually_cleared',
				'blocked_spams'   => $spam['spam_count'] ?? '',

			)
		),
	);


	( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_performance_cache( 'delete', 'setting', 'logs', ONECOM_SP_PLUGIN_SLUG, $additional_info ) : '' );

	$spam['records']    = array();
	$spam['spam_count'] = 0;

	oc_save_sp_options( $spam, 'onecom_sp_spam_logs' );
	$success_notice = '<div class="notice notice-success is-dismissible"><p>' . __( 'Spam Logs cleared!', 'onecom-sp' ) . '</p></div>';
}
	$oc_nonce = wp_create_nonce( 'one_sp_nonce' );
if ( ! empty( $success_notice ) ) {
	echo "$success_notice";
}?>
	<div class="one-sp-logs">
	<?php
	if ( ! isset( $spam['records'] ) || empty( $spam['records'] ) ) {
		echo '<p>' . __( 'No logs found!', 'onecom-sp' ) . '</p></div></div>';
		return false;
	} else {
		$spam_logs = $spam['records'];
	}
	?>
	<form id="sp-clear-logs" method="post">
		<input type="hidden" name="one_sp_nonce" class="one_sp_nonce" value="<?php echo $oc_nonce; ?>"/>

		<p><span class="log-text"><?php _e( 'Logs of blocked spam attempts', 'onecom-sp' ); ?></span><input type="submit" class="oc-save" name="oc-clear-logs" value="Clear Logs">
			<span id="oc_sp_spinner" class="oc_cb_spinner spinner"></span></p>
	</form>
	<div class="oc_logs">
	<table name="one-sp-log" id="ocSpLog" style="width:100%;" aria-describedby="Spam protection table">
		<thead>
		<tr>
			<th scope="col">
<?php _e( 'Date & Time', 'onecom-sp' ); ?>
			</th>
			<th scope="col" ><?php _e( 'IP', 'onecom-sp' ); ?></th>
			<th scope="col" ><?php _e( 'Email', 'onecom-sp' ); ?></th>
			<th scope="col" ><?php _e( 'Username', 'onecom-sp' ); ?></th>
			<th scope="col" >URL</th>
			<th scope="col" ><?php _e( 'Reason', 'onecom-sp' ); ?>
			</th>
		</tr>
		</thead>
		<?php


		krsort( $spam_logs );
		$ip        = '';
		$email     = '';
		$user_name = '';
		$url       = '';
		$detection = '';
		foreach ( $spam_logs as $key => $log ) {
			$ip        = $log[0];
			$email     = $log[1];
			$user_name = $log[2];
			$url       = $log[3];
			$detection = $log[4];



			echo "<tr>
            <td>$key</td>
            <td>$ip</td>
            <td>$email</td>
            <td>$user_name</td>
            <td>$url</td>
            <td>$detection</td>
        </tr>";
		}
		?>
		<tbody>
		</tbody>
	</table>

		<div class="oc-mobile-logs">
			<?php
			$mobile_view = '';
			foreach ( $spam_logs as $key => $log ) {


				$mobile_view .= '<hr>';
				$mobile_view .= '<p><span class="sp-th-head">' . __( 'Date & Time', 'onecom-sp' ) . '</span><span class="sp-th-value">' . $key . '</span></p>';
				$mobile_view .= '<p><span class="sp-th-head">' . __( 'IP', 'onecom-sp' ) . '</span><span class="sp-th-value">' . $ip . '</span></p>';
				$mobile_view .= '<p><span class="sp-th-head">' . __( 'Email', 'onecom-sp' ) . '</span><span class="sp-th-value">' . $email . '</span></p>';
				$mobile_view .= '<p><span class="sp-th-head">' . __( 'Username', 'onecom-sp' ) . '</span><span class="sp-th-value">' . $user_name . '</span></p>';
				$mobile_view .= '<p><span class="sp-th-head">URL</span><span class="sp-th-value">' . $url . '</span></p>';
				$mobile_view .= '<p><span class="sp-th-head">' . __( 'Reason', 'onecom-sp' ) . '</span><span class="sp-th-value">' . $detection . '</span></p>';


			}

			echo $mobile_view;
			?>

		</div>
	</div>
	</div>