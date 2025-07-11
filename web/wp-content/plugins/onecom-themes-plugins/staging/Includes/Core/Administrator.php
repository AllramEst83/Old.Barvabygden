<?php
namespace OneStaging\Core;

defined( 'WPINC' ) or die(); // No Direct Access

use OneStaging\Core\Jobs\Cancel;
use OneStaging\Core\Jobs\Cloning;
use OneStaging\Core\Jobs\Updating;
use OneStaging\Core\Jobs\Data;
use OneStaging\Core\Jobs\Database;
use OneStaging\Core\Jobs\Delete;
use OneStaging\Core\Jobs\Files;
use OneStaging\Core\Jobs\Scan;
use OneStaging\Core\Jobs\Logs;
use OneStaging\Core\InjectionAware;
use OneStaging\Core\Settings;
use OneStaging\OneStaging;
/**
 * Class Administrator
 * @package OneStaging\Backend
 */
class Administrator extends InjectionAware {

	/**
	 * @var string
	 */
	private $path;

	/**
	 * @var string
	 */
	private $url;

	/**
	 * Initialize class
	 */
	public function initialize() {

		$this->defineHooks();

		// Path to backend
		$this->path = plugin_dir_path( __FILE__ );

		// URL to public backend folder
		$this->url = plugin_dir_url( __FILE__ ) . 'public/';
	}

	/**
	 * Define Hooks
	 */
	private function defineHooks() {

		// Get loader
		$loader = $this->di->get( 'loader' );

		// Check if staging not supported. If not then stop here.
		if ( is_multisite() || ( get_option( 'siteurl' ) !== get_option( 'home' ) ) ) {
			$loader->addAction( 'admin_menu', $this, 'addMenuBlocked', 0 );
			$loader->addAction( 'network_admin_menu', $this, 'addMenuBlocked', 0 );
			return;
		}

		// Staging admin menu pages
		$loader->addAction( 'admin_menu', $this, 'addMenu', 0 );

		// Staging ajax requests
		$loader->addAction( 'wp_ajax_onestg_overview', $this, 'ajaxOverview' );
		$loader->addAction( 'wp_ajax_onestg_scanning', $this, 'ajaxScan' );
		$loader->addAction( 'wp_ajax_onestg_get_staging', $this, 'ajaxGetStaging' );
		$loader->addAction( 'wp_ajax_onestg_check_clone', $this, 'ajaxcheckCloneName' );
		$loader->addAction( 'wp_ajax_onestg_update', $this, 'ajaxUpdateProcess' );
		$loader->addAction( 'wp_ajax_onestg_deploy', $this, 'ajaxDeployLive' );
		$loader->addAction( 'wp_ajax_onestg_cloning', $this, 'ajaxStartClone' );
		$loader->addAction( 'wp_ajax_onestg_clone_database', $this, 'ajaxCloneDatabase' );
		$loader->addAction( 'wp_ajax_onestg_clone_prepare_directories', $this, 'ajaxPrepareDirectories' );
		$loader->addAction( 'wp_ajax_onestg_clone_files', $this, 'ajaxCopyFiles' );
		$loader->addAction( 'wp_ajax_onestg_clone_replace_data', $this, 'ajaxReplaceData' );
		$loader->addAction( 'wp_ajax_onestg_clone_finish', $this, 'ajaxFinish' );
		$loader->addAction( 'wp_ajax_onestg_confirm_delete_clone', $this, 'ajaxDeleteConfirmation' );
		$loader->addAction( 'wp_ajax_onestg_delete_clone', $this, 'ajaxDeleteClone' );
		$loader->addAction( 'wp_ajax_onestg_logs', $this, 'ajaxLogs' );
		$loader->addAction( 'wp_ajax_onestg_clone_log_error', $this, 'logError' );
		$loader->addAction( 'wp_ajax_onestg_check_disk_space', $this, 'ajaxCheckFreeSpace' );

		//login attempt on stagging
		$loader->addAction( 'wp_ajax_onestg_log_attempt', $this, 'getStgDetailsForLogin' );
		// Upcoming..
		/*$loader->addAction( "wp_ajax_onestg_update_struc", $this, "ajaxStartUpdate" );
		$loader->addAction( "wp_ajax_onestg_cancel_clone", $this, "ajaxCancelClone" );
		$loader->addAction( "admin_notices", $this, "messages" );*/
	}

