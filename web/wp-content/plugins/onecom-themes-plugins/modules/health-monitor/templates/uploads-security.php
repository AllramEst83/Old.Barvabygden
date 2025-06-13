<?php
$ht                 = new OnecomFileSecurity();
$default_file_types = $ht->get_default_file_types();
$ht_content         = $ht->get_htaccess();
$file_extensions    = $ht->get_htaccess_extensions();
$is_js_blocked      = $ht->check_js_block();
if ( ! $file_extensions ) {
	return;
}

?>
<div class="oc-file-security-wrap">
	<div class="one-card one-card-cdn">
		<div class="one-card-inline-block one-card-align-left onecom-staging-logo">
			<?php
			/*<img id="oc-cdn-icon" src="<?php echo $cdn_icon ?>" alt="one.com"
			srcset="<?php echo $cdn_icon_2x; ?>"
			style="display: <?php echo $cdn_enabled != 'true' ? 'inline' : 'none' ?>"/>
			<img id="oc-cdn-icon-active" src="<?php echo $cdn_icon_active ?>" alt="one.com"
			srcset="<?php echo $cdn_icon_2x_active; ?>"
			style="display: <?php echo $cdn_enabled == 'true' ? 'inline' : 'none' ?>"/>
			*/
			?>

		</div>
		<div class="one-card-inline-block one-card-align-left one-card-staging-content">
			<div id="staging-create" class="one-card-staging-create card-1">
				<div class="one-card-staging-create-info">
					<h3 class="no-top-margin">
						<?php _e( 'Modify the file security settings in your uploads directory', 'onecom-wp' ); ?>
					</h3>
					<p class="ocsh-page-desc"><?php _e( 'Select the files that you want to block in your uploads directory. More the number of files selected, higher is the security', 'onecom-wp' ); ?></p>
					<br/>
					<label for="oc-manual-edit" class="ocsh-scan-title oc-label">
					<span class="oc_cb_switch">
						<input type="checkbox" class="" id="oc-manual-edit" name="oc-manual-edit"/>
						<span class="oc_cb_slider" data-target="oc-cdn-icon"></span>
					</span>
						<?php echo __( 'Edit the rules manually', 'onecom-wp' ); ?>
					</label>
					<p class="oc_ht_warning"><?php echo __( 'Please use manual edit mode only if you know what you are doing.', 'onecom-wp' ); ?></p>
					<div class="oc-file-edit-wrap">
						<div id="oc-ht-checkbox-wrap">
							<div class="oc-checkbox-group">
								<?php
								$break_count = ceil( count( $default_file_types ) / 6 );
								foreach ( $default_file_types as $key => $extension ) :
									$checked = '';
									if ( in_array( $extension, $file_extensions ) ) {
										$checked = 'checked';
									}
									if ( $extension === 'js' && $is_js_blocked ) {
										$checked = 'checked';
									}
									?>
									<label class="oc-block-d <?php echo $checked; ?>">
										<input type="checkbox" name="oc_file_extensions[]"
												value="<?php echo $extension; ?>" <?php echo $checked; ?>
												class="oc_file_extensions"/><?php echo $extension; ?>
									</label>
									<?php
									if ( ( ++$key ) % $break_count === 0 ) {
										echo '</div><div class="oc-checkbox-group">';
									}
								endforeach;
								echo '<br/>';
								?>
							</div>
							<div class="clearfix"></div>
						</div>

						<div id="oc-ht-wrap">
							<label class="ocsh-scan-title"><?php echo __( 'Current .htaccess file', 'onecom-wp' ); ?></label>
							<br/>
							<input type="hidden" value="<?php echo base64_encode( $ht_content ); ?>"
									id="oc-original-content">
							<textarea class="ht_textarea" id="oc_ht_textarea"
										rows="4"><?php echo $ht_content; ?></textarea>
						</div>
					</div>
					<button type="button" class="button button-primary"
							id="oc_ht_text_button"><?php echo __( 'Save', 'onecom-wp' ); ?></button>
					<span id="oc_ht_spinner" class="components-spinner"></span>
					<a href="javascript:void(0)" id="oc-ht-reset"><?php echo __( 'Reset', 'onecom-wp' ); ?></a>
					<p id="oc_ht_message"></p>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="oc-clearfix"></div>