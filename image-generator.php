<?php
/**
 * Plugin Name: Image Generator
 * Plugin URI: https://github.com/10up/Image-Generator
 * Description: Generates images on the fly.
 * Author: 10up Inc
 * Author URI: https://10up.com/
 * Version: 1.0.0
 * License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

/**
 * Sends 404 not found response and exits.
 */
function _tenup_ig_send_not_found() {
	if ( ! headers_sent() ) {
		status_header( 404 );
	}
	exit;
}

/**
 * Returns size dimensions.
 *
 * @global array $_wp_additional_image_sizes The array of additional sizes.
 * @param string $size The size name.
 * @return array The size dimensions if size has been found, otherwise FALSE.
 */
function _tenup_ig_get_size_dinmensions( $size ) {
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
	if ( isset( $_wp_additional_image_sizes[ $size ] ) ) {
		return array_values( $_wp_additional_image_sizes[ $size ] );
	}

	return false;
}

/**
 * Generates missed image and sends it into reposnose.
 */
function tenup_ig_generate_image() {
	$matches = array();
	$image = current( explode( '?', $_SERVER['REQUEST_URI'] ) );

	// do nothing if referer empty or from else site
	$referer = wp_get_referer();
	$referer_host = false;
	if ( ! empty( $referer ) ) {
		$referer_host = parse_url( $referer, PHP_URL_HOST );
	}

	if ( empty( $referer ) || $referer_host != parse_url( home_url(), PHP_URL_HOST ) ) {
		_tenup_ig_send_not_found();
	}

	// do nothing if image doesn't have dimensions
	if ( ! preg_match( '~.*?\-(\d+)x(\d+)?(c|\:\w+x\w+)?(\.\w+)$~', $image, $matches ) ) {
		_tenup_ig_send_not_found();
	}

	// extract image, dimensions and extension
	list( $image, $width, $height, $crop, $extension ) = $matches;

	// build paths to original and requested images
	$uploads = wp_upload_dir();
	$rel_path = str_replace( parse_url( $uploads['baseurl'], PHP_URL_PATH ), '', $image );

	$requested_image = $uploads['basedir'] . $rel_path;

	$original_image = str_replace( "-{$width}x{$height}{$crop}{$extension}", $extension, $rel_path );
	$original_image = $uploads['basedir'] . $original_image;

	// generate requested image
	$editor = wp_get_image_editor( $original_image );
	if ( is_wp_error( $editor ) ) {
		_tenup_ig_send_not_found();
	}

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

	// resize image
	add_filter( 'image_resize_dimensions', 'tenup_ig_get_resize_dimensions', 10, 6 );
	$resized = $editor->resize( $width, $height, $crop );
	if ( is_wp_error( $resized ) ) {
		_tenup_ig_send_not_found();
	}

	// save to disk
	$editor->save( $requested_image );

	// smash generated image

	// currently it is not clear how to do it to achive the best results,
	// probably we could use Kraken.io or something similar, the perfect
	// tool for this is Google PageSpeed module for Nginx, but I'm not sure
	// if WPEngine supports it...
	//
	// https://kraken.io/docs/getting-started


	// send it in the response
	$editor->stream();
	exit;
}
add_action( 'wp_ajax_generate_image', 'tenup_ig_generate_image' );
add_action( 'wp_ajax_nopriv_generate_image', 'tenup_ig_generate_image' );

/**
 * Returns image downsize even if it hasn't been added to the image meta list.
 *
 * @param array $downsize The initial information about image downsize.
 * @param int $image_id The image id.
 * @param array|string $size The image size.
 * @return array|boolean The proper image downsize on success, otherwise initial value.
 */
function tenup_ig_get_image_downsize( $downsize, $image_id, $size ) {
	$img_url = wp_get_attachment_url( $image_id );
	$meta = wp_get_attachment_metadata( $image_id );
	if ( empty( $size ) || empty( $meta['width'] ) || empty( $meta['height'] ) ) {
		return $downsize;
	}

	$dimensions = _tenup_ig_get_size_dinmensions( $size );
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
		$width = empty( $crop )
			? ceil( $height * $meta['width'] / $meta['height'] )
			: $height;
	} elseif ( empty( $height ) ) {
		$height = empty( $crop )
			? ceil( $width * $meta['height'] / $meta['width'] )
			: $width;
	}

	if ( ! empty( $crop ) ) {
		if ( is_array( $crop ) && count( $crop ) >= 2 ) {
			$crop = array_values( $crop );
			$crop = ":{$crop[0]}x{$crop[1]}";
		} else {
			$crop = 'c';
		}

		if ( empty( $height ) ) {
			$height = $width;
		}
	} elseif ( ! $had_empty_width ) {
		$height = ceil( $width * $meta['height'] / $meta['width'] );
	}

	return array(
		preg_replace( '~(\.\w+)$~', "-{$width}x{$height}{$crop}$1", $img_url ),
		$width,
		$height,
		! empty( $crop ),
	);
}
add_filter( 'image_downsize', 'tenup_ig_get_image_downsize', 10, 3 );

/**
 * Returns proper resize dimentions.
 *
 * @param array|null $dimensions The incoming dimensions.
 * @param int $orig_w The original width.
 * @param int $orig_h The original height.
 * @param int $dest_w The destination width.
 * @param int $dest_h The destination height.
 * @param boolean|array $crop Determines whether or not we should crop an image.
 * @return array Dimensions array.
 */
function tenup_ig_get_resize_dimensions( $dimensions, $orig_w, $orig_h, $dest_w, $dest_h, $crop ) {
	if ( $crop ) {
		// crop the largest possible portion of the original image that we can size to $dest_w x $dest_h
		$aspect_ratio = $orig_w / $orig_h;
		$new_w = min( $dest_w, $orig_w );
		$new_h = min( $dest_h, $orig_h );

		if ( ! $new_w ) {
			$new_w = (int) round( $new_h * $aspect_ratio );
		}

		if ( ! $new_h ) {
			$new_h = (int) round( $new_w / $aspect_ratio );
		}

		$size_ratio = max( $new_w / $orig_w, $new_h / $orig_h );

		$crop_w = round( $new_w / $size_ratio );
		$crop_h = round( $new_h / $size_ratio );
		if ( ! is_array( $crop ) || count( $crop ) !== 2 ) {
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