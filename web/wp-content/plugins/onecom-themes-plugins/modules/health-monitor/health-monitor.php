<?php
defined( 'WPINC' ) or die(); // No Direct Access
const OC_OPEN         = 1;
const OC_RESOLVED     = 0;
const HT_NONCE_STRING = 'ht_nonce_string';
const MODULE_PATH     = ONECOM_WP_PATH . 'modules' . DIRECTORY_SEPARATOR . 'health-monitor' . DIRECTORY_SEPARATOR;
require_once 'inc' . DIRECTORY_SEPARATOR . 'functions.php';
require_once 'traits' . DIRECTORY_SEPARATOR . 'trait-onecom-texts.php';
require_once 'traits' . DIRECTORY_SEPARATOR . 'trait-onecom-check-category.php';
require_once 'traits' . DIRECTORY_SEPARATOR . 'trait-onecom-checks-list.php';
require_once 'traits' . DIRECTORY_SEPARATOR . 'trait-onecom-lite.php';
spl_autoload_register(
	function ( $class ) {
		$filename = strtolower(
			preg_replace(
				array( '/([A-Z]+)/', '/_([A-Z]+)([A-Z][a-z])/' ),
				array( '-$1', '-$1-$2' ),
				lcfirst( $class )
			)
		);
		if ( strpos( $filename, 'trait' ) === 0 ) {
			$include_file = MODULE_PATH . 'traits' . DIRECTORY_SEPARATOR . 'trait-' . $filename . '.php';
		} else {
			$include_file = MODULE_PATH . 'classes' . DIRECTORY_SEPARATOR . 'class-' . $filename . '.php';
		}

		if ( file_exists( $include_file ) ) {
			require $include_file;
		}
	}
);

$ht = new OnecomFileSecurity();

// admin pages
$health_monitor_pages = new OnecomAdminPages();
$health_monitor_pages->init();

// ajax requests
$ajax = new OnecomHealthMonitorAjax();
$ajax->init();

$checks = new OnecomChecks();
$checks->init();

// logout fixes
$logout = new OnecomCheckLogin();
$logout->init();

$notices = new OnecomAdminNotices();
$notices->init();

// user enumeration fixes
$user_enumeration = new OnecomCheckUsername();
$user_enumeration->init();

// add cron for weekly stats
$cron = new OnecomHealthMonitorCron();
$cron->init();

// schedule single event to run HM scan
add_action( 'onecom_hm_scan', array( $cron, 'hm_on_demand_scan' ) );
