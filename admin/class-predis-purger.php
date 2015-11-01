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
 * Description of Predis_Purger
 *
 * @package    nginx-helper
 * @subpackage nginx-helper/admin
 * @author     rtCamp
 */

class Predis_Purger extends Purger {
    
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
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/predis.php';
        }
        Predis\Autoloader::register();

        //redis server parameter
        $this->redis_object = new Predis\Client([
            'host' => $nginx_helper_admin->options['redis_hostname'],
            'port' => $nginx_helper_admin->options['redis_port'],
        ]);
        
        try {
            $this->redis_object->connect();
        } catch ( Exception $e ) {
            $this->log( $e->getMessage(), 'ERROR' );
        }
    }
    
    public function purgeAll() {
        global $nginx_helper_admin;
        
        $this->log("* * * * *");
        $this->log("* Purged Everything!");
        $this->log("* * * * *");
        $this->delete_keys_by_wildcard("*");
    }
    
    public function purgeUrl( $url, $feed = true ) {
        global $nginx_helper_admin;

        $this->log( "- Purging URL | " . $url );

        $parse = parse_url( $url );
        $host = $nginx_helper_admin->options['redis_hostname'];
        $prefix = $nginx_helper_admin->options['redis_prefix'];
        $_url_purge_base = $prefix . $parse['scheme'] . 'GET' . $parse['host'] . $parse['path'];
        $this->delete_single_key( $_url_purge_base );
    }

    public function customPurgeUrls() {
        global $nginx_helper_admin;

        $parse = parse_url( site_url() );
        $host = $nginx_helper_admin->options['redis_hostname'];
        $prefix = $nginx_helper_admin->options['redis_prefix'];
        $_url_purge_base = $prefix . $parse['scheme'] . 'GET' . $parse['host'];

        $purge_urls = isset( $nginx_helper_admin->options['purge_url'] ) && ! empty( $nginx_helper_admin->options['purge_url'] ) ?
            explode( "\r\n", $nginx_helper_admin->options['purge_url'] ) : array();
			
        // Allow plugins/themes to modify/extend urls. Pass urls array in first parameter, second says if wildcards are allowed
        $purge_urls = apply_filters( 'rt_nginx_helper_purge_urls', $purge_urls, true );
        
        if( is_array( $purge_urls ) && ! empty( $purge_urls ) ) {
            foreach ( $purge_urls as $purge_url ) {
                $purge_url = trim( $purge_url );

                if ( strpos( $purge_url, '*' ) === false ) {
                    $purge_url = $_url_purge_base . $purge_url;
                    $status = $this->delete_single_key( $purge_url );
                    if ( $status ) {
                        $this->log( "- Purge URL | " . $purge_url );
                    } else {
                        $this->log( "- Not Found | " . $purge_url, 'ERROR' );
                    }
                } else {
                    $purge_url = $_url_purge_base . $purge_url;
                    $status = $this->delete_keys_by_wildcard( $purge_url );
                    if ( $status ) {
                        $this->log( "- Purge Wild Card URL | " . $purge_url . " | " . $status . " url purged" );
                    } else {
                        $this->log( "- Not Found | " . $purge_url, 'ERROR' );
                    }
                }
            }
        }
    }
    
    /**
     * Single Key Delete Example
     * e.g. $key can be nginx-cache:httpGETexample.com/
     */
    public function delete_single_key( $key ) {
       
        try {
            return $this->redis_object->executeRaw( ['DEL', $key ] );
        } catch ( Exception $e ) { 
            $this->log( $e->getMessage(), 'ERROR' ); 
        }
    }
    
    /**
     * Delete Keys by wildcar
     * e.g. $key can be nginx-cache:httpGETexample.com*
     * 
     * Lua Script block to delete multiple keys using wildcard
	 * Script will return count i.e. number of keys deleted
	 * if return value is 0, that means no matches were found
     * 
     * Call redis eval and return value from lua script
     */
    public function delete_keys_by_wildcard( $pattern ) {

//Lua Script
$lua = <<<LUA
local k =  0
for i, name in ipairs(redis.call('KEYS', KEYS[1]))
do
    redis.call('DEL', name)
    k = k+1
end
return k
LUA;
        try {
           return $this->redis_object->eval( $lua, 1, $pattern );
        } catch ( Exception $e ) {
            $this->log( $e->getMessage(), 'ERROR' ); 
        }
    }
}