<?php
/**
 * A client for the Cloudflare API, built on the WordPress HTTP API.
 *
 * @package nginx-helper
 */

namespace EECacheHelper;

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
	 * Most tags or URLs named in the log line of a purge request. The rest are counted.
	 *
	 * @var integer
	 */
	const LOG_ITEMS_LIMIT = 20;

	/**
	 * Description of the plugin's cache rule, shared by every site on a zone.
	 *
	 * @var string
	 */
	const RULE_DESCRIPTION = 'EasyEngine Cache Helper Ruleset';

	/**
	 * Cloudflare API base URL.
	 *
	 * @var string
	 */
	const API_BASE = 'https://api.cloudflare.com/client/v4/';

	/**
	 * Seconds to wait for an API response. WordPress uses this for both connecting and the whole request.
	 *
	 * @var integer
	 */
	const API_TIMEOUT = 30;

	/**
	 * Rulesets requested per page (Cloudflare allows 1 to 50).
	 *
	 * @var integer
	 */
	const RULESETS_PER_PAGE = 50;

	/**
	 * Most pages of rulesets read while looking for the cache ruleset.
	 *
	 * @var integer
	 */
	const RULESETS_MAX_PAGES = 20;

	/**
	 * Site transient holding the last failure, shown to the user until a purge succeeds.
	 * It holds one record per API token (as a hash), as Cloudflare's limits are per account.
	 *
	 * @var string
	 */
	const FAILURE_TRANSIENT = 'ec_cf_purge_failure';

	/**
	 * Seconds the last failure is remembered.
	 *
	 * @var integer
	 */
	const FAILURE_TTL = 3600;

	/**
	 * Seconds to wait before reaching out the API when Cloudflare does not say how long to wait.
	 *
	 * @var integer
	 */
	const DEFAULT_WAIT = 60;

	/**
	 * Longest wait, in seconds, after a rate limit.
	 *
	 * @var integer
	 */
	const MAX_WAIT = 3600;

	/**
	 * Site transient holding purges that were not sent because Cloudflare was rate limiting, per set of credentials.
	 *
	 * @var string
	 */
	const BACKLOG_TRANSIENT = 'ec_cf_purge_backlog';

	/**
	 * Site option used as a lock, so only one request at a time sends the backlog.
	 *
	 * @var string
	 */
	const BACKLOG_LOCK = 'ec_cf_backlog_lock';

	/**
	 * Most tags, and most URLs, kept in the backlog per set of credentials. More are dropped (and logged).
	 *
	 * @var integer
	 */
	const BACKLOG_LIMIT = 1000;

	/**
	 * Most tags, and most URLs, taken from the backlog by one request: one purge request's worth, so a
	 * backlog is paid back slowly and cannot bring the rate limit down again at once.
	 *
	 * @var integer
	 */
	const BACKLOG_DRAIN_LIMIT = self::PURGE_BATCH_SIZE;

	/**
	 * Seconds a backlog is kept. Purges older than this are dropped, as the pages have likely expired.
	 *
	 * @var integer
	 */
	const BACKLOG_TTL = 3600;

	/**
	 * Seconds after which the backlog lock counts as abandoned by a request that died.
	 *
	 * @var integer
	 */
	const LOCK_TTL = 60;

	/**
	 * Start of the names of the site options used as locks while one request checks that a rate limit is over.
	 *
	 * @var string
	 */
	const PROBE_LOCK_PREFIX = 'ec_cf_probe_';

	/**
	 * Tags and URLs waiting to be purged at shutdown, per blog: [ blog ID => [ 'tags' => [], 'urls' => [] ] ].
	 *
	 * Each list is a set (the tag or URL is the key), so queueing the same one again costs nothing and a bulk
	 * operation does not grow the queue or slow down with every call.
	 *
	 * Kept per blog because each blog can use its own Cloudflare credentials on multisite.
	 *
	 * @var array
	 */
	private static $queue = [];

	/**
	 * Queue tags to be purged once at the end of the request.
	 *
	 * Nothing is queued for a site that has no Cloudflare credentials.
	 *
	 * @param array $tags    The tags to purge.
	 * @param int   $blog_id Site the tags are for, the current site if not given.
	 */
	public static function queue_tags( array $tags, $blog_id = 0 ) {
		$blog_id = $blog_id ? (int) $blog_id : get_current_blog_id();

		if ( empty( $tags ) || ! self::is_enabled_for_blog( $blog_id ) ) {
			return;
		}

		self::$queue[ $blog_id ] = self::$queue[ $blog_id ] ?? [ 'tags' => [], 'urls' => [] ];
		self::$queue[ $blog_id ]['tags'] += array_fill_keys( array_map( 'strval', $tags ), true );
		self::hook_shutdown();
	}

	/**
	 * Queue URLs to be purged once at the end of the request.
	 *
	 * Paths are converted to full URLs now, so the right site is used on multisite. Nothing is queued for a site
	 * that has no Cloudflare credentials.
	 *
	 * @param array $urls    The URLs or paths to purge.
	 * @param int   $blog_id Site the URLs are for, the current site if not given.
	 */
	public static function queue_urls( array $urls, $blog_id = 0 ) {
		$blog_id = $blog_id ? (int) $blog_id : get_current_blog_id();

		if ( empty( $urls ) || ! self::is_enabled_for_blog( $blog_id ) ) {
			return;
		}

		$urls = array_filter( array_map( array( self::class, 'to_full_url' ), $urls ) );

		if ( empty( $urls ) ) {
			return;
		}

		self::$queue[ $blog_id ] = self::$queue[ $blog_id ] ?? [ 'tags' => [], 'urls' => [] ];
		self::$queue[ $blog_id ]['urls'] += array_fill_keys( $urls, true );
		self::hook_shutdown();
	}

	/**
	 * Whether Cloudflare is set up for a site.
	 *
	 * @param int $blog_id Site ID.
	 *
	 * @return bool
	 */
	private static function is_enabled_for_blog( $blog_id ) {
		global $nginx_helper_admin;

		return $nginx_helper_admin && $nginx_helper_admin->is_cloudflare_enabled( $blog_id );
	}

	/**
	 * Register the shutdown flush once.
	 */
	private static function hook_shutdown() {
		if ( false === has_action( 'shutdown', array( self::class, 'flush_queue' ) ) ) {
			add_action( 'shutdown', array( self::class, 'flush_queue' ), 100 );
		}
	}

	/**
	 * Send everything queued during the request, after the response has been delivered where possible.
	 */
	public static function flush_queue() {
		$queue       = self::$queue;
		self::$queue = [];

		// Blogs that use the same Cloudflare credentials are sent together, so they cost one request, not one per blog.
		$groups = [];

		foreach ( $queue as $blog_id => $items ) {
			$credentials = self::credentials_for_blog( $blog_id );

			if ( ! $credentials ) {
				continue;
			}

			$key = self::credentials_key( $credentials );

			$groups[ $key ] = $groups[ $key ] ?? [
				'credentials' => $credentials,
				'blogs'       => [],
				'tags'        => [],
				'urls'        => [],
			];

			$groups[ $key ]['blogs'][] = (int) $blog_id;
			$groups[ $key ]['tags']    = array_merge( $groups[ $key ]['tags'], array_map( 'strval', array_keys( $items['tags'] ) ) ); // PHP turns numeric keys into integers.
			$groups[ $key ]['urls']    = array_merge( $groups[ $key ]['urls'], array_keys( $items['urls'] ) );
		}

		$response_finished = false;

		foreach ( $groups as $group ) {
			/**
			 * Filters the cache tags about to be purged at the end of the request.
			 *
			 * Runs once per request (once per set of Cloudflare credentials on multisite), so it can add,
			 * change or remove tags. Return an empty array to skip the purge.
			 *
			 * @param string[] $tags     Cache tags collected during the request.
			 * @param int[]    $blog_ids Blogs the tags were collected for.
			 */
			$tags = array_values( array_unique( array_filter( (array) apply_filters( 'ec_cf_flush_tags', array_values( array_unique( $group['tags'] ) ), $group['blogs'] ) ) ) );

			/**
			 * Filters the URLs about to be purged at the end of the request.
			 *
			 * Runs once per request (once per set of Cloudflare credentials on multisite). Paths are converted
			 * to full URLs before they are sent.
			 *
			 * @param string[] $urls     URLs collected during the request.
			 * @param int[]    $blog_ids Blogs the URLs were collected for.
			 */
			$urls = array_values( array_unique( array_filter( (array) apply_filters( 'ec_cf_flush_urls', array_values( array_unique( $group['urls'] ) ), $group['blogs'] ) ) ) );

			if ( empty( $tags ) && empty( $urls ) ) {
				continue;
			}

			// Pay back a little of what an earlier rate limit made us skip: bounded, and by one request at a time.
			$backlog   = self::take_backlog( $group['credentials'], $tags, $urls );
			$has_lock  = ! empty( $backlog['tags'] ) || ! empty( $backlog['urls'] );
			$tags    = array_values( array_unique( array_merge( $tags, $backlog['tags'] ) ) );
			$urls    = array_values( array_unique( array_merge( $urls, $backlog['urls'] ) ) );

			// Let the visitor's response finish first so they do not wait for the API calls.
			if ( ! $response_finished && function_exists( 'fastcgi_finish_request' ) ) {
				fastcgi_finish_request();
				$response_finished = true;
			}

			// What a rate limit stopped from being sent goes to the backlog, not away.
			if ( ! empty( $tags ) ) {
				$unsent = [];
				self::send_purge( 'tags', $tags, $group['credentials'], $unsent );
				self::add_backlog( $group['credentials'], 'tags', $unsent );
			}

			if ( ! empty( $urls ) ) {
				$unsent = [];
				self::send_purge( 'files', $urls, $group['credentials'], $unsent );
				self::add_backlog( $group['credentials'], 'files', $unsent );
			}

			if ( $has_lock ) {
				self::release_lock( self::BACKLOG_LOCK );
			}
		}
	}

	/**
	 * Key identifying a set of credentials in the backlog. A hash, so the token is never stored there.
	 *
	 * @param array $credentials [ token, zone ID ].
	 *
	 * @return string
	 */
	private static function credentials_key( array $credentials ) {
		return substr( wp_hash( $credentials[0] . '|' . $credentials[1] ), 0, 20 );
	}

	/**
	 * Get all backlogs, keyed by credentials, without the ones that are too old.
	 *
	 * @return array
	 */
	private static function get_backlogs() {
		$backlogs = get_site_transient( self::BACKLOG_TRANSIENT );

		if ( ! is_array( $backlogs ) ) {
			return [];
		}

		foreach ( $backlogs as $key => $backlog ) {
			if ( ! is_array( $backlog ) || ! isset( $backlog['time'] ) || (int) $backlog['time'] + self::BACKLOG_TTL < time() ) {
				unset( $backlogs[ $key ] );
			}
		}

		return $backlogs;
	}

	/**
	 * Keep purges that a rate limit stopped from being sent, to send them once the limit is over.
	 *
	 * The backlog is capped, and dropped when old, so a burst of edits cannot pile up without end.
	 *
	 * @param array  $credentials [ token, zone ID ].
	 * @param string $type        'tags' or 'files' (URLs).
	 * @param array  $items       The tags or URLs that were not sent.
	 */
	private static function add_backlog( array $credentials, $type, array $items ) {
		if ( empty( $items ) ) {
			return;
		}

		$field    = 'tags' === $type ? 'tags' : 'urls';
		$backlogs = self::get_backlogs();
		$key      = self::credentials_key( $credentials );
		$backlog  = $backlogs[ $key ] ?? [
			'tags' => [],
			'urls' => [],
			'time' => time(),
		];

		$merged = array_values( array_unique( array_merge( $backlog[ $field ], $items ) ) );

		if ( count( $merged ) > self::BACKLOG_LIMIT ) {
			error_log( 'Advanced Cloudflare Cache: Backlog is full, dropping ' . ( count( $merged ) - self::BACKLOG_LIMIT ) . ' ' . ( 'tags' === $field ? 'tags' : 'URLs' ) . ' that could not be purged.' );
			$merged = array_slice( $merged, 0, self::BACKLOG_LIMIT );
		}

		$backlog[ $field ] = $merged;
		$backlogs[ $key ]  = $backlog;

		set_site_transient( self::BACKLOG_TRANSIENT, $backlogs, self::BACKLOG_TTL );
	}

	/**
	 * Take a small part of the backlog for these credentials, if nobody else is sending it and the limit is over.
	 *
	 * Takes at most BACKLOG_DRAIN_LIMIT tags and URLs. When it returns anything, the caller holds the lock and must call release_lock( self::BACKLOG_LOCK ) afterwards.
	 * Whatever stays in the backlog and is about to be sent with the current purge is dropped from it, so
	 * nothing is purged twice.
	 *
	 * @param array $credentials  [ token, zone ID ].
	 * @param array $current_tags Tags about to be sent with the current purge.
	 * @param array $current_urls URLs about to be sent with the current purge.
	 *
	 * @return array [ 'tags' => [], 'urls' => [] ]
	 */
	private static function take_backlog( array $credentials, array $current_tags = [], array $current_urls = [] ) {
		$taken    = [
			'tags' => [],
			'urls' => [],
		];
		$backlogs = self::get_backlogs();
		$key      = self::credentials_key( $credentials );

		if ( empty( $backlogs[ $key ] ) || ! self::acquire_lock( self::BACKLOG_LOCK, self::LOCK_TTL ) ) {
			return $taken;
		}

		// Read again now that nobody else can change it.
		$backlogs = self::get_backlogs();
		$backlog  = $backlogs[ $key ] ?? null;

		if ( $backlog ) {
			// Tags and URLs are limited separately by Cloudflare, so each waits for its own limit.
			foreach ( [
				'tags' => 'tags',
				'urls' => 'files',
			] as $field => $bucket ) {
				if ( empty( $backlog[ $field ] ) || self::rate_limited_for( $credentials[0], $bucket ) > 0 ) {
					continue;
				}

				$taken[ $field ]   = array_slice( $backlog[ $field ], 0, self::BACKLOG_DRAIN_LIMIT );
				$backlog[ $field ] = array_slice( $backlog[ $field ], self::BACKLOG_DRAIN_LIMIT );

				// What is left and is sent with the current purge anyway must not be purged a second time later.
				$backlog[ $field ] = array_values( array_diff( $backlog[ $field ], 'tags' === $field ? $current_tags : $current_urls ) );
			}

			if ( empty( $backlog['tags'] ) && empty( $backlog['urls'] ) ) {
				unset( $backlogs[ $key ] );
			} else {
				$backlogs[ $key ] = $backlog;
			}

			if ( empty( $backlogs ) ) {
				delete_site_transient( self::BACKLOG_TRANSIENT );
			} else {
				set_site_transient( self::BACKLOG_TRANSIENT, $backlogs, self::BACKLOG_TTL );
			}
		}

		if ( empty( $taken['tags'] ) && empty( $taken['urls'] ) ) {
			self::release_lock( self::BACKLOG_LOCK );
		}

		return $taken;
	}

	/**
	 * Forget the backlog of credentials, after everything was purged for them.
	 *
	 * @param array $credentials [ token, zone ID ].
	 */
	private static function clear_backlog( array $credentials ) {
		$backlogs = self::get_backlogs();
		$key      = self::credentials_key( $credentials );

		if ( ! isset( $backlogs[ $key ] ) ) {
			return;
		}

		unset( $backlogs[ $key ] );

		if ( empty( $backlogs ) ) {
			delete_site_transient( self::BACKLOG_TRANSIENT );
		} else {
			set_site_transient( self::BACKLOG_TRANSIENT, $backlogs, self::BACKLOG_TTL );
		}
	}

	/**
	 * Take a lock, so only one request at a time does what it protects. A lock left behind by a dead request expires.
	 *
	 * @param string  $name Site option used as the lock.
	 * @param integer $ttl  Seconds after which the lock counts as abandoned.
	 *
	 * @return bool Whether the lock is ours now.
	 */
	private static function acquire_lock( $name, $ttl ) {
		if ( add_site_option( $name, time() ) ) {
			return true;
		}

		$since = (int) get_site_option( $name );

		if ( $since && time() - $since > $ttl ) {
			delete_site_option( $name );

			if ( add_site_option( $name, time() ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Give a lock back. Only call it after acquire_lock() succeeded.
	 *
	 * @param string $name Site option used as the lock.
	 */
	private static function release_lock( $name ) {
		delete_site_option( $name );
	}

	/**
	 * Get the Cloudflare credentials a blog uses. On multisite this looks at that blog's own settings.
	 *
	 * @param int  $blog_id Blog ID.
	 * @param bool $log     Whether to log when Cloudflare is not configured for the blog.
	 *
	 * @return array|null [ token, zone ID ], or null if the blog was deleted or has no credentials.
	 */
	private static function credentials_for_blog( $blog_id, $log = true ) {
		// The blog may have been deleted since the purge was queued.
		if ( is_multisite() && ! get_site( $blog_id ) ) {
			return null;
		}

		return self::get_credentials( $log, $blog_id );
	}

	/**
	 * Drop what is queued for blogs that use the given credentials, after everything was purged for them.
	 *
	 * @param array $credentials [ token, zone ID ].
	 */
	private static function forget_queued( array $credentials ) {
		foreach ( array_keys( self::$queue ) as $blog_id ) {
			if ( self::credentials_for_blog( $blog_id, false ) === $credentials ) {
				unset( self::$queue[ $blog_id ] );
			}
		}
	}

	/**
	 * Purge the cache for a given set of tags.
	 *
	 * @param array $tags The tags to purge.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function purge_by_tags( array $tags ) {
		return self::send_purge( 'tags', $tags );
	}

	/**
	 * Purge the cache for a given set of URLs.
	 *
	 * @param array $urls The URLs to purge.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function purge_by_urls( array $urls ) {
		$urls = array_values( array_unique( array_filter( array_map( array( self::class, 'to_full_url' ), $urls ) ) ) );

		return self::send_purge( 'files', $urls );
	}

	/**
	 * Purge the entire cache for the zone.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function purge_everything() {
		$credentials = self::get_credentials();

		if ( ! $credentials ) {
			return false;
		}

		list( $token, $zone_id ) = $credentials;

		$result = self::api_request( $token, 'POST', 'zones/' . rawurlencode( $zone_id ) . '/purge_cache', [ 'purge_everything' => true ], 'tags' );

		if ( ! $result['ok'] ) {
			self::record_failure( $result, $token );

			return false;
		}

		error_log( 'Advanced Cloudflare Cache: Successfully purged everything.' );

		// Everything was purged, so anything still queued or waiting in the backlog for the same zone is redundant.
		self::forget_queued( $credentials );
		self::clear_backlog( $credentials );
		self::clear_failure( $token, $result['bucket'] );

		return true;
	}

	/**
	 * Send tags or URLs to Cloudflare in batches.
	 *
	 * Stops at the first batch that fails, as the rest would hit the same limit or outage.
	 *
	 * @param string     $type        'tags' or 'files' (URLs).
	 * @param array      $items       The tags or URLs.
	 * @param array|null $credentials [ token, zone ID ] to use, or null for the current blog's.
	 * @param array|null $unsent      Filled with what a rate limit stopped from being sent, if given.
	 *
	 * @return bool True if everything was sent.
	 */
	private static function send_purge( $type, array $items, ?array $credentials = null, ?array &$unsent = null ) {
		if ( empty( $items ) ) {
			return false;
		}

		global $nginx_helper_admin;

		// Same as the Nginx purger: do not purge while an import is running.
		if ( ! $nginx_helper_admin || $nginx_helper_admin->is_import_request() ) {
			return false;
		}

		$credentials = $credentials ?? self::get_credentials();

		if ( ! $credentials ) {
			return false;
		}

		list( $token, $zone_id ) = $credentials;

		$label = 'tags' === $type ? 'tags' : 'URLs';
		$path  = 'zones/' . rawurlencode( $zone_id ) . '/purge_cache';

		// Cloudflare limits the number of tags or URLs accepted per purge request.
		$batches = array_chunk( array_values( array_unique( $items ) ), self::PURGE_BATCH_SIZE );

		foreach ( $batches as $index => $batch ) {
			$result = self::api_request( $token, 'POST', $path, [ $type => $batch ], 'tags' === $type ? 'tags' : 'files' );

			if ( ! $result['ok'] ) {
				self::record_failure( $result, $token );

				// A rate limit is temporary, so the caller can keep what was not sent for later.
				if ( null !== $unsent && $result['rate_limited'] ) {
					foreach ( array_slice( $batches, $index ) as $remaining ) {
						$unsent = array_merge( $unsent, $remaining );
					}
				}

				return false;
			}

			error_log( 'Advanced Cloudflare Cache: Successfully purged by ' . $label . ': ' . implode( ', ', array_slice( $batch, 0, self::LOG_ITEMS_LIMIT ) ) . ( count( $batch ) > self::LOG_ITEMS_LIMIT ? ' and ' . ( count( $batch ) - self::LOG_ITEMS_LIMIT ) . ' more' : '' ) );
		}

		self::clear_failure( $token, $result['bucket'] );

		return true;
	}

	/**
	 * Get the API token and zone ID of the current blog, or log why they are not available.
	 *
	 * @param bool $log Whether to log when Cloudflare is not configured.
	 *
	 * @return array|null [ token, zone ID ], or null when Cloudflare is not configured.
	 */
	private static function get_credentials( $log = true, $blog_id = 0 ) {
		global $nginx_helper_admin;

		if ( ! $nginx_helper_admin ) {
			return null;
		}

		$options = $nginx_helper_admin->get_cloudflare_settings( $blog_id );
		$token   = isset( $options['api_token'] ) ? sanitize_text_field( $options['api_token'] ) : '';
		$zone_id = isset( $options['zone_id'] ) ? sanitize_text_field( $options['zone_id'] ) : '';

		if ( empty( $token ) || empty( $zone_id ) ) {
			if ( $log ) {
				error_log( 'Advanced Cloudflare Cache: API Token or Zone ID not configured.' );
			}

			return null;
		}

		return [ $token, $zone_id ];
	}

	/**
	 * Send a request to the Cloudflare API.
	 *
	 * Nothing is sent while Cloudflare is rate limiting us. The reason for every failure is logged.
	 *
	 * @param string     $token  API token.
	 * @param string     $method GET, POST or PATCH.
	 * @param string     $path   Path after the API base, e.g. zones/<id>/purge_cache.
	 * @param array|null $body   JSON body.
	 * @param string     $bucket Rate limit the request counts against. Tag, hostname, prefix and purge-everything
	 *                           calls share one ('tags'), single-file (URL) purges have their own ('files'), and every
	 *                           other API call such as rulesets is limited on its own again ('api').
	 *
	 * @return array {
	 *     @type bool        $ok           Whether the request succeeded.
	 *     @type object|null $data         Decoded response.
	 *     @type bool        $rate_limited Whether Cloudflare's rate limit stopped the request.
	 *     @type int         $wait         Seconds until the rate limit ends, 0 if not rate limited.
	 *     @type bool        $skipped      Only set when the request was not sent because of a rate limit.
	 *     @type string      $bucket       Rate limit the request counts against: 'tags', 'files' or 'api'.
	 * }
	 */
	private static function api_request( $token, $method, $path, ?array $body = null, $bucket = 'api' ) {
		$result = self::send_request( $token, $method, $path, $body, $bucket );

		$result['bucket'] = $bucket;

		return $result;
	}

	/**
	 * Send a request to the Cloudflare API, unless the limit it counts against is still reached.
	 *
	 * When a rate limit has just ended, only one request at a time is let through to find out whether it is
	 * really over. The others are told it still is, so a crowd of requests does not hit the API together and
	 * start the limit again. A success clears the failure, which lets everyone through.
	 *
	 * @param string     $token  API token.
	 * @param string     $method GET, POST or PATCH.
	 * @param string     $path   Path after the API base.
	 * @param array|null $body   JSON body.
	 * @param string     $bucket Rate limit the request counts against.
	 *
	 * @return array See api_request().
	 */
	private static function send_request( $token, $method, $path, ?array $body, $bucket ) {
		$failure = self::get_failure_for( $token, $bucket );
		$wait    = $failure ? max( 0, (int) $failure['until'] - time() ) : 0;

		if ( $wait > 0 ) {
			error_log( 'Advanced Cloudflare Cache: ' . $method . ' ' . $path . ' not sent, Cloudflare is rate limiting this account for another ' . $wait . ' seconds.' );

			return [
				'ok'           => false,
				'data'         => null,
				'rate_limited' => true,
				'wait'         => $wait,
				'skipped'      => true,
			];
		}

		$probe = '';

		if ( $failure && $failure['rate_limited'] ) {
			$probe = self::PROBE_LOCK_PREFIX . self::failure_key( $token, $bucket );

			if ( ! self::acquire_lock( $probe, self::API_TIMEOUT ) ) {
				error_log( 'Advanced Cloudflare Cache: ' . $method . ' ' . $path . ' not sent, another request is checking whether the rate limit is over.' );

				// Still limited as far as this request knows. How long is up to the request that is checking.
				return [
					'ok'           => false,
					'data'         => null,
					'rate_limited' => true,
					'wait'         => 0,
					'skipped'      => true,
				];
			}
		}

		$result = self::dispatch_request( $token, $method, $path, $body );

		if ( '' !== $probe ) {
			// Update the failure before giving the lock back, so the next request does not start another check.
			if ( $result['ok'] ) {
				self::clear_failure( $token, $bucket );
			} else {
				self::record_failure( $result + [ 'bucket' => $bucket ], $token );
			}

			self::release_lock( $probe );
		}

		return $result;
	}

	/**
	 * Send a request to the Cloudflare API and read the response.
	 *
	 * @param string     $token  API token.
	 * @param string     $method GET, POST or PATCH.
	 * @param string     $path   Path after the API base.
	 * @param array|null $body   JSON body.
	 *
	 * @return array See api_request().
	 */
	private static function dispatch_request( $token, $method, $path, ?array $body ) {
		$args = [
			'method'  => $method,
			'timeout' => self::API_TIMEOUT,
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			],
		];

		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( self::API_BASE . $path, $args );

		if ( is_wp_error( $response ) ) {
			error_log( 'Advanced Cloudflare Cache: ' . $method . ' ' . $path . ' did not get through: ' . $response->get_error_message() );

			return [
				'ok'           => false,
				'data'         => null,
				'rate_limited' => false,
				'wait'         => 0,
			];
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ) );

		if ( $status >= 200 && $status < 300 && isset( $data->success ) && true === $data->success ) {
			return [
				'ok'           => true,
				'data'         => $data,
				'rate_limited' => false,
				'wait'         => 0,
			];
		}

		$code         = isset( $data->errors[0]->code ) ? (int) $data->errors[0]->code : 0;
		$message      = isset( $data->errors[0]->message ) ? $data->errors[0]->message : 'no error message';
		$rate_limited = 429 === $status || 1134 === $code; // 1134: "Unable to purge, rate limit reached".
		$wait         = $rate_limited ? self::rate_limit_wait( $response ) : 0;

		error_log(
			sprintf(
				'Advanced Cloudflare Cache: %s %s failed with HTTP %d%s: %s%s',
				$method,
				$path,
				$status,
				$code ? ', error ' . $code : '',
				$message,
				$rate_limited ? ' (rate limited, waiting ' . $wait . ' seconds)' : ''
			)
		);

		return [
			'ok'           => false,
			'data'         => $data,
			'rate_limited' => $rate_limited,
			'wait'         => $wait,
		];
	}

	/**
	 * Seconds Cloudflare asked us to wait, from the Retry-After header or the Ratelimit header.
	 *
	 * @param array $response HTTP API response.
	 *
	 * @return int
	 */
	private static function rate_limit_wait( $response ) {
		$retry_after = wp_remote_retrieve_header( $response, 'retry-after' );
		$retry_after = is_array( $retry_after ) ? reset( $retry_after ) : $retry_after;
		$wait        = (int) $retry_after;

		if ( $wait <= 0 ) {
			// Example: "default";r=0;t=30 where t is the seconds until the limit resets.
			$ratelimit = wp_remote_retrieve_header( $response, 'ratelimit' );
			$ratelimit = is_array( $ratelimit ) ? reset( $ratelimit ) : $ratelimit;

			if ( preg_match( '/;\s*t=(\d+)/', (string) $ratelimit, $matches ) ) {
				$wait = (int) $matches[1];
			}
		}

		if ( $wait <= 0 ) {
			$wait = self::DEFAULT_WAIT;
		}

		return min( self::MAX_WAIT, $wait );
	}

	/**
	 * Key identifying an API token and rate limit in the failure records. A hash, so the token itself is never stored there.
	 *
	 * Cloudflare's rate limits are per account, and a token belongs to one account, so the records are kept per token.
	 * Each kind of request (see api_request()) has its own limit, so each has its own record.
	 *
	 * @param string $token  API token.
	 * @param string $bucket 'tags', 'files' or 'api'.
	 *
	 * @return string
	 */
	private static function failure_key( $token, $bucket ) {
		return substr( wp_hash( $token . '|' . $bucket ), 0, 20 );
	}

	/**
	 * Get all remembered failures, keyed by token and limit, without the ones that have expired.
	 *
	 * @return array
	 */
	private static function get_failures() {
		$failures = get_site_transient( self::FAILURE_TRANSIENT );

		if ( ! is_array( $failures ) ) {
			return [];
		}

		foreach ( $failures as $key => $failure ) {
			if ( ! is_array( $failure ) || ! isset( $failure['time'] ) || (int) $failure['time'] + self::FAILURE_TTL < time() ) {
				unset( $failures[ $key ] );
			}
		}

		return $failures;
	}

	/**
	 * Remember that a request failed, so the user can be told. While rate limited no request of that kind is sent.
	 *
	 * @param array  $result Result of api_request().
	 * @param string $token  API token the request used.
	 */
	private static function record_failure( array $result, $token ) {
		// A request that was not even sent because of a rate limit already recorded must not extend that limit.
		if ( ! empty( $result['skipped'] ) ) {
			return;
		}

		$failures = self::get_failures();

		$failures[ self::failure_key( $token, $result['bucket'] ) ] = [
			'time'         => time(),
			'rate_limited' => $result['rate_limited'],
			'until'        => $result['rate_limited'] ? time() + $result['wait'] : 0,
		];

		set_site_transient( self::FAILURE_TRANSIENT, $failures, self::FAILURE_TTL );
	}

	/**
	 * Forget the last failure of a token and limit after a request of that kind went through.
	 *
	 * @param string $token  API token the request used.
	 * @param string $bucket 'tags', 'files' or 'api'.
	 */
	private static function clear_failure( $token, $bucket ) {
		$failures = self::get_failures();
		$key      = self::failure_key( $token, $bucket );

		if ( ! isset( $failures[ $key ] ) ) {
			return;
		}

		unset( $failures[ $key ] );

		if ( empty( $failures ) ) {
			delete_site_transient( self::FAILURE_TRANSIENT );
		} else {
			set_site_transient( self::FAILURE_TRANSIENT, $failures, self::FAILURE_TTL );
		}
	}

	/**
	 * Get the last failure of a token and limit, if any.
	 *
	 * @param string $token  API token.
	 * @param string $bucket 'tags', 'files' or 'api'.
	 *
	 * @return array|false See get_failure().
	 */
	private static function get_failure_for( $token, $bucket ) {
		$failures = self::get_failures();
		$key      = self::failure_key( $token, $bucket );

		if ( ! isset( $failures[ $key ] ) ) {
			return false;
		}

		return wp_parse_args(
			$failures[ $key ],
			[
				'time'         => 0,
				'rate_limited' => false,
				'until'        => 0,
			]
		);
	}

	/**
	 * Seconds left until requests of one kind may be sent again with this token, 0 if not rate limited.
	 *
	 * @param string $token  API token.
	 * @param string $bucket 'tags', 'files' or 'api'.
	 *
	 * @return int
	 */
	private static function rate_limited_for( $token, $bucket ) {
		$failure = self::get_failure_for( $token, $bucket );

		return $failure ? max( 0, (int) $failure['until'] - time() ) : 0;
	}

	/**
	 * Get the last failure of the current blog's Cloudflare account, if any. Used to tell the user that a purge failed.
	 *
	 * With several failures (for example tags and URLs) the one still rate limited for longest is returned.
	 *
	 * @return array|false {
	 *     @type int  $time         When it happened.
	 *     @type bool $rate_limited Whether Cloudflare's rate limit was reached.
	 *     @type int  $until        When the rate limit ends (a timestamp), 0 if not rate limited.
	 * }
	 */
	public static function get_failure() {
		global $nginx_helper_admin;

		$options = $nginx_helper_admin ? $nginx_helper_admin->get_cloudflare_settings() : [];
		$token   = isset( $options['api_token'] ) ? sanitize_text_field( $options['api_token'] ) : '';

		if ( '' === $token ) {
			return false;
		}

		$latest = false;

		foreach ( [ 'tags', 'files', 'api' ] as $bucket ) {
			$failure = self::get_failure_for( $token, $bucket );

			if ( ! $failure ) {
				continue;
			}

			if ( ! $latest || (int) $failure['until'] > (int) $latest['until'] || ( (int) $failure['until'] === (int) $latest['until'] && (int) $failure['time'] > (int) $latest['time'] ) ) {
				$latest = $failure;
			}
		}

		return $latest;
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
	 * @return string 'created', 'exists', 'updated' or 'failed'.
	 */
	public static function setup_cache_rules() {
		$credentials = self::get_credentials();

		if ( ! $credentials ) {
			return 'failed';
		}

		list( $token, $zone_id ) = $credentials;

		$zone_path = 'zones/' . rawurlencode( $zone_id );

		// Find the zone's cache ruleset. Cloudflare returns rulesets a page at a time.
		$cache_ruleset_id = null;
		$cursor           = '';

		for ( $page = 0; $page < self::RULESETS_MAX_PAGES; $page++ ) {
			$path     = $zone_path . '/rulesets?per_page=' . self::RULESETS_PER_PAGE . ( '' !== $cursor ? '&cursor=' . rawurlencode( $cursor ) : '' );
			$rulesets = self::api_request( $token, 'GET', $path );

			if ( ! $rulesets['ok'] || ! isset( $rulesets['data']->result ) || ! is_array( $rulesets['data']->result ) ) {
				error_log( 'Advanced Cloudflare Cache: Invalid response when fetching rulesets.' );
				self::record_failure( $rulesets, $token );
				return 'failed';
			}

			foreach ( $rulesets['data']->result as $ruleset ) {
				if ( isset( $ruleset->phase, $ruleset->id ) && 'http_request_cache_settings' === $ruleset->phase ) {
					$cache_ruleset_id = sanitize_text_field( $ruleset->id );
					break 2;
				}
			}

			$cursor = isset( $rulesets['data']->result_info->cursors->after ) ? (string) $rulesets['data']->result_info->cursors->after : '';

			if ( '' === $cursor ) {
				break;
			}
		}

		// The site address goes into a string in the rule expression, so it is escaped for that, not for HTML.
		$site_url = self::escape_expression_string( home_url() );
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

			$created = self::api_request( $token, 'POST', $zone_path . '/rulesets', $ruleset );

			if ( ! $created['ok'] ) {
				self::record_failure( $created, $token );
			}

			return $created['ok'] ? 'created' : 'failed';
		}

		// Get the existing rules of the cache ruleset.
		$rules_uri = $zone_path . '/rulesets/' . rawurlencode( $cache_ruleset_id );
		$existing  = self::api_request( $token, 'GET', $rules_uri );

		if ( ! $existing['ok'] ) {
			error_log( 'Advanced Cloudflare Cache: Failed to fetch existing cache rule. Ruleset ID: ' . wp_json_encode( $cache_ruleset_id ) );
			self::record_failure( $existing, $token );
			return 'failed';
		}

		$existing_rules = ( isset( $existing['data']->result->rules ) && is_array( $existing['data']->result->rules ) ) ? $existing['data']->result->rules : [];

		// Find this site's rule, if it was set up before. The address without the scheme tells sites on one zone apart.
		$site_address = str_replace( [ 'https://', 'http://' ], '', $site_url );
		$site_rule    = null;
		foreach ( $existing_rules as $existing_rule ) {
			if ( self::is_site_rule( $existing_rule, $site_address ) ) {
				$site_rule = $existing_rule;
				break;
			}
		}

		$rules_uri .= '/rules';

		if ( null === $site_rule ) {
			// Add only our rule, first in the list. The other rules in the ruleset are not touched.
			$new_rule = $rule;
			if ( ! empty( $existing_rules[0]->id ) ) {
				$new_rule['position'] = [ 'before' => $existing_rules[0]->id ];
			}

			$added = self::api_request( $token, 'POST', $rules_uri, $new_rule );

			if ( ! $added['ok'] ) {
				self::record_failure( $added, $token );
			}

			return $added['ok'] ? 'created' : 'failed';
		}

		if ( self::rule_matches( $site_rule, $rule ) ) {
			return 'exists';
		}

		// The rule is outdated (changed expression, other host, or disabled). Replace just this rule, keeping its position.
		if ( empty( $site_rule->id ) ) {
			return 'failed';
		}

		$updated = self::api_request( $token, 'PATCH', $rules_uri . '/' . rawurlencode( $site_rule->id ), $rule + [ 'enabled' => true ] );

		if ( ! $updated['ok'] ) {
			self::record_failure( $updated, $token );
		}

		return $updated['ok'] ? 'updated' : 'failed';
	}

	/**
	 * Escape a value for use inside a double-quoted string of a Cloudflare rule expression.
	 *
	 * @param string $value The value.
	 *
	 * @return string
	 */
	private static function escape_expression_string( $value ) {
		return str_replace( [ '\\', '"' ], [ '\\\\', '\\"' ], (string) $value );
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
}
