<?php
/*
  Plugin Name: Nginx Helper
  Plugin URI: https://rtcamp.com/nginx-helper/
  Description: Cleans nginx's fastcgi/proxy cache or redis-cache whenever a post is edited/published. Also does few more things.
  Version: 1.9.5
  Author: rtCamp
  Author URI: https://rtcamp.com
  Text Domain: nginx-helper
  Requires at least: 3.0
  Tested up to: 4.2.3
 */

namespace rtCamp\WP\Nginx {
	define( 'rtCamp\WP\Nginx\RT_WP_NGINX_HELPER_PATH', plugin_dir_path( __FILE__ ) );
	define( 'rtCamp\WP\Nginx\RT_WP_NGINX_HELPER_URL', plugin_dir_url( __FILE__ ) );

	class Helper {

		var $minium_WP = '3.0';
		var $options = null;
		var $plugin_name = 'nginx-helper';

		const WP_CLI_COMMAND = 'nginx-helper';

		function __construct()
		{

			if ( !$this->required_wp_version() )
				if ( !$this->required_php_version() )
					return;

			// Load Plugin Text Domain
			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

			$this->load_options();
			$this->plugin_name = plugin_basename( __FILE__ );

			register_activation_hook( $this->plugin_name, array( &$this, 'activate' ) );
			register_deactivation_hook( $this->plugin_name, array( &$this, 'deactivate' ) );

			add_action( 'init', array( &$this, 'start_helper' ), 15 );
		}

		function start_helper()
		{

			global $rt_wp_nginx_purger;
			add_action( 'shutdown', array( &$this, 'add_timestamps' ), 99999 );
			add_action( 'add_init', array( &$this, 'update_map' ) );

			//add_action( 'save_post', array( &$rt_wp_nginx_purger, 'purgePost' ), 200, 1 );
			// add_action( 'publish_post', array( &$rt_wp_nginx_purger, 'purgePost' ), 200, 1 );
			// add_action( 'publish_page', array( &$rt_wp_nginx_purger, 'purgePost' ), 200, 1 );
			add_action( 'wp_insert_comment', array( &$rt_wp_nginx_purger, 'purgePostOnComment' ), 200, 2 );
			add_action( 'transition_comment_status', array( &$rt_wp_nginx_purger, 'purgePostOnCommentChange' ), 200, 3 );

			// $args = array( '_builtin' => false );
			// $_rt_custom_post_types = get_post_types( $args );
			// if ( isset( $post_types ) && !empty( $post_types ) ) {
			// 	if ( $this->options['rt_wp_custom_post_types'] == true ) {
			// 		foreach ( $_rt_custom_post_types as $post_type ) {
			// 			add_action( 'publish_' . trim( $post_type ), array( &$rt_wp_nginx_purger, 'purgePost' ), 200, 1 );
			// 		}
			// 	}
			// }

			add_action( 'transition_post_status', array( &$this, 'set_future_post_option_on_future_status' ), 20, 3 );
			add_action( 'delete_post', array( &$this, 'unset_future_post_option_on_delete' ), 20, 1 );
			add_action( 'nm_check_log_file_size_daily', array( &$rt_wp_nginx_purger, 'checkAndTruncateLogFile' ), 100, 1 );
			add_action( 'edit_attachment', array( &$rt_wp_nginx_purger, 'purgeImageOnEdit' ), 100, 1 );
			add_action( 'wpmu_new_blog', array( &$this, 'update_new_blog_options' ), 10, 1 );
			add_action( 'transition_post_status', array( &$rt_wp_nginx_purger, 'purge_on_post_moved_to_trash' ), 20, 3 );
			add_action( 'edit_term', array( &$rt_wp_nginx_purger, 'purge_on_term_taxonomy_edited' ), 20, 3 );
			add_action( 'delete_term', array( &$rt_wp_nginx_purger, 'purge_on_term_taxonomy_edited' ), 20, 3 );
			add_action( 'check_ajax_referer', array( &$rt_wp_nginx_purger, 'purge_on_check_ajax_referer' ), 20, 2 );
			add_action( 'admin_init', array( &$this, 'purge_all' ) );

			// expose action to allow other plugins to purge the cache
			add_action( 'rt_nginx_helper_purge_all', array( &$this, 'true_purge_all' ) );

			// Load WP-CLI command
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				require_once RT_WP_NGINX_HELPER_PATH . 'wp-cli.php';
				\WP_CLI::add_command( self::WP_CLI_COMMAND, 'Nginx_Helper_WP_CLI_Command' );
			}
		}

