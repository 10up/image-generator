<?php

// +----------------------------------------------------------------------+
// | Copyright 2015 10up Inc                                              |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

namespace TENUP\ImageGenerator\Provider;

class Aws extends \TENUP\ImageGenerator\Provider {

	/**
	 * Generates an image and saves it in an appropriate place.
	 *
	 * @since 1.1.0
	 *
	 * @access public
	 * @param string $image The image URL.
	 * @param int $width The image width.
	 * @param int $height The image height.
	 * @param bool|array $crop Determines whether or not to crop the image. If passed array, will be used to determine cropping center.
	 * @param string $extension The image extension.
	 * @return boolean TRUE on success, otherwise FALSE.
	 */
	public function generate( $image, $width, $height, $crop, $extension ) {
		require_once TENUP_IMAGEGENERATOR_ABSPATH . '/includes/Aws/aws.phar';

		return false;
	}

	/**
	 * Sends image to browser.
	 *
	 * @since 1.1.0
	 *
	 * @access public
	 * @return boolean TRUE on sucess, otherwise FALSE.
	 */
	public function send() {
		return false;
	}

	/**
	 * Returns requested image URL.
	 *
	 * @since 1.1.0
	 *
	 * @access public
	 * @return string The image URL.
	 */
	public function get_image_url() {
		return filter_input( INPUT_GET, 'image' );
	}

}