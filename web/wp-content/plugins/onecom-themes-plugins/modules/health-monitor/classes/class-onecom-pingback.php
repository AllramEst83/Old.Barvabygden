<?php
declare( strict_types=1 );

/**
 * Deals with Pingbacks
 */
class OnecomPingback extends OnecomHealthMonitor {
	/**
	 * Check if pingbacks are enabled. This function checks for following options
	 * default_ping_status=open and default_pingback_flag=1. if this condition is met, pingbacks
	 * are considered enabled.
	 * @return array
	 */
	public function check_pingbacks(): array {
		$default_ping_status   = get_option( 'default_ping_status' );
		$default_pingback_flag = get_option( 'default_pingback_flag' );
		if ( $default_ping_status === 'open' || intval( $default_pingback_flag ) === 1 ) {
			$title = __( 'Pingback is enabled.', 'onecom-wp' );
			$desc  = __( 'You have pingbacks enabled on your site.', 'onecom-wp' );

			return $this->format_result( $this->flag_open, $title, $desc );
		} else {
			$title = __( 'Pingbacks are disabled.', 'onecom-wp' );

			return $this->format_result( $this->flag_resolved, $title );
		}
	}

	/**
	 * Disabled pingbacks
	 * @return array
	 */
	public function fix_pingback(): array {
		if ( update_option( 'default_ping_status', '', 'no' ) && update_option( 'default_pingback_flag', '', 'no' ) ) {
			return $this->format_result(
				$this->flag_resolved,
				$this->text['pingbacks'][ $this->fix_confirmation ],
				$this->text['pingbacks'][ $this->status_desc ][ $this->status_resolved ]
			);
		}

		return $this->format_result( $this->flag_open );
	}

	public function undo(): array {
		if ( update_option( 'default_ping_status', 'open', 'no' ) && update_option( 'default_pingback_flag', '1', 'no' ) ) {
			$check = 'pingbacks';

			return array(
				$this->status_key      => $this->flag_resolved,
				$this->fix_button_text => $this->text[ $check ][ $this->fix_button_text ],
				$this->desc_key        => $this->text[ $check ][ $this->status_desc ][ $this->status_open ],
				$this->how_to_fix      => $this->text[ $check ][ $this->how_to_fix ],
				'ignore_text'          => $this->ignore_text,
			);
		} else {
			return $this->format_result( $this->status_open );
		}
	}
}
