<?php
$template       = new OnecomTemplate();
$is_premium     = $template->onecom_is_premium( 'all_plugins' );
$is_mwp         = $template->onecom_is_premium();
$scan_result    = get_site_transient( 'ocsh_site_scan_result' );
$last_scan_time = $scan_result['time'] ?? __( 'No scan available', 'onecom-wp' );

/* Format the last scan date time as per WP date-time settings */
if ( is_numeric( $last_scan_time ) && function_exists( 'wp_date' ) ) {
	$frmt                     = 'l ' . get_site_option( 'date_format' ) . ' ' . get_site_option( 'time_format' );
	$tz                       = get_site_option( 'timezone_string' ) && ! empty( get_site_option( 'timezone_string' ) ) ? get_site_option( 'timezone_string' ) : 'UTC';
	$last_scan_time_localised = wp_date( $frmt, $last_scan_time, new DateTimeZone( $tz ) );
} else {
	$last_scan_time_localised = __( 'No scan available', 'onecom-wp' );

}

	require_once ONECOM_WP_PATH . 'modules' . DIRECTORY_SEPARATOR . 'vulnerability-monitor' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'class-ocvm.php';
	require_once ONECOM_WP_PATH . 'modules' . DIRECTORY_SEPARATOR . 'vulnerability-monitor' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'class-ocvm-admin-page.php';

	$ocvmsettings  = new OCVM();
	$ocvmadminPage = new OCVMAdmin( $ocvmsettings->get_version(), $ocvmsettings->get_OCVM() );


// get vulnerabilities count
$count = oc_vulns_count();

//get vm features check
$settings = new OCVMSettings();
$vmcheck  = $settings->isPremium();

if ( ! class_exists( 'OnecomPluginsApi' ) ) {
	require_once ONECOM_WP_PATH . '/modules/api/class-onecom-plugins-api.php';
}
$api         = new OnecomPluginsApi();
$health_scan = $api->get_health_monitor_recent_results();
$calc        = oc_sh_calculate_score( $health_scan );
$score       = round( $calc['score'] );
$todo_count  = $calc['todo'];
unset( $health_scan['time'] );
$ignored       = get_site_option( 'oc_marked_resolved', array() );
$ignored_count = is_countable( $ignored ) ? count( $ignored ) : 0;
$done_count    = count( $health_scan ) - ( $todo_count + $ignored_count );
$status        = $template->get_status_with_score( $score );
$prev_scan     = get_site_transient( 'ocsh_site_previous_scan' );
$oc_body_class = ! $prev_scan ? 'oc-nps' : '';

