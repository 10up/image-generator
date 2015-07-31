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
		// do nothing if access key and secret are not provided
		if ( ! defined( 'AWS_ACCESS_KEY_ID' ) || ! defined( 'AWS_SECRET_ACCESS_KEY' ) || ! defined( 'AWS_S3_BUCKET' ) ) {
			return false;
		}

		// load libraries
		require_once TENUP_IMAGEGENERATOR_ABSPATH . '/includes/Aws/functions.php';
		require_once TENUP_IMAGEGENERATOR_ABSPATH . '/includes/GuzzleHttp/functions.php';
		require_once TENUP_IMAGEGENERATOR_ABSPATH . '/includes/GuzzleHttp/Psr7/functions.php';
		require_once TENUP_IMAGEGENERATOR_ABSPATH . '/includes/GuzzleHttp/Promise/functions.php';
		require_once TENUP_IMAGEGENERATOR_ABSPATH . '/includes/JmesPath/JmesPath.php';

		try {
			$s3 = new \Aws\S3\S3Client( array(
				'version' => 'latest',
				'region'  => AWS_S3_REGION,
				'credentials' => array(
					'key'    => AWS_ACCESS_KEY_ID,
					'secret' => AWS_SECRET_ACCESS_KEY,
				),
			) );

			try {
				// check if image already created and return false if it exists
				$object = $s3->getObject( array( 'Bucket' => AWS_S3_BUCKET, 'Key' => $image ) );
				return false;
			} catch ( \Exception $e ) {
				// exception means that object doesn't exist yet and we need to generate it
			}

			$filename = wp_tempnam();
			$original = $this->_get_original_image( $image, $width, $height, $crop, $extension );

			// download original file from S3 storage
			$object = $s3->getObject( array( 'Bucket' => AWS_S3_BUCKET, 'Key' => $original ) );
			file_put_contents( $filename, $object->get( 'Body' )->getContents() );

			// init image editor
			$this->_editor = wp_get_image_editor( $filename );
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
			$saved = $this->_editor->save();
			if ( ! is_wp_error( $saved ) ) {
				// save to S3 storage
				$s3->putObject( array(
					'Bucket'      => AWS_S3_BUCKET,
					'Key'         => $image,
					'ACL'         => 'public-read',
					'Body'        => file_get_contents( $saved['path'] ),
					'ContentType' => $saved['mime-type'],
				) );

				return true;
			}
		} catch( \Exception $e ) {
		}

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