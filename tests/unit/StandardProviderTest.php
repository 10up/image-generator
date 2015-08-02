<?php

namespace TENUP\ImageGenerator\Tests;

class StandardProviderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * The instance of standard provider.
	 *
	 * @access protected
	 * @var \TENUP\ImageGenerator\Provider\Standard
	 */
	protected $_provider;

	/**
	 * The instance of faker generator class.
	 *
	 * @access protected
	 * @var \Faker\Generator
	 */
	protected $_faker;

	/**
	 * Sets up environment for each test.
	 *
	 * @access protected
	 */
	protected function setUp() {
		$this->_provider = new \TENUP\ImageGenerator\Provider\Standard();
		$this->_faker = \Faker\Factory::create();
	}

	/**
	 * Cleans up environment after each test.
	 *
	 * @access protected
	 */
	protected function tearDown() {
		$this->_provider = null;
		$this->_faker = null;
	}

	/**
	 * Tests Provider::get_image_url() method.
	 *
	 * @access public
	 * @see \TENUP\ImageGenerator\Provider::get_image_url()
	 */
	public function testGetImageUrl() {
		$url = parse_url( $this->_faker->url, PHP_URL_PASS );

		// url without query string
		$_SERVER['REQUEST_URI'] = $url;
		$image_url = $this->_provider->get_image_url();
		$this->assertEquals( $url, $image_url, 'URL without query string parsed incorrectly.' );

		// url with query string
		$_SERVER['REQUEST_URI'] = "{$url}?a=1&b=2";
		$image_url = $this->_provider->get_image_url();
		$this->assertEquals( $url, $image_url, 'URL with query string parsed incorrectly.' );
	}

	/**
	 * Tests Provider::parse_image() method.
	 *
	 * @access public
	 * @see \TENUP\ImageGenerator\Provider::parse_image()
	 */
	public function testParseImage() {
		$width = rand( 100, 200 );
		$height = rand( 100, 200 );
		$extension = '.jpg';
		$horizontal = rand( 1, 100 ) % 2 == 1 ? 'left' : 'right';
		$vertical = rand( 1, 100 ) %2 == 1 ? 'top' : 'bottom';

		// test original image
		$url = 'http://example.com/wp-content/uploads/2015/08/test-image.jpg';
		$parsed = $this->_provider->parse_image( $url );
		$this->assertFalse( $parsed, 'Images without dimensions should return FALSE.' );

		// test image without crop
		$url = "http://example.com/wp-content/uploads/2015/08/test-image-{$width}x{$height}{$extension}";
		$parsed = $this->_provider->parse_image( $url );
		$this->assertNotFalse( $parsed, 'Images with dimensions should not return FALSE.' );
		$this->assertCount( 5, $parsed, 'Properly parsed image should return array with 5 elements.' );
		$this->assertEquals( $url, $parsed[0], 'The first parameter should equal to incoming url.' );
		$this->assertEquals( $width, $parsed[1], 'The second parameter should contain image width.' );
		$this->assertEquals( $height, $parsed[2], 'The third parameter should contain image height.' );
		$this->assertEmpty( $parsed[3], 'The fourth parameter should be empty for image without crop.' );
		$this->assertEquals( $extension, $parsed[4], 'The fifth parameter should contain image extension.' );

		// test image with simple crop
		$url = "http://example.com/wp-content/uploads/2015/08/test-image-{$width}x{$height}c{$extension}";
		$parsed = $this->_provider->parse_image( $url );
		$this->assertNotFalse( $parsed, 'Images with dimensions should not return FALSE.' );
		$this->assertEquals( 'c', $parsed[3], 'The fourth parameter should not be empty for image with crop.' );

		// test image with advanced crop
		$url = "http://example.com/wp-content/uploads/2015/08/test-image-{$width}x{$height}:{$horizontal}x{$vertical}{$extension}";
		$parsed = $this->_provider->parse_image( $url );
		$this->assertNotFalse( $parsed, 'Images with dimensions should not return FALSE.' );
		$this->assertEquals( ":{$horizontal}x{$vertical}", $parsed[3], 'The fourth parameter should not be empty for image with crop.' );
	}

}