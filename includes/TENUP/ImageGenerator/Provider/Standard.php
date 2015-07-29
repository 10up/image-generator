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
	 * The editor instance.
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 * @var \WP_Image_Editor
	 */
	protected $_editor;

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

		$original_image = str_replace( "-{$width}x{$height}{$crop}{$extension}", $extension, $rel_path );
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

	/**
	 * Sends image to browser.
	 *
	 * @since 1.1.0
	 *
	 * @access public
	 * @return boolean TRUE on sucess, otherwise FALSE.
	 */
	public function send() {
		// return FALSE if editor is not created
		if ( ! $this->_editor || is_wp_error( $this->_editor ) ) {
			return false;
		}

		// smash generated image
		// currently it is not clear how to do it to achive the best results,
		// probably we could use Kraken.io or something similar, the perfect
		// tool for this is Google PageSpeed module for Nginx, but I'm not sure
		// if WPEngine supports it...
		//
		// https://kraken.io/docs/getting-started
		// send it in the response
		$this->_editor->stream();

		return true;
	}

	/**
	 * Returns proper resize dimentions.
	 *
	 * @since 1.0.0
	 * @filter image_resize_dimensions
	 *
	 * @access public
	 * @param array|null $dimensions The incoming dimensions.
	 * @param int $orig_w The original width.
	 * @param int $orig_h The original height.
	 * @param int $dest_w The destination width.
	 * @param int $dest_h The destination height.
	 * @param boolean|array $crop Determines whether or not we should crop an image.
	 * @return array Dimensions array.
	 */
	public function get_resize_dimensions( $dimensions, $orig_w, $orig_h, $dest_w, $dest_h, $crop ) {
		if ( $crop ) {
			// crop the largest possible portion of the original image that we can size to $dest_w x $dest_h
			$aspect_ratio = $orig_w / $orig_h;
			$new_w = min( $dest_w, $orig_w );
			$new_h = min( $dest_h, $orig_h );

			if ( !$new_w ) {
				$new_w = (int) round( $new_h * $aspect_ratio );
			}

			if ( !$new_h ) {
				$new_h = (int) round( $new_w / $aspect_ratio );
			}

			$size_ratio = max( $new_w / $orig_w, $new_h / $orig_h );

			$crop_w = round( $new_w / $size_ratio );
			$crop_h = round( $new_h / $size_ratio );
			if ( !is_array( $crop ) || count( $crop ) !== 2 ) {
				$crop = array( 'center', 'center' );
			}

			list( $x, $y ) = $crop;

			if ( 'left' === $x ) {
				$s_x = 0;
			} elseif ( 'right' === $x ) {
				$s_x = $orig_w - $crop_w;
			} else {
				$s_x = floor( ( $orig_w - $crop_w ) / 2 );
			}

			if ( 'top' === $y ) {
				$s_y = 0;
			} elseif ( 'bottom' === $y ) {
				$s_y = $orig_h - $crop_h;
			} else {
				$s_y = floor( ( $orig_h - $crop_h ) / 2 );
			}
		} else {
			// don't crop, just resize using $dest_w x $dest_h as a maximum bounding box
			$crop_w = $orig_w;
			$crop_h = $orig_h;

			$s_x = 0;
			$s_y = 0;

			list( $new_w, $new_h ) = wp_constrain_dimensions( $orig_w, $orig_h, $dest_w, $dest_h );
		}

		// the return array matches the parameters to imagecopyresampled()
		// int dst_x, int dst_y, int src_x, int src_y, int dst_w, int dst_h, int src_w, int src_h
		$dimensions = array( 0, 0, (int) $s_x, (int) $s_y, (int) $new_w, (int) $new_h, (int) $crop_w, (int) $crop_h );

		return $dimensions;
	}

}