<?php
/**
 * Created by PhpStorm.
 * User: udit
 * Date: 19/3/15
 * Time: 2:06 PM
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
			global $rt_wp_nginx_purger;
			$rt_wp_nginx_purger->true_purge_all();
			$message = __( 'Purged Everything!' );
			WP_CLI::success( $message );
		}

	}

}