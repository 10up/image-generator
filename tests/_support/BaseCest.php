<?php

namespace TENUP\ImageGenerator\Tests;

use TENUP\ImageGenerator\Tests\FunctionalTester;

class BaseCest {

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
	 * The image aspect ratio.
	 *
	 * @access protected
	 * @var float
	 */
	protected $_aspect = 1;

	/**
	 * Creates a new attachment for a test.
	 *
	 * @access public
	 * @param \TENUP\ImageGenerator\Tests\FunctionalTester $I The tester instance.
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

		$image = wp_get_attachment_image_src( $this->_attachment->ID, 'full-size-image-' . rand( 0, 100 ) );
		if ( ! empty( $image[1] ) && ! empty( $image[2] ) ) {
			$this->_aspect = $image[1] / $image[2];
		}
	}

	/**
	 * Cleans up created content.
	 *
	 * @access public
	 * @param \TENUP\ImageGenerator\Tests\FunctionalTester $I The tester instance.
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