<?php
namespace OneStaging\Core\Jobs;

defined( 'WPINC' ) or die(); // No Direct Access

use OneStaging\OneStaging;
use OneStaging\Core\JobInterface;
use OneStaging\Core\Logger;
use OneStaging\Core\Cache;

/**
 * Class Job
 * @package OneStaging\Core\Jobs
 */
abstract class Job implements JobInterface {


	const EXECUTION_TIME_RATIO = 0.8;

	const MAX_MEMORY_RATIO = 0.8;

	/**
	 * @var Cache
	 */
	protected $cache;

	/**
	 * @var Logger
	 */
	protected $logger;

	/**
	 * @var bool
	 */
	protected $rebuilding = false;

	/**
	 * @var bool
	 */
	protected $hasLoggedFileNameSet = false;

	/**
	 * @var object
	 */
	protected $options;

	/**
	 * @var object
	 */
	protected $settings;

	/**
	 * System total maximum memory consumption
	 * @var int
	 */
	protected $maxMemoryLimit;

	/**
	 * Script maximum memory consumption
	 * @var int
	 */
	protected $memoryLimit;

	/**
	 * @var int
	 */
	protected $maxExecutionTime;


	/**
	 * @var int
	 */
	protected $executionLimit;

	/**
	 * @var int
	 */
	protected $totalRecursion;

	/**
	 * @var int
	 */
	protected $maxRecursionLimit;

	/**
	 * @var int
	 */
	protected $start;

	/**
	 * Job constructor.
	 */
	public function __construct() {
		// Get max limits
		$this->start            = $this->time();
		$this->maxMemoryLimit   = $this->getMemoryInBytes( @ini_get( 'memory_limit' ) );
		$this->maxExecutionTime = (int) 30;

		// Services
		$this->cache  = new Cache( -1, \OneStaging\OneStaging::getContentDir() );
		$this->logger = OneStaging::getInstance()->get( 'logger' );

		// Settings and Options
		$this->options = $this->cache->get( 'clone_options' );

		$this->settings = (object) \OneStaging\OneStaging::getInstance()->get( 'settings' );

		if ( $this->options ) {

			@$this->options->isStagingSite = $this->settings->_isStaging;

		} else {
			$this->options = new \stdClass();
		}

		if ( isset( $this->options->existingClones ) && is_object( $this->options->existingClones ) ) {
			$this->options->existingClones = json_decode( json_encode( $this->options->existingClones ), true );

			// If existing clone then check if rebuilding staging by running the process on live site.
			if ( $this->settings->_isStaging !== true ) {
				$this->rebuilding = true;
			}
		}

		// check default options
		if ( ! isset( $this->settings ) ||
				! isset( $this->settings->queryLimit ) ||
				! isset( $this->settings->batchSize ) ||
				! isset( $this->settings->cpuLoad ) ||
				! isset( $this->settings->fileLimit )
		) {

			$this->settings = new \stdClass();
			$this->setDefaultSettings();
		}

		// Set limits accordingly to CPU LIMITS
		$this->setLimits();

		$this->maxRecursionLimit = (int) ini_get( 'xdebug.max_nesting_level' );

		/*
		 * This is needed to make sure that maxRecursionLimit = -1
		 * if xdebug is not used in production env.
		 * For using xdebug, maxRecursionLimit must be larger
		 * otherwise xdebug is throwing an error 500 while debugging
		 */
		if ( $this->maxRecursionLimit < 1 ) {
			$this->maxRecursionLimit = -1;
		} else {
			$this->maxRecursionLimit = $this->maxRecursionLimit - 50; // just to make sure
		}

		if ( method_exists( $this, 'initialize' ) ) {
			$this->initialize();
		}
	}

	/**
	 * Job destructor
	 */
	public function __destruct() {
		// Commit logs
		$this->logger->commit();
	}

	/**
	 * Set default settings
	 */
	protected function setDefaultSettings() {
		$this->settings->queryLimit = '1000';
		$this->settings->fileLimit  = '1';
		$this->settings->batchSize  = '2';
		$this->settings->cpuLoad    = 'medium';
		$this->settings->_isStaging = \OneStaging\OneStaging::getInstance()->get( 'settings' )->isStagingSite();
		\OneStaging\OneStaging::getInstance()->set( 'settings', $this->settings );
	}

	/**
	 * Set limits accordingly to
	 */
	protected function setLimits() {

		if ( ! isset( $this->settings->cpuLoad ) ) {
			$this->settings->cpuLoad = 'fast';
		}

		$memoryLimit = 1;
		$timeLimit   = self::EXECUTION_TIME_RATIO;

		switch ( $this->settings->cpuLoad ) {
			case 'medium':
				//$memoryLimit= $memoryLimit / 2; // 0.4
				$timeLimit = $timeLimit / 2;
				break;
			case 'low':
				//$memoryLimit= $memoryLimit / 4; // 0.2
				$timeLimit = $timeLimit / 4;
				break;
			case 'fast': // 0.8
			default:
				break;
		}

		$this->memoryLimit    = $this->maxMemoryLimit * $memoryLimit;
		$this->executionLimit = $this->maxExecutionTime * $timeLimit;
	}

	/**
	 * Save options
	 * @param null|array|object $options
	 * @return bool
	 */
	protected function saveOptions( $options = null ) {
		// Get default options
		if ( null === $options ) {
			$options = $this->options;
		}

		// Ensure that it is an object
		$options = json_decode( json_encode( $options ) );
		return $this->cache->save( 'clone_options', $options );
	}

