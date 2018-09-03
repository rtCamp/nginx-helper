<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://rtcamp.com/nginx-helper/
 * @since             2.0.0
 * @package           nginx-helper
 *
 * @wordpress-plugin
 * Plugin Name:       Nginx Helper
 * Plugin URI:        https://rtcamp.com/nginx-helper/
 * Description:       Cleans nginx's fastcgi/proxy cache or redis-cache whenever a post is edited/published. Also does few more things.
 * Version:           2.0.0
 * Author:            rtCamp
 * Author URI:        https://rtcamp.com
 * Text Domain:       nginx-helper
 * Domain Path:       /languages
 * Requires at least: 3.0
 * Tested up to: 4.2.2
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Base URL of plugin
 */
if( !defined( 'NGINX_HELPER_BASEURL' ) ) {
    define( 'NGINX_HELPER_BASEURL', plugin_dir_url( __FILE__ ) );
}

/**
 * Base Name of plugin
 */
if( !defined( 'NGINX_HELPER_BASENAME' ) ) {
    define( 'NGINX_HELPER_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-nginx-helper-activator.php
 */
function activate_nginx_helper() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-nginx-helper-activator.php';
	Nginx_Helper_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-nginx-helper-deactivator.php
 */
function deactivate_nginx_helper() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-nginx-helper-deactivator.php';
	Nginx_Helper_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_nginx_helper' );
register_deactivation_hook( __FILE__, 'deactivate_nginx_helper' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-nginx-helper.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    2.0.0
 */
function run_nginx_helper() {
	global $nginx_helper;

	$nginx_helper = new Nginx_Helper();
	$nginx_helper->run();

	// Load WP-CLI command.
	if ( defined( 'WP_CLI' ) && WP_CLI ) {

		require_once plugin_dir_path( __FILE__ ) . 'wp-cli.php';
		\WP_CLI::add_command( 'nginx-helper', 'Nginx_Helper_WP_CLI_Command' );

	}

}
run_nginx_helper();
