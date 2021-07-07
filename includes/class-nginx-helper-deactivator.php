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
	 * Remove nginx helper capability.
	 *
	 * @since    2.0.0
	 */
	public static function deactivate() {

		$role = get_role( 'administrator' );
		$role->remove_cap( 'Nginx Helper | Config' );
		$role->remove_cap( 'Nginx Helper | Purge cache' );

	}

}
