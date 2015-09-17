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

class Autoloader {

	/**
	 * The array of allowed namespaces to load.
	 *
	 * @since 1.2.0
	 *
	 * @access protected
	 * @var array
	 */
	protected $_namespaces = array();

	/**
	 * Registers autoloader function.
	 *
	 * @since 1.2.0
	 *
	 * @access public
	 */
	public function register() {
		spl_autoload_register( array( $this, 'load_class' ) );
	}

	/**
	 * Unregisters autoloader function.
	 *
	 * @since 1.2.0
	 *
	 * @access public
	 */
	public function unregister() {
		spl_autoload_unregister( array( $this, 'load_class' ) );
	}

	/**
	 * Loads a class based on its fully qualified name.
	 *
	 * @since 1.2.0
	 *
	 * @access public
	 * @param string $class The class name.
	 * @return boolean TRUE on success, otherwise FALSE.
	 */
	public function load_class( $class ) {
		foreach ( $this->_namespaces as $namespace ) {
			if ( substr( $class, 0, strlen( $namespace ) ) != $namespace ) {
				continue;
			}

			$file = str_replace( array( '_', '\\' ), DIRECTORY_SEPARATOR, $class );
			$file = implode( DIRECTORY_SEPARATOR, array( TENUP_IMAGEGENERATOR_ABSPATH, 'includes', $file . '.php' ) );
			if ( is_readable( $file ) ) {
				require_once $file;
				return true;
			}
		}

		return false;
	}

	/**
	 * Registers new namespaces to load classes from.
	 *
	 * @since 1.2.0
	 *
	 * @access public
	 * @param array $namespaces The array of namespaces to register.
	 */
	public function add_namespaces( $namespaces ) {
		$namespaces = func_get_args();
		if ( count( $namespaces ) == 1 && is_array( $namespaces[0] ) ) {
			$namespaces = $namespaces[0];
		}

		foreach ( $namespaces as $namespace ) {
			if ( ! empty( $namespace ) ) {
				if ( ! is_array( $namespace ) ) {
					$this->_namespaces[] = $namespace;
				} else {
					$this->_namespaces = array_merge( $this->_namespaces, $namespace );
				}
			}
		}

		$this->_namespaces = array_unique( array_filter( array_map( 'trim', $this->_namespaces ) ) );
	}

}