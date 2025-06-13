<?php
namespace OneStaging\Core\Iterators;

defined( 'WPINC' ) or die(); // No Direct Access

class RecursiveFilterExclude extends \RecursiveFilterIterator {

	protected $exclude = array();

	public function __construct( \RecursiveIterator $iterator, $exclude = array() ) {
		parent::__construct( $iterator );

		// Set exclude filter
		$this->exclude = $exclude;
	}

	#[\ReturnTypeWillChange] // Added to fix warning in PHP 8.1
	public function accept() {
		return ! in_array( $this->getInnerIterator()->getSubPathname(), $this->exclude );
	}

	#[\ReturnTypeWillChange] // Added to fix warning in PHP 8.1
	public function getChildren() {
		return new self( $this->getInnerIterator()->getChildren(), $this->exclude );
	}
}
