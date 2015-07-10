<?php
/**
 * @author: Saurabh Shukla <saurabh.shukla@rtcamp.com>
 *
 * Parts of code based off http://wordpress.org/extend/plugins/nginx-manager/ by http://profiles.wordpress.org/hpatoio/ and http://profiles.wordpress.org/rukbat/
 */
namespace rtCamp\WP\Nginx {
	if ( preg_match( '#' . basename( __FILE__ ) . '#', $_SERVER[ 'PHP_SELF' ] ) ) {
		die( 'You are not allowed to call this page directly.' );
	}

	function rt_wp_nginx_helper_install() {
		global $wp_roles, $rt_wp_nginx_helper;

                if ( ! current_user_can( 'activate_plugins' ) ) {
                    return;
                }

		$role = get_role( 'administrator' );

		if ( empty( $role ) ) {
			update_site_option( "rt_wp_nginx_helper_init_check", __( 'Sorry, you need to be an administrator to use Nginx Helper', 'nginx-helper' ) );
			return;
		}

		$role->add_cap( 'Nginx Helper | Config' );
		$role->add_cap( 'Nginx Helper | Purge cache' );

		$rt_wp_nginx_helper_get_options = get_site_option( 'rt_wp_nginx_helper_global_options' );

		if ( empty( $rt_wp_nginx_helper_get_options ) ) {
			$rt_wp_nginx_helper_get_options = rt_wp_nginx_helper_get_options();
			update_site_option( "rt_wp_nginx_helper_global_options", $rt_wp_nginx_helper_get_options );
		}

		if ( is_multisite() ) {
			$blogs = get_blogs_of_user( true );
			foreach ( $blogs as $b ) {
				$rt_wp_nginx_helper_options = get_blog_option( $b->userblog_id, 'rt_wp_nginx_helper_options' );
				if ( empty( $rt_wp_nginx_helper_options ) ) {
					$rt_wp_nginx_helper_options = rt_wp_nginx_helper_get_options();
					update_blog_option( $b->userblog_id, "rt_wp_nginx_helper_options", $rt_wp_nginx_helper_options );
				}
			}
		} else {
			$rt_wp_nginx_helper_options = get_option( 'rt_wp_nginx_helper_options' );
			if ( empty( $rt_wp_nginx_helper_options ) ) {
				$rt_wp_nginx_helper_options = rt_wp_nginx_helper_get_options();
				update_option( "rt_wp_nginx_helper_options", $rt_wp_nginx_helper_options );
			}
		}
		wp_schedule_event( time(), 'daily', 'rt_wp_nginx_helper_check_log_file_size_daily' );
	}

	function rt_wp_nginx_helper_uninstall() {
		wp_clear_scheduled_hook( 'rt_wp_nginx_helper_check_log_file_size_daily' );
		delete_site_option( 'rt_wp_nginx_helper_options' );
		rt_wp_nginx_helper_remove_capability( 'Nginx Helper | Config' );
		rt_wp_nginx_helper_remove_capability( 'Nginx Helper | Purge cache' );
	}

	function rt_wp_nginx_helper_remove_capability( $capability ) {
		$check_order = array( "subscriber", "contributor", "author", "editor", "administrator" );

		foreach ( $check_order as $role ) {
			$role = get_role( $role );
			$role->remove_cap( $capability );
		}
	}

	function rt_wp_nginx_helper_get_options() {
		$rt_wp_nginx_helper_get_options = array( );
		$rt_wp_nginx_helper_get_options[ 'log_level' ] = 'INFO';
		$rt_wp_nginx_helper_get_options[ 'log_filesize' ] = 5;

		$rt_wp_nginx_helper_get_options[ 'enable_purge' ] = 0;
		$rt_wp_nginx_helper_get_options[ 'enable_map' ] = 0;
		$rt_wp_nginx_helper_get_options[ 'enable_log' ] = 0;
		$rt_wp_nginx_helper_get_options[ 'enable_stamp' ] = 0;

		$rt_wp_nginx_helper_get_options[ 'purge_homepage_on_new' ] = 1;
		$rt_wp_nginx_helper_get_options[ 'purge_homepage_on_edit' ] = 1;
		$rt_wp_nginx_helper_get_options[ 'purge_homepage_on_del' ] = 1;

		$rt_wp_nginx_helper_get_options[ 'purge_archive_on_new' ] = 1;
		$rt_wp_nginx_helper_get_options[ 'purge_archive_on_edit' ] = 1;
		$rt_wp_nginx_helper_get_options[ 'purge_archive_on_del' ] = 1;

		$rt_wp_nginx_helper_get_options[ 'purge_archive_on_new_comment' ] = 0;
		$rt_wp_nginx_helper_get_options[ 'purge_archive_on_deleted_comment' ] = 0;

		$rt_wp_nginx_helper_get_options[ 'purge_page_on_mod' ] = 1;
		$rt_wp_nginx_helper_get_options[ 'purge_page_on_new_comment' ] = 1;
		$rt_wp_nginx_helper_get_options[ 'purge_page_on_deleted_comment' ] = 1;

		$rt_wp_nginx_helper_get_options[ 'purge_method' ] = 'get_request';

		return $rt_wp_nginx_helper_get_options;
	}
}