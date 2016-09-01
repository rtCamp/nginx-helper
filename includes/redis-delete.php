<?php

//TODO:: phpRedis based implementation https://github.com/phpredis/phpredis#eval
//include predis (php implementation for redis)

global $myredis, $rt_wp_nginx_helper, $redis_api, $rt_wp_nginx_purger;

$host = $rt_wp_nginx_helper->options['redis_hostname'];
$port = $rt_wp_nginx_helper->options['redis_port'];
$redis_api = '';

if ( class_exists( 'Redis' ) ) { // Use PHP5-Redis if installed.
    try {
        $myredis = new Redis();
        $myredis->connect( $host, $port, 5 );
        $redis_api = 'php-redis';
    } catch ( Exception $e ) { 
        if( isset($rt_wp_nginx_purger) && !empty($rt_wp_nginx_purger) ) {
            $rt_wp_nginx_purger->log( $e->getMessage(), 'ERROR' ); 
        }
    }
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
    } catch ( Exception $e ) { 
        if( isset($rt_wp_nginx_purger) && !empty($rt_wp_nginx_purger) ) {
            $rt_wp_nginx_purger->log( $e->getMessage(), 'ERROR' ); 
        }
    }
}

/**
 * Delete multiple single keys without wildcard using redis pipeline feature to speed up things
 */
function delete_multi_keys( $key ) //TODO: Remove if not used
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

    return false;
}

/**
 * Single Key Delete
 * @param string $key can be nginx-cache:httpsGETexample.com/
 *
 * @return bool|int|mixed
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

    return false;
}

/**
 * Delete Keys by wildcard
 * @param string $pattern can be nginx-cache:httpsGETexample.com*
 *
 * @return mixed What is returned depends on what the LUA script itself returns.
 */
function delete_keys_by_wildcard( $pattern )
{
	global $myredis, $redis_api, $rt_wp_nginx_purger;

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
        if ( ! empty( $myredis ) ) {
            if( $redis_api == 'predis') {
                return $myredis->eval( $lua, 1, $pattern );
            } else if( $redis_api == 'php-redis') {
                return $myredis->eval( $lua, [ $pattern ], 1 );
            }
        } else {
            return false;
        }
    } catch ( Exception $e ) { $rt_wp_nginx_purger->log( $e->getMessage(), 'ERROR' ); }

	return false;
}