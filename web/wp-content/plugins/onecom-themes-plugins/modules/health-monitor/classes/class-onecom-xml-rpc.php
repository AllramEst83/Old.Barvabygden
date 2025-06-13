<?php

/**
 * Class OnecomXmlRpc
 * Deals with all the XML RPC related features
 */
class OnecomXmlRpc extends OnecomHealthMonitor {
	private $blocking_code = <<<htcode
#one.com block xmlrpc
<Files xmlrpc.php>
order deny,allow
deny from all
</Files>
#one.com block xmlrpc END
htcode;
	private $htaccess      = ABSPATH . '/.htaccess';

	public function check_xmlrpc(): array {
		$url      = get_home_url( null, 'xmlrpc.php' );
		$response = wp_remote_post( $url );
		$status   = wp_remote_retrieve_response_code( $response );
		$jetpack  = '';

		if ( $this->is_jetpack_active() ) {
			$jetpack = '<p>' . __( 'You can ignore this warning if you are using Jetpack', 'onecom-wp' ) . '</p>';
		}
		if ( $status === 200 ) {
			return $this->format_result( $this->flag_open );
		} else {
			return $this->format_result( $this->flag_resolved );
		}
	}

	public function fix_check_xmlrpc(): array {
		//if blocking code is already present in .htaccess file, return with success status
		$contents = file_get_contents( $this->htaccess );
		if ( strpos( $contents, $this->blocking_code ) !== false ) {
			return $this->format_result(
				$this->flag_resolved,
				$this->text['xmlrpc'][ $this->fix_confirmation ],
				$this->text['xmlrpc'][ $this->status_desc ][ $this->status_resolved ]
			);
		}

		if ( file_put_contents( $this->htaccess, "\n" . $this->blocking_code, FILE_APPEND ) ) {
			return $this->format_result(
				$this->flag_resolved,
				$this->text['xmlrpc'][ $this->fix_confirmation ],
				$this->text['xmlrpc'][ $this->status_desc ][ $this->status_resolved ]
			);
		} else {
			return $this->format_result( $this->flag_open, __( 'Failed to disable XML RPC', 'onecom-wp' ) );
		}
	}

	public function undo_check_xmlrpc(): array {
		$content     = file_get_contents( $this->htaccess );
		$new_content = str_replace( array( $this->blocking_code, "\n" . $this->blocking_code ), '', $content );
		if ( file_put_contents( $this->htaccess, $new_content ) ) {
			$check = 'xmlrpc';

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

	public function is_jetpack_active(): bool {
		$active_plugins = get_option( 'active_plugins' );

		return in_array( 'jetpack/jetpack.php', $active_plugins );
	}
}