		function activate()
		{

			$path = $this->functional_asset_path();
			if ( !is_dir( $path ) ) {
				mkdir( $path );
			}
			include_once (RT_WP_NGINX_HELPER_PATH . 'admin/install.php');
			rt_wp_nginx_helper_install();
		}

		function deactivate()
		{
			include_once (RT_WP_NGINX_HELPER_PATH . 'admin/install.php');
			rt_wp_nginx_helper_uninstall();
		}

		function required_wp_version()
		{

			global $wp_version;
			$wp_ok = version_compare( $wp_version, $this->minium_WP, '>=' );
			if ( ($wp_ok == FALSE ) ) {
				add_action( 'admin_notices', create_function( '', 'global $rt_wp_nginx_helper; printf (\'<div id="message" class="error"><p><strong>\' . __(\'Sorry, Nginx Helper requires WordPress %s or higher\', "nginx-helper" ) . \'</strong></p></div>\', $rt_wp_nginx_helper->minium_WP );' ) );
				add_action( 'network_admin_notices', create_function( '', 'global $rt_wp_nginx_helper; printf (\'<div id="message" class="error"><p><strong>\' . __(\'Sorry, Nginx Helper requires WordPress %s or higher\', "nginx-helper" ) . \'</strong></p></div>\', $rt_wp_nginx_helper->minium_WP );' ) );
				return false;
			}

			return true;
		}

		function load_options()
		{
			$this->options = get_site_option( 'rt_wp_nginx_helper_options' );
		}

		function set_future_post_option_on_future_status( $new_status, $old_status, $post )
		{

			global $blog_id, $rt_wp_nginx_purger;
            $skip_status = array( 'auto-draft', 'draft', 'inherit', 'trash', 'pending' );
            $purge_status = array( 'publish', 'future' );

			if ( !$this->options['enable_purge'] || in_array( $old_status, $skip_status ) ) {
				return;
			}

            if( in_array( $old_status, $purge_status ) || in_array( $new_status, $purge_status ) ) {
				$rt_wp_nginx_purger->log( "Purge post on transition post STATUS from " . $old_status . " to " . $new_status );
				$rt_wp_nginx_purger->purgePost( $post->ID );
			}

			if ( $new_status == 'future' ) {
				if ( $post && $post->post_status == 'future' && ( ( $post->post_type == 'post' || $post->post_type == 'page' ) || ( isset( $this->options['custom_post_types_recognized'] ) && in_array( $post->post_type, $this->options['custom_post_types_recognized'] ) ) ) ) {
					$rt_wp_nginx_purger->log( "Set/update future_posts option (post id = " . $post->ID . " and blog id = " . $blog_id . ")" );
					$this->options['future_posts'][$blog_id][$post->ID] = strtotime( $post->post_date_gmt ) + 60;
					update_site_option( "rt_wp_nginx_helper_global_options", $this->options );
				}
			}
		}

		function unset_future_post_option_on_delete( $post_id )
		{

			global $blog_id, $rt_wp_nginx_purger;
			if ( !$this->options['enable_purge'] ) {
				return;
			}
			if ( $post_id && !wp_is_post_revision( $post_id ) ) {

				if ( isset( $this->options['future_posts'][$blog_id][$post_id] ) && count( $this->options['future_posts'][$blog_id][$post_id] ) ) {
					$rt_wp_nginx_purger->log( "Unset future_posts option (post id = " . $post_id . " and blog id = " . $blog_id . ")" );
					unset( $this->options['future_posts'][$blog_id][$post_id] );
					update_site_option( "rt_wp_nginx_helper_global_options", $this->options );

					if ( !count( $this->options['future_posts'][$blog_id] ) ) {
						unset( $this->options['future_posts'][$blog_id] );
						update_site_option( "rt_wp_nginx_helper_global_options", $this->options );
					}
				}
			}
		}

		function update_new_blog_options( $blog_id )
		{
			global $rt_wp_nginx_purger;
			include_once (RT_WP_NGINX_HELPER_PATH . 'admin/install.php');
			$rt_wp_nginx_purger->log( "New site added (id $blog_id)" );
			$this->update_map();
			$rt_wp_nginx_purger->log( "New site added to nginx map (id $blog_id)" );
			$helper_options = rt_wp_nginx_helper_get_options();
			update_blog_option( $blog_id, "rt_wp_nginx_helper_options", $helper_options );
			$rt_wp_nginx_purger->log( "Default options updated for the new blog (id $blog_id)" );
		}

