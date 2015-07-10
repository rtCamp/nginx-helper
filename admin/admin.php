<?php

namespace rtCamp\WP\Nginx {

    class Admin {

        /**
         * Holds the values to be used in the fields callbacks
         */
        private $nginx_helper_tabs;

        function __construct() {
            if ( is_multisite() ) {
                add_action( 'network_admin_menu', array( &$this, 'add_network_menu' ) );
            } else {
                add_action( 'admin_menu', array( &$this, 'add_menu' ) );
            }

            add_action( 'admin_init', array( $this, 'nginx_admin_page_init' ) );

            /**
             * Define Tabs
             */
            $this->nginx_helper_tabs = apply_filters( 'rt_nginx_helper_tabs', array(
                'general' => array(
                    'menu_title'    => __( 'General', 'nginx-helper' ),
                    'menu_slug'     => 'general'
                ),
                'support' => array(
                    'menu_title'    => __( 'Support', 'nginx-helper' ),
                    'menu_slug'     => 'support'
                ) )
            );
        }

        /**
         * Add setting sub-menu for single site
         */
        function add_menu() {
            add_submenu_page( 'options-general.php', __( 'Nginx Helper', 'nginx-helper' ), __( 'Nginx Helper', 'nginx-helper' ), 'manage_options', 'nginx', array( &$this, 'nginx_create_admin_page' ) );
        }

        /**
         * Add setting sub-menu for multi site
         */
        function add_network_menu() {
            add_submenu_page( 'settings.php', __( 'Nginx Helper', 'nginx-helper' ), __( 'Nginx Helper', 'nginx-helper' ), 'manage_options', 'nginx', array( &$this, 'nginx_create_admin_page' ) );
        }

        /**
         * Create tab with links
         * 
         * @param type $current current tab
         */
        function nginx_admin_page_tabs( $current = 'general' ) {
            echo '<h2 class="nav-tab-wrapper">';
            foreach ( $this->nginx_helper_tabs as $tab => $name ) {
                $class = ( $tab == $current ) ? ' nav-tab-active' : '';
                echo '<a class="nav-tab' . $class . '" href="?page=nginx&tab=' . $name['menu_slug'] . '">' . $name['menu_title'] . '</a>';
            }
            echo '</h2>';
        }

        /**
         * Options page callback
         */
        function nginx_create_admin_page() {
            global $pagenow;
            
            /**
             * Includes PHP files located in 'admin/lib/' folder
             */
            foreach (glob(plugin_dir_path(__FILE__) . "lib/*.php") as $lib_filename) {
                require_once( $lib_filename );
            } ?>

            <div class="wrap rt-nginx-wrapper">
                <h2 class="rt_option_title"><?php _e( 'Nginx Settings', 'nginx-helper' ); ?></h2>
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="post-body-content"><?php
                        
                            /* Show Tabs */
                            if ( ( 'options-general.php' == $pagenow || 'settings.php' == $pagenow ) && isset( $_GET['tab'] ) ) {
                                $this->nginx_admin_page_tabs( $_GET['tab'] );
                            } else {
                                $this->nginx_admin_page_tabs( 'general' );
                            }
                            
                            /* Fetch Page Content */
                            $current = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
                            if ( ( 'options-general.php' == $pagenow || 'settings.php' == $pagenow ) && isset( $_GET['page'] ) ) {
                                switch ( $current ) {
                                    case 'general' :
                                        nginx_general_options_page();
                                        break;
                                    case 'support' :
                                        nginx_support_options_page();
                                        break;
                                }
                            } ?>
                        </div> <!-- End of #post-body-content -->
                        <div id="postbox-container-1" class="postbox-container"><?php
                            default_admin_sidebar(); ?>
                        </div> <!-- End of #postbox-container-1 -->
                    </div> <!-- End of #post-body -->
                </div> <!-- End of #poststuff -->
            </div> <!-- End of .wrap .rt-nginx-wrapper -->
            <?php
        }

        function nginx_admin_page_init() {
            add_action( 'admin_enqueue_scripts', array( $this, 'nginx_admin_enqueue_assets' ), 999 );
            add_action( 'admin_bar_menu', array( &$this, 'nginx_toolbar_purge_item' ), 100 );
        }

        function nginx_toolbar_purge_item( $admin_bar ) {
            if ( !current_user_can( 'manage_options' ) ) {
                return;
            }
            $purge_url = add_query_arg( array( 'nginx_helper_action' => 'purge', 'nginx_helper_urls' => 'all' ) );
            $nonced_url = wp_nonce_url( $purge_url, 'nginx_helper-purge_all' );
            $admin_bar->add_menu( array( 'id' => 'nginx-helper-purge-all', 'title' => __( 'Purge Cache', 'nginx-helper' ), 'href' => $nonced_url, 'meta' => array( 'title' => __( 'Purge Cache', 'nginx-helper' ), ), ) );
        }

        function nginx_admin_enqueue_assets($hook) {
            if ( 'settings_page_nginx' != $hook ) {
                return;
            }

            /* Load Plugin CSS */
            wp_enqueue_style('rt-nginx-admin-icon', plugin_dir_url(__FILE__) . 'assets/nginx-helper-icons/css/nginx-fontello.css');
            wp_enqueue_style('rt-nginx-admin-css', plugin_dir_url(__FILE__) . 'assets/style.css');

            /* Load Plugin Scripts */
            $admin_js = trailingslashit( site_url() ) . '?get_feeds=1';
            wp_enqueue_script( 'nginx-js', plugin_dir_url( __FILE__ ) . 'assets/nginx.js', '', '', true );
            wp_localize_script( 'nginx-js', 'news_url', $admin_js );
        }
    }
}
