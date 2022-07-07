<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://rtcamp.com/nginx-helper/
 * @since      2.0.0
 *
 * @package    nginx-helper
 * @subpackage nginx-helper/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      2.0.0
 * @package    nginx-helper
 * @subpackage nginx-helper/includes
 * @author     rtCamp
 */
class Nginx_Helper {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    2.0.0
	 * @access   protected
	 * @var      Nginx_Helper_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    2.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    2.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Minimum WordPress Version Required.
	 *
	 * @since    2.0.0
	 * @access   public
	 * @var      string    $minium_wp
	 */
	protected $minimum_wp;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    2.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'nginx-helper';
		$this->version     = '2.2.2';
		$this->minimum_wp  = '3.0';

		if ( ! $this->required_wp_version() ) {
			return;
		}

		if ( ! defined( 'RT_WP_NGINX_HELPER_CACHE_PATH' ) ) {
			define( 'RT_WP_NGINX_HELPER_CACHE_PATH', '/var/run/nginx-cache' );
		}

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Nginx_Helper_Loader. Orchestrates the hooks of the plugin.
	 * - Nginx_Helper_i18n. Defines internationalization functionality.
	 * - Nginx_Helper_Admin. Defines all hooks for the admin area.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    2.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-nginx-helper-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-nginx-helper-i18n.php';

		/**
		 * The class responsible for defining all actions that required for purging urls.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-purger.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-nginx-helper-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		$this->loader = new Nginx_Helper_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Nginx_Helper_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    2.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Nginx_Helper_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality of the plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		global $nginx_helper_admin, $nginx_purger;

		$nginx_helper_admin = new Nginx_Helper_Admin( $this->get_plugin_name(), $this->get_version() );

		// Defines global variables.
		if ( ! empty( $nginx_helper_admin->options['cache_method'] ) && 'enable_redis' === $nginx_helper_admin->options['cache_method'] ) {

			if ( class_exists( 'Redis' ) ) { // Use PHP5-Redis extension if installed.

				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-phpredis-purger.php';
				$nginx_purger = new PhpRedis_Purger();

			} else {

				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-predis-purger.php';
				$nginx_purger = new Predis_Purger();

			}
		} else if ( ! empty( $nginx_helper_admin->options['cache_method'] ) && 'enable_memcached' === $nginx_helper_admin->options['cache_method'] ) {

			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-memcached-purger.php';
			$nginx_purger = new Memcached_Purger();

		} else {

			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-fastcgi-purger.php';
			$nginx_purger = new FastCGI_Purger();

		}

		$this->loader->add_action( 'admin_enqueue_scripts', $nginx_helper_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $nginx_helper_admin, 'enqueue_scripts' );

		if ( is_multisite() ) {
			$this->loader->add_action( 'network_admin_menu', $nginx_helper_admin, 'nginx_helper_admin_menu' );
			$this->loader->add_filter( 'network_admin_plugin_action_links_' . NGINX_HELPER_BASENAME, $nginx_helper_admin, 'nginx_helper_settings_link' );
		} else {
			$this->loader->add_action( 'admin_menu', $nginx_helper_admin, 'nginx_helper_admin_menu' );
			$this->loader->add_filter( 'plugin_action_links_' . NGINX_HELPER_BASENAME, $nginx_helper_admin, 'nginx_helper_settings_link' );
		}

		if ( ! empty( $nginx_helper_admin->options['enable_purge'] ) ) {
			$this->loader->add_action( 'admin_bar_menu', $nginx_helper_admin, 'nginx_helper_toolbar_purge_link', 100 );
		}

		$this->loader->add_action( 'wp_ajax_rt_get_feeds', $nginx_helper_admin, 'nginx_helper_get_feeds' );

		$this->loader->add_action( 'shutdown', $nginx_helper_admin, 'add_timestamps', 99999 );
		$this->loader->add_action( 'add_init', $nginx_helper_admin, 'update_map' );

		// Add actions to purge.
		$this->loader->add_action( 'wp_insert_comment', $nginx_purger, 'purge_post_on_comment', 200, 2 );
		$this->loader->add_action( 'transition_comment_status', $nginx_purger, 'purge_post_on_comment_change', 200, 3 );
		$this->loader->add_action( 'transition_post_status', $nginx_helper_admin, 'set_future_post_option_on_future_status', 20, 3 );
		$this->loader->add_action( 'delete_post', $nginx_helper_admin, 'unset_future_post_option_on_delete', 20, 1 );
		$this->loader->add_action( 'edit_attachment', $nginx_purger, 'purge_image_on_edit', 100, 1 );
		$this->loader->add_action( 'wpmu_new_blog', $nginx_helper_admin, 'update_new_blog_options', 10, 1 );
		$this->loader->add_action( 'transition_post_status', $nginx_purger, 'purge_on_post_moved_to_trash', 20, 3 );
		$this->loader->add_action( 'edit_term', $nginx_purger, 'purge_on_term_taxonomy_edited', 20, 3 );
		$this->loader->add_action( 'delete_term', $nginx_purger, 'purge_on_term_taxonomy_edited', 20, 3 );
		$this->loader->add_action( 'check_ajax_referer', $nginx_purger, 'purge_on_check_ajax_referer', 20 );
		$this->loader->add_action( 'admin_bar_init', $nginx_helper_admin, 'purge_all' );
		// $this->loader->add_action( 'post_updated', $nginx_purger, 'purge_post_on_update', 20, 3 );
		// $this->loader->add_action( 'updated_post_meta', $nginx_purger, 'updated_meta', 20, 4 );
		// $this->loader->add_action( 'clean_post_cache', $nginx_purger, 'purge_clean_post_cache', 20, 2 );
		$this->loader->add_action( 'wp_after_insert_post', $nginx_purger, 'purge_wp_after_insert_post', 20, 4 );

		$this->loader->add_action( 'elementor/document/after_save', $nginx_purger, 'purge_elementor' );
		$this->loader->add_action( 'elementor/document/before_save', $nginx_purger, 'init_elementor', 10, 2 );

		// expose action to allow other plugins to purge the cache.
		$this->loader->add_action( 'rt_nginx_helper_purge_all', $nginx_purger, 'purge_all' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    2.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     2.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     2.0.0
	 *
	 * @return Nginx_Helper_Loader Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     2.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Check wp version.
	 *
	 * @since     2.0.0
	 *
	 * @global string $wp_version
	 *
	 * @return boolean
	 */
	public function required_wp_version() {

		global $wp_version;

		$wp_ok = version_compare( $wp_version, $this->minimum_wp, '>=' );

		if ( false === $wp_ok ) {

			add_action( 'admin_notices', array( &$this, 'display_notices' ) );
			add_action( 'network_admin_notices', array( &$this, 'display_notices' ) );
			return false;

		}

		return true;

	}

	/**
	 * Dispay plugin notices.
	 */
	public function display_notices() {
		?>
	<div id="message" class="error">
		<p>
			<strong>
				<?php
				printf(
					/* translators: %s is Minimum WP version. */
					esc_html__( 'Sorry, Nginx Helper requires WordPress %s or higher', 'nginx-helper' ),
					esc_html( $this->minimum_wp )
				);
				?>
			</strong>
		</p>
	</div>
		<?php
	}
}