		function get_map()
		{
			if ( !$this->options['enable_map'] ) {
				return;
			}

			if ( is_multisite() ) {

				global $wpdb;

				$rt_all_blogs = $wpdb->get_results( $wpdb->prepare( "SELECT blog_id, domain, path FROM " . $wpdb->blogs . " WHERE site_id = %d AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0'", $wpdb->siteid ) );
				$wpdb->dmtable = $wpdb->base_prefix . 'domain_mapping';
				$rt_domain_map_sites = '';
				if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->dmtable}'" ) == $wpdb->dmtable ) {
					$rt_domain_map_sites = $wpdb->get_results( "SELECT blog_id, domain FROM {$wpdb->dmtable} ORDER BY id DESC" );
				}
				$rt_nginx_map = "";
				$rt_nginx_map_array = array();


				if ( $rt_all_blogs )
					foreach ( $rt_all_blogs as $blog ) {
						if ( SUBDOMAIN_INSTALL == "yes" ) {
							$rt_nginx_map_array[$blog->domain] = $blog->blog_id;
						} else {
							if ( $blog->blog_id != 1 ) {
								$rt_nginx_map_array[$blog->path] = $blog->blog_id;
							}
						}
					}

				if ( $rt_domain_map_sites ) {
					foreach ( $rt_domain_map_sites as $site ) {
						$rt_nginx_map_array[$site->domain] = $site->blog_id;
					}
				}

				foreach ( $rt_nginx_map_array as $domain => $domain_id ) {
					$rt_nginx_map .= "\t" . $domain . "\t" . $domain_id . ";\n";
				}

				return $rt_nginx_map;
			}
		}

		function functional_asset_path()
		{
			$dir = wp_upload_dir();
			$path = $dir['basedir'] . '/nginx-helper/';
			return apply_filters( 'nginx_asset_path', $path );
		}

		function functional_asset_url()
		{
			$dir = wp_upload_dir();
			$url = $dir['baseurl'] . '/nginx-helper/';
			return apply_filters( 'nginx_asset_url', $url );
		}

		function update_map()
		{
			if ( is_multisite() ) {
				$rt_nginx_map = $this->get_map();

				if ( $fp = fopen( $this->functional_asset_path() . 'map.conf', 'w+' ) ) {
					fwrite( $fp, $rt_nginx_map );
					fclose( $fp );
					return true;
				}
			}
		}

		function add_timestamps()
		{
			if ( $this->options['enable_purge'] != 1 )
				return;
			if ( $this->options['enable_stamp'] != 1 )
				return;
			if ( is_admin() )
				return;
			foreach ( headers_list() as $header ) {
				list($key, $value) = explode( ':', $header, 2 );
				if ( $key == 'Content-Type' && strpos( trim( $value ), 'text/html' ) !== 0 ) {
					return;
				}
				if ( $key == 'Content-Type' )
					break;
			}

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
				return;
			$timestamps = "\n<!--" .
					"Cached using Nginx-Helper on " . current_time( 'mysql' ) . ". " .
					"It took " . get_num_queries() . " queries executed in " . timer_stop() . " seconds." .
					"-->\n" .
					"<!--Visit http://wordpress.org/extend/plugins/nginx-helper/faq/ for more details-->";
			echo $timestamps;
		}

		function show_notice()
		{
			echo '<div class="updated"><p>' . __( 'Purge initiated', 'nginx-helper' ) . '</p></div>';
		}

		function purge_all()
		{
			if ( !isset( $_REQUEST['nginx_helper_action'] ) )
				return;

			if ( !current_user_can( 'manage_options' ) )
				wp_die( 'Sorry, you do not have the necessary privileges to edit these options.' );

			$action = $_REQUEST['nginx_helper_action'];

			if ( $action == 'done' ) {
				add_action( 'admin_notices', array( &$this, 'show_notice' ) );
				add_action( 'network_admin_notices', array( &$this, 'show_notice' ) );
				return;
			}

			check_admin_referer( 'nginx_helper-purge_all' );

			switch ( $action ) {
				case 'purge':
					$this->true_purge_all();
					break;
			}
			wp_redirect( esc_url_raw( add_query_arg( array( 'nginx_helper_action' => 'done' ) ) ) );
		}

		function true_purge_all()
		{
			global $rt_wp_nginx_purger;
			$rt_wp_nginx_purger->true_purge_all();
		}

		/**
		 * Load the translation file for current language.
		 */
		function load_plugin_textdomain()
		{
			load_plugin_textdomain( 'nginx-helper', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

	}

}

