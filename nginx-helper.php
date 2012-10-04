<?php
/*
Plugin Name: Nginx Helper
Plugin URI: http://rtcamp.com/
Description: An nginx helper that serves various functions.
Version: 1.3
Author: rtCamp
Author URI: http://rtcamp.com
Requires at least: 3.0
Tested up to: 3.4.2
*/
namespace rtCamp\WP\Nginx {
    define( 'rtCamp\WP\Nginx\RT_WP_NGINX_HELPER_PATH', plugin_dir_path(__FILE__) );
    define( 'rtCamp\WP\Nginx\RT_WP_NGINX_HELPER_URL', plugin_dir_url(__FILE__) );
    class Helper{
        var $version            = '1.0'; // Plugin version
        var $db_version         = '0.1'; // DB version, change it to show the upgrade page
        var $minium_WP          = '3.0';
        var $minimum_PHP        = '5.3';
	var $options            = null;

        function __construct() {

                if ( !$this->required_wp_version() )
                    if( !$this->required_php_version() )
                        return;

                $this->load_options();
                $this->define_constant();
                $this->plugin_name = plugin_basename(__FILE__);

                register_activation_hook( $this->plugin_name, array(&$this, 'activate') );
                register_deactivation_hook( $this->plugin_name, array(&$this, 'deactivate') );

                add_action( 'init', array(&$this, 'start_helper'), 15 );

        }
        function start_helper() {

            global $rt_wp_nginx_purger;

            add_action( 'publish_post', array(&$rt_wp_nginx_purger, 'purgePost'), 200, 1);
            add_action( 'publish_page', array(&$rt_wp_nginx_purger, 'purgePost'), 200, 1);
            add_action( 'comment_post', array(&$rt_wp_nginx_purger, 'purgePostOnComment'), 200, 1);
            add_action( 'transition_comment_status', array(&$rt_wp_nginx_purger, 'purgePostOnComment'), 200, 2);

            $args=array('_builtin'=>false);
            $_rt_custom_post_types = get_post_types( $args );
            if ( isset($post_types) && !empty($post_types) ) {
                if($this->options['rt_wp_custom_post_types']==true){
                    foreach ( $_rt_custom_post_types as $post_type ) {
                        add_action( 'publish_'.trim($post_type), array( &$rt_wp_nginx_purger, 'purgePost' ), 200, 1 );
                    }
                }

            }

            add_action( 'transition_post_status', array(&$this, 'set_future_post_option_on_future_status'), 20, 3 );
            add_action( 'delete_post',            array(&$this, 'unset_future_post_option_on_delete'), 20, 1 );

            add_action( 'wp_headers', array(&$rt_wp_nginx_purger, 'correctExpires'), 100, 1 );

            add_action( 'nm_check_log_file_size_daily', array(&$rt_wp_nginx_purger, 'checkAndTruncateLogFile'), 100, 1 );

            add_action( 'edit_attachment',      array(&$rt_wp_nginx_purger, 'purgeImageOnEdit'), 100, 1 );

            add_action( 'wpmu_new_blog', array(&$this, 'update_new_blog_options'), 10, 1 );

            add_action( 'transition_post_status', array(&$rt_wp_nginx_purger, 'purge_on_post_moved_to_trash'), 20, 3 );

            add_action( 'edit_term',   array(&$rt_wp_nginx_purger, 'purge_on_term_taxonomy_edited'), 20, 3 );
            add_action( 'delete_term', array(&$rt_wp_nginx_purger, 'purge_on_term_taxonomy_edited'), 20, 3 );

            add_action( 'check_ajax_referer', array(&$rt_wp_nginx_purger, 'purge_on_check_ajax_referer'), 20, 2 );

        }

        function activate() {
            include_once (RT_WP_NGINX_HELPER_PATH. 'admin/install.php');
            rt_wp_nginx_helper_install();
        }

	function deactivate() {
            include_once (RT_WP_NGINX_HELPER_PATH. 'admin/install.php');
            rt_wp_nginx_helper_uninstall();
        }

	function define_constant() {
	    define('RT_WP_NGINX_HELPER_VERSION',   $this->version );
            define('RT_WP_NGINX_HELPER_DB_VERSION', $this->db_version );
            define('RT_WP_NGINX_HELPER_FOLDER', plugin_basename( dirname(__FILE__)) );
	}

