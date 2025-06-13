</div> <!-- wrap -->

<div id="onecom-staging-error-wrapper">
	<div id="onecom-staging-error-details"></div>
</div>

<?php add_thickbox(); ?>

<!-- LIVE TO STAGING MODALS -->
<div id="staging-update-confirmation" style="display:none">
	<div class="one-dialog-container">
		<span class="dashicons dashicons-no-alt one-dialog-close" title="close"></span>
		<div class="one-dialog-container-content">
			<h3><?php _e( 'Are you sure?', 'onecom-wp' ); ?></h3>
			<p>
				<?php _e( 'This will overwrite your staging website with a copy of the files and database from your live website. All changes made in your staging website will be lost.', 'onecom-wp' ); ?>
			</p>
			<p class="extra-padding">
				<button class="one-button btn button_1 one-button-update-staging-confirm confirm-done"><?php _e( 'OK', 'onecom-wp' ); ?></button>
				<button class="one-button btn button_3 one-button-update-staging-cancel cancel-done"><?php _e( 'Cancel', 'onecom-wp' ); ?></button>
			</p>
		</div>
	</div>
</div>
<div id="staging-deployment-confirmation" style="display:none">
	<div class="one-dialog-container">
		<span class="dashicons dashicons-no-alt one-dialog-close" title="close"></span>
		<div class="one-dialog-container-content">
			<h3><?php _e( 'Are you sure?', 'onecom-wp' ); ?></h3>
			<p>
				<?php _e( 'This takes a snapshot of your blog and copies it to a "staging area" where you can test changes without affecting your live site. There\'s only one staging area, so every time you click this button the old staging area is lost forever, replaced with a snapshot of your live blog.', 'onecom-wp' ); ?>
			</p>
			<p class="extra-padding">
				<button class="one-button btn button_1 one-button-copy-to-live-confirm confirm-done"><?php _e( 'OK', 'onecom-wp' ); ?></button>
				<button class="one-button btn button_3 one-button-copy-to-live-cancel cancel-done"><?php _e( 'Cancel', 'onecom-wp' ); ?></button>
			</p>
		</div>
	</div>
</div>
<div id="staging-delete" style="display:none">
	<div class="one-dialog-container">
		<span class="dashicons dashicons-no-alt one-dialog-close" title="close"></span>
		<div class="one-dialog-container-content">
			<h3><?php _e( 'Are you sure?', 'onecom-wp' ); ?></h3>
			<p>
				<?php _e( 'The staging site will be lost.', 'onecom-wp' ); ?>
			</p>

			<p class="extra-padding">
				<button class="one-button btn button_1 one-button-delete-staging-confirm confirm-done"><?php _e( 'OK', 'onecom-wp' ); ?></button>
				<button class="one-button btn button_3 one-button-delete-staging-cancel cancel-done"><?php _e( 'Cancel', 'onecom-wp' ); ?></button>
			</p>
		</div>
	</div>
</div>

<!-- STAGING TO LIVE MODAL -->
<div id="staging-copy-confirmation" style="display:none">
	<div class="one-dialog-container">
		<span class="dashicons dashicons-no-alt one-dialog-close" title="close"></span>
		<div class="one-dialog-container-content">
			<h3><?php _e( 'Are you sure?', 'onecom-wp' ); ?></h3>
			<p>
				<?php _e( 'This will overwrite your live website with a copy of the files and database from your staging website.', 'onecom-wp' ); ?>
			</p>
			<p class="extra-padding">
				<button id="one-button-copy-to-live-confirm" class="one-button btn button_1 one-button-copy-to-live-confirm confirm-done"><?php _e( 'OK', 'onecom-wp' ); ?></button>
				<button class="one-button btn button_3 one-button-copy-to-live-cancel cancel-done"><?php _e( 'Cancel', 'onecom-wp' ); ?></button>
			</p>
		</div>
	</div>
</div>

<!-- loader -->
<div class="loading-overlay fullscreen-loader">
	<div class="loader"></div>
</div>

<!-- Scroll to top -->
<span class="dashicons dashicons-arrow-up-alt onecom-move-up"></span>