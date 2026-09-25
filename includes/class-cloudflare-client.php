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
	 * @return string 'created', 'exists', or 'failed'.
	 */
	public static function setupCacheRule() {
		global $nginx_helper_admin;

		if ( ! $nginx_helper_admin ) {
			return 'failed';
		}

		$options = $nginx_helper_admin->get_cloudflare_settings();
		$token   = isset( $options['api_token'] ) ? sanitize_text_field( $options['api_token'] ) : '';
		$zone_id = isset( $options['zone_id'] ) ? sanitize_text_field( $options['zone_id'] ) : '';

		if ( empty( $token ) || empty( $zone_id ) ) {
			error_log( 'Advanced Cloudflare Cache: API Token or Zone ID not configured.' );
			return 'failed';
		}

		$key     = new APIToken( $token );
		$adapter = new Guzzle( $key );

		try {
			$rulesets_response = $adapter->get( sprintf( 'zones/%s/rulesets', esc_attr( $zone_id ) ) );
			$raw_response      = $rulesets_response->getBody() ?? '';
			$response_data     = json_decode( $raw_response, true );

			if ( ! is_array( $response_data ) || ! array_key_exists( 'result', $response_data ) ) {
				error_log( 'Advanced Cloudflare Cache: Invalid response when fetching rulesets.' );
				return 'failed';
			}
		} catch ( Exception $e ) {
			error_log( 'Advanced Cloudflare Cache: Exception when fetching rulesets: ' . esc_html( $e->getMessage() ) );
			return 'failed';
		}

		$cache_ruleset_id = null;
		foreach ( $response_data['result'] as $ruleset ) {
			if ( 'http_request_cache_settings' === $ruleset['phase'] ) {
				$cache_ruleset_id = sanitize_text_field( $ruleset['id'] );
				break;
			}
		}

		$site_url = esc_url( get_site_url() );
		$rule     = [
			'expression'        => '(http.request.full_uri wildcard "' . $site_url . '/*" and not http.cookie contains "wordpress_logged" and not http.cookie contains "NO_CACHE" and not http.cookie contains "S+ESS" and not http.cookie contains "fbs" and not http.cookie contains "SimpleSAML" and not http.cookie contains "PHPSESSID" and not http.cookie contains "wordpress" and not http.cookie contains "wp-" and not http.cookie contains "comment_author_" and not http.cookie contains "duo_wordpress_auth_cookie" and not http.cookie contains "duo_secure_wordpress_auth_cookie" and not http.cookie contains "bp_completed_create_steps" and not http.cookie contains "bp_new_group_id" and not http.cookie contains "wp-resetpass-" and not http.cookie contains "woocommerce" and not http.cookie contains "amazon_Login_")',
			'action'            => 'set_cache_settings',
			'action_parameters' => [
				'cache' => true,
			],
			'description'       => 'EasyEngine Cache Manager Ruleset',
		];

		// If no cache rule exist then we can directly create a new.
		if ( null === $cache_ruleset_id ) {
			$ruleset = [
				'name'        => 'default',
				'kind'        => 'zone',
				'phase'       => 'http_request_cache_settings',
				'description' => 'Set\'s the edge cache rules by Nginx-Helper Cache Manager.',
				'rules'       => [ $rule ],
			];

			try {
				$ruleset_resp     = $adapter->post( sprintf( 'zones/%s/rulesets', esc_attr( $zone_id ) ), $ruleset );
				$raw_ruleset_body = $ruleset_resp->getBody();
				$ruleset_body     = json_decode( $raw_ruleset_body );

				if ( isset( $ruleset_body->success ) && true === $ruleset_body->success ) {
					return 'created';
				}

				error_log( 'Advanced Cloudflare Cache: Failed to create cache rule. Response: ' . wp_json_encode( $ruleset_body ) );
				return 'failed';
			} catch ( Exception $e ) {
				error_log( 'Advanced Cloudflare Cache: Exception when creating cache ruleset: ' . esc_html( $e->getMessage() ) );
				return 'failed';
			}
		}

		// Get the existing rule for cache and then update it to add our new rule.
		try {
			$ruleset_resp = $adapter->get( sprintf( 'zones/%s/rulesets/%s', esc_attr( $zone_id ), esc_attr( $cache_ruleset_id ) ) );

			if ( 200 !== $ruleset_resp->getStatusCode() ) {
				error_log( 'Advanced Cloudflare Cache: Failed to fetch existing cache rule. Ruleset ID: ' . wp_json_encode( $cache_ruleset_id ) );
				return 'failed';
			}
		} catch ( Exception $e ) {
			error_log( 'Advanced Cloudflare Cache: Exception when fetching existing ruleset: ' . esc_html( $e->getMessage() ) );
			return 'failed';
		}

		$raw_ruleset_body = $ruleset_resp->getBody();
		$ruleset_body     = json_decode( $raw_ruleset_body, true );

		$existing_rules = is_array( $ruleset_body['result']['rules'] ) ? $ruleset_body['result']['rules'] : [];

		$rule_exists = false;
		foreach ( $existing_rules as $existing_rule ) {
			if ( isset( $existing_rule['description'] ) && 'EasyEngine Cache Manager Ruleset' === $existing_rule['description'] ) {
				$rule_exists = true;
				break;
			}
		}

		if ( $rule_exists ) {
			return 'exists';
		}

		$existing_rules[] = $rule;

		try {
			$ruleset_resp     = $adapter->put( sprintf( 'zones/%s/rulesets/%s', esc_attr( $zone_id ), esc_attr( $cache_ruleset_id ) ), [ 'rules' => $existing_rules ] );
			$raw_ruleset_body = $ruleset_resp->getBody();
			$ruleset_body     = json_decode( $raw_ruleset_body );

			if ( isset( $ruleset_body->success ) && true === $ruleset_body->success ) {
				return 'created';
			}

			error_log( 'Advanced Cloudflare Cache: Failed to update cache rule. Response: ' . wp_json_encode( $ruleset_body ) );
			return 'failed';
		} catch ( Exception $e ) {
			error_log( 'Advanced Cloudflare Cache: Exception when updating cache ruleset: ' . esc_html( $e->getMessage() ) );
			return 'failed';
		}
	}
}
