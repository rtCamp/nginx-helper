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
            $this->redis_object->connect(
                $nginx_helper_admin->options['redis_hostname'],
                $nginx_helper_admin->options['redis_port'],
                5
            );
        } catch ( Exception $e ) {
            $this->log( $e->getMessage(), 'ERROR' );
        }
    }
    
    public function purgeAll() {
        global $nginx_helper_admin;
        
        $this->log( "* * * * *" );
        $this->log( "* Purged Everything!" );
        $total_keys_purged = $this->delete_keys_by_wildcard( "*" );
        if( $total_keys_purged ) {
            $this->log( "Total {$total_keys_purged} urls purged." );
        } else {
            $this->log( "No Cache found." );
        }
        $this->log( "* * * * *" );
    }
    
    public function purgeUrl( $url, $feed = true ) {
        global $nginx_helper_admin;

        $parse = parse_url( $url );
        $host = $nginx_helper_admin->options['redis_hostname'];
        $prefix = $nginx_helper_admin->options['redis_prefix'];
        $_url_purge_base = $prefix . $parse['scheme'] . 'GET' . $parse['host'] . $parse['path'];
        $is_purged = $this->delete_single_key( $_url_purge_base );
        
        if( $is_purged ) {
            $this->log( "- Purged URL | " . $url );
        } else {
            $this->log( "- Cache Not Found | " . $url, 'ERROR' );
        }
        $this->log( "* * * * *" );
    }

    public function customPurgeUrls() {
        global $nginx_helper_admin;

        $parse = parse_url( site_url() );
        $host = $nginx_helper_admin->options['redis_hostname'];
        $prefix = $nginx_helper_admin->options['redis_prefix'];
        $_url_purge_base = $prefix . $parse['scheme'] . 'GET' . $parse['host'];

        if ( isset( $nginx_helper_admin->options['purge_url'] ) && ! empty( $nginx_helper_admin->options['purge_url'] ) ) {
            $purge_urls = explode( "\r\n", $nginx_helper_admin->options['purge_url'] );

            foreach ( $purge_urls as $purge_url ) {
                $purge_url = trim( $purge_url );

                if ( strpos( $purge_url, '*' ) === false ) {
                    $purge_url = $_url_purge_base . $purge_url;
                    $status = $this->delete_single_key( $purge_url );
                    if ( $status ) {
                        $this->log( "- Purge URL | " . $purge_url );
                    } else {
                        $this->log( "- Cache Not Found | " . $purge_url, 'ERROR' );
                    }
                } else {
                    $purge_url = $_url_purge_base . $purge_url;
                    $status = $this->delete_keys_by_wildcard( $purge_url );
                    if ( $status ) {
                        $this->log( "- Purge Wild Card URL | " . $purge_url . " | " . $status . " url purged" );
                    } else {
                        $this->log( "- Cache Not Found | " . $purge_url, 'ERROR' );
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
            return $this->redis_object->del( $key );
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
            return $this->redis_object->eval( $lua, array( $pattern ), 1 );
        } catch ( Exception $e ) { 
            $this->log( $e->getMessage(), 'ERROR' ); 
        }
    }
}
