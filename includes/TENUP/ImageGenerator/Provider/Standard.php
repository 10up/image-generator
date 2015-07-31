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

class Standard extends \TENUP\ImageGenerator\Provider {

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
		// build paths to original and requested images
		$uploads = wp_upload_dir();
		$rel_path = str_replace( parse_url( $uploads['baseurl'], PHP_URL_PATH ), '', $image );

		$requested_image = $uploads['basedir'] . $rel_path;

		$original_image = $this->_get_original_image( $rel_path, $width, $height, $crop, $extension );
		$original_image = $uploads['basedir'] . $original_image;

		// generate requested image
		$this->_editor = wp_get_image_editor( $original_image );
		if ( is_wp_error( $this->_editor ) ) {
			return false;
		}

		// resize image
		add_filter( 'image_resize_dimensions', array( $this, 'get_resize_dimensions' ), 10, 6 );
		$resized = $this->_editor->resize( $width, $height, $crop );
		if ( is_wp_error( $resized ) ) {
			return false;
		}

		// save to disk
		$this->_editor->save( $requested_image );

		return true;
	}

}