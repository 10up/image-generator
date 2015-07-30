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

class Client {

	/**
	 * Attaches hooks for actions and filters.
	 *
	 * @since 1.1.0
	 *
	 * @access public
	 */
	public function attach() {
		// setup action hooks
		add_action( 'wp_ajax_generate_image', array( $this, 'generate_image' ) );
		add_action( 'wp_ajax_nopriv_generate_image', array( $this, 'generate_image' ) );

		// setup filter hooks
		add_filter( 'image_downsize', array( $this, 'get_image_downsize' ), 10, 3 );
	}

	/**
	 * Unregisters attached hooks.
	 *
	 * @since 1.1.0
	 *
	 * @access public
	 */
	public function detach() {
		// unregister action hooks
		remove_action( 'wp_ajax_generate_image', array( $this, 'generate_image' ) );
		remove_action( 'wp_ajax_nopriv_generate_image', array( $this, 'generate_image' ) );

		// unregister filter hooks
		remove_filter( 'image_downsize', array( $this, 'get_image_downsize' ), 10, 3 );
	}

	/**
	 * Sends 404 not found response and exits.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function _send_not_found() {
		if ( ! headers_sent() ) {
			status_header( 404 );
		}
		exit;
	}

	/**
	 * Returns size dimensions.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 * @global array $_wp_additional_image_sizes The array of additional sizes.
	 * @param string $size The size name.
	 * @return array The size dimensions if size has been found, otherwise FALSE.
	 */
	protected function _get_size_dinmensions( $size ) {
		global $_wp_additional_image_sizes;

		// do nothing if size is already an array with dinemnsions
		if ( is_array( $size ) ) {
			return $size;
		}

		// if it is standard size, then just read width from options table
		if ( in_array( $size, array( 'thumbnail', 'medium', 'large' ) ) ) {
			return array(
				get_option( $size . '_size_w' ),
				get_option( $size . '_size_h' ),
				get_option( $size . '_crop' ),
			);
		}

		// if it is additional size, then return width from sizes array
		if ( isset( $_wp_additional_image_sizes[$size] ) ) {
			return array_values( $_wp_additional_image_sizes[$size] );
		}

		return false;
	}

	/**
	 * Generates missed image and sends it into reposnose.
	 *
	 * @since 1.0.0
	 * @action wp_ajax_generate_image
	 * @action wp_ajax_nopriv_generate_image
	 *
	 * @access public
	 */
	public function generate_image() {
		$matches = array();
		$image = current( explode( '?', $_SERVER['REQUEST_URI'] ) );

		// do nothing if referer empty or from else site
		$referer = wp_get_referer();
		$referer_host = false;
		if ( ! empty( $referer ) ) {
			$referer_host = parse_url( $referer, PHP_URL_HOST );
		}

		if ( empty( $referer ) || $referer_host != parse_url( home_url(), PHP_URL_HOST ) ) {
			$this->_send_not_found();
		}

		// do nothing if image doesn't have dimensions
		if ( ! preg_match( '~.*?\-(\d+)x(\d+)?(c|\:\w+x\w+)?(\.\w+)$~', $image, $matches ) ) {
			$this->_send_not_found();
		}

		// extract image, dimensions and extension
		list( $image, $width, $height, $crop, $extension ) = $matches;

		// prepare width and height
		$width = intval( $width );
		$height = intval( $height );

		// prepare crop settings
		if ( ! empty( $crop ) ) {
			if ( 'c' == $crop ) {
				$crop = true;
			} else {
				$crop = explode( 'x', ltrim( $crop, ':' ) );
			}
		} else {
			$crop = false;
		}

		// grab provider name, use Standard provider by default
		$provider = ucfirst( filter_input( INPUT_GET, 'provider' ) );
		if ( empty( $provider ) ) {
			$provider = 'Standard';
		}

		// create provider instance
		$provider = "\TENUP\ImageGenerator\Provider\\{$provider}";
		if ( ! class_exists( $provider ) ) {
			$this->_send_not_found();
		} else {
			$provider = new $provider();
		}

		// generate and send image
		$provider = new \TENUP\ImageGenerator\Provider\Standard();
		$generated = $provider->generate( $image, $width, $height, $crop, $extension );
		if ( ! $generated || ! $provider->send() ) {
			$this->_send_not_found();
		}

		exit;
	}

	/**
	 * Returns image downsize even if it hasn't been added to the image meta list.
	 *
	 * @since 1.0.0
	 * @filter image_downsize
	 *
	 * @access public
	 * @param array $downsize The initial information about image downsize.
	 * @param int $image_id The image id.
	 * @param array|string $size The image size.
	 * @return array|boolean The proper image downsize on success, otherwise initial value.
	 */
	public function get_image_downsize( $downsize, $image_id, $size ) {
		$img_url = wp_get_attachment_url( $image_id );
		$meta = wp_get_attachment_metadata( $image_id );
		if ( empty( $size ) || empty( $meta['width'] ) || empty( $meta['height'] ) ) {
			return $downsize;
		}

		$dimensions = $this->_get_size_dinmensions( $size );
		if ( ! $dimensions ) {
			return $downsize;
		}

		if ( ! isset( $dimensions[2] ) ) {
			$dimensions[2] = false;
		}

		list( $width, $height, $crop ) = $dimensions;
		if ( empty( $width ) && empty( $height ) ) {
			return $downsize;
		}

		$had_empty_width = false;
		if ( empty( $width ) ) {
			$had_empty_width = true;
			$width = empty( $crop ) ? ceil( $height * $meta['width'] / $meta['height'] ) : $height;
		} elseif ( empty( $height ) ) {
			$height = empty( $crop ) ? ceil( $width * $meta['height'] / $meta['width'] ) : $width;
		}

		if ( !empty( $crop ) ) {
			if ( is_array( $crop ) && count( $crop ) >= 2 ) {
				$crop = array_values( $crop );
				$crop = ":{$crop[0]}x{$crop[1]}";
			} else {
				$crop = 'c';
			}

			if ( empty( $height ) ) {
				$height = $width;
			}
		} elseif ( !$had_empty_width ) {
			$height = ceil( $width * $meta['height'] / $meta['width'] );
		}

		$meta_size = is_array( $size ) ? sha1( serialize( $size ) ) : $size;
		if ( empty( $meta['sizes'][ $meta_size ] ) ) {
			$meta['sizes'][ $meta_size ] = array(
				'file'      => preg_replace( '~(\.\w+)$~', "-{$width}x{$height}{$crop}$1", basename( $img_url ) ),
				'width'     => $width,
				'height'    => (int) $height,
				'mime-type' => get_post_mime_type( $image_id ),
			);

			wp_update_attachment_metadata( $image_id, $meta );
		}

		return array(
			preg_replace( '~(\.\w+)$~', "-{$width}x{$height}{$crop}$1", $img_url ),
			$width,
			$height,
			!empty( $crop ),
		);
	}

}