?>
<div class="wrap ocsh-wrap">
	<div class="wrap-top-onecom-heading-desc">
		<h1 class="onecom-main-heading"><?php echo __( 'Health and Security Tools', 'onecom-wp' ); ?></h1>
		<p class="onecom-main-desc"><?php echo __( 'Monitor the essential security and performance checkpoints and fix them if needed.', 'onecom-wp' ); ?></p>
	</div>
	<div class="inner one_wrap bg_box_main_container">
		<div class="wrap_inner">
			<div class="onecom_critical__wrap critical" id="critical">
				<ul class="critical"></ul>
			</div>
			<div class="onecom_head">
				<div class="onecom_head__inner onecom_head_left">
					<h2 class="onecom_heading"><img src="<?php echo ONECOM_WP_URL; ?>modules/health-monitor/assets/images/health-monitor-icon.svg" alt="" class="onecom-heading-icon"><?php echo $template->get_title(); ?></h2>
					<p class="onecom_description"><?php echo $template->get_description(); ?></p>
					<p class="oc-last-scan"><span><?php _e( 'Last scan', 'onecom-wp' ); ?>: </span><span id="oc-last-scan-datetime"><?php echo $last_scan_time_localised; ?></span>
						<span class="scan-btn"><a class="oc-trigger-hmscan ocwp_ocp_hm_scan_started_event"><?php _e( 'Scan now', 'onecom-wp' ); ?></a> </span>
					</p>
				</div>
				<div class="onecom_head__inner onecom_head_right"
				<?php
				if ( ! $is_premium ) {
					echo 'style="display:none"';}
				?>
				>
					<div class="onecom_card">
						<span class="onecom_card_title"><?php echo __( 'Score', 'onecom-wp' ); ?>

							<div class="oc-tooltip">
							<img
									class="onecom_info_icon"
									src="<?php echo $template->get_info_icon(); ?>"
									alt="info">
								<img
										class="onecom_up-arrow"
										src="<?php echo ONECOM_WP_URL; ?>modules/health-monitor/assets/images/arrow-up.svg"
										alt="info">
							<span class="tooltiptext"><?php echo __( 'The score indicates the health of your website out of 100%', 'onecom-wp' ); ?></span>
						</div>
						</span>
						<span id="onecom_card_result" class="onecom_card_value"><span class="<?php echo $status; ?>"><?php echo $score; ?>%</span></span>

					</div>
					<div class="onecom_card">
						<span class="onecom_card_title"><?php echo __( 'To do', 'onecom-wp' ); ?>
							<div class="oc-tooltip">
							<img
									class="onecom_info_icon"
									src="<?php echo $template->get_info_icon(); ?>"
									alt="info">
							<img
									class="onecom_up-arrow"
									src="<?php echo ONECOM_WP_URL; ?>modules/health-monitor/assets/images/arrow-up.svg"
									alt="info">
							<span class="tooltiptext"><?php echo __( "Recommendations are common security and performance improvements you can do to enhance your site's defense against hackers and bots.", 'onecom-wp' ); ?></span>
						</div>
						</span>
						<span id="onecom_card_todo_score" class="onecom_card_value"><?php echo $todo_count; ?></span>
					</div>
					<div class="onecom_card">
						<span class="onecom_card_title"><?php echo __( 'Vulnerabilities', 'onecom-wp' ); ?>
						</span>
						<span id="onecom_card_vulnerability_score" class="onecom_card_value
						<?php
						if ( $count > 0 ) {
							echo 'poor';} else {
							echo 'none';}
							?>
							">
							<?php echo $count; ?>
						</span>
					</div>
				</div>

			</div>
		</div>
		<div class="onecom_body <?php echo $oc_body_class; ?>">
			<div class="h-parent-wrap"
			<?php
			if ( ! $is_premium ) {
				echo 'style="display:none"';}
			?>
			>
				<div class="h-parent">
					<div class="h-child">
						<div class="onecom_tabs_container" data-error="<?php echo ini_get( 'display_errors' ); ?>">
							<div class="onecom_tab active ocwp_ocp_hm_todo_tab_visited_event" data-tab="todo"><?php echo __( 'To do', 'onecom-wp' ); ?>
								<span
										class="count" id="todo_count"><?php echo $todo_count; ?></span></div>
							<div class="onecom_tab ocwp_ocp_hm_done_tab_visited_event" data-tab="done"><?php echo __( 'Done', 'onecom-wp' ); ?><span
										class="count" id="done_count"><?php echo $done_count; ?></span></div>
							<div class="onecom_tab ocwp_ocp_hm_ignored_tab_visited_event" data-tab="ignored">
								<?php echo __( 'Ignored', 'onecom-wp' ); ?><span class="count" id="ignored_count"><?php echo $ignored_count; ?></span>
							</div>
							<div class="onecom_tab ocwp_ocp_vm_tab_visited_event" data-tab="vulnerability">
								<?php echo __( 'Vulnerabilities', 'onecom-wp' ); ?><span class="count" id="vulnerability" data-count="<?php echo $count; ?>"><?php echo $count; ?></span>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="onecom_tabs_panels"
			<?php
			if ( ! $is_premium ) {
				echo 'style="display:none"';}
			?>
			>
				<div class="onecom_tabs_panel todo" id="todo">
					<ul id="plugin-filter" class="todo">
						<?php echo $template->get_generated_html_for_checks( 1 ); ?>
					</ul>
				</div>
				<div class="onecom_tabs_panel done oc_hidden" id="done">
					<ul class="done">
						<?php echo $template->get_generated_html_for_checks( 0 ); ?>
					</ul>
				</div>
				<div class="onecom_tabs_panel ignored oc_hidden" id="ignored">
					<?php echo $template->get_ignored_ul(); ?>
				</div>
				<div class="onecom_tabs_panel vulnerability oc_hidden" id="vulnerability">
					<?php $ocvmadminPage->vm_page_callback(); ?>
				</div>
			</div>
			<?php if ( ! $is_premium ) { ?>
				<div class="innerNoFound" style="text-align:center;color:#8A8989;">
					<img src="<?php echo ONECOM_WP_URL; ?>modules/health-monitor/assets/images/beginner-icon.svg"
						alt="<?php echo __( 'Get access to Health Monitor and more for free with Managed WordPress.', 'onecom-wp' ); ?>">
					<p><?php echo sprintf( __( 'Get access to Health Monitor and more for free with Managed WordPress.%s %sGet Started%s', 'onecom-wp' ), '<br>', '<a href="' . oc_upgrade_link( 'top_banner' ) . '" target="_blank" style="font-weight:600;text-decoration:none;">', '</a>' ); ?></p>
				</div>

			<?php } ?>
		</div>
	</div>
</div>