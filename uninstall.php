<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://rtcamp.com/nginx-helper/
 * @since      2.0.0
 *
 * @package    nginx-helper
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove the Cloudflare settings (they include the API token) and the leftovers of the Cloudflare purge notices.
delete_site_option( 'easyengine_cache_manager_cf_settings' );
delete_site_transient( 'ec_cf_purge_failure' );

// On multisite every site can also have its own Cloudflare settings.
if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $ec_blog_id ) {
		switch_to_blog( $ec_blog_id );
		delete_option( 'easyengine_cache_manager_cf_site_settings' );
		delete_transient( 'ec_page_rule_save_state_admin_notice' );
		restore_current_blog();
	}
} else {
	delete_option( 'easyengine_cache_manager_cf_site_settings' );
	delete_transient( 'ec_page_rule_save_state_admin_notice' );
}
