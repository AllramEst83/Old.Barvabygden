<?php
namespace OneStaging\Core\Iterators;

defined( 'WPINC' ) or die(); // No Direct Access

class RecursiveDirectoryIterator extends \RecursiveDirectoryIterator {

	protected $exclude = array();

	public function __construct( $path ) {
		parent::__construct( $path );

		// Skip current and parent directory
		$this->skipdots();
	}

	public function rewind(): void {
		parent::rewind();

		// Skip current and parent directory
		$this->skipdots();
	}

	public function next(): void {
		parent::next();

		// Skip current and parent directory
		$this->skipdots();
	}

	/**
	 * Returns whether current entry is a directory and not '.' or '..'
	 *
	 * Explicitly set allow links flag, because RecursiveDirectoryIterator::FOLLOW_SYMLINKS
	 * is not supported by <= PHP 5.3.0
	 *
	 * @return bool
	 */
	#[\ReturnTypeWillChange] // Added to fix warning in PHP 8.1
	public function hasChildren( $allow_links = true ) {
		return parent::hasChildren( $allow_links );
	}

	protected function skipdots() {
		while ( $this->isDot() ) {
			parent::next();
		}
	}
}
