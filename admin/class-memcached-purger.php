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
 * Description of Memcached_Purger
 *
 * @package    nginx-helper
 * @subpackage nginx-helper/admin
 * @author     rtCamp
 */
class Memcached_Purger extends Purger {

	/**
	 * Memcached api object.
	 *
	 * @since    2.0.0
	 * @access   public
	 * @var      string    $memcached_object    Memcached api object.
	 */
	public $memcached_object;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 */
	public function __construct() {

		global $nginx_helper_admin;

		try {

			$this->memcached_object = new Memcached();
			$this->connect(
				$nginx_helper_admin->options['memcached_hostname'],
				$nginx_helper_admin->options['memcached_port']
			);
			
		} catch ( Exception $e ) {
			$this->log( $e->getMessage(), 'ERROR' );
		}

	}

    public function connect($host , $port){
        // https://www.php.net/manual/en/memcached.addserver.php#110003
        $servers = $this->memcached_object->getServerList();
        if(is_array($servers)) {
            foreach ($servers as $server) {
                if($server['host'] == $host and $server['port'] == $port)
                return true;
            }
        }
        return $this->memcached_object->addServer($host , $port);
    }

	/**
	 * Purge all cache.
	 */
	public function purge_all() {

		global $nginx_helper_admin;

		$prefix = trim( $nginx_helper_admin->options['memcached_prefix'] );
		$versioned_cache_key = trim( $nginx_helper_admin->options['memcached_versioned_cache_key'] );

		$this->log( '* * * * *' );

		// If the cache is versioned, delete the cache version
		if ( "" !== $versioned_cache_key ) {
			/**
			 * There are a couple of reasons for why the cache version is being removed and not changed:
			 *
			 * 1. By deciding the version in the nginx conf, the case where memcached is restarted and the version
			 *    key is lost is handled. Memcached might also decide to evict the key if memory constrained, but that
			 *    should not happen as it is frequently accessed and should be set with no expiration time.
			 *
			 * 2. It lets the user decide what the version will be. One might choose the timestamp in seconds or
			 *    a totally random key for example. It's not a good idea to increment the key based on the previous version
			 *    as resetting to 0 might cause old cached pages to be served again.
			 */
			$this->memcached_object->delete($versioned_cache_key);

			$result_code = $this->memcached_object->getResultCode();
			if ( Memcached::RES_SUCCESS === $result_code || Memcached::RES_NOTFOUND === $result_code ) {
				$this->log( ' * Purged cache by invalidating the cache version. * ' );
			} else {
				$this->log( ' * Failed to invalidate cache version * ', 'ERROR' );
			}
		} else {

			// If Purge Cache link click from network admin then purge all.
			if (is_network_admin()) {

				$this->log('* Attempting to purge every key matching "' . $prefix . '" * ');
				$total_keys_purged = $this->delete_keys_by_wildcard($prefix . '*');
				$this->log('* Purged Everything! * ');

			} else { // Else purge only site specific cache.

				$parse = wp_parse_url(get_home_url());
				$parse['path'] = empty($parse['path']) ? '/' : $parse['path'];
				$this->log('* Attempting to purge every key matching "' . $prefix . $parse['scheme'] . 'GET' . $parse['host'] . $parse['path'] . '*' . '" * ');
				$total_keys_purged = $this->delete_keys_by_wildcard($prefix . $parse['scheme'] . 'GET' . $parse['host'] . $parse['path'] . '*');
				$this->log('* ' . get_home_url() . ' Purged! * ');

			}

			if ( $total_keys_purged ) {
				$this->log( "{$total_keys_purged} urls purged." );
			} else {
				$this->log( 'No Cache found.' );
			}
		}

		$this->log( '* * * * *' );

		/**
		 * Fire an action after the Memcached cache has been purged.
		 *
		 * @since 2.1.0
		 */
		do_action( 'rt_nginx_helper_after_memcached_purge_all' );
	}

	/**
	 * Purge url.
	 *
	 * @param string $url URL to purge.
	 * @param bool   $feed Feed or not.
	 */
	public function purge_url( $url, $feed = true ) {

		global $nginx_helper_admin;

		/**
		 * Filters the URL to be purged.
		 *
		 * @since 2.1.0
		 *
		 * @param string $url URL to be purged.
		 */
		$url = apply_filters( 'rt_nginx_helper_purge_url', $url );

		$parse = wp_parse_url( $url );

		if ( ! isset( $parse['path'] ) ) {
			$parse['path'] = '';
		}

		$prefix              = $nginx_helper_admin->options['memcached_prefix'];
		$versioned_cache_key = trim( $nginx_helper_admin->options['memcached_versioned_cache_key'] );
		$version             = "";

		if ( "" !== $versioned_cache_key ) {
			$version = $this->memcached_object->get($versioned_cache_key);
			if ( '' === $version ) {
				$this->log( '- Cache version empty or not found | ' .  $versioned_cache_key, 'ERROR' );
			}
		}

		$_url_purge_base = $prefix . $version . $parse['scheme'] . 'GET' . $parse['host'] . $parse['path'];

		/**
		 * To delete device type caches such as `<URL>--mobile`, `<URL>--desktop`, `<URL>--lowend`, etc.
		 * This would need $url above to be changed with this filter `rt_nginx_helper_purge_url` by cache key that Nginx sets while generating cache.
		 *
		 * For example: If page is accessed from desktop, then cache will be generated by appending `--desktop` to current URL.
		 * Add this filter in separate plugin or simply in theme's function.php file:
		 * ```
		 * add_filter( 'rt_nginx_helper_purge_url', function( $url ) {
		 *      $url = $url . '--*';
		 *      return $url;
		 * });
		 * ```
		 *
		 * Beware that when using the versioned cache option, wildcard purging does not work.
		 *
		 * Regardless of what key / suffix is being to store `$device_type` cache , it will be deleted.
		 *
		 * @since 2.1.0
		 */
		if ( false === strpos( $_url_purge_base, '*' ) || "" !== $versioned_cache_key  ) {

			$status = $this->delete_single_key( $_url_purge_base );

			if ( $status ) {
				$this->log( '- Purge URL | ' . $_url_purge_base );
			} else {
				$this->log( '- Cache Not Found | ' . $_url_purge_base, 'ERROR' );
			}
		} else {

			$status = $this->delete_keys_by_wildcard( $_url_purge_base );

			if ( $status ) {
				$this->log( '- Purge Wild Card URL | ' . $_url_purge_base . ' | ' . $status . ' url purged' );
			} else {
				$this->log( '- Cache Not Found | ' . $_url_purge_base, 'ERROR' );
			}
		}

		$this->log( '* * * * *' );
	}

