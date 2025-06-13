<?php
declare( strict_types=1 );

/**
 * Class OnecomTemplate
 * Contains all the template functions used to render page content
 */
class OnecomTemplate extends OnecomHealthMonitor {

	/**
	 * Get page title
	 * @return string
	 */
	public $svg_icon = '<svg width="37" height="24" viewBox="0 0 37 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="0.5" y="0.5" width="36" height="23" rx="3.5" fill="white"/>
                                <path d="M12.618 7.6C13.706 7.6 14.562 7.86 15.186 8.38C15.81 8.9 16.122 9.616 16.122 10.528C16.122 11.44 15.81 12.156 15.186 12.676C14.562 13.196 13.706 13.456 12.618 13.456H10.542V16H9.34203V7.6H12.618ZM12.582 12.412C13.342 12.412 13.922 12.252 14.322 11.932C14.722 11.604 14.922 11.136 14.922 10.528C14.922 9.92 14.722 9.456 14.322 9.136C13.922 8.808 13.342 8.644 12.582 8.644H10.542V12.412H12.582ZM18.7155 10.708C18.9155 10.34 19.2115 10.06 19.6035 9.868C19.9955 9.676 20.4715 9.58 21.0315 9.58V10.696C20.9675 10.688 20.8795 10.684 20.7675 10.684C20.1435 10.684 19.6515 10.872 19.2915 11.248C18.9395 11.616 18.7635 12.144 18.7635 12.832V16H17.6115V9.64H18.7155V10.708ZM25.1168 16.072C24.4848 16.072 23.9168 15.932 23.4128 15.652C22.9088 15.372 22.5128 14.988 22.2248 14.5C21.9448 14.004 21.8048 13.444 21.8048 12.82C21.8048 12.196 21.9448 11.64 22.2248 11.152C22.5128 10.656 22.9088 10.272 23.4128 10C23.9168 9.72 24.4848 9.58 25.1168 9.58C25.7488 9.58 26.3128 9.72 26.8088 10C27.3128 10.272 27.7048 10.656 27.9848 11.152C28.2728 11.64 28.4168 12.196 28.4168 12.82C28.4168 13.444 28.2728 14.004 27.9848 14.5C27.7048 14.988 27.3128 15.372 26.8088 15.652C26.3128 15.932 25.7488 16.072 25.1168 16.072ZM25.1168 15.064C25.5248 15.064 25.8888 14.972 26.2088 14.788C26.5368 14.596 26.7928 14.332 26.9768 13.996C27.1608 13.652 27.2528 13.26 27.2528 12.82C27.2528 12.38 27.1608 11.992 26.9768 11.656C26.7928 11.312 26.5368 11.048 26.2088 10.864C25.8888 10.68 25.5248 10.588 25.1168 10.588C24.7088 10.588 24.3408 10.68 24.0128 10.864C23.6928 11.048 23.4368 11.312 23.2448 11.656C23.0608 11.992 22.9688 12.38 22.9688 12.82C22.9688 13.26 23.0608 13.652 23.2448 13.996C23.4368 14.332 23.6928 14.596 24.0128 14.788C24.3408 14.972 24.7088 15.064 25.1168 15.064Z" fill="#0078C8"/>
                                <rect x="0.5" y="0.5" width="36" height="23" rx="3.5" stroke="#0078C8"/>
                            </svg>';

	public function get_title(): string {
		$title = __( 'Health Monitor', 'onecom-wp' );
		if ( $this->onecom_is_premium() ) {
			$title .= apply_filters( 'onecom_hm_subtitle_html', '<span class="onecom_subheading">' . $this->svg_icon . '</span>' );
		}
		//      else {
		//          $title .= apply_filters( 'onecom_hm_subtitle_html', '<sup class="onecom_subheading onecom_subheading_lite">' . __( 'Lite', 'onecom-wp' ) . '</sup>' );
		//      }

		return $title;
	}

	/**
	 * Get page description
	 * @return string
	 */
	public function get_description(): string {
		if ( $this->onecom_is_premium() ) {
			return $this->hm_description_premium;
		}
		return $this->hm_description;
	}

	/**
	 * Get info (i) icon
	 * @return string
	 */
	public function get_info_icon(): string {
		return ONECOM_WP_URL . '/modules/health-monitor/assets/images/info.svg';
	}

	public function get_ignored_ul(): string {
		if ( $this->onecom_is_premium() ) {
			return '<ul class="ignored">' . $this->get_generated_html_for_checks( 3 ) . '</ul>';
		}

		return '<div class="onecom_ignored"><img src="' . ONECOM_WP_URL . '/assets/images/lock.svg" alt="one.com lock"><p>' . $this->ignored_lite_text . ' <a class="onecom__open-modal">' . $this->get_started . '</a></p></div>';
	}

	public function get_status_with_score( $score ): string {
		$status = 'poor';
		if ( $score > 75 ) {
			$status = 'good';
		} elseif ( $score >= 50 ) {
			$status = 'ok';
		}

		return $status;
	}

	/**
	 * @param $status
	 * gets the html for checks based on the status of checks from the stored scan results
	 * @return false|string
	 */
	public function get_generated_html_for_checks( $status ) {
		$scan_result = get_site_transient( 'ocsh_site_previous_scan' );
		if ( is_array( $scan_result ) ) {
			$todo    = array();
			$ignored = array();
			foreach ( $scan_result as $key => $check ) {

				if ( $this->is_ignored( $key ) ) {
					$ignored[] = $this->get_html( $key, $check );
					continue;
				}
				if ( $status !== 3 && $check['status'] === $status ) {
					if ( $key === 'performance_cache' ) {
						$key = 'check_' . $key;
					}
					$todo[] = $this->get_html( $key, $check );
				}
			}
			if ( $status === 3 && ! empty( $ignored ) ) {
				return implode( '', $ignored );
			} else {
				return implode( '', $todo );
			}
		}

		return false;
	}
}