	/**
	 * Add Admin SubMenu
	 */
	public function addMenu() {
		add_submenu_page(
			$parent_slug = OC_PLUGIN_DOMAIN,
			$page_title  = __( 'Staging', 'onecom-wp' ),
			$menu_title  = '<span id="onecom_staging">' . __( 'Staging', 'onecom-wp' ) . '</span>',
			$capability  = 'manage_options',
			$menu_slug   = 'onecom-wp-staging',
			$function    = array( $this, 'getClonePage' ),
			1
		);
	}

	/**
	 * Add Admin SubMenu
	 */
	public function addMenuBlocked() {
		add_submenu_page(
			$parent_slug = OC_PLUGIN_DOMAIN,
			$page_title  = __( 'Staging', 'onecom-wp' ),
			$menu_title  = __( 'Staging', 'onecom-wp' ),
			$capability  = 'manage_options',
			$menu_slug   = 'onecom-wp-staging-blocked',
			$function    = array( $this, 'getClonePageBlocked' ),
			1
		);
	}

	/**
	 * Clone Page
	 */
	public function getClonePage() {
		// Existing clones
		$availableStaging = get_option( 'onecom_staging_existing_staging', array() );
		$availableLive    = get_option( 'onecom_staging_existing_live', array() );
		$is_staging       = (bool) get_option( 'onecom_is_staging_site' );
		require_once "{$this->path}views/index.php";
	}
	/**
	 * Clone Page Disallowed
	 */
	public function getClonePageBlocked() {
		// Existing clones
		require_once "{$this->path}views/blocked.php";
		if ( (bool) get_site_option( 'staging_block' ) !== true ) {
			( class_exists( 'OCPushStats' ) ? \OCPushStats::generic_log( 'staging_block', 'Staging not supported', null ) : '' );
			add_site_option( 'staging_block', true );
		}
	}

	/**
	 * Ajax Overview
	 */
	public function ajaxOverview() {
		check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );

		// Existing clones
		$availableClones = get_option( 'onecom_staging_existing_staging', array() );

