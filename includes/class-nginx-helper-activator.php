<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      2.0.0
 * @link       https://rtcamp.com/nginx-helper/
 *
 * @package    nginx-helper
 * @subpackage nginx-helper/includes
 *
 * @author     rtCamp
 */

/**
 * Class Nginx_Helper_Activator
 */
class Nginx_Helper_Activator {

	/**
	 * Create log directory. Add capability of nginx helper.
	 *
	 * @since    2.0.0
	 *
	 * @global Nginx_Helper_Admin $nginx_helper_admin
	 */
	public static function activate() {

		global $nginx_helper_admin;

		$path = $nginx_helper_admin->functional_asset_path();

		if ( ! is_dir( $path ) ) {
			mkdir( $path );
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$role = get_role( 'administrator' );

		if ( empty( $role ) ) {

			update_site_option(
				'rt_wp_nginx_helper_init_check',
				__( 'Sorry, you need to be an administrator to use Nginx Helper', 'nginx-helper' )
			);

			return;

		}

		$role->add_cap( 'Nginx Helper | Config' );
		$role->add_cap( 'Nginx Helper | Purge cache' );

	}

}
