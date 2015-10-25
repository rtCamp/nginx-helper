<?php

/**
 * Fired during plugin activation
 *
 * @link       https://rtcamp.com/nginx-helper/
 * @since      2.0.0
 *
 * @package    nginx-helper
 * @subpackage nginx-helper/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      2.0.0
 * @package    nginx-helper
 * @subpackage nginx-helper/includes
 * @author     rtCamp
 */
class Nginx_Helper_Activator {

	/**
	 * Create log directory. Add capability of nginx helper.
     * Schedule event to check log file size daily. 
	 *
	 * @since    2.0.0
	 */
	public static function activate() {
        global $wp_roles, $nginx_helper_admin;
        
        $path = $nginx_helper_admin->get_log_path();
        if ( !is_dir( $path ) ) {
            mkdir( $path );
        }
        
        if ( !current_user_can( 'activate_plugins' ) ) {
            return;
        }

		$role = get_role( 'administrator' );

		if ( empty( $role ) ) {
			update_site_option(
                "rt_wp_nginx_helper_init_check",
                __( 'Sorry, you need to be an administrator to use Nginx Helper', 'nginx-helper' )
            );
			return;
		}

		$role->add_cap( 'Nginx Helper | Config' );
		$role->add_cap( 'Nginx Helper | Purge cache' );
        
        wp_schedule_event( time(), 'daily', 'rt_wp_nginx_helper_check_log_file_size_daily' );
    }
}
