<?php
/**
 * A wrapper for the Cloudflare API client.
 *
 * @package nginx-helper
 */

namespace EECacheHelper;

\ec_cf_maybe_load_vendor_autoloader();

use Cloudflare\API\Auth\APIToken;
use Cloudflare\API\Endpoints\Zones;

/**
 * Class Cloudflare_Client
 */
class Cloudflare_Client {

	/**
	 * Maximum number of tags or URLs sent in a single purge request.
	 *
	 * @var integer
	 */
	const PURGE_BATCH_SIZE = 100;

	/**
	 * Description of the plugin's cache rule, shared by every site on a zone.
	 *
	 * @var string
	 */
	const RULE_DESCRIPTION = 'EasyEngine Cache Helper Ruleset';

	/**
	 * Tags waiting to be purged at shutdown.
	 *
	 * @var array
	 */
	private static $queued_tags = [];

	/**
	 * URLs waiting to be purged at shutdown.
	 *
	 * @var array
	 */
	private static $queued_urls = [];

	/**
	 * Whether the shutdown flush has been registered.
	 *
	 * @var bool
	 */
	private static $shutdown_hooked = false;

	/**
	 * Whether the Cloudflare SDK is available. Logs the reason when it is not.
	 *
	 * Callers such as the CLI commands do not check that Cloudflare is enabled, and constructing an
	 * SDK class that was never loaded is a fatal error that cannot be caught as an Exception.
	 *
	 * @return bool
	 */
	private static function is_ready() {
		$is_ready = ec_cf_maybe_load_vendor_autoloader();

		if ( ! $is_ready ) {
			error_log( 'Advanced Cloudflare Cache: The Cloudflare SDK is missing. Run "composer install" in the plugin folder or use the release version.' );
		}

		return $is_ready;
	}

	/**
	 * Queue tags to be purged once at the end of the request.
	 *
	 * @param array $tags The tags to purge.
	 */
	public static function queueTags( array $tags ) {
		if ( empty( $tags ) ) {
			return;
		}

		self::$queued_tags = array_merge( self::$queued_tags, $tags );
		self::hook_shutdown();
	}

	/**
	 * Queue URLs to be purged once at the end of the request.
	 *
	 * Paths are converted to full URLs now, so the right site is used on multisite.
	 *
	 * @param array $urls The URLs or paths to purge.
	 */
	public static function queueUrls( array $urls ) {
		$urls = array_filter( array_map( array( self::class, 'to_full_url' ), $urls ) );

		if ( empty( $urls ) ) {
			return;
		}

		self::$queued_urls = array_merge( self::$queued_urls, $urls );
		self::hook_shutdown();
	}

	/**
	 * Register the shutdown flush once.
	 */
	private static function hook_shutdown() {
		if ( self::$shutdown_hooked ) {
			return;
		}

		self::$shutdown_hooked = true;
		add_action( 'shutdown', array( self::class, 'flush_queue' ), 100 );
	}

	/**
	 * Send everything queued during the request, after the response has been delivered where possible.
	 */
	public static function flush_queue() {
		$tags = array_values( array_unique( self::$queued_tags ) );
		$urls = array_values( array_unique( self::$queued_urls ) );

		self::$queued_tags = [];
		self::$queued_urls = [];

		/**
		 * Filters the cache tags about to be purged at the end of the request.
		 *
		 * Runs once per request, so it can add, change or remove tags. Return an empty array to skip the purge.
		 *
		 * @param string[] $tags Cache tags collected during the request.
		 */
		$tags = array_values( array_unique( array_filter( (array) apply_filters( 'ec_cf_flush_tags', $tags ) ) ) );

		/**
		 * Filters the URLs about to be purged at the end of the request.
		 *
		 * Runs once per request. Paths are converted to full URLs before they are sent.
		 *
		 * @param string[] $urls URLs collected during the request.
		 */
		$urls = array_values( array_unique( array_filter( (array) apply_filters( 'ec_cf_flush_urls', $urls ) ) ) );

		if ( empty( $tags ) && empty( $urls ) ) {
			return;
		}

		// Let the visitor's response finish first so they do not wait for the API calls.
		if ( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		}

		if ( ! empty( $tags ) ) {
			self::purgeByTags( $tags );
		}

		if ( ! empty( $urls ) ) {
			self::purgeByUrls( $urls );
		}
	}

