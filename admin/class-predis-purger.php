<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    nginx-helper
 */

/**
 * Description of Predis_Purger
 *
 * @package    nginx-helper
 * @subpackage nginx-helper/admin
 * @author     rtCamp
 */
class Predis_Purger extends Purger {

	use Redis_Purge_Traits;

	/**
	 * Predis api object.
	 *
	 * @since    2.0.0
	 * @access   public
	 * @var      string    $redis_object    Predis api object.
	 */
	public $redis_object;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 */
	public function __construct() {

		global $nginx_helper_admin;

		if ( ! class_exists( 'Predis\Autoloader' ) ) {
			require_once NGINX_HELPER_BASEPATH . 'admin/predis.php';
		}

		Predis\Autoloader::register();

		/*Composer sets default to version that doesn't allow modern php*/
		$predis_connection_array = array();

		$path                                =  $nginx_helper_admin->options['redis_unix_socket'];
		$username                            =  $nginx_helper_admin->options['redis_username'];
		$password                            =  $nginx_helper_admin->options['redis_password'];
		$predis_connection_array['database'] = $nginx_helper_admin->options['redis_database'];

		if ( $path ) {
			$predis_connection_array['path'] = $path;
		} else {
			$predis_connection_array['host'] = $nginx_helper_admin->options['redis_hostname'];;
			$predis_connection_array['port'] = $nginx_helper_admin->options['redis_port'];
		}

		if ( $username && $password ) {
			$predis_connection_array['username'] = $username;
			$predis_connection_array['password'] = $password;
		}

		// redis server parameter.
		$this->redis_object = new Predis\Client( $predis_connection_array );

		try {
			$this->redis_object->connect();
		} catch ( Exception $e ) {
			$this->log( $e->getMessage(), 'ERROR' );
		}

	}

}
