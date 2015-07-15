<?php

//TODO:: phpRedis based implementation https://github.com/phpredis/phpredis#eval
//include predis (php implementation for redis)

global $myredis, $rt_wp_nginx_helper, $redis_api, $lua, $rt_wp_nginx_purger;

$host = $rt_wp_nginx_helper->options['redis_hostname'];
$port = $rt_wp_nginx_helper->options['redis_port'];
$redis_api = '';

if ( class_exists( 'Redis' ) ) { // Use PHP5-Redis if installed.
    try {
        $myredis = new Redis();
        $myredis->connect( $host, $port, 5 );
        $redis_api = 'php-redis';
    } catch ( Exception $e ) { $rt_wp_nginx_purger->log( $e->getMessage(), 'ERROR' ); }
} else {
    if( ! class_exists( 'Predis\Autoloader' ) ) {
        require_once 'predis.php';
    }
    Predis\Autoloader::register();
    
    //redis server parameter
    $myredis = new Predis\Client( [
        'host' => $host,
        'port' => $port,
    ] );
    //connect
    try {
        $myredis->connect();
        $redis_api = 'predis';
    } catch ( Exception $e ) { $rt_wp_nginx_purger->log( $e->getMessage(), 'ERROR' ); }
}

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

/*
  Delete multiple single keys without wildcard using redis pipeline feature to speed up things
 */

function delete_multi_keys( $key )
{   
    global $myredis, $redis_api, $rt_wp_nginx_purger;
    
    try {
        if ( !empty( $myredis ) ) {
            $matching_keys = $myredis->keys( $key );
            if( $redis_api == 'predis') {
                foreach ( $matching_keys as $key => $value ) {
                    $myredis->executeRaw( ['DEL', $value ] );
                }
            } else if( $redis_api == 'php-redis') {
                return $myredis->del( $matching_keys );
            }
        } else {
            return false;
        }
    } catch ( Exception $e ) { $rt_wp_nginx_purger->log( $e->getMessage(), 'ERROR' ); }
}

/*
 *  Delete all the keys from currently selected database
 */

function flush_entire_db()
{
	global $myredis, $rt_wp_nginx_purger;
	try {
        if ( !empty( $myredis ) ) {
            return $myredis->flushdb();
        } else {
            return false;
        }
    } catch ( Exception $e ) { $rt_wp_nginx_purger->log( $e->getMessage(), 'ERROR' ); }
}

/*
  Single Key Delete Example
  e.g. $key can be nginx-cache:httpsGETexample.com/
 */

function delete_single_key( $key )
{
	global $myredis, $redis_api, $rt_wp_nginx_purger;
    try {
        if ( !empty( $myredis ) ) {
            if( $redis_api == 'predis') {
                return $myredis->executeRaw( ['DEL', $key ] );
            } else if( $redis_api == 'php-redis') {
                return $myredis->del( $key );
            }
        } else {
            return false;
        }
    } catch ( Exception $e ) { $rt_wp_nginx_purger->log( $e->getMessage(), 'ERROR' ); }
}

/*
  Delete Keys by wildcar
  e.g. $key can be nginx-cache:httpsGETexample.com*
 */

function delete_keys_by_wildcard( $pattern )
{
	global $myredis, $lua, $redis_api, $rt_wp_nginx_purger;
	/*
	  Lua Script block to delete multiple keys using wildcard
	  Script will return count i.e. number of keys deleted
	  if return value is 0, that means no matches were found
	 */

	/*
	  Call redis eval and return value from lua script
	 */
    try {
        if ( ! empty( $myredis ) ) {
            if( $redis_api == 'predis') {
                return $myredis->eval( $lua, 1, $pattern );
            } else if( $redis_api == 'php-redis') {
                return $myredis->eval( $lua, array( $pattern ), 1 );
            }
        } else {
            return false;
        }
    } catch ( Exception $e ) { $rt_wp_nginx_purger->log( $e->getMessage(), 'ERROR' ); }
}

?>