	/**
	 * Purge the cache for a given set of tags.
	 *
	 * @param array $tags The tags to purge.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function purgeByTags( array $tags ) {
		if ( empty( $tags ) || ! self::is_ready() ) {
			return false;
		}

		global $nginx_helper_admin;

		// Same as the Nginx purger: do not purge while an import is running.
		if ( $nginx_helper_admin->is_import_request() ) {
			return false;
		}

		$options = $nginx_helper_admin->get_cloudflare_settings();
		$token   = isset( $options['api_token'] ) ? $options['api_token'] : '';
		$zone_id = isset( $options['zone_id'] ) ? $options['zone_id'] : '';

		if ( empty( $token ) || empty( $zone_id ) ) {
			error_log( 'Advanced Cloudflare Cache: API Token or Zone ID not configured.' );

			return false;
		}

		$success = true;

		// Cloudflare limits the number of tags accepted per purge request.
		foreach ( array_chunk( array_values( array_unique( $tags ) ), self::PURGE_BATCH_SIZE ) as $batch ) {
			try {
				$key     = new APIToken( $token );
				$adapter = new Cloudflare_Adapter( $key );
				$zones   = new Zones( $adapter );

				$result = $zones->cachePurge( $zone_id, null, $batch, null );

				if ( $result ) {
					error_log( 'Advanced Cloudflare Cache: Successfully purged by tags: ' . implode( ', ', $batch ) );
				} else {
					error_log( 'Advanced Cloudflare Cache: Failed to purge by tags: ' . implode( ', ', $batch ) );
					$success = false;
				}
			} catch ( \Throwable $e ) {
				error_log( 'Advanced Cloudflare Cache: Exception when purging by tags: ' . $e->getMessage() );
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Purge the entire cache for the zone.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function purgeEverything() {
		if ( ! self::is_ready() ) {
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
			$adapter = new Cloudflare_Adapter( $key );
			$zones   = new Zones( $adapter );

			$result = $zones->cachePurgeEverything( $zone_id );

			if ( $result ) {
				error_log( 'Advanced Cloudflare Cache: Successfully purged everything.' );

				// Everything was purged, so anything still queued is redundant.
				self::$queued_tags = [];
				self::$queued_urls = [];

				return true;
			} else {
				error_log( 'Advanced Cloudflare Cache: Failed to purge everything.' );

				return false;
			}
		} catch ( \Throwable $e ) {
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

		if ( empty( $urls ) || ! self::is_ready() ) {
			return false;
		}

		$urls = array_values( array_unique( array_filter( array_map( array( self::class , 'to_full_url' ), $urls ) ) ) );

		if ( empty( $urls ) ) {
			return false;
		}

		global $nginx_helper_admin;

		// Same as the Nginx purger: do not purge while an import is running.
		if ( $nginx_helper_admin->is_import_request() ) {
			return false;
		}

		$options = $nginx_helper_admin->get_cloudflare_settings();
		$token   = isset( $options['api_token'] ) ? $options['api_token'] : '';
		$zone_id = isset( $options['zone_id'] ) ? $options['zone_id'] : '';

		if ( empty( $token ) || empty( $zone_id ) ) {
			error_log( 'Advanced Cloudflare Cache: API Token or Zone ID not configured.' );

			return false;
		}

		$success = true;

		// Cloudflare limits the number of URLs accepted per purge request.
		foreach ( array_chunk( $urls, self::PURGE_BATCH_SIZE ) as $batch ) {
			try {
				$key     = new APIToken( $token );
				$adapter = new Cloudflare_Adapter( $key );
				$zones   = new Zones( $adapter );

				$result = $zones->cachePurge( $zone_id, $batch, null, null );

				if ( $result ) {
					error_log( 'Advanced Cloudflare Cache: Successfully purged by URLs: ' . implode( ', ', $batch ) );
				} else {
					error_log( 'Advanced Cloudflare Cache: Failed to purge by URLs: ' . implode( ', ', $batch ) );
					$success = false;
				}
			} catch ( \Throwable $e ) {
				error_log( 'Advanced Cloudflare Cache: Exception when purging by URLs: ' . $e->getMessage() );
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Convert a path to a full URL, leaving full URLs untouched.
	 *
	 * @param mixed $path Path such as '/' or '/blog/', or a full URL.
	 *
	 * @return string Full URL, or an empty string for an empty value.
	 */
	public static function to_full_url( $path ) {
		$path = trim( (string) $path );

		if ( '' === $path ) {
			return '';
		}

		$parsed = wp_parse_url( $path );

		// Skip values that are absolute URLs.
		if ( ! empty( $parsed['host'] ) && ! empty( $parsed['scheme'] ) && in_array( strtolower( $parsed['scheme'] ), array( 'http', 'https' ), true ) ) {
			return $path;
		}

		return home_url( $path );
	}