        function required_wp_version() {

            global $wp_version;
            $wp_ok = version_compare( $wp_version, $this->minium_WP, '>=' );

            if ( ($wp_ok == FALSE) ) {
                add_action(
                    'admin_notices',
                    create_function(
                        '',
                        'global $rt_wp_nginx_helper; printf (\'<div id="message" class="error"><p><strong>\' . __(\'Sorry, Nginx Helper requires WordPress %s or higher\', "rt_wp_nginx_helper" ) . \'</strong></p></div>\', $rt_wp_nginx_helper->minium_WP );'
                    )
                );
                return false;
            }

            return true;

        }
        function required_php_version(){

            $php_ok = version_compare(PHP_VERSION, '5.3', '>=');
            if ( ($php_ok == FALSE) ) {
                add_action(
                    'admin_notices',
                    create_function(
                        '',
                        'global $rt_wp_nginx_helper; printf (\'<div id="message" class="error"><p><strong>\' . __(\'Sorry, Nginx Helper requires PHP %s or higher\', "rt_wp_nginx_helper" ) . \'</strong></p></div>\', $rt_wp_nginx_helper->minium_PHP );'
                    )
                );
                return false;
            }

            return true;
        }

        function load_options() {
            $this->options   = get_site_option( 'rt_wp_nginx_helper_options' );
        }

        function set_future_post_option_on_future_status($new_status, $old_status, $post) {

            global $blog_id, $rt_wp_nginx_purger;
            if(!$this->options['enable_purge']){
                return;
            }
            if ( $old_status != $new_status
                && $old_status != 'inherit'
                && $new_status != 'inherit'
                && $old_status != 'auto-draft'
                && $new_status != 'auto-draft'
                && $new_status != 'publish'
                && !wp_is_post_revision( $post->ID ) ) {
                    $rt_wp_nginx_purger->log( "Purge post on transition post STATUS from ".$old_status." to ".$new_status );
                    $rt_wp_nginx_purger->purgePost($post->ID);
            }

            if ($new_status == 'future') {
                if ( $post && $post->post_status == 'future' && ( ( $post->post_type == 'post' || $post->post_type == 'page' ) || ( in_array($post->post_type, $this->options['custom_post_types_recognized']) ) ) ) {
                   $rt_wp_nginx_purger->log( "Set/update future_posts option (post id = ".$post->ID." and blog id = ".$blog_id.")" );
                   $this->options['future_posts'][$blog_id][$post->ID] = strtotime($post->post_date_gmt)+60;
                   update_site_option("rt_wp_nginx_helper_global_options", $this->options);
		}
            }
        }

        function unset_future_post_option_on_delete($post_id) {

            global $blog_id, $rt_wp_nginx_purger;
            if(!$this->options['enable_purge']){
                return;
            }
            if ($post_id && !wp_is_post_revision($post_id)) {

                if ( isset($this->options['future_posts'][$blog_id][$post_id]) && count($this->options['future_posts'][$blog_id][$post_id]) ) {
                    $rt_wp_nginx_purger->log( "Unset future_posts option (post id = ".$post_id." and blog id = ".$blog_id.")" );
                    unset($this->options['future_posts'][$blog_id][$post_id]);
                    update_site_option("rt_wp_nginx_helper_global_options", $this->options);

                    if ( !count($this->options['future_posts'][$blog_id]) ) {
                        unset($this->options['future_posts'][$blog_id]);
			update_site_option("rt_wp_nginx_helper_global_options", $this->options);
                    }
                }
            }
	}

	function update_new_blog_options( $blog_id ) {

            global $rt_wp_nginx_purger;

            include_once (RT_WP_NGINX_HELPER_PATH . 'admin/install.php');

            $rt_wp_nginx_purger->log( "New site added (id $blog_id)" );

            $this->update_map();

            $rt_wp_nginx_purger->log( "New site added to nginx map (id $blog_id)" );

            $helper_options = rt_wp_nginx_helper_get_options();

            update_blog_option( $blog_id, "rt_wp_nginx_helper_options", $helper_options, true );

            $rt_wp_nginx_purger->log( "Default options updated for the new blog (id $blog_id)" );

        }
        function get_map(){
            if(!$this->options['enable_map']){
                return;
            }

            if (is_multisite()){

                global $wpdb;

                $rt_all_blogs   = $wpdb->get_results($wpdb->prepare("SELECT blog_id, domain, path FROM " . $wpdb->blogs . " WHERE site_id = %d AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0'", $wpdb->siteid));
                $wpdb->dmtable = $wpdb->base_prefix . 'domain_mapping';
                if($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->dmtable}'") != $wpdb->dmtable) {
                    $rt_domain_map_sites = $wpdb->get_results( "SELECT blog_id, domain FROM {$wpdb->dmtable} ORDER BY id DESC" );
                }
                $rt_nginx_map   ="";

                if ($rt_all_blogs)
                        foreach ($rt_all_blogs as $blog)
                                if ( SUBDOMAIN_INSTALL == "yes" )
                                        $rt_nginx_map   .= "\t" . $blog->domain . "\t" . $blog->blog_id . ";\n";
                                else
                                        if ( $blog->blog_id != 1 )
                                                $rt_nginx_map   .= "\t" . $blog->path . "\t" . $blog->blog_id . ";\n";

                if($rt_domain_map_sites)
                        foreach($rt_domain_map_sites as $site)
                            $rt_nginx_map .= "\t" . $site->domain . "\t" . $site->site_id . ";\n";

                return $rt_nginx_map;
            }
        }
	function update_map(){
            if (is_multisite()){
                $rt_nginx_map = $this->get_map();
                if ($fp = fopen(RT_WP_NGINX_HELPER_PATH .'map.conf','w+')) {
                    fwrite($fp, $rt_nginx_map);
                    fclose($fp);
                    return true;
                }
            }
        }

    }

}

