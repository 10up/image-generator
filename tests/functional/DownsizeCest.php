<?php

namespace TENUP\ImageGenerator\Tests;

use TENUP\ImageGenerator\Tests\FunctionalTester;

class DownsizeCest extends BaseCest {

	/**
	 * Checks image with certain size and replace pattern.
	 *
	 * @access protected
	 * @param FunctionalTester $I The tester instance.
	 * @param string|array $size The size name or direct size dimensions.
	 * @param string $replace The replace pattern to use for expected URL.
	 * @param string $going Description what we are going to do.
	 */
	protected function _assertImage( $I, $size, $replace, $going ) {
		$image = wp_get_attachment_image_src( $this->_attachment->ID, $size );
		$expected_url = preg_replace( '~(\.\w+)$~i', $replace, $this->_url );

		$I->amGoingTo( $going );
		$I->assertNotEmpty( $image );
		$I->assertEquals( $expected_url, current( $image ) );
	}

	/**
	 * Checks if downsize returns url to a full size image if not existing image size is passed.
	 *
	 * @access public
	 * @param \TENUP\ImageGenerator\Tests\FunctionalTester $I The tester instance.
	 */
	public function testNotExistingImageSize( FunctionalTester $I ) {
		$image = wp_get_attachment_image_src( $this->_attachment->ID, 'non-existing-image-size' . rand( 0, 100 ) );

		$I->amGoingTo( 'check not existing image size' );
		$I->assertNotEmpty( $image );
		$I->assertEquals( $this->_url, current( $image ) );
	}

	/**
	 * Checks existing image size.
	 *
	 * @access public
	 * @param \TENUP\ImageGenerator\Tests\FunctionalTester $I The tester instance.
	 */
	public function testCustomImageSizes( FunctionalTester $I ) {
		$size = '_ten_gi_size_' . rand( 0, 100 );
		$width = rand( 200, 300 );
		$height = ceil( $width / $this->_aspect );
		$horizontal = rand( 1, 100 ) % 2 == 1 ? 'left' : 'right';
		$vertical = rand( 1, 100 ) %2 == 1 ? 'top' : 'bottom';

		// image size without crop
		add_image_size( $size, $width, $height, false );
		$this->_assertImage( $I, $size, "-{$width}x{$height}\$1", 'check existing image size without crop' );

		// image size with crop
		add_image_size( $size, $width, $height, true );
		$this->_assertImage( $I, $size, "-{$width}x{$height}c\$1", 'check existing image size with crop' );

		// image size with different crop center
		add_image_size( $size, $width, $height, array( $horizontal, $vertical ) );
		$this->_assertImage( $I, $size, "-{$width}x{$height}:{$horizontal}x{$vertical}\$1", 'check existing image size wit different crop center' );
	}

	/**
	 * Checks image downsize generation for cases when a direct image size is passed.
	 *
	 * @access public
	 * @param \TENUP\ImageGenerator\Tests\FunctionalTester $I The tester instance.
	 */
	public function testDirectImageSizes( FunctionalTester $I ) {
		$width = rand( 200, 300 );
		$height = ceil( $width / $this->_aspect );

		// without crop
		$this->_assertImage( $I, array( $width, $height ), "-{$width}x{$height}\$1", 'check direct image size without crop' );
		// with crop
		$this->_assertImage( $I, array( $width, $height, true ), "-{$width}x{$height}c\$1", 'check direct image size with crop' );
	}

	/**
	 * Checks incomplete sizes.
	 *
	 * @access public
	 * @param \TENUP\ImageGenerator\Tests\FunctionalTester $I The tester instance.
	 */
	public function testIncompleteSizes( FunctionalTester $I ) {
		$size = '_ten_gi_size_' . rand( 0, 100 );
		$dimension = rand( 200, 300 );
		$width = ceil( $dimension * $this->_aspect );
		$height = ceil( $dimension / $this->_aspect );

		// image size without height
		add_image_size( $size, $dimension );
		$this->_assertImage( $I, $size, "-{$dimension}x{$height}\$1", 'check incomplete image size without height' );

		// image size without width
		add_image_size( $size, 0, $dimension );
		$this->_assertImage( $I, $size, "-{$width}x{$dimension}\$1", 'check incomplete image size without width' );
	}

}