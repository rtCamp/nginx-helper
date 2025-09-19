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

		global $nginx_helper_admin;

		$options = $nginx_helper_admin->get_cloudflare_settings();
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
		global $nginx_helper_admin;

		$options = $nginx_helper_admin->get_cloudflare_settings();
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

		global $nginx_helper_admin;

		$options = $nginx_helper_admin->get_cloudflare_settings();
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
	 * Sets up the "Cache Rule" required to purge the edge cache.
	 *
	 * @return string|false 'created', 'exists', or false on failure.
	 */
	public static function setupCacheRule() {
		global $nginx_helper_admin;

		if ( ! $nginx_helper_admin ) {
			return;
		}

		$options = $nginx_helper_admin->get_cloudflare_settings();

		$token   = isset( $options['api_token'] ) ? $options['api_token'] : '';
		$zone_id = isset( $options['zone_id'] ) ? $options['zone_id'] : '';

		if ( empty( $token ) || empty( $zone_id ) ) {
			error_log( 'Advanced Cloudflare Cache: API Token or Zone ID not configured.' );
			return false;
		}

		try {
			$key       = new APIToken( $token );
			$adapter   = new Guzzle( $key );

			$rulesets_response = $adapter->get( sprintf( "zones/%s/rulesets", $zone_id ) );
			$raw_existing_response    = $rulesets_response->getBody() ?? [];
			$cache_ruleset_id  = null;

			$existing_rules = json_decode( $raw_existing_response, true );

			if( ! array_key_exists( 'result', $existing_rules ) ) {
				return false;
			}

			$existing_rules = $existing_rules['result'];
			
			foreach ( $existing_rules as $ruleset ) {
				if ( 'http_request_cache_settings' === $ruleset['phase']  && 
					isset( $ruleset['name'] ) && 'nginx_helper_cache_ruleset' === $ruleset['name'] ) {
					return 'exists';
				}
				
				if ( 'http_request_cache_settings' === $ruleset['phase'] && $cache_ruleset_id === null ) {
					$cache_ruleset_id = $ruleset->id;
				}
			}

			$site_url = get_site_url();

			$ruleset = array(
				'kind'        => 'zone',
				'name'        => 'nginx_helper_cache_ruleset',
				'phase'       => 'http_request_cache_settings',
				'description' => 'Set\'s the edge cache rules by Nginx Helper Plugin. ',
				'rules'       => [
					[
						'expression'        => "(http.request.full_uri wildcard \"". $site_url ."/*\" and not http.cookie contains \"wordpress_logged\" and not http.cookie contains \"NO_CACHE\" and not http.cookie contains \"S+ESS\" and not http.cookie contains \"fbs\" and not http.cookie contains \"SimpleSAML\" and not http.cookie contains \"PHPSESSID\" and not http.cookie contains \"wordpress\" and not http.cookie contains \"wp-\" and not http.cookie contains \"comment_author_\" and not http.cookie contains \"duo_wordpress_auth_cookie\" and not http.cookie contains \"duo_secure_wordpress_auth_cookie\" and not http.cookie contains \"bp_completed_create_steps\" and not http.cookie contains \"bp_new_group_id\" and not http.cookie contains \"wp-resetpass-\" and not http.cookie contains \"woocommerce\" and not http.cookie contains \"amazon_Login_\")",
						'action'            => 'set_cache_settings',
						'action_parameters' => [
							'cache'       => true,
							'edge_ttl'    => [
								'mode'    => 'override_origin',
								'default' => 3600
							],
							'browser_ttl' => [
								'mode'    => 'override_origin',
								'default' => 1800
							]
						]
					]
				]
			);

			if ( $cache_ruleset_id === null ) {
				$ruleset_resp = $adapter->post( sprintf( 'zones/%s/rulesets', $zone_id ), $ruleset );
			} else {
				$ruleset_resp = $adapter->put( sprintf( 'zones/%s/rulesets/%s', $zone_id, $cache_ruleset_id ), array( 'rules' => $ruleset ) );
			}

			if ( isset( $ruleset_resp->success ) && $ruleset_resp->success ) {
				return 'created';
			} else {
				error_log( 'Advanced Cloudflare Cache: Failed to create/update cache rule. Response: ' . json_encode( $ruleset_resp ) );
				return false;
			}

		} catch ( Exception $e ) {
			error_log( 'Advanced Cloudflare Cache: Exception when setting up Cache Rule: ' . $e->getMessage() );
			return false;
		}
	}
}
