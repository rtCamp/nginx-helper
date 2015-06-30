<?php

//TODO:: phpRedis based implementation https://github.com/phpredis/phpredis#eval
//include predis (php implementation for redis)
require_once 'predis.php';
Predis\Autoloader::register();

$rt_wp_nginx_helper_options = get_site_option( 'rt_wp_nginx_helper_options' );
$host = $rt_wp_nginx_helper_options['redis_hostname'];
$port = $rt_wp_nginx_helper_options['redis_port'];
global $myredis;
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

//redis server parameter
$myredis = new Predis\Client( [
	'host' => $host,
	'port' => $port,
		] );

//connect
try {
	$myredis->connect();
} catch ( Exception $e ) {
	
}


/*
  Delete multiple single keys without wildcard using redis pipeline feature to speed up things
 */

function delete_multi_keys( $key )
{
	global $myredis;

//TODO :: implementation
}

/*
  Single Key Delete Example
  e.g. $key can be nginx-cache:httpsGETexample.com/
 */

function delete_single_key( $key )
{
	global $myredis;
	if ( !empty( $myredis ) ) {
		return $myredis->executeRaw( ['DEL', $key ] );
	}
}

/*
  Delete Keys by wildcar
  e.g. $key can be nginx-cache:httpsGETexample.com*
 */

function delete_keys_by_wildcard( $pattern )
{
	global $myredis, $lua;
	/*
	  Lua Script block to delete multiple keys using wildcard
	  Script will return count i.e. number of keys deleted
	  if return value is 0, that means no matches were found
	 */

	/*
	  Call redis eval and return value from lua script
	 */
	if ( !empty( $myredis ) ) {
		return $myredis->eval( $lua, 1, $pattern );
	}
}

//Example Usage
//echo delete_keys_by_wildcard("foo*");
//echo delete_single_key("bar");
?>