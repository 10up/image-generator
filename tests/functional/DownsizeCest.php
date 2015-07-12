<?php

require_once codecept_root_dir() . 'tests/_support/BaseCest.php';

class DownsizeCest extends Tenup_IG_BaseCest {

	/**
	 * Checks if downsize returns url to a full size image if not existing image size is passed.
	 *
	 * @access public
	 * @param FunctionalTester $I The tester instance.
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
	 * @param FunctionalTester $I The tester instance.
	 */
	public function testCustomImageSizes( FunctionalTester $I ) {
		$size = '_ten_gi_size_' . rand( 0, 100 );
		$width = $height = rand( 200, 300 );
		$horizontal = rand( 1, 100 ) % 2 == 1 ? 'left' : 'right';
		$vertical = rand( 1, 100 ) %2 == 1 ? 'top' : 'bottom';

		// image size without crop
		add_image_size( $size, $width, $height, false );

		$image = wp_get_attachment_image_src( $this->_attachment->ID, $size );
		$expected_url = preg_replace( '~(\.\w+)$~i', "-{$width}x{$height}\$1", $this->_url );

		$I->amGoingTo( 'check existing image size without crop' );
		$I->assertNotEmpty( $image );
		$I->assertEquals( $expected_url, current( $image ) );

		// image size with crop
		add_image_size( $size, $width, $height, true );

		$image = wp_get_attachment_image_src( $this->_attachment->ID, $size );
		$expected_url = preg_replace( '~(\.\w+)$~i', "-{$width}x{$height}c\$1", $this->_url );

		$I->amGoingTo( 'check existing image size with crop' );
		$I->assertNotEmpty( $image );
		$I->assertEquals( $expected_url, current( $image ) );

		// image size with different crop center
		add_image_size( $size, $width, $height, array( $horizontal, $vertical ) );

		$image = wp_get_attachment_image_src( $this->_attachment->ID, $size );
		$expected_url = preg_replace( '~(\.\w+)$~i', "-{$width}x{$height}:{$horizontal}x{$vertical}\$1", $this->_url );

		$I->amGoingTo( 'check existing image size wit different crop center' );
		$I->assertNotEmpty( $image );
		$I->assertEquals( $expected_url, current( $image ) );
	}

	/**
	 * Checks image downsize generation for cases when a direct image size is passed.
	 *
	 * @access public
	 * @param FunctionalTester $I The tester instance.
	 */
	public function testDirectImageSizes( FunctionalTester $I ) {
		$width = $height = rand( 200, 300 );

		// without crop
		$image = wp_get_attachment_image_src( $this->_attachment->ID, array( $width, $height ) );
		$expected_url = preg_replace( '~(\.\w+)$~i', "-{$width}x{$height}\$1", $this->_url );

		$I->amGoingTo( 'check direct image size without crop' );
		$I->assertNotEmpty( $image );
		$I->assertEquals( $expected_url, current( $image ) );

		// with crop
		$image = wp_get_attachment_image_src( $this->_attachment->ID, array( $width, $height, true ) );
		$expected_url = preg_replace( '~(\.\w+)$~i', "-{$width}x{$height}c\$1", $this->_url );

		$I->amGoingTo( 'check direct image size with crop' );
		$I->assertNotEmpty( $image );
		$I->assertEquals( $expected_url, current( $image ) );
	}

}