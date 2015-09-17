<?php
/**
 * Plugin Name: Image Generator
 * Plugin URI: https://github.com/10up/Image-Generator
 * Description: Generates images on the fly.
 * Author: 10up Inc
 * Author URI: https://10up.com/
 * Version: 1.1.0
 * License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

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

// do nothing if PHP version is less than 5.5
if ( version_compare( PHP_VERSION, '5.5', '<' ) ) {
	return;
}

// let's call self-invoked function to not pollute global namespace
call_user_func( function() {
	global $image_generator;

	// do nothing if image generator is already defined
	if ( ! empty( $image_generator ) ) {
		return;
	}

	// define constants
	define( 'TENUP_IMAGEGENERATOR_VERSION', '1.1.0' );
	define( 'TENUP_IMAGEGENERATOR_ABSPATH', __DIR__ );

	require_once 'includes/TENUP/ImageGenerator/Autoloader.php';

	// register autoloader
	$loader = new \TENUP\ImageGenerator\Autoloader();
	$loader->add_namespaces( 'TENUP\ImageGenerator' );
	$loader->register();

	// create a new instance of image generator and attach its hooks
	$image_generator = new \TENUP\ImageGenerator\Client();
	$image_generator->autoloader( $loader );
	$image_generator->attach();
} );