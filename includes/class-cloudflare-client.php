<?php
/**
 * A wrapper for the Cloudflare API client.
 *
 * @package EasyCache
 */

namespace EasyCache;

use Cloudflare\API\Auth\APIToken;
use Cloudflare\API\Adapter\Guzzle;
use Cloudflare\API\Endpoints\Zones;
use Cloudflare\API\Endpoints\PageRules;
use Cloudflare\API\Configurations\PageRules as PageRulesConfig;
use Exception;

/**
 * Class Cloudflare_Client
 */
class Cloudflare_Client {

	/**
	 * Purge the cache for a given set of tags.
	 *
	 * @param array $tags The tags to purge.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function purgeByTags( array $tags ) {
		if ( empty( $tags ) ) {
			return false;
		}

		$options = get_option( 'easycache_cf_settings' );
		$token   = isset( $options['api_token'] ) ? $options['api_token'] : '';
		$zone_id = isset( $options['zone_id'] ) ? $options['zone_id'] : '';

		if ( empty( $token ) || empty( $zone_id ) ) {
			error_log( 'Advanced Cloudflare Cache: API Token or Zone ID not configured.' );

			return false;
		}

		try {
			$key     = new APIToken( $token );
			$adapter = new Guzzle( $key );
			$zones   = new Zones( $adapter );

			$result = $zones->cachePurge( $zone_id, null, $tags, null );

			if ( $result ) {
				error_log( 'Advanced Cloudflare Cache: Successfully purged by tags: ' . implode( ', ', $tags ) );

				return true;
			} else {
				error_log( 'Advanced Cloudflare Cache: Failed to purge by tags: ' . implode( ', ', $tags ) );

				return false;
			}
		} catch ( Exception $e ) {
			error_log( 'Advanced Cloudflare Cache: Exception when purging by tags: ' . $e->getMessage() );

			return false;
		}
	}

	/**
	 * Purge the entire cache for the zone.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function purgeEverything() {
		$options = get_option( 'easycache_cf_settings' );
		$token   = isset( $options['api_token'] ) ? $options['api_token'] : '';
		$zone_id = isset( $options['zone_id'] ) ? $options['zone_id'] : '';

		if ( empty( $token ) || empty( $zone_id ) ) {
			error_log( 'Advanced Cloudflare Cache: API Token or Zone ID not configured.' );

			return false;
		}

		try {
			$key     = new APIToken( $token );
			$adapter = new Guzzle( $key );
			$zones   = new Zones( $adapter );

			$result = $zones->cachePurgeEverything( $zone_id );

			if ( $result ) {
				error_log( 'Advanced Cloudflare Cache: Successfully purged everything.' );

				return true;
			} else {
				error_log( 'Advanced Cloudflare Cache: Failed to purge everything.' );

				return false;
			}
		} catch ( Exception $e ) {
			error_log( 'Advanced Cloudflare Cache: Exception when purging everything: ' . $e->getMessage() );

			return false;
		}
	}

	/**
	 * Purge the cache for a given set of URLs.
	 *
	 * @param array $urls The URLs to purge.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function purgeByUrls( array $urls ) {
		if ( empty( $urls ) ) {
			return false;
		}

		$options = get_option( 'easycache_cf_settings' );
		$token   = isset( $options['api_token'] ) ? $options['api_token'] : '';
		$zone_id = isset( $options['zone_id'] ) ? $options['zone_id'] : '';

		if ( empty( $token ) || empty( $zone_id ) ) {
			error_log( 'Advanced Cloudflare Cache: API Token or Zone ID not configured.' );

			return false;
		}

		try {
			$key     = new APIToken( $token );
			$adapter = new Guzzle( $key );
			$zones   = new Zones( $adapter );

			$result = $zones->cachePurge( $zone_id, $urls, null, null );

			if ( $result ) {
				error_log( 'Advanced Cloudflare Cache: Successfully purged by URLs: ' . implode( ', ', $urls ) );

				return true;
			} else {
				error_log( 'Advanced Cloudflare Cache: Failed to purge by URLs: ' . implode( ', ', $urls ) );

				return false;
			}
		} catch ( Exception $e ) {
			error_log( 'Advanced Cloudflare Cache: Exception when purging by URLs: ' . $e->getMessage() );

			return false;
		}
	}

	/**
	 * Sets up the "Cache Everything" Page Rule in Cloudflare.
	 *
	 * @return string|false 'created', 'exists', or false on failure.
	 */
	public static function setupCacheEverythingPageRule() {
		$options = get_option( 'easycache_cf_settings' );
		$token   = isset( $options['api_token'] ) ? $options['api_token'] : '';
		$zone_id = isset( $options['zone_id'] ) ? $options['zone_id'] : '';

		if ( empty( $token ) || empty( $zone_id ) ) {
			error_log( 'Advanced Cloudflare Cache: API Token or Zone ID not configured.' );

			return false;
		}

		try {
			$key       = new APIToken( $token );
			$adapter   = new Guzzle( $key );
			$pageRules = new PageRules( $adapter );

			$rules    = $pageRules->listPageRules( $zone_id );
			$rule_url = home_url() . '/*';

			foreach ( $rules->result as $rule ) {
				if ( ! empty( $rule->targets ) ) {
					foreach ( $rule->targets as $target ) {
						if ( $target->target === 'url' && $target->constraint->operator === 'matches' && $target->constraint->value === $rule_url ) {
							if ( ! empty( $rule->actions ) ) {
								foreach ( $rule->actions as $action ) {
									if ( $action->id === 'cache_level' && $action->value === 'cache_everything' ) {
										return 'exists';
									}
								}
							}
						}
					}
				}
			}

			$config = new PageRulesConfig( $rule_url, 'cache_everything' );
			$config->setPriority( 1 );

			if ( $pageRules->createPageRule( $zone_id, $config ) ) {
				return 'created';
			}

			return false;

		} catch ( Exception $e ) {
			error_log( 'Advanced Cloudflare Cache: Exception when setting up Page Rule: ' . $e->getMessage() );

			return false;
		}
	}
}
