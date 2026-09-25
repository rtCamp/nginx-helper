<?php
/**
 * Autoloader to load the files.
 *
 * @package nginx-helper
 */


/**
 * Registers the class autoloader.
 */
spl_autoload_register(
	function ( $class ) {
		$class = ltrim( $class, '\\' );
		if ( 0 !== stripos( $class, 'EasyCache\\' ) ) {
			return;
		}

		$parts = explode( '\\', $class );
		array_shift( $parts ); // Don't need "EasyCache".
		$last    = array_pop( $parts ); // File should be 'class-[...].php'.
		$last    = 'class-' . $last . '.php';
		$parts[] = $last;
		$file    = NGINX_HELPER_BASEPATH . 'includes/' . str_replace( '_', '-', strtolower( implode( '/', $parts ) ) );
		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);
