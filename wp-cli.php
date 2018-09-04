<?php
/**
 * Contains class for WP-CLI command.
 */

/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Nginx_Helper_WP_CLI_Command' ) ) {

	class Nginx_Helper_WP_CLI_Command extends WP_CLI_Command {

		/**
		 * Subcommand to purge all cache from Nginx
		 *
		 * Examples:
		 * wp nginx-helper purge-all
		 *
		 * @subcommand purge-all
		 */
		public function purge_all( $args, $assoc_args ) {

			global $nginx_purger;

			$nginx_purger->purgeAll();

			$message = __( 'Purged Everything!' );
			WP_CLI::success( $message );

		}

	}

}
