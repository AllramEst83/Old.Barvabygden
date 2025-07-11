<div id="onestaging-clonepage-wrapper">

	<!-- Page Header -->
	<?php require_once $this->path . 'views/includes/header.php'; ?>

	<h2 class="onestaging-nav-tab-wrapper"></h2>

	<?php
		do_action( 'onestaging_notifications' );

		/* This view is for staging site */
	if ( (bool) $is_staging === true ) {
		require_once $this->path . 'views/staging.php';

		// log api call only once per user
		if ( (bool) get_site_option( 'staging_admin_staging' ) !== true ) {
			( class_exists( 'OCPushStats' ) ? \OCPushStats::push_pageview_stats_request( 'staging_wp_admin', 'staging' ) : '' );
			add_site_option( 'staging_admin_staging', true );
		}
	}

		/* This view is for live site */
	elseif ( (bool) $is_staging === false ) {
		$clones      = get_option( 'onecom_staging_existing_staging' );
		$cloneExists = self::checkCloneExists( $clones );
		require_once $this->path . 'views/live.php';

		// log api call only once per user
		if ( (bool) get_site_option( 'staging_admin_live' ) !== true ) {
			( class_exists( 'OCPushStats' ) ? \OCPushStats::push_pageview_stats_request( 'production_wp_admin', 'staging' ) : '' );
			add_site_option( 'staging_admin_live', true );
		}

		// log api call only once per user
		if ( ! empty( $clones ) && ! $cloneExists ) {
			if ( (bool) get_site_option( 'staging_broken' ) !== true ) {
				( class_exists( 'OCPushStats' ) ? \OCPushStats::generic_log( 'staging_broken', 'Broken staging detected.', null ) : '' );
				add_site_option( 'staging_broken', true );
			}
		}
	}
	?>

	<!-- Page Footer -->
	<?php require_once $this->path . 'views/includes/footer.php'; ?>

</div>