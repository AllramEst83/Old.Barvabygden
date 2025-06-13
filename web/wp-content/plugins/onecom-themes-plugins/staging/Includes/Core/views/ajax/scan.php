<label id="onestaging-clone-label" for="onestaging-new-clone" style="display: none">
	<?php echo __( 'Staging Site Name:', 'onestaging' ); ?>
	<input type="text" id="onestaging-new-clone-id" value="<?php echo $options->current; ?>"
																		<?php
																		if ( null !== $options->current ) {
																			echo " disabled='disabled'"; }
																		?>
	>
</label>

<span class="onestaging-error-msg" id="onestaging-clone-id-error" style="display:none;">
		<?php
		echo __(
			'<br>Probably not enough free disk space to create a staging site. ' .
			'<br> You can continue but its likely that the copying process will fail.',
			'onestaging'
		)
		?>
</span>