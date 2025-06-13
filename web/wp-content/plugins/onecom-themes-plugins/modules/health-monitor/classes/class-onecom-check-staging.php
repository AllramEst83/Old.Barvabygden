<?php
declare( strict_types=1 );

/**
 * Class OnecomCheckStaging
 * Deals with the checks related to staging site created by plugin
 */
class OnecomCheckStaging extends OnecomHealthMonitor {
	private $replica_site;
	private $threshold = 15552000; //six months


	/**
	 * Check if current site has an existing staging site
	 *
	 * @param void
	 *
	 * @return bool
	 */
	private function has_staging(): bool {
		$this->replica_site = get_option( 'onecom_staging_existing_staging', '' );
		return ( ! empty( $this->replica_site ) );
	}

	/**
	 * Check if stale staging site is present
	 * @return array
	 */
	public function check_staging_time(): array {
		$allowed_timestamp = time() - $this->threshold;
		if ( ! $this->has_staging() ) {
			return $this->format_result( $this->flag_resolved, 'No staging site', '' );
		}
		$staging_info = $this->replica_site[ array_key_first( $this->replica_site ) ];
		$replica_path = $staging_info['path'] . DIRECTORY_SEPARATOR . 'wp-config.php';
		$created_time = filemtime( $replica_path );
		if ( $created_time < $allowed_timestamp ) {
			return $this->format_result( $this->flag_open, 'Stale staging site present', 'A staging site that was created 6 months ago is present. You should consider deleting it. ' );
		} else {
			return $this->format_result( $this->flag_resolved, 'Staging site was created recently', '' );
		}
	}
}
