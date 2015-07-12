<?php

class Tenup_IG_BaseCest {

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
	 * The image filename.
	 *
	 * @access protected
	 * @var string
	 */
	protected $_filename;

	/**
	 * The image url.
	 *
	 * @access protected
	 * @var string
	 */
	protected $_url;

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

		$dirs = wp_upload_dir();
		if ( ! is_dir( $dirs['path'] ) ) {
			mkdir( $dirs['path'] );
		}

		$baseimage = codecept_data_dir() . 'image.jpg';
		$filename = wp_unique_filename( $dirs['path'], 'downsize-image-test.jpg' );

		$this->_url = trailingslashit( $dirs['url'] ) . $filename;
		$this->_filename = rtrim( $dirs['path'], DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . $filename;

		copy( $baseimage, $this->_filename );

		$this->_attachment = $this->_factory->attachment->createAndGet( array( 'file' => $this->_filename ) );
	}

	/**
	 * Cleans up created content.
	 *
	 * @access public
	 * @param FunctionalTester $I The tester instance.
	 */
	public function _after( FunctionalTester $I ) {
		if ( $this->_factory ) {
			$this->_factory->cleanup();
		}
	}

	/**
	 * Clears generated content on fail.
	 *
	 * @access public
	 */
	public function _failed() {
		if ( $this->_factory ) {
			$this->_factory->cleanup();
		}
	}

}