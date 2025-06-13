<?php
	$sp_options = oc_get_sp_options( 'onecom_sp_protect_options' );

	$initiate_checks = new OnecomSp();
	$table           = '';


	$nonce = '';
if ( array_key_exists( 'one_sp_nonce', $_POST ) ) {
	$nonce = $_POST['one_sp_nonce'];
}

if ( isset( $_POST['oc-reset-settings'] ) ) {


	$sp_options = oc_get_sp_options( 'onecom_sp_protect_options' );
	if ( ! empty( $sp_options ) ) {
		$default_options                   = oc_set_default_options();
		$default_options['oc_sp_quickres'] = 'true';

		foreach ( $default_options as $key => $value ) {
			$sp_options['checks'][ $key ] = $value;

		}

		oc_save_sp_options( $sp_options, 'onecom_sp_protect_options' );
	}


	$success_notice = '<div class="notice notice-success is-dismissible"><p>' . __( 'Settings restored.', 'onecom-sp' ) . '</p></div>';

}


	$oc_nonce = wp_create_nonce( 'one_sp_nonce' );

if ( ! empty( $success_notice ) ) {
	echo $success_notice;
}

?>
<p class="diagnostics-desc">
	<?php echo __( 'You can use this form to test if a value will be categorised as spam.</br>The input values of diagnostics form passes through different spam checks and</br> based on the result of these checks the final diagnosis report appears.', 'onecom-sp' ); ?>
</p>
<div class="sp-diagnostics-wrap">


		<div class="ocdg-form-section">
	<form id="sp-diagnostics" class="sp-diagnostics" name="sp-diagnostics" method="post">
		<input type="hidden" name="one_sp_nonce" value="<?php echo $oc_nonce; ?>"/>

		<div class="fieldset">

		<label for="ocvalidateip">
			<?php _e( 'IP', 'onecom-sp' ); ?>:
			<input type="text" name="oc_validate_ip" id="ocvalidateip">

		</label>
		</div>

		<div class="fieldset">

		<label for="ocvalidateuser">
			<?php _e( 'Username', 'onecom-sp' ); ?>:
			<input type="text" name="oc_validate_user" id="ocvalidateuser">

		</label>
		</div>

		<div class="fieldset">
		<label for="ocvalidateemail">
			<?php _e( 'Email', 'onecom-sp' ); ?>:

			<input type="email" name="oc_validate_email" id="ocvalidateemail">

		</label>
		</div>

		<div class="fieldset">
		<label for="ocvalidateuseragent">
			<?php _e( 'User agent', 'onecom-sp' ); ?>:

			<input type="text" name="oc_validate_user_agent" id="ocvalidateuseragent">

		</label>
		</div>

		<div class="fieldset">
		<label for="ocvalidatecontent">
			<?php _e( 'Comment content', OC_SP_TEXTDOMAIN ); ?>:

			<textarea name="oc_validate_content" rows="5" cols="40" id="ocvalidatecontent"></textarea>

		</label>
		</div>
<p>
		<input type="submit" name="check_spam" class="oc-save" value="<?php _e( 'Check Spam', OC_SP_TEXTDOMAIN ); ?>">
		<span id="oc_sp_spinner" class="oc_cb_spinner spinner"></span>
</p>
	</form></div>
<div class="ocdg-results">


	<?php

	if ( $table && $table !== '' ) {

		echo $table;

	}

	?>

		</div>
</div>