		wp_die();
	}

	/**
	 * Ajax Scan
	 */
	public function ajaxScan() {
		check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );
		// Scan
		$scan = new Scan();
		$scan->start();

		// Get Options
		$options = $scan->getOptions();
		require_once $this->path . 'views/ajax/scan.php';
		wp_die();
	}

	/**
	 * Check If Clone Exists
	 */
	public function checkCloneExists( $data ) {
		if ( ! ( isset( $data ) && ! empty( $data ) ) ) {
			return false;
		}
		$cloneKey = key( $data );

		// Check Staging Directory
		$dirExist = file_exists( $data[ $cloneKey ]['path'] );

		// Check DB tables
		$tablesExist = \OneStaging\OneStaging::getInstance()->get( 'wpdb' )->get_results( "SHOW TABLES LIKE '%$cloneKey%'" );

		// Because DB tables should be minimum 12 (wp default tables)
		if ( ( ! $dirExist ) || ( count( $tablesExist ) < 12 ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Ajax Start Rebuilding Staging
	 */
	public function ajaxUpdateProcess() {
		check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );

		$scan = new Scan();
		$scan->start();

		$updating = new Updating();

		if ( ! $updating->save() ) {
			wp_die( 'Updating : can not save clone data' );
		} else {
			( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_event_staging( 'rebuild', 'staging' ) : '' );
		}
		wp_die();
	}

	/**
	 * Ajax Start Staging to Live copy
	 */
	public function ajaxDeployLive() {
		check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );

		$scan = new Scan();
		$scan->start();

		$cloning = new Cloning();

		if ( ! $cloning->save() ) {
			wp_die( 'Deploying : can not save clone data' );
		} else {
			( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_event_staging( 'deploy', 'staging' ) : '' );
		}

		wp_die();
	}

	/**
	 * Ajax Start Copying Process
	 */
	public function ajaxStartClone() {
		check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );

		$cloning = new Cloning();
		if ( @\OneStaging\OneStaging::getInstance()->get( 'wpdb' )->_isStaging === true ) {
			( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_event_staging( 'deploy', 'staging' ) : '' );
		} else {
			( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_event_staging( 'create', 'staging' ) : '' );
		}

		if ( ! $cloning->save() ) {
			wp_die( 'can not save clone data' );
		}
		wp_die();
	}

	/**
	 * Ajax Clone Database
	 */
	public function ajaxCloneDatabase() {
		check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );
		$cloning = new Cloning();

		wp_send_json( $cloning->start() );
	}

	/**
	 * Ajax Prepare Directories (get listing of files)
	 */
	public function ajaxPrepareDirectories() {
		check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );

		$cloning = new Cloning();

		wp_send_json( $cloning->start() );
	}

	/**
	 * Ajax Clone Files
	 */
	public function ajaxCopyFiles() {
		check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );

		$cloning = new Cloning();

		wp_send_json( $cloning->start() );
	}

	/**
	 * Ajax Replace Data
	 */
	public function ajaxReplaceData() {
		check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );

		$cloning = new Cloning();

		wp_send_json( $cloning->start() );
	}

	/**
	 * Ajax Finish
	 */
	public function ajaxFinish() {
		check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );

		$cloning = new Cloning();
		wp_send_json( $cloning->start() );
	}

	/**
	 * Ajax Delete Confirmation
	 */
	public function ajaxDeleteConfirmation() {
		check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );

		$delete = new Delete();
		$delete->setData();
		$clone = $delete->getClone();
		wp_die();
	}

	/**
	 * Delete clone
	 */
	public function ajaxDeleteClone() {
		check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );

		$delete = new Delete();

		if ( (bool) get_site_option( 'onecom_staging_delete' ) !== true ) {
			( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_event_staging( 'delete', 'staging' ) : '' );
			add_site_option( 'onecom_staging_delete', true );
		}

		wp_send_json( $delete->start() );
	}

	/**
	 * Delete clone
	 */
	public function ajaxCancelClone() {
		check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );

		$cancel = new Cancel();
		wp_send_json( $cancel->start() );
	}

	public function ajaxGetStaging() {
		require_once $this->path . 'views/ajax/staging_details.php';
		wp_die();
	}

	/**
	 * Clone logs
	 */
	public function ajaxLogs() {
		check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );

		$logs = new Logs();
		wp_send_json( $logs->start() );
	}

	/**
	 * API logs
	 */
	public function logError() {
		check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );

		$action = sanitize_text_field( $_REQUEST['action'] );
		$msg    = sanitize_text_field( $_REQUEST['msg'] );

		if ( ! ( strlen( $msg ) && strlen( $action ) ) ) {
			die( 'Action/message missing.' );
		}

		( class_exists( 'OCPushStats' ) ? \OCPushStats::generic_log( $action, $msg, true ) : '' );
	}

	/**
	 * Ajax Checks Free Disk Space
	 */
	public function ajaxCheckFreeSpace() {
		check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );

		$scan = new Scan();

		return $scan->hasFreeDiskSpace();
	}

	/**
	 *  Get stg details for login
	 */
	public function getStgDetailsForLogin() {

		check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );

		$staginStatus = (int) get_site_option( 'staging_admin_live', true );
		$response     = array(
			'success' => false,
			'data'    => array(),
		);

		if ( $staginStatus === 1 ) {
			global $wpdb;
			$stgDetails = get_site_option( 'onecom_staging_existing_staging', true );
			if ( is_array( $stgDetails ) ) {
				foreach ( $stgDetails as $stgprefix => $stgallDetails ) {

					$encrypt_method = 'AES-256-CBC';
					$userHash       = $this->getUserHashPass( $stgallDetails['prefix'] );
					$secret_key     = $wpdb->prefix . '__' . $stgallDetails['prefix'] . $userHash;
					$secret_iv      = $stgallDetails['prefix'] . '__' . $wpdb->prefix;
					// hash
					$key    = hash( 'sha256', $secret_key );
					$iv     = substr( hash( 'sha256', $secret_iv ), 0, 16 );
					$string = $stgallDetails['url'] . '/onecom-' . $secret_key;
					$output = openssl_encrypt( $string, $encrypt_method, $key, 0, $iv );
					$output = base64_encode( $output );

					//send response on ajax
					$response = array(
						'success' => true,
						'data'    => array(
							'timelog' => $output,
							'stgUrl'  => $stgallDetails['url'],
						),
					);
				}
			}
		}
		wp_send_json( $response );
	}

	/**
	 * @param $stgPrefix
	 * @return int
	 * get user pass hash
	 */
	public function getUserHashPass( $stgPrefix ) {

			global $wpdb;
			$getAdminQuery = 'SELECT u.user_pass FROM ' . $stgPrefix . 'users u, ' . $stgPrefix . 'usermeta m WHERE u.ID = m.user_id AND m.meta_key LIKE "' . $stgPrefix . 'capabilities" AND m.meta_value LIKE "%administrator%" order by ID ASC';
			//Get admin user hash
			$wpAuthUser = $wpdb->get_results( $getAdminQuery );
			//return first admin pass hash
		if ( ! empty( $wpAuthUser ) ) {
			return $wpAuthUser[0]->user_pass;
		}
			return 0;
	}
}
