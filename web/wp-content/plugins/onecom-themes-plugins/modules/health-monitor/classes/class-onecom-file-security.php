<?php
/**
 * Class OnecomFileSecurity
 * Manage file execution settings.
 */
declare( strict_types=1 );

class OnecomFileSecurity extends OnecomHealthMonitor {
	private $ht_file;
	private $ht_start   = '<FilesMatch "\.(';
	private $ht_content = <<<HTA
# one.com block executables
<FilesMatch "\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|html|htm|shtml|sh|cgi|suspected)$">
    deny from all
</FilesMatch>
# one.com block executables end
HTA;

	public function __construct() {
		parent::__construct();
		$uploads       = wp_upload_dir();
		$this->ht_file = $uploads['basedir'] . DIRECTORY_SEPARATOR . '.htaccess';
	}

	public function get_default_file_types() {

		$default_file_types = array(
			'js',
			'php',
			'phtml',
			'php3',
			'php4',
			'php5',
			'pl',
			'py',
			'jsp',
			'asp',
			'html',
			'htm',
			'shtml',
			'sh',
			'cgi',
			'suspected',
		);
		$file_extensions    = $this->get_htaccess_extensions();
		$extensions_merged  = array_unique( array_merge( $default_file_types, $file_extensions ) );
		sort( $extensions_merged );

		return $extensions_merged;
	}

	public function get_htaccess() {
		if ( ! file_exists( $this->ht_file ) ) {
			//return false;
			touch( $this->ht_file );//create htaccess file if not exist
		}

		return trim( file_get_contents( $this->ht_file ) );
	}

	private function get_file_pattern() {
		$ht_content = $this->get_htaccess();
		if ( ! $ht_content ) {
			return false;
		}
		$exploded_file_content = explode( "\n", $ht_content );
		if ( ! $exploded_file_content ) {
			return false;
		}
		$files_string = '';
		foreach ( $exploded_file_content as $line ) {
			if ( strpos( $line, $this->ht_start ) === 0 ) {
				$files_string = str_replace( array( $this->ht_start, ')$">' ), '', $line );
			}
		}

		return $files_string;
	}

	public function get_htaccess_extensions() {

		$files_string = $this->get_file_pattern();
		if ( ! $files_string ) {
			return array();
		}

		return explode( '|', $files_string );
	}

	public function oc_save_ht_cb() {
		if ( ! is_writeable( $this->ht_file ) ) {
			wp_send_json(
				array(
					$this->status_key => $this->flag_open,
				)
			);

			return;
		}
		if ( file_put_contents( $this->ht_file, $this->ht_content ) ) {
			wp_send_json(
				$this->format_result(
					$this->flag_resolved,
					$this->text['file_execution'][ $this->fix_confirmation ],
					$this->text['file_execution'][ $this->status_desc ][ $this->status_resolved ]
				)
			);
		} else {
			wp_send_json( $this->format_result( $this->flag_open ) );
		}
	}

	public function check_js_block() {
		$file_content_arr = explode( "\n", $this->get_htaccess() );
		$js_string        = 'RewriteRule ^(.*\.js)$ - [F,L]';

		return in_array( $js_string, $file_content_arr );
	}
}
