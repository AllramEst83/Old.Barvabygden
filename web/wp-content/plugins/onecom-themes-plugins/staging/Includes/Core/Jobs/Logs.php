<?php
namespace OneStaging\Core\Jobs;

defined( 'WPINC' ) or die(); // No Direct Access

/**
 * Class Logs
 * @package OneStaging\Core\Jobs
 */
class Logs extends Job {

	private $clone = null;

	/**
	 * Initialization
	 */
	public function initialize() {
		if ( isset( $_POST['clone'] ) ) {
			$this->clone = $_POST['clone'];
		}
	}

	/**
	 * @return string
	 */
	protected function getCloneFileName() {
		return ( null === $this->clone ) ? $this->options->clone : $this->clone;
	}

	/**
	 * @param null $clone
	 */
	public function setClone( $clone ) {
		$this->clone = $clone;
	}

	/**
	 * Start Module
	 * @return string
	 */
	public function start() {
		$logs = explode( PHP_EOL, $this->logger->read( $this->getCloneFileName() ) );
		return trim( implode( '<br>', array_reverse( $logs ) ), '<br>' );
	}
}
