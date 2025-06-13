<?php
$oc_hm_status = 'status';
$oc_hm_desc   = 'desc';
$oc_hm_title  = 'title';
if ( ! defined( 'OC_HM_SCORE' ) ) {
	define( 'OC_HM_SCORE', 'score' );
}

if ( ! function_exists( 'oc_sh_save_result' ) ) {
	function oc_sh_save_result( $stage, $oc_hm_status, $finish = 0 ) {
		$result = get_site_transient( 'ocsh_site_scan_result' );
		$time   = time();
		if ( ! $result ) {
			$result = array();
		}
		$result['time']   = $time;
		$result[ $stage ] = $oc_hm_status;
		$save             = set_site_transient( 'ocsh_site_scan_result', $result, 4 * HOUR_IN_SECONDS );

		if ( $finish == 1 ) {
			unset( $result['time'] );
			$health                 = array();
			$health['issues']       = $result;
			$health[ OC_HM_SCORE ] = round( oc_sh_calculate_score( $result )[ OC_HM_SCORE ] );

			/* save health monitor result */
			oc_sh_log_entry( '== one.com Health Monitor Scan ==' );
			oc_sh_log_entry( json_encode( $health ), 1 );

			( class_exists( 'OCPushStats' ) ? \OCPushStats::push_health_monitor_stats_request( 'scan', 'blog', OCPushStats::get_subdomain(), '1', $health ) : '' );
		}

		return $save;
	}
}

if ( ! function_exists( 'oc_sh_calculate_score' ) ) {
	function oc_sh_calculate_score( $transient ) {
		$count = oc_vulns_count() > 0 ? 1 : 0;

		if ( ! $transient || empty( $transient ) ) {
			return 0;
		}
		$ignored_checks = get_site_option( 'oc_marked_resolved', array() );
		if ( empty( $ignored_checks ) ) {
			$ignored_checks = array();
		}
		@$time = $transient['time'];
		unset( $transient['time'] );

		$success = 0;
		$todo    = 0;

		foreach ( $transient as $check => $score ) {
			if ( in_array( str_replace( array( 'ocsh_fix_', 'ocsh_fix_check_', 'check_' ), '', $check ), $ignored_checks ) ) {
				$score = OC_RESOLVED;
			}

			if ( $score == OC_RESOLVED ) {
				$success++;
			} elseif ( $score == OC_OPEN ) {
				$todo++;
			}
		}
		$percent = floor( ( $success * 100 ) / ( count( $transient ) + $count ) );
		if ( $percent == '100.00' ) {
			$percent = 100;
		}

		return array(
			OC_HM_SCORE => $percent,
			'time'       => $time,
			'todo'       => $todo,
		);
	}
}

if ( ! function_exists( 'oc_vulns_count' ) ) {
	function oc_vulns_count() {
		if ( class_exists( 'OCVMNotifications' ) ) {
			$notices = new OCVMNotifications();
			$notices->prepareNotifications( 1 );
			// get notices count
			$count = is_countable( $notices->notices ) ? count( $notices->notices ) : 0;
		} else {
			$count = 0;
		}
		return $count;
	}
}
