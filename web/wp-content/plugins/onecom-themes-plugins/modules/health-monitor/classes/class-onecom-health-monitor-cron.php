<?php
declare(strict_types=1);

class OnecomHealthMonitorCron extends OnecomHealthMonitor {

	private $hm_transient = 'ocsh_site_scan_result';


	public function init() {
		add_filter( 'cron_schedules', array( $this, 'cron_interval' ) );
		add_action( 'onecom_hm_hook_overall_log', array( $this, 'onecom_hm_hook_overall_log_exec' ) );
		add_action( 'onecom_hm_hook_daily_scan', array( $this, 'onecom_hm_hook_daily_exec' ) );
		add_action( 'one_hm_on_demand_scan', array( $this, 'run_scan' ) );
		add_action( 'upgrader_process_complete', array( $this, 'execute_hm_scan_post_wp_update' ), 10, 2 );
		$this->schedule_tasks();
	}

	public function cron_interval( $schedules ) {
		$schedules['onecom_hm_weekly'] = array(
			'interval' => 604800, // 1 week in seconds
			'display'  => 'Once in a week',
		);
		$schedules['onecom_hm_daily']  = array(
			'interval' => 86400, // 24 hours in seconds.
			'display'  => 'Once in a day',
		);

		return $schedules;
	}

	public function schedule_tasks() {
		if ( ! wp_next_scheduled( 'onecom_hm_hook_overall_log' ) && ( ! empty( get_site_transient( $this->hm_transient ) ) ) ) {
			wp_schedule_event( time(), 'onecom_hm_weekly', 'onecom_hm_hook_overall_log' );
		}
		if ( ! wp_next_scheduled( 'onecom_hm_hook_daily_scan' ) ) {
			wp_schedule_event( time(), 'onecom_hm_daily', 'onecom_hm_hook_daily_scan' );
		}
	}

	public function onecom_hm_hook_overall_log_exec() {
		if ( ! class_exists( 'OCPushStats' ) ) {
			return;
		}
		$scan_result = get_site_transient( $this->hm_transient );
		$score       = $this->calculate_score( $scan_result );
		$ignored     = json_encode( get_option( $this->resolved_option, array() ) );
		\OCPushStats::push_health_monitor_stats_request(
			'scan',
			'blog',
			OCPushStats::get_subdomain(),
			'1',
			$scan_result,
			array(
				'item_source'    => 'health_monitor',
				'score'          => "{$score['score']}",
				'ignored_checks' => "{$ignored}",
			)
		);
	}

	public function onecom_hm_hook_daily_exec() {
		$this->run_scan();
	}

	/**
	 * @return void
	 * Function to execute the cron after 30 seconds.
	 * It will also validate if the hm scan got triggered recently, in that case scan will not trigger.
	 */
	public function hm_on_demand_scan() {
		$lock_name           = 'one_hm_scan_locked';
		$last_execution_time = get_site_transient( $lock_name );

		// Check if the function was executed recently.
		if ( $last_execution_time && ( time() - $last_execution_time < 30 ) ) {
			error_log( 'HM scan was executed recently. Skipping scheduling.' );
			return;
		}

		set_site_transient( $lock_name, time(), 120 );

		error_log( 'executing HM scan in next 30 seconds' );
		$scan_executed = wp_schedule_single_event( time() + 30, 'one_hm_on_demand_scan' );
		if ( $scan_executed ) {
			error_log( 'HM scan scheduled in next 30 seconds successfully' );
		} else {
			error_log( 'There was some issue with HM scan scheduling please try again' );
		}
	}


	/**
	 * @param $upgrader_object
	 * @param $options
	 * executes HM scan post wp core update
	 * @return void
	 */
	public function execute_hm_scan_post_wp_update( $upgrader_object, $options ): void {
		// execute the scan in case of wp core update.
		if ( 'core' === $options['type'] ) {
			// added this call through cron to avoid issues when WP gets updated through VM.
			wp_schedule_single_event( time() + 30, 'onecom_hm_scan' );
		}
	}

	// run all the scans, reusing API module
	public function run_scan() {
		if ( ! class_exists( 'OnecomPluginsApi' ) ) {
			require_once ONECOM_WP_PATH . '/modules/api/class-onecom-plugins-api.php';
		}
		$api = new OnecomPluginsApi();
		$api->health_monitor_scan();
	}
}
