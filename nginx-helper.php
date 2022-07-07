<?php
/**
 * Plugin Name:       Server Cache
 * Plugin URI:        https://rtcamp.com/nginx-helper/
 * Description:       Manage server FastCGI cache. Automatic cache clearing when a post is updated. Warning:disabling this plugin won't disable server cache.
 * Version:           2.2.2
 * Author:            rtCamp
 * Author URI:        https://rtcamp.com
 * Text Domain:       nginx-helper
 * Domain Path:       /languages
 * Requires at least: 3.0
 * Tested up to:      5.4
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
 * FEED purge status
 */
if ( ! defined( 'NGINX_HOME_PURGE' ) ) {
	define( 'NGINX_HOME_PURGE', false );
}

/**
 * FEED purge status
 */
if ( ! defined( 'NGINX_FEED_PURGE' ) ) {
	define( 'NGINX_FEED_PURGE', false );
}

/**
 * ARCHIVE purge status
 */
if ( ! defined( 'NGINX_ARCHIVE_PURGE' ) ) {
	define( 'NGINX_ARCHIVE_PURGE', false );
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

register_activation_hook( __FILE__, 'activate_nginx_helper' );
register_deactivation_hook( __FILE__, 'deactivate_nginx_helper' );

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
