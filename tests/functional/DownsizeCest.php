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
		$this->_factory->cleanup();
	}

	/**
	 * Checks if downsize returns url to a full size image if not existing image size is passed.
	 *
	 * @access public
	 * @param FunctionalTester $I The tester instance.
	 */
	public function testNotExistingImageSize( FunctionalTester $I ) {
		$image = wp_get_attachment_image_src( $this->_attachment->ID, 'non-existing-image-size' . rand( 0, 100 ) );
		$I->assertNotEmpty( $image );
		$I->assertEquals( $this->_url, current( $image ) );
	}

}