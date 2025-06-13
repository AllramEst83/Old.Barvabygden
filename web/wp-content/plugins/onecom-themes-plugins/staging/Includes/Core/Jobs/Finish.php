<?php
namespace OneStaging\Core\Jobs;

defined( 'WPINC' ) or die(); // No Direct Access

use OneStaging\OneStaging;

/**
 * Class Finish
 * @package OneStaging\Core\Jobs
 */
#[\AllowDynamicProperties]
class Finish extends Job {

	/**
	 * Clone Key
	 * @var string
	 */
	private $clone = '';

	/**
	 * Start Module
	 * @return object
	 */
	public function start() {
		// sanitize the clone name before saving
		if ( $this->options->isStagingSite === true ) {

			$this->clone = preg_replace( '#\W+#', '-', strtolower( $this->options->existingLive->directoryName ) );
		} else {
			$this->clone = preg_replace( '#\W+#', '-', strtolower( $this->options->clone ) );
		}

		// Delete Cache Files
		$this->deleteCacheFiles();

		// Prepare clone records & save scanned directories for delete job later
		$this->prepareCloneDataRecords();

		$return = array(
			'directoryName' => $this->options->cloneDirectoryName,
			'path'          => ABSPATH . $this->options->cloneDirectoryName,
			'url'           => get_site_url() . '/' . $this->options->cloneDirectoryName,
			'number'        => $this->options->cloneNumber,
			'version'       => \OneStaging\OneStaging::getVersion(),
			'status'        => false,
			'prefix'        => $this->options->prefix,
			'last_msg'      => $this->logger->getLastLogMsg(),
			'job'           => $this->options->currentJob,
		);

		$this->options->currentJob = null;

		return (object) $return;
	}

	/**
	 * Delete Cache Files
	 */
	protected function deleteCacheFiles() {
		$this->log( "Finish: Deleting clone job's cache files..." );

		// Clean cache files
		$this->cache->delete( 'clone_options' );
		$this->cache->delete( 'files_to_copy' );

		$this->log( "Finish: Clone job's cache files have been deleted!" );
	}

	/**
	 * Get total size of a directory including all its subdirectories
	 * @param string $dir
	 * @return int
	 */
	function getDirectorySizeInclSubdirs( $dir ) {
		$size = 0;
		foreach ( glob( rtrim( $dir, '/' ) . '/*', GLOB_NOSORT ) as $each ) {
			$size += is_file( $each ) ? filesize( $each ) : $this->getDirectorySizeInclSubdirs( $each );
		}
		return $size;
	}

	/**
	 * Prepare clone records
	 * @return bool
	 */
	protected function prepareCloneDataRecords() {

		$totalTime = ceil( (int) $_REQUEST['totalTime'] );
		if ( isset( $this->options->totalFileSize ) && $this->options->totalFileSize !== '' ) {
			$totalSize = $this->options->totalFileSize;
		} else {
			$totalSize = $this->getDirectorySizeInclSubdirs( ABSPATH );
		}
		//reset timer
		unset( $this->options->timer );

		//If job is running on live site.
		// Push time in Milliseconds in Stats
		$totalTimeMS = $totalTime * 1000;
		if ( $this->options->isStagingSite !== true ) {
			if ( $this->rebuilding === true ) {
				( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_event_staging( 'rebuild', 'staging', "$totalTimeMS", "$totalSize" ) : '' );
			} else {
				( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_event_staging( 'create', 'staging', "$totalTimeMS", "$totalSize" ) : '' );
			}
		} else {
			( class_exists( 'OCPushStats' ) ? \OCPushStats::push_stats_event_staging( 'deploy', 'staging', "$totalTimeMS", "$totalSize" ) : '' );
		}

		// Clone data already exists
		if ( isset( $this->options->existingClones[ $this->options->clone ] ) ) {
			$this->log( 'Finish: Clone data already exists, no need to update, the job finished' );
			return true;
		}

		// Save new clone data
		$this->log( "Finish: {$this->options->clone}'s clone job's data is not in database, generating data" );

		if ( $this->options->isStagingSite !== true ) {
			$this->options->existingClones[ $this->clone ] = array(
				'directoryName' => $this->options->cloneDirectoryName,
				'path'          => ABSPATH . $this->options->cloneDirectoryName,
				'url'           => get_site_url() . '/' . $this->options->cloneDirectoryName,
				'number'        => $this->options->cloneNumber,
				'version'       => \OneStaging\OneStaging::getVersion(),
				'status'        => false,
				'prefix'        => $this->options->prefix,
			);

			if ( false === update_option( 'onecom_staging_existing_staging', $this->options->existingClones, 'no' ) ) {
				$this->log( "Finish: Failed to save {$this->options->clone}'s clone job data to database'" );
				return false;
			}
		}
		return true;
	}
}
