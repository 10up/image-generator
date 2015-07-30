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

namespace TENUP\ImageGenerator;

abstract class Provider {

	/**
	 * Generates an image and saves it in an appropriate place.
	 *
	 * @since 1.1.0
	 *
	 * @abstract
	 * @access public
	 * @param string $image The image URL.
	 * @param int $width The image width.
	 * @param int $height The image height.
	 * @param bool|array $crop Determines whether or not to crop the image. If passed array, will be used to determine cropping center.
	 * @param string $extension The image extension.
	 * @return boolean TRUE on success, otherwise FALSE.
	 */
	public abstract function generate( $image, $width, $height, $crop, $extension );

	/**
	 * Sends image to browser.
	 *
	 * @since 1.1.0
	 *
	 * @access public
	 * @return boolean TRUE on sucess, otherwise FALSE.
	 */
	public abstract function send();

	/**
	 * Returns requested image URL.
	 *
	 * @since 1.1.0
	 *
	 * @access public
	 * @return string The image URL.
	 */
	public function get_image_url() {
		return current( explode( '?', $_SERVER['REQUEST_URI'] ) );
	}

	/**
	 * Parses image and returns size information.
	 *
	 * @since 1.1.0
	 *
	 * @access public
	 * @param string $image The image URL.
	 * @return array|false The size information on success, otherwise FALSE.
	 */
	public function parse_image( $image ) {
		$matches = array();
		$pattern = '~.*?\-(\d+)x(\d+)?(c|\:\w+x\w+)?(\.\w+)$~';

		return preg_match( $pattern, $image, $matches ) ? $matches : false;
	}

}