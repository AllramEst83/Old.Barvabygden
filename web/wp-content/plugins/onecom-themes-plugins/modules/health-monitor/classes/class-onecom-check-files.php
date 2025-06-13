<?php
declare( strict_types=1 );

class OnecomCheckFiles extends OnecomHealthMonitor {

	private $uploads_folder              = '';
	private $excludedFiles               = '';
	private $desired_file_count          = 100000;
	private $desired_single_folder_count = 25000;

	public function __construct() {
		parent::__construct();
		$this->uploads_folder = wp_upload_dir( null, false );
		$this->excludedFiles  = array(
			'.',
			'..',
			'.DS_Store',
		);
	}

	/**
	 * Check which files are allowed to be executed in uploads folder
	 * @return array
	 */
	function check_execution(): array {
		$this->status_key;
		$this->log_entry( 'Checking if File Execution is enabled in uploads folder' );
		//create a php file in uploads folder and check if it can be executed
		$uploads_dir = $this->uploads_folder;
		$result      = array();
		$time        = time();
		$php_file    = $uploads_dir['basedir'] . DIRECTORY_SEPARATOR . $time . '.php';
		$php_script  = '<?php header("X-One-Executable:true");?>';
		$this->log_entry( 'Creating a dummy php file in uploads' );
		file_put_contents( $php_file, $php_script );
		//check response of calling the file
		$url = $uploads_dir['baseurl'] . '/' . $time . '.php';
		$this->log_entry( 'Retriving headers from dummy file' );
		$headers = $this->get_curl_header( $url );
		$this->log_entry( 'Deleting dummy file' );
		unlink( $php_file );
		if ( array_key_exists( 'x-one-executable', $headers ) ) {
			$guide_link = sprintf( "<a href='https://help.one.com/hc/%s/articles/360002102258-Disable-file-execution-in-the-WordPress-uploads-folder' target='_blank'>", onecom_generic_locale_link( '', get_locale(), 1 ) );
			$result     = $this->format_result( $this->flag_open, __( 'File execution in uploads', 'onecom-wp' ), sprintf( 'File execution is allowed in your uploads folder. This means that an attacker can upload malware and execute it by simply trying to access it from their browser. %sDisable file execution in the WordPress uploads folder%s', $guide_link, '</a>' ) );
		} else {
			$result = $this->format_result( $this->flag_resolved, __( 'File execution is blocked in "Uploads" folder.', 'onecom-wp' ), '' );
		}
		$this->log_entry( 'Finished checking for File Execution' );

		//@todo oc_sh_save_result( 'file_execution', $result[ $this->status_key ] );

		return $result;
	}

	/**
	 * Check the index of uploads directory, if the overall count of files
	 * is more than specified number, warn user
	 * @return array
	 */
	public function check_index(): array {
		$source      = $this->uploads_folder['basedir'];
		$result      = $this->count_files( $source );
		$count       = $result['count'];
		$files_array = $result['counted_files'];

		$get_single_file_scan = get_site_transient( 'onecom_uploads_single_folder_scan' );
		$single_file_limit    = ! empty( $get_single_file_scan ) ? json_decode( $get_single_file_scan, true ) : array();

		$tran_scan_file_set = false;
		$directory_tree     = '';

		//if transient value empty set flag false
		if ( empty( $single_file_limit ) ) {
			$tran_scan_file_set = true;
		}

		//if transient empty then check by traverse file
		if ( $tran_scan_file_set ) {
			$directory_tree    = $this->get_directory_tree( $files_array );
			$single_file_limit = array();
			if ( count( $directory_tree ) > 0 ) {
				$max               = $this->desired_single_folder_count;
				$single_file_limit = array_filter(
					$directory_tree,
					function ( $value ) use ( $max ) {
						return ( $value >= $max );
					}
				);
			}
			//Set individual folder file limit
			set_site_transient( 'onecom_uploads_single_folder_scan', json_encode( $single_file_limit ), 10 * HOUR_IN_SECONDS );
		}

		if ( $count >= $this->desired_file_count ) {
			$directory_tree      = $this->get_directory_tree( $files_array );
			$result              = $this->format_result( $this->flag_open, 'The index of uploads directory is huge', __( sprintf( 'The total file count (%s) of uploads directory exceeds the desired limits (%s). Following are some of the directories you can review.', $count, $this->desired_file_count ), 'onecom-wp' ) );
			$result['file-list'] = array_slice( $directory_tree, 0, 3 );

		} elseif ( ( $count <= $this->desired_file_count ) && ( count( $single_file_limit ) ) > 0 ) {
			if ( $tran_scan_file_set === false ) {
				$directory_tree = $this->get_directory_tree( $files_array );
			}
			$result              = $this->format_result( $this->flag_open, 'The index of uploads directory is huge', __( sprintf( 'The total file count (%s) of uploads directory exceeds the desired limits (%s). Following are some of the directories you can review.', $count, $this->desired_file_count ), 'onecom-wp' ) );
			$result['file-list'] = array_slice( $directory_tree, 0, 3 );
		} else {
			$result = $this->format_result( $this->flag_resolved, 'The index of uploads directory is optimal', __( sprintf( 'The total file count (%s) of uploads directory is within desired limits (%s).', $count, $this->desired_file_count ), 'onecom-wp' ) );
		}

		return $result;
	}