namespace {

	if ( !defined( 'RT_WP_NGINX_HELPER_CACHE_PATH' ) ) {
		define( 'RT_WP_NGINX_HELPER_CACHE_PATH', '/var/run/nginx-cache' );
	}
	global $current_blog;

	if ( is_admin() ) {
		require_once (rtCamp\WP\Nginx\RT_WP_NGINX_HELPER_PATH . '/admin/admin.php');
		$rtwpAdminPanel = new \rtCamp\WP\Nginx\Admin();
	}

	require_once (rtCamp\WP\Nginx\RT_WP_NGINX_HELPER_PATH . 'purger.php');
	require_once (rtCamp\WP\Nginx\RT_WP_NGINX_HELPER_PATH . 'redis-purger.php');
	require_once (rtCamp\WP\Nginx\RT_WP_NGINX_HELPER_PATH . 'compatibility.php');

	global $rt_wp_nginx_helper, $rt_wp_nginx_purger, $rt_wp_nginx_compatibility;
	$rt_wp_nginx_helper = new \rtCamp\WP\Nginx\Helper;

	if ( !empty( $rt_wp_nginx_helper->options['cache_method'] ) && $rt_wp_nginx_helper->options['cache_method'] == "enable_redis" ) {
		$rt_wp_nginx_purger = new \rtCamp\WP\Nginx\Redispurger;
	} else {
		$rt_wp_nginx_purger = new \rtCamp\WP\Nginx\Purger;
	}
	$rt_wp_nginx_compatibility = namespace\rtCamp\WP\Nginx\Compatibility::instance();
	if ( $rt_wp_nginx_compatibility->haveNginx() && !function_exists( 'wp_redirect' ) ) {

		function wp_redirect( $location, $status = 302 )
		{
			$location = apply_filters( 'wp_redirect', $location, $status );

			if ( empty( $location ) ) {
				return false;
			}

			$status = apply_filters( 'wp_redirect_status', $status, $location );
			if ( $status < 300 || $status > 399 ) {
				$status = 302;
			}

			if ( function_exists( 'wp_sanitize_redirect' ) ) {
				$location = wp_sanitize_redirect( $location );
			}
			header( 'Location: ' . $location, true, $status );
		}

	}

	// Add settings link on plugin page
	function nginx_settings_link( $links )
	{
		if ( is_network_admin() ) {
			$u = 'settings.php';
		} else {
			$u = 'options-general.php';
		}
		$settings_link = '<a href="' . $u . '?page=nginx">' . __( 'Settings', 'nginx-helper' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	if ( is_multisite() ) {
		add_filter( "network_admin_plugin_action_links_" . plugin_basename( __FILE__ ), 'nginx_settings_link' );
	} else {
		add_filter( "plugin_action_links_" . plugin_basename( __FILE__ ), 'nginx_settings_link' );
	}

	function get_feeds( $feed_url = 'http://rtcamp.com/blog/feed/' )
	{
		// Get RSS Feed(s)
		require_once( ABSPATH . WPINC . '/feed.php' );
		$maxitems = 0;
		// Get a SimplePie feed object from the specified feed source.
		$rss = fetch_feed( $feed_url );
		if ( !is_wp_error( $rss ) ) { // Checks that the object is created correctly
			// Figure out how many total items there are, but limit it to 5.
			$maxitems = $rss->get_item_quantity( 5 );

			// Build an array of all the items, starting with element 0 (first element).
			$rss_items = $rss->get_items( 0, $maxitems );
		}
		?>
		<ul role="list"><?php
			if ( $maxitems == 0 ) {
				echo '<li role="listitem">' . __( 'No items', 'nginx-helper' ) . '.</li>';
			} else {
				// Loop through each feed item and display each item as a hyperlink.
				foreach ( $rss_items as $item ) {
					?>
					<li role="listitem">
						<a href='<?php echo $item->get_permalink(); ?>' title='<?php echo __( 'Posted ', 'nginx-helper' ) . $item->get_date( 'j F Y | g:i a' ); ?>'><?php echo $item->get_title(); ?></a>
					</li><?php
				}
			}
			?>
		</ul><?php
	}

	function fetch_feeds()
	{
		if ( isset( $_GET['get_feeds'] ) && $_GET['get_feeds'] == '1' ) {
			get_feeds();
			die();
		}
	}

	add_action( 'init', 'fetch_feeds' );
}
