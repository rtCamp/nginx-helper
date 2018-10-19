<?php
/**
 * Contains Nginx_Helper_Deactivator class.
 *
 * @package    nginx-helper
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      2.0.0
 * @link       https://rtcamp.com/nginx-helper/
 *
 * @package    nginx-helper
 * @subpackage nginx-helper/includes
 *
 * @author     rtCamp
 */
class Nginx_Helper_Deactivator {

	/**
	 * Schedule event to check log file size daily. Remove nginx helper capability.
	 *
	 * @since    2.0.0
	 */
	public static function deactivate() {

		wp_clear_scheduled_hook( 'rt_wp_nginx_helper_check_log_file_size_daily' );

		$role = get_role( 'administrator' );
		$role->remove_cap( 'Nginx Helper | Config' );
		$role->remove_cap( 'Nginx Helper | Purge cache' );

	}

}
