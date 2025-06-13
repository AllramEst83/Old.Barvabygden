<?php
/**
 * Class OnecomAdminNotices
 * Manage all the admin notices in this module
 */
declare( strict_types=1 );

class OnecomAdminNotices extends OnecomHealthMonitor {

	public function init() {
		add_action( 'admin_notices', array( $this, 'critical_check_notice' ) );
	}


	public function critical_check_notice(): void {
		$screen = get_current_screen();
		if ( $screen->base === '_page_onecom-wp-health-monitor' ) {
			return;
		}
		$options = get_option( $this->option_key );
		if ( empty( $options )
			|| ( ! array_key_exists( $this->saved_critical_todo, $options ) )
			|| empty( $options[ $this->saved_critical_todo ] )
		) {
			return;
		}
		$html           = '';
		$ignored_checks = $this->ignored;
		if ( empty( $ignored_checks ) ) {
			$ignored_checks = array();
		}
		foreach ( $options[ $this->saved_critical_todo ] as $check => $content ) {
			if ( ! in_array( $check, $ignored_checks ) ) {
				$html .= $content;
			}
		}
		if ( $html !== '' ) :
			?>
			<div class="notice notice-error onecom_hm__notice">
				<ul class="critical">
					<?php echo $html; ?>
				</ul>
			</div>
			<?php
		endif;
	}
}