	/**
	 * Count the files and directories in a directory
	 *
	 * @param false $dir
	 *
	 * @return array
	 */
	private function count_files( $source ): array {
		$count       = 0;
		$source      = str_replace( '\\', '/', realpath( $source ) );
		$files_array = array();
		if ( is_dir( $source ) === true ) {
			//use RecursiveDirectoryIterator to loop through nested subfolders
			$files      = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source, RecursiveDirectoryIterator::SKIP_DOTS ), RecursiveIteratorIterator::SELF_FIRST );
			$temp_array = iterator_to_array( $files );
			foreach ( $temp_array as $f ) {
				if ( in_array( $f->getFilename(), $this->excludedFiles ) ) {
					continue;
				}
				$files_array[] = $f;
				$count++;
				//break loop traversing if count reached at desired_file_count
				if ( $count >= $this->desired_file_count ) {
					break;
				}
			}
		} elseif ( is_file( $source ) === true && ( ! in_array( $source, $this->excludedFiles ) ) ) {
			$count++;
			$files_array[] = $source;
		}

		return array(
			'count'         => $count,
			'counted_files' => $files_array,
		);
	}

	/**
	 * Count the top level files and directories
	 *
	 * @param array $files array of filesystem path names.
	 *
	 * @return array
	 */
	private function get_directory_tree( array $files ): array {
		if ( ! $files ) {
			return array();
		}
		$tree = array();

		foreach ( $files as $file ) {
			$path_name = $file->getPathName();
			$file_name = $file->getFilename();
			if ( ( ! is_dir( $path_name ) ) || in_array( $file_name, $this->excludedFiles ) ) {
				continue;
			}
			$tree[ $path_name ] = ( count( scandir( $path_name ) ) - 2 );
		}
		//count the files in uploads basedir
		$tree[ $this->uploads_folder['basedir'] ] = ( count( scandir( $this->uploads_folder['basedir'] ) ) - 2 );
		arsort( $tree, SORT_NUMERIC );

		return $tree;
	}

	/**
	 * Check if .zip files are present in root or uploads directories.
	 * @return array
	 */
	public function check_backup_zips(): array {
		$files_in_root = glob( ABSPATH . '*.zip' );
		$source        = $this->uploads_folder['basedir'];
		// Array of directory names to be excluded
		$excludedDirectories = array( 'thrive-theme' );
		$directory           = new \RecursiveDirectoryIterator(
			$source,
			\FilesystemIterator::FOLLOW_SYMLINKS
		);
		$filter              = new \RecursiveCallbackFilterIterator(
			$directory,
			function ( $current, $key, $iterator ) use ( $excludedDirectories ) {
				$key;
				$path = $current->getPathname();

				// Check if the current item is inside any of the excluded directories
				foreach ( $excludedDirectories as $excludedDir ) {
					if ( stripos( $path, DIRECTORY_SEPARATOR . $excludedDir . DIRECTORY_SEPARATOR ) !== false ) {
						return false; // Exclude ZIP files inside the excluded directories
					}
				}

				return $current->getExtension() === 'zip' || $iterator->hasChildren();
			}
		);
		$iterator            = new \RecursiveIteratorIterator( $filter );
		$nested_files        = iterator_to_array( $iterator );
		$nested_file_paths   = array();
		foreach ( $nested_files as $file ) {
			$nested_file_paths[] = $file->getPathname();
		}
		$backup_files = array_merge( $files_in_root, $nested_file_paths );
		if ( count( $backup_files ) > 0 ) {
			$result         = $this->format_result( $this->flag_open, 'Some archived files are present', 'We found some archived files (.zip) present in your site. You probably created them for backup. Consider cleaning them up.' );
			$result['list'] = $backup_files;
		} else {
			$result = $this->format_result( $this->flag_resolved, 'No archived files present', '' );
		}

		return $result;
	}

	/**
	 * Delete a zip file
	 *
	 * @param string $file , the file to delete
	 *
	 * @return string
	 */
	public function fix_backup_zips( $file ): array {
		$file_path = ABSPATH . $file;
		$result    = $this->format_result( $this->flag_open, __( 'Failed', 'onecom-wp' ) );
		if ( ! $file || ! file_exists( $file_path ) ) {
			return $result;
		}

		if ( unlink( $file_path ) ) {
			$result = $this->format_result( $this->flag_resolved, __( 'Deleted', 'onecom-wp' ) );
		}

		return $result;
	}

	/**
	 * Check file permissions
	 * @return array
	 */
	public function check_permission(): array {
		$this->log_entry( 'Scanning for WP file permissions.' );
		clearstatcache();
		$bad_permission = false;
		$files          = array_diff( scandir( ABSPATH ), array( '.', '..', '.DS_Store', '.tmb' ) );
		foreach ( $files as $file ) {
			$valid_permission = 755;
			if ( is_dir( ABSPATH . DIRECTORY_SEPARATOR . $file ) ) {
				$valid_permission = 755;
			}
			if ( $valid_permission < decoct( fileperms( ABSPATH . DIRECTORY_SEPARATOR . $file ) & 0777 ) ) {
				$bad_permission = true;
			}
		}
		if ( $bad_permission ) {

			$guide_link = sprintf( "<a href='https://help.one.com/hc/%s/articles/360002087097-Change-the-file-permissions-via-an-FTP-client' target='_blank'>", onecom_generic_locale_link( '', get_locale(), 1 ) );
			$status     = $this->flag_open;
			$title      = __( 'WP file directory and files permissions', 'onecom-wp' );
			$desc       = sprintf( __( 'Your file and folder permissions are not set correctly. If they are too strict you get errors on your site, if they are too loose this poses a security risk. %sChange the file permissions via an FTP client%s', 'onecom-wp' ), $guide_link, '</a>' );

		} else {
			$status = $this->flag_resolved;
			$title  = __( 'Correct file permissions.', 'onecom-wp' );
			$desc   = '';

		}

		//      @todo oc_sh_save_result( 'file_permissions', $result[ $oc_hm_status ] );
		$this->log_entry( 'Finished scanning for WP file permissions.' );

		return $this->format_result( $status, $title, $desc );
	}

	/**
	 * Check if file editing is allowed in admin
	 * @return array
	 */
	public function check_file_editing(): array {
		$this->log_entry( 'Checking if file editing enabled from admin' );
		$file_editing_enabled = true;
		if ( defined( 'DISALLOW_FILE_EDIT' ) && ( DISALLOW_FILE_EDIT ) ) {
			$file_editing_enabled = false;
		}

		if ( ! $file_editing_enabled ) {
			$title  = __( 'File editing from WordPress admin is disabled', 'onecom-wp' );
			$desc   = '';
			$status = $this->flag_resolved;
		} else {

			$guide_link = sprintf( "<a href='https://help.one.com/hc/%s/articles/360002104398' target='_blank'>", onecom_generic_locale_link( '', get_locale(), 1 ) );
			$title      = __( 'File editing from WordPress admin is allowed', 'onecom-wp' );
			$desc       = sprintf( __( 'File editing from your WordPress dashboard is allowed, meaning users with a role that has this right can edit all the core files of your site. Someone might accidentally break it, or a hacker might get access to a password. %sDisable file editing in WordPress admin%s', 'onecom-wp' ), $guide_link, '</a>' );
			$status     = $this->flag_open;
		}
		$this->log_entry( 'Finished checking for file editing enabled from admin' );

		//@todo oc_sh_save_result( 'admin_file_edit', $result[ $oc_hm_status ] );

		return $this->format_result( $status, $title, $desc );
	}
}
