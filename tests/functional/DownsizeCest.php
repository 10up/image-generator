<?php

class DownsizeCest {

	/**
	 * The factory instance.
	 *
	 * @access protected
	 * @var \WPCC\Helper\Factory
	 */
	protected $_factory;

	/**
	 * The attachment instance.
	 *
	 * @access protected
	 * @var WP_Post
	 */
	protected $_attachment;

	/**
	 * Creates a new attachment for a test.
	 *
	 * @access public
	 * @param FunctionalTester $I The tester instance.
	 */
	public function _before( FunctionalTester $I ) {
		if ( ! $this->_factory ) {
			$this->_factory = \WPCC\Helper\Factory::create();
		}

		$tmp_name = wp_tempnam( 'image' );
		$this->_attachment = $this->_factory->attachment->createAndGet( array(
			'file' => $tmp_name,
		) );
	}

	/**
	 * Cleans up created content.
	 *
	 * @access public
	 * @param FunctionalTester $I The tester instance.
	 */
	public function _after( FunctionalTester $I ) {
		$this->_factory->cleanup();
	}

	// tests
	public function tryToTest( FunctionalTester $I ) {

	}

}