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
 * Description of PhpRedis_Purger
 *
 * @package    nginx-helper
 * @subpackage nginx-helper/admin
 * @author     rtCamp
 */
class PhpRedis_Purger extends Purger {

	use Redis_Purge_Traits;

	/**
	 * PHP Redis api object.
	 *
	 * @since    2.0.0
	 * @access   public
	 * @var      string    $redis_object    PHP Redis api object.
	 */
	public $redis_object;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 */
	public function __construct() {

		global $nginx_helper_admin;

		try {

			$this->redis_object = new Redis();

			/*Composer sets default to version that doesn't allow modern php*/
			$redis_connection_others_array = array();

			$path = $nginx_helper_admin->options['redis_unix_socket'];

			if ( $path ) {
				$host = $path;
				$port = 0;
			} else {
				$host = $nginx_helper_admin->options['redis_hostname'];
				$port = $nginx_helper_admin->options['redis_port'];
			}

			$username = $nginx_helper_admin->options['redis_username'];
			$password = $nginx_helper_admin->options['redis_password'];

			if ( $username && $password ) {
				$redis_connection_others_array['auth'] = [$username, $password];
			}

			$this->redis_object->connect(
				$host,
				$port,
				5,
				'',
				100,
				1.5,
				$redis_connection_others_array
			);

			$redis_database = $nginx_helper_admin->options['redis_database'];

			$this->redis_object->select($redis_database);

		} catch ( Exception $e ) {
			$this->log( $e->getMessage(), 'ERROR' );
		}

	}

}
