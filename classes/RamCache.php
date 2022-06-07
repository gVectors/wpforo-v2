<?php

namespace wpforo\classes;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class RamCache {
	/**
	 * @var array
	 */
	private static $ram_cache;

	/**
	 * wpForoRamCache constructor.
	 */
	public function __construct() {
		$this->reset();
	}

	/**
	 * set empty array to static $ram_cache
	 *
	 * @param mixed $key
	 *
	 * @return void
	 */
	public function reset( $key = null ) {
		if( is_null( $key ) ) {
			self::$ram_cache = [];
		} else {
			unset( self::$ram_cache[ $this->fix_key( $key ) ] );
		}
	}

	/**
	 * @param mixed $key
	 *
	 * @return string
	 */
	private function fix_key( $key ) {
		if( ! is_scalar( $key ) ) $key = json_encode( $key );

		return md5( $key );
	}

	/**
	 * checking if this data already cached
	 *
	 * @param mixed $key unique key
	 *
	 * @return bool
	 */
	public function exists( $key ) {
		return array_key_exists( $this->fix_key( $key ), self::$ram_cache );
	}

	/**
	 * return already cached data
	 *
	 * @param mixed $key unique key
	 *
	 * @return mixed
	 */
	public function get( $key ) {
		if( $this->exists( $key ) ) {
			return self::$ram_cache[ $this->fix_key( $key ) ];
		} else {
			return null;
		}
	}

	/**
	 * storing a cache of provided data
	 *
	 * @param mixed $key unique key
	 * @param mixed $data
	 */
	public function set( $key, $data ) {
		self::$ram_cache[ $this->fix_key( $key ) ] = $data;
	}

	/**
	 * call callable function and return function returned value
	 * and store in static property for next call
	 *
	 * @param callable $func
	 * @param mixed ... $_, ... [optional] call_user_func parameters
	 *
	 * @return mixed
	 */
	public function call_user_func( $func ) {
		if( ! is_callable( $func, false, $callable_name ) ) return null;
		$args = func_get_args();
		array_shift( $args );
		$key = [ $callable_name, $args ];
		if( $this->exists( $key ) ) return $this->get( $key );
		$data = call_user_func_array( $func, $args );
		$this->set( $key, $data );

		return $data;
	}
}