	/**
	 * Custom purge urls.
	 */
	public function custom_purge_urls() {

		global $nginx_helper_admin;

		$parse               = wp_parse_url( home_url() );
		$prefix              = $nginx_helper_admin->options['memcached_prefix'];
		$versioned_cache_key = trim( $nginx_helper_admin->options['memcached_versioned_cache_key'] );
		$version             = "";

		if ( "" !== $versioned_cache_key ) {
			$version = $this->memcached_object->get($versioned_cache_key);
			if ( '' === $version ) {
				$this->log( '- Cache version empty or not found | ' .  $versioned_cache_key, 'ERROR' );
			}
		}

		$_url_purge_base = $prefix . $version . $parse['scheme'] . 'GET' . $parse['host'];

		$purge_urls = isset( $nginx_helper_admin->options['purge_url'] ) && ! empty( $nginx_helper_admin->options['purge_url'] ) ?
			explode( "\r\n", $nginx_helper_admin->options['purge_url'] ) : array();

		/**
		 * Allow plugins/themes to modify/extend urls.
		 *
		 * @param array $purge_urls URLs which needs to be purged.
		 * @param bool  $wildcard   If wildcard in url is allowed or not. default true.
		 */
		$purge_urls = apply_filters( 'rt_nginx_helper_purge_urls', $purge_urls, true );

		if ( is_array( $purge_urls ) && ! empty( $purge_urls ) ) {

			foreach ( $purge_urls as $purge_url ) {

				$purge_url = trim( $purge_url );

				if ( false === strpos( $purge_url, '*' ) || "" !== $versioned_cache_key ) {

					$purge_url = $_url_purge_base . $purge_url;
					$status    = $this->delete_single_key( $purge_url );

					if ( $status ) {
						$this->log( '- Purge URL | ' . $purge_url );
					} else {
						$this->log( '- Cache Not Found | ' . $purge_url, 'ERROR' );
					}
				} else {

					$purge_url = $_url_purge_base . $purge_url;
					$status    = $this->delete_keys_by_wildcard( $purge_url );

					if ( $status ) {
						$this->log( '- Purge Wild Card URL | ' . $purge_url . ' | ' . $status . ' url purged' );
					} else {
						$this->log( '- Cache Not Found | ' . $purge_url, 'ERROR' );
					}
				}
			}
		}

	}

	/**
	 * Delete cache by single key.
	 *
	 * @param string $key The cache key to be deleted.
	 *                    e.g. $key can be nginx-cache:httpGETexample.com/
	 *
	 * @return bool True if deleted, False otherwise.
	 */
	public function delete_single_key( $key ) {

		try {
			return $this->memcached_object->delete( $key );
		} catch ( Exception $e ) {
			$this->log( $e->getMessage(), 'ERROR' );
		}

	}

	/**
	 * Delete cache by wildcard key.
	 * It is expected to be much slower than the Redis version, since it fetches all the existing
	 * keys from memcached in order to match. Use responsibly.
	 *
	 * @param string $pattern The pattern to which cache keys are being matched.
	 *                        e.g. $pattern can be nginx-cache:httpGETexample.com*
	 *
	 * @return int Number of deleted keys.
	 */
	public function delete_keys_by_wildcard( $pattern ) {

		try {
			$keys = $this->memcached_object->getAllKeys();
			$deleted_count = 0;

			foreach ($keys as $index => $key) {
				// fnmatch knows additional syntax such as ? and [ ] but these are unlikely
				// to be encountered as:
				//   - '?' is being stripped from the pattern that reaches this function
				//   - '[' and ']' should be escaped in urls and are only realistically found
				//     in query params (which also don't reach this function)
				if (true === fnmatch($pattern, $key)) {
					if (true === $this->memcached_object->delete($key)) {
						$deleted_count += 1;
					}
				} else {
					unset($keys[$index]);
				}
			}

			return $deleted_count;
		} catch ( Exception $e ) {
			$this->log( $e->getMessage(), 'ERROR' );
		}

	}

}
