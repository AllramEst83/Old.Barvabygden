<div class="wrap onecom-staging-wrap" id="onecom-ui">
	<div class="onecom-notifier"></div>

	<h1 class="one-title" style="align-items: center;line-height: 1;display: inline-flex;"> <?php _e( 'Staging', 'onecom-wp' ); ?></h1>
	<div class="page-subtitle stg-subtitle">
		<?php
		if ( isset( $is_staging ) && (bool) $is_staging === true ) {
					_e( 'This is your Staging site.', 'onecom-wp' );
			?>
			<?php
		} else {
			_e( 'Create a staging environment of your site to try out new plugins, themes, and customizations.', 'onecom-wp' );
		}
		?>
	</div>