	/**
	 * @return object
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * @param string $memory
	 * @return int
	 */
	protected function getMemoryInBytes( $memory ) {
		// Handle unlimited ones
		if ( 1 > (int) $memory ) {
			return (int) $memory;
		}

		$bytes = (int) $memory; // grab only the number
		$size  = trim( str_replace( $bytes, '', strtolower( $memory ) ) ); // strip away number and lower-case it || replaced null with '' since supporting null is deprecated in php 8.1

		// Actual calculation
		switch ( $size ) {
			case 'k':
				$bytes *= 1024;
				break;
			case 'm':
				$bytes *= ( 1024 * 1024 );
				break;
			case 'g':
				$bytes *= ( 1024 * 1024 * 1024 );
				break;
		}

		return $bytes;
	}

	/**
	 * Format bytes into ini_set favorable form
	 * @param int $bytes
	 * @return string
	 */
	protected function formatBytes( $bytes ) {
		if ( (int) $bytes < 1 ) {
			return '';
		}

		$units = array( 'B', 'K', 'M', 'G' ); // G since PHP > 5

		$bytes = (int) $bytes;
		$base  = log( $bytes ) / log( 1000 );
		$pow   = pow( 1000, $base - floor( $base ) );

		return round( $pow, 0 ) . $units[ (int) floor( $base ) ];
	}

	/**
	 * Get current time in seconds
	 * @return float
	 */
	protected function time() {
		$time = microtime();
		$time = explode( ' ', $time );
		$time = $time[1] + $time[0];
		return $time;
	}

	/**
	 * @return bool
	 */
	protected function isOverThreshold() {
		// Check if the memory is over threshold
		$usedMemory = (int) @memory_get_usage( true );

		$this->debugLog( 'Used Memory: ' . $this->formatBytes( $usedMemory ) . ' Max Memory Limit: ' . $this->formatBytes( $this->maxMemoryLimit ) . ' Max Script Memory Limit: ' . $this->formatBytes( $this->memoryLimit ), Logger::TYPE_DEBUG );

		if ( $usedMemory >= $this->memoryLimit ) {
			$this->log( 'Used Memory: ' . $this->formatBytes( $usedMemory ) . ' Memory Limit: ' . $this->formatBytes( $this->maxMemoryLimit ) . ' Max Script memory limit: ' . $this->formatBytes( $this->memoryLimit ), Logger::TYPE_ERROR );
			return true;
		}

		if ( $this->isRecursionLimit() ) {
			return true;
		}

		// Check if execution time is over threshold
		///$time = round($this->start + $this->time(), 4);
		$time = round( $this->time() - $this->start, 4 );

		if ( $time >= $this->executionLimit ) {
			$this->log( 'RESET TIME: current time: ' . $time . ', Start Time: ' . $this->start . ', exec time limit: ' . $this->executionLimit );
			return true;
		}

		return false;
	}

	/**
	 * Attempt to reset memory
	 * @return bool
	 *
	 */
	protected function resetMemory() {
		$newMemoryLimit = $this->maxMemoryLimit * 2;

		// Failed to set
		if ( false === ini_set( 'memory_limit', $this->formatBytes( $newMemoryLimit ) ) ) {
			$this->log( 'Can not free some memory', Logger::TYPE_CRITICAL );
			return false;
		}

		// Double checking
		$newMemoryLimit = $this->getMemoryInBytes( @ini_get( 'memory_limit' ) );
		if ( $newMemoryLimit <= $this->maxMemoryLimit ) {
			return false;
		}

		// Set the new Maximum memory limit
		$this->maxMemoryLimit = $newMemoryLimit;

		// Calculate threshold limit
		$this->memoryLimit = $newMemoryLimit * self::MAX_MEMORY_RATIO;

		return true;
	}

	/**
	 * Checks if calls are over recursion limit
	 * @return bool
	 */
	protected function isRecursionLimit() {
		return ( $this->maxRecursionLimit > 0 && $this->totalRecursion >= $this->maxRecursionLimit );
	}

	/**
	 * @param string $msg
	 * @param string $type
	 */
	protected function log( $msg, $type = Logger::TYPE_INFO ) {

		if ( ! isset( $this->options->clone ) ) {
			$this->options->clone = date( DATE_ATOM, mktime( 0, 0, 0, 7, 1, 2000 ) );
		}

		if ( false === $this->hasLoggedFileNameSet && 0 < strlen( $this->options->clone ) ) {
			$this->logger->setFileName( $this->options->clone );
			$this->hasLoggedFileNameSet = true;
		}

		$this->logger->add( $msg, $type );
	}
	/**
	 * @param string $msg
	 * @param string $type
	 */
	protected function debugLog( $msg, $type = Logger::TYPE_INFO ) {
		if ( ! isset( $this->options->clone ) ) {
			$this->options->clone = date( DATE_ATOM, mktime( 0, 0, 0, 7, 1, 2000 ) );
		}

		if ( false === $this->hasLoggedFileNameSet && 0 < strlen( $this->options->clone ) ) {
			$this->logger->setFileName( $this->options->clone );
			$this->hasLoggedFileNameSet = true;
		}

		if ( isset( $this->settings->debugMode ) ) {
			$this->logger->add( $msg, $type );
		}
	}

	/**
	 * Throw a error message via json and stop further execution
	 * @param string $message
	 */
	protected function returnException( $message = '' ) {
		wp_die(
			json_encode(
				array(
					'job'     => isset( $this->options->currentJob ) ? $this->options->currentJob : '',
					'status'  => false,
					'message' => $message,
					'error'   => true,
				)
			)
		);
	}
}
