<?php
/**
 * Deal with database operation
 */
declare( strict_types=1 );

class OnecomCheckDB extends OnecomHealthMonitor {

	private $upper_limit = 20000;

	public function check_woocommerce_session(): array {
		global $wpdb;
		$timestamp     = time();
		$table         = $wpdb->prefix . 'woocommerce_sessions';
		$session_count = $wpdb->get_var( "SELECT COUNT(session_id) FROM $table WHERE session_expiry < $timestamp" );

		if ( intval( $session_count ) === 0 ) {
			$result = $this->format_result( $this->flag_resolved, 'No outdated woocommerce session data', 'woocommerce session data is cleaned up regularly' );
		} else {
			$result = $this->format_result( $this->flag_open, 'Expired woocommerce session data present', 'You have some expired session data present in your database.' );
		}

		return $result;
	}

	/**
	 * Check the count of rows in options table
	 * @return array
	 */
	public function check_options_table(): array {
		global $wpdb;
		$row_count = $wpdb->get_var( "SELECT COUNT(option_id) FROM $wpdb->options" );
		if ( $row_count <= $this->upper_limit ) {
			$result = $this->format_result( $this->flag_resolved, 'Options table is optimal.', 'The number of rows in options table is within desired limits.' );

		} else {
			$result = $this->format_result( $this->flag_open );
		}
		return $result;
	}

	public function fix_woocommerce_sessions(): array {
		global $wpdb;
		$timestamp = time();
		$table     = $wpdb->prefix . 'woocommerce_sessions';
		$result    = $wpdb->query( "DELETE FROM $table WHERE session_expiry < $timestamp" );
		if ( $result ) {
			$result = $this->format_result(
				$this->flag_resolved,
				$this->text['woocommerce_sessions'][ $this->fix_confirmation ],
				$this->text['woocommerce_sessions'][ $this->status_desc ][ $this->status_resolved ]
			);
		} else {
			$result = $this->format_result( $this->flag_open );
		}

		return $result;
	}

	public function check_db_security() {
		global $wpdb;
		$this->log_entry( 'Scanning DB security' );
		$has_default_prefix = false;
		if ( $wpdb->prefix === 'wp_' ) {
			$has_default_prefix = true;
		}

		$guide_link = sprintf( "<a href='https://help.one.com/hc/%s/articles/360002107438-Change-the-table-prefix-for-WordPress-' target='_blank'>", onecom_generic_locale_link( '', get_locale(), 1 ) );

		if ( $has_default_prefix ) {
			$status = $this->flag_open;
			$title  = __( 'Database security', 'onecom-wp' );
			$desc   = sprintf( __( 'You are using default table prefix. This means that attackers can easily guess your database configuration. %sChange the table prefix for WordPress%s', 'onecom-wp' ), $guide_link, '</a>' );

		} else {
			$status = $this->flag_resolved;
			$title  = __( 'You are not using default table prefix', 'onecom-wp' );
			$desc   = '';
		}
		$this->log_entry( 'Finished scanning DB security' );

		//@todo oc_sh_save_result( 'db_security', $result[ $oc_hm_status ] );

		return $this->format_result( $status, $title, $desc );
	}
}
