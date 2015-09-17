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

class S3Uploads extends \TENUP\ImageGenerator\Provider\Aws {

	/**
	 * Generates an image and saves it in an appropriate place.
	 *
	 * @since 1.2.0
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
		// use Aws implementation if S3_Uploads class is not found
		if ( ! class_exists( 'S3_Uploads' ) && defined( 'S3_UPLOADS_BUCKET' ) && ! empty( S3_UPLOADS_BUCKET ) ) {
			return parent::generate( $image, $width, $height, $crop, $extension );
		}

		try {
			$s3_uploads = \S3_Uploads::get_instance();
			$s3_uploads->tear_down();

			$s3 = $s3_uploads->s3();
			$bucket = strtok( S3_UPLOADS_BUCKET, '/' );

			try {
				// check if image already created and return false if it exists
				$object = $s3->getObject( array( 'Bucket' => $bucket, 'Key' => $image ) );
				return false;
			} catch ( \Exception $e ) {
				// exception means that object doesn't exist yet and we need to generate it
			}

			$filename = wp_tempnam() . $extension;
			$original = $this->_get_original_image( $image, $width, $height, $crop, $extension );

			// download original file from S3 storage
			$object = $s3->getObject( array( 'Bucket' => $bucket, 'Key' => $original ) );
			file_put_contents( $filename, (string) $object->get( 'Body' ) );

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
					'Bucket'       => $bucket,
					'Key'          => $image,
					'ACL'          => 'public-read',
					'Body'         => file_get_contents( $saved['path'] ),
					'ContentType'  => $saved['mime-type'],
					'StorageClass' => 'STANDARD',
				) );

				return true;
			}
		} catch ( \Exception $e ) {
		}

		return false;
	}

}