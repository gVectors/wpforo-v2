<?php

use wpforo\wpforo;

spl_autoload_register( function( $namespace ) {
	if( strpos( $namespace, 'wpforo' ) === 0 || strpos( $namespace, 'go2wpforo' ) === 0 ) {
		$filepath = rtrim(
            trim(
	            str_replace(
					[ '/', '\\', '\\\\' ], DIRECTORY_SEPARATOR, WP_PLUGIN_DIR . "\\" . $namespace
	            )
            ),
            DIRECTORY_SEPARATOR
        ) . ".php";
		if( is_file( $filepath ) && is_readable( $filepath ) ) require_once $filepath;
	}
} );

/**
 * Main instance of wpForo.
 *
 * Returns the main instance of WPF to prevent the need to use globals.
 *
 * @return wpforo
 * @since  1.4.3
 */
if( ! function_exists( 'WPF' ) ) {
	function WPF() {
		return wpforo::instance();
	}
}

// Global for backwards compatibility.
$GLOBALS['wpforo'] = WPF();
