<?php
/**
 * Contains class for WP-CLI command.
 *
 * @since      2.0.0
 * @package    nginx-helper
 */

/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Nginx_Helper_WP_CLI_Command' ) ) {

	/**
	 * Purge site cache from Nginx.
	 */
	class Nginx_Helper_WP_CLI_Command extends WP_CLI_Command {

		/**
		 * Subcommand to purge all cache from Nginx
		 *
		 * Examples:
		 * wp nginx-helper purge-all
		 *
		 * @subcommand purge-all
		 *
		 * @param array $args Arguments.
		 * @param array $assoc_args Arguments in associative array.
		 */
		public function purge_all( $args, $assoc_args ) {

			global $nginx_purger;

			$nginx_purger->purge_all();

			$message = __( 'Purged Everything!', 'nginx-helper' );
			WP_CLI::success( $message );

		}

	}

}