	/**
	 * Sets up the "Cache Rule" required to purge the edge cache.
	 *
	 * @return string 'created', 'exists', or 'failed'.
	 */
	public static function setupCacheRule() {
		global $nginx_helper_admin;

		if ( ! $nginx_helper_admin || ! self::is_ready() ) {
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
		$adapter = new Cloudflare_Adapter( $key );

		try {
			$rulesets_response = $adapter->get( sprintf( 'zones/%s/rulesets', esc_attr( $zone_id ) ) );
			$raw_response      = $rulesets_response->getBody() ?? '';
			$response_data     = json_decode( $raw_response, true );

			if ( ! is_array( $response_data ) || ! array_key_exists( 'result', $response_data ) ) {
				error_log( 'Advanced Cloudflare Cache: Invalid response when fetching rulesets.' );
				return 'failed';
			}
		} catch ( \Throwable $e ) {
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

		$site_url = esc_url( home_url() );
		$rule     = [
			'expression'        => '(http.request.full_uri wildcard "' . $site_url . '/*" and not http.cookie contains "wordpress_logged" and not http.cookie contains "NO_CACHE" and not http.cookie contains "S+ESS" and not http.cookie contains "fbs" and not http.cookie contains "SimpleSAML" and not http.cookie contains "PHPSESSID" and not http.cookie contains "wordpress" and not http.cookie contains "wp-" and not http.cookie contains "comment_author_" and not http.cookie contains "duo_wordpress_auth_cookie" and not http.cookie contains "duo_secure_wordpress_auth_cookie" and not http.cookie contains "bp_completed_create_steps" and not http.cookie contains "bp_new_group_id" and not http.cookie contains "wp-resetpass-" and not http.cookie contains "woocommerce" and not http.cookie contains "amazon_Login_")',
			'action'            => 'set_cache_settings',
			'action_parameters' => [
				'cache' => true,
			],
			'description'       => self::RULE_DESCRIPTION,
		];

		// If no cache rule exist then we can directly create a new.
		if ( null === $cache_ruleset_id ) {
			$ruleset = [
				'name'        => 'default',
				'kind'        => 'zone',
				'phase'       => 'http_request_cache_settings',
				'description' => 'Set\'s the edge cache rules by EasyEngine Cache Helper.',
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
			} catch ( \Throwable $e ) {
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
		} catch ( \Throwable $e ) {
			error_log( 'Advanced Cloudflare Cache: Exception when fetching existing ruleset: ' . esc_html( $e->getMessage() ) );
			return 'failed';
		}

		$raw_ruleset_body = $ruleset_resp->getBody();
		// Decode as objects so empty JSON objects ({}) are not turned into arrays ([]) on the way back.
		$ruleset_body   = json_decode( $raw_ruleset_body );
		$existing_rules = ( isset( $ruleset_body->result->rules ) && is_array( $ruleset_body->result->rules ) ) ? $ruleset_body->result->rules : [];

		// Find this site's rule, if it was set up before. The address without the scheme tells sites on one zone apart.
		$site_address = str_replace( [ 'https://', 'http://' ], '', $site_url );
		$site_rule    = null;
		foreach ( $existing_rules as $existing_rule ) {
			if ( self::is_site_rule( $existing_rule, $site_address ) ) {
				$site_rule = $existing_rule;
				break;
			}
		}

		$rules_uri = sprintf( 'zones/%s/rulesets/%s/rules', esc_attr( $zone_id ), esc_attr( $cache_ruleset_id ) );

		if ( null === $site_rule ) {
			// Add only our rule, first in the list. The other rules in the ruleset are not touched.
			$new_rule = $rule;
			if ( ! empty( $existing_rules[0]->id ) ) {
				$new_rule['position'] = [ 'before' => $existing_rules[0]->id ];
			}

			return self::send_rule_request( $adapter, 'post', $rules_uri, $new_rule, 'created' );
		}

		if ( self::rule_matches( $site_rule, $rule ) ) {
			return 'exists';
		}

		// The rule is outdated (changed expression, other host, or disabled). Replace just this rule, keeping its position.
		if ( empty( $site_rule->id ) ) {
			return 'failed';
		}

		$updated_rule = $rule + [ 'enabled' => true ];

		return self::send_rule_request( $adapter, 'patch', $rules_uri . '/' . rawurlencode( $site_rule->id ), $updated_rule, 'updated' );
	}

	/**
	 * Whether an existing rule is the plugin's rule for this site.
	 *
	 * Descriptions are not unique in Cloudflare, and all sites on a zone share ours, so the rule is
	 * identified by the plugin's description plus this site's address in its expression.
	 *
	 * @param object $existing_rule Rule from the ruleset.
	 * @param string $site_address  This site's address without the scheme (host, port and path).
	 *
	 * @return bool
	 */
	private static function is_site_rule( $existing_rule, $site_address ) {
		return isset( $existing_rule->description, $existing_rule->expression )
			&& self::RULE_DESCRIPTION === $existing_rule->description
			&& false !== stripos( $existing_rule->expression, '://' . $site_address . '/*"' );
	}

	/**
	 * Whether an existing rule is enabled and already does what the rule built for this site does.
	 *
	 * Only the settings we send are compared, so extra defaults added by Cloudflare do not count as a change.
	 *
	 * @param object $existing_rule Rule from the ruleset.
	 * @param array  $rule          The rule built for this site.
	 *
	 * @return bool
	 */
	private static function rule_matches( $existing_rule, array $rule ) {
		if ( isset( $existing_rule->enabled ) && true !== $existing_rule->enabled ) {
			return false;
		}

		foreach ( [ 'expression', 'action' ] as $field ) {
			if ( ! isset( $existing_rule->$field ) || $rule[ $field ] !== $existing_rule->$field ) {
				return false;
			}
		}

		$existing_parameters = isset( $existing_rule->action_parameters ) ? json_decode( wp_json_encode( $existing_rule->action_parameters ), true ) : [];

		foreach ( $rule['action_parameters'] as $name => $value ) {
			if ( ! is_array( $existing_parameters ) || ! array_key_exists( $name, $existing_parameters ) || $value !== $existing_parameters[ $name ] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Send a rule to Cloudflare and report the result.
	 *
	 * @param Cloudflare_Adapter $adapter Cloudflare API adapter.
	 * @param string $method  'post' to add a rule or 'patch' to replace one.
	 * @param string $uri     API path.
	 * @param array  $payload Rule definition.
	 * @param string $success Result to return on success.
	 *
	 * @return string The given success result, or 'failed'.
	 */
	private static function send_rule_request( $adapter, $method, $uri, array $payload, $success ) {
		try {
			$response = $adapter->$method( $uri, $payload );
			$body     = json_decode( $response->getBody() );

			if ( isset( $body->success ) && true === $body->success ) {
				return $success;
			}

			error_log( 'Advanced Cloudflare Cache: Failed to save cache rule. Response: ' . wp_json_encode( $body ) );
			return 'failed';
		} catch ( \Throwable $e ) {
			error_log( 'Advanced Cloudflare Cache: Exception when saving cache rule: ' . esc_html( $e->getMessage() ) );
			return 'failed';
		}
	}
}
