<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://rtcamp.com/nginx-helper/
 * @since      2.0.0
 *
 * @package    nginx-helper
 * @subpackage nginx-helper/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    nginx-helper
 * @subpackage nginx-helper/admin
 * @author     rtCamp
 */
class Nginx_Helper_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
    
    /**
	 * Various settings tabs.
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      string    $settings_tabs    Various settings tabs.
	 */
	private $settings_tabs;
    
    /**
	 * Purge options.
	 *
	 * @since    2.0.0
	 * @access   public
	 * @var      string    $options    Purge options.
	 */
	public $options;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
        /**
          * Define settings tabs
          */
        $this->settings_tabs = apply_filters( 'rt_nginx_helper_settings_tabs', array(
            'general' => array(
                'menu_title'    => __( 'General', 'nginx-helper' ),
                'menu_slug'     => 'general'
            ),
            'support' => array(
                'menu_title'    => __( 'Support', 'nginx-helper' ),
                'menu_slug'     => 'support'
            ) )
        );
        
        $this->options = $this->nginx_helper_settings();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    2.0.0
	 */
	public function enqueue_styles( $hook ) {
        
        /**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Nginx_Helper_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Nginx_Helper_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
        
        if ( 'settings_page_nginx' != $hook ) {
            return;
        }
        wp_enqueue_style( $this->plugin_name.'-icons', plugin_dir_url( __FILE__ ) . 'icons/css/nginx-fontello.css', array(), $this->version, 'all' );
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/nginx-helper-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    2.0.0
	 */
	public function enqueue_scripts( $hook ) {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Nginx_Helper_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Nginx_Helper_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
        
        if ( 'settings_page_nginx' != $hook ) {
            return;
        }
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/nginx-helper-admin.js', array( 'jquery' ), $this->version, false );
    }
    
    /**
	 * Add admin menu.
	 *
	 * @since    2.0.0
	 */
    public function nginx_helper_admin_menu() {
        
        if ( is_multisite() ) {
            add_submenu_page(
                'settings.php',
                __( 'Nginx Helper', 'nginx-helper' ),
                __( 'Nginx Helper', 'nginx-helper' ),
                'manage_options',
                'nginx',
                array( &$this, 'nginx_helper_setting_page' ) 
            );
        } else {
            add_submenu_page(
                'options-general.php',
                __( 'Nginx Helper', 'nginx-helper' ),
                __( 'Nginx Helper', 'nginx-helper' ),
                'manage_options',
                'nginx',
                array( &$this, 'nginx_helper_setting_page' ) 
            );
        }
    }
    
    public function nginx_helper_toolbar_purge_link( $wp_admin_bar ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        $purge_url = add_query_arg( array( 'nginx_helper_action' => 'purge', 'nginx_helper_urls' => 'all' ) );
        $nonced_url = wp_nonce_url( $purge_url, 'nginx_helper-purge_all' );
        $wp_admin_bar->add_menu(
            array(
                'id'    => 'nginx-helper-purge-all',
                'title' => __( 'Purge Cache', 'nginx-helper' ),
                'href'  => $nonced_url,
                'meta'  => array( 'title' => __( 'Purge Cache', 'nginx-helper' ) ), 
            ) 
        );
    }
    
    /**
     * Display settings.
     * @global $string $pagenow Contain current admin page.
     * 
     * @since    2.0.0
     */
    public function nginx_helper_setting_page() {
        include 'partials/nginx-helper-admin-display.php';
    }
    
    /**
     * Default settings.
     * 
     * @since    2.0.0
     * @return array
     */
    public function nginx_helper_default_settings() {
        return array(
            'enable_purge'                      => 0,
            'cache_method'                      => '',
            'purge_method'                      => '',
            'enable_map'                        => 0,
            'enable_log'                        => 0,
            'log_level'                         => 'INFO',
            'log_filesize'                      => '5',
            'enable_stamp'                      => 0,
            'purge_homepage_on_new'             => 0,
            'purge_homepage_on_edit'            => 0,
            'purge_homepage_on_del'             => 0,
            'purge_archive_on_new'              => 0,
            'purge_archive_on_edit'             => 0,
            'purge_archive_on_del'              => 0,
            'purge_archive_on_new_comment'      => 0,
            'purge_archive_on_deleted_comment'  => 0,
            'purge_page_on_mod'                 => 0,
            'purge_page_on_new_comment'         => 0,
            'purge_page_on_deleted_comment'     => 0,
            'redis_hostname'                    => '127.0.0.1',
            'redis_port'                        => '6379',
            'redis_prefix'                      => 'nginx-cache:',
        );
    }
    
    /**
     * Get settings.
     * 
     * @since    2.0.0
     */
    public function nginx_helper_settings() {
        return wp_parse_args( 
            get_site_option( 'rt_wp_nginx_helper_options', array() ), 
            $this->nginx_helper_default_settings()
        );
    }
    
    /**
	 * Retrieve the log path.
	 *
	 * @since     2.0.0
	 * @return    string    log path of the plugin.
	 */
	public function get_log_path() {
		$log_dir = wp_upload_dir();
        $log_path = $log_dir['basedir'] . '/nginx-helper/';
        
        return apply_filters( 'rt_nginx_helper_log_path', $log_path );
	}
    
    /**
	 * Retrieve the log url.
	 *
	 * @since     2.0.0
	 * @return    string    log url of the plugin.
	 */
	public function get_log_url() {
		$log_dir = wp_upload_dir();
        $log_url = $log_dir['baseurl'] . '/nginx-helper/';
        
        return apply_filters( 'rt_nginx_helper_log_url', $log_url );
	}
    
    /**
     * Get latest news.
     * 
     * @since     2.0.0
     */
    public function nginx_helper_get_feeds() {
        // Get RSS Feed(s)
		require_once( ABSPATH . WPINC . '/feed.php' );
		$maxitems = 0;
		// Get a SimplePie feed object from the specified feed source.
		$rss = fetch_feed( 'http://rtcamp.com/blog/feed/' );
		if ( ! is_wp_error( $rss ) ) { // Checks that the object is created correctly
			// Figure out how many total items there are, but limit it to 5.
			$maxitems = $rss->get_item_quantity( 5 );
			// Build an array of all the items, starting with element 0 (first element).
			$rss_items = $rss->get_items( 0, $maxitems );
		}
		?>
		<ul role="list">
        <?php
			if ( $maxitems == 0 ) {
				echo '<li role="listitem">' . __( 'No items', 'nginx-helper' ) . '.</li>';
			} else {
				// Loop through each feed item and display each item as a hyperlink.
				foreach ( $rss_items as $item ) {
        ?>
					<li role="listitem">
						<a href='<?php echo $item->get_permalink(); ?>' title='<?php echo __( 'Posted ', 'nginx-helper' ) . $item->get_date( 'j F Y | g:i a' ); ?>'><?php echo $item->get_title(); ?></a>
					</li>
        <?php
				}
			}
        ?>
		</ul>
        <?php
        die();
    }
}