namespace{
    global $current_blog;

    if ( is_admin() ) {
        require_once (rtCamp\WP\Nginx\RT_WP_NGINX_HELPER_PATH. '/admin/admin.php');
        $rtwpAdminPanel = new \rtCamp\WP\Nginx\Admin();
    }

    require_once (rtCamp\WP\Nginx\RT_WP_NGINX_HELPER_PATH . 'purger.php');

    require_once (rtCamp\WP\Nginx\RT_WP_NGINX_HELPER_PATH . 'compatibility.php');

    global $rt_wp_nginx_helper,$rt_wp_nginx_purger,$rt_wp_nginx_compatibility;
    $rt_wp_nginx_helper = new \rtCamp\WP\Nginx\Helper;
    $rt_wp_nginx_purger = new \rtCamp\WP\Nginx\Purger;
    $rt_wp_nginx_compatibility = namespace\rtCamp\WP\Nginx\Compatibility::instance();
    if ($rt_wp_nginx_compatibility->haveNginx() && !function_exists('wp_redirect')) {

        function wp_redirect($location, $status = 302){
            $location = apply_filters('wp_redirect', $location, $status);

            if (empty($location)) {
                return false;
            }

            $status = apply_filters('wp_redirect_status', $status, $location);
            if ($status < 300 || $status > 399) {
                $status = 302;
            }

            $location = wp_sanitize_redirect($location);
                header('Location: ' . $location, true, $status);
            }

    }
    // Add settings link on plugin page
    function nginx_settings_link($links) {
      if(is_network_admin()){
          $u='settings.php';
      }else{
          $u='options-general.php';
      }
      $settings_link = '<a href="'.$u.'?page=nginx">Settings</a>';
      array_unshift($links, $settings_link);
      return $links;
    }

    $plugin = plugin_basename(__FILE__);
    if(is_multisite()){
        add_filter("network_admin_plugin_action_links_$plugin", 'nginx_settings_link' );
    }else{
        add_filter("plugin_action_links_$plugin", 'nginx_settings_link' );
    }
    function get_feeds($feed_url = 'http://rtcamp.com/blog/feed/') {
            // Get RSS Feed(s)
            require_once( ABSPATH . WPINC . '/feed.php' );
            $maxitems = 0;
            // Get a SimplePie feed object from the specified feed source.
            $rss = fetch_feed($feed_url);
            if (!is_wp_error($rss)) { // Checks that the object is created correctly
             // Figure out how many total items there are, but limit it to 5.
             $maxitems = $rss->get_item_quantity(5);

             // Build an array of all the items, starting with element 0 (first element).
             $rss_items = $rss->get_items(0, $maxitems);
            }
            ?>
            <ul><?php
            if ($maxitems == 0) {
             echo '<li>' . __('No items', 'bp-media') . '.</li>';
            } else {
             // Loop through each feed item and display each item as a hyperlink.
             foreach ($rss_items as $item) {
              ?>
               <li>
                <a href='<?php echo $item->get_permalink(); ?>' title='<?php echo __('Posted ', 'bp-media') . $item->get_date('j F Y | g:i a'); ?>'><?php echo $item->get_title(); ?></a>
               </li><?php
             }
            }
            ?>
            </ul><?php
        }
        function fetch_feeds() {
         if(isset($_GET['get_feeds'])&&$_GET['get_feeds']=='1'){
          get_feeds();
          die();
         }
        }
        add_action('init','fetch_feeds');
}
?>
