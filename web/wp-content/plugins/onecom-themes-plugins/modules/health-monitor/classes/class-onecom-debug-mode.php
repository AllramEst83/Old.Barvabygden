<?php
/**
 * Class OnecomDebugMode
 * De
 */
declare( strict_types=1 );

class OnecomDebugMode extends OnecomHealthMonitor {
	public function check_error_reporting() {
		$this->log_entry( 'Scanning debug mode' );
		$display_errors = isset( $_POST['err'] ) ? intval( $_POST['err'] ) : 0;
		if ( ( $display_errors && ( $display_errors === 1 ) ) || WP_DEBUG ) {

			$guide_link = sprintf( "<a href='https://help.one.com/hc/%s/articles/115005593705-How-do-I-enable-error-messages-for-PHP-' target='_blank'>", onecom_generic_locale_link( '', get_locale(), 1 ) );

			$guide_link2 = sprintf( "<a href='https://help.one.com/hc/%s/articles/115005594045-How-do-I-enable-debugging-in-WordPress-' target='_blank'>", onecom_generic_locale_link( '', get_locale(), 1 ) );

			$result = $this->format_result( $this->flag_open );
		} else {
			$result = $this->format_result( $this->flag_resolved );
		}
		return $result;
	}

	public function check_debug_enabled() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG === true ) {
			$result = $this->format_result( $this->flag_open );
		} else {
			$result = $this->format_result( $this->flag_resolved );
		}

		return $result;
	}



	/**
	 * Check if the debug.log file exists and its size is greater than 100 MB.
	 *
	 * This function checks if the debug.log file exists in the WP_CONTENT_DIR and if its size
	 * is greater than 100 MB.
	 *
	 * @return array True if the debug.log file exists and its size is greater than 100 MB, false otherwise.
	 */
	public function check_debug_log_size() {
		$debugLogPath = WP_CONTENT_DIR . '/debug.log';
		$result       = $this->format_result( $this->flag_resolved );

		if ( file_exists( $debugLogPath ) ) {
			$fileSize = filesize( $debugLogPath ); // in bytes

			// Convert file size to MB
			$fileSizeMB = $fileSize / ( 1024 * 1024 ); // MB

			if ( $fileSizeMB > 100 ) {
				$result = $this->format_result( $this->flag_open );
			}
		}

		return $result;
	}

	/**
	 * Delete the debug log file based on an AJAX request and return a response.
	 * It attempts to delete the debug.log file, and sends a JSON response
	 * indicating whether the deletion was successful or not.
	 **/

	public function fix_debug_log_size() {

		$debugLogPath = WP_CONTENT_DIR . '/debug.log';

		// Check if the debug.log file exists
		if ( file_exists( $debugLogPath ) ) {
			// Delete the debug.log file
			if ( unlink( $debugLogPath ) ) {
				$response = $this->format_result(
					$this->flag_resolved,
					$this->text['debug_log_size'][ $this->fix_confirmation ],
					$this->text['debug_log_size'][ $this->status_desc ][ $this->status_resolved ]
				);
			} else {
				$response = $this->format_result(
					$this->flag_open,
					__( 'Something went wrong with deleting the file. Please try again.', 'onecom-wp' ),
					''
				);
			}
		} else {
			$response = $this->format_result(
				$this->flag_open,
				__( 'Something went wrong with deleting the file. Please try again.', 'onecom-wp' ),
				''
			);
		}
		return $response;
	}
}
