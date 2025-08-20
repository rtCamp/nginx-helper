<?php
/**
 * Plugin Name:       Nginx Helper
 * Plugin URI:        https://rtcamp.com/nginx-helper/
 * Description:       Cleans nginx's fastcgi/proxy cache or redis-cache whenever a post is edited/published. Also does few more things.
 * Version:           2.3.4
 * Author:            rtCamp
 * Author URI:        https://rtcamp.com
 * Text Domain:       nginx-helper
 * Domain Path:       /languages
 * Requires at least: 3.0
 * Tested up to:      6.8
 *
 * @link              https://rtcamp.com/nginx-helper/
 * @since             2.0.0
 * @package           nginx-helper
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Base URL of plugin
 */
if ( ! defined( 'NGINX_HELPER_BASEURL' ) ) {
	define( 'NGINX_HELPER_BASEURL', plugin_dir_url( __FILE__ ) );
}

/**
 * Base Name of plugin
 */
if ( ! defined( 'NGINX_HELPER_BASENAME' ) ) {
	define( 'NGINX_HELPER_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * Base PATH of plugin
 */
if ( ! defined( 'NGINX_HELPER_BASEPATH' ) ) {
	define( 'NGINX_HELPER_BASEPATH', plugin_dir_path( __FILE__ ) );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-nginx-helper-activator.php
 */
function activate_nginx_helper() {
	require_once NGINX_HELPER_BASEPATH . 'includes/class-nginx-helper-activator.php';
	Nginx_Helper_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-nginx-helper-deactivator.php
 */
function deactivate_nginx_helper() {
	require_once NGINX_HELPER_BASEPATH . 'includes/class-nginx-helper-deactivator.php';
	Nginx_Helper_Deactivator::deactivate();
}

/**
 * The code that runs during plugin upgrade.
 *
 * @param WP_Upgrader $upgrader_object The Wordpress Upgrader Object.
 * @param array       $options The options for the upgrade process.
 */
function handle_nginx_helper_upgrade( $upgrader_object, $options ) {

	if ( ! is_array( $options ) ) {
		return;
	}

	if ( ! array_key_exists( 'type', $options ) || ! array_key_exists( 'action', $options ) ) {
		return;
	}

	if ( 'plugin' !== $options['type'] ||  ! in_array( $options['action'], [ 'install', 'update' ] ) ) {
		return;
	}

	if ( 'update' === $options['action'] ) {
		if ( ! is_array( $options['plugins'] ) || 
			! in_array( NGINX_HELPER_BASENAME, $options['plugins'] ) ) {
			return;
		}
		
	}
	
	if ( 'install' === $options['action'] ) {
    
		if ( ! is_array( $upgrader_object->result ) || 
			! array_key_exists( 'destination_name', $upgrader_object->result ) || 
			$upgrader_object->result['destination_name'] !== dirname( NGINX_HELPER_BASENAME ) ) {
			return;
		}
	}
	
	require_once NGINX_HELPER_BASEPATH . 'includes/class-nginx-helper-activator.php';
	Nginx_Helper_Activator::set_user_caps();

}

register_activation_hook( __FILE__, 'activate_nginx_helper' );
register_deactivation_hook( __FILE__, 'deactivate_nginx_helper' );
add_action( 'upgrader_process_complete', 'handle_nginx_helper_upgrade', 1, 2 );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require NGINX_HELPER_BASEPATH . 'includes/class-nginx-helper.php';

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

		require_once NGINX_HELPER_BASEPATH . 'class-nginx-helper-wp-cli-command.php';
		\WP_CLI::add_command( 'nginx-helper', 'Nginx_Helper_WP_CLI_Command' );

	}

}
run_nginx_helper();
