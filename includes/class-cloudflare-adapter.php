<?php
/**
 * Cloudflare API adapter with request timeouts.
 *
 * The SDK's Guzzle adapter sets no timeouts, so a stalled connection to the API would block the request forever.
 *
 * @package nginx-helper
 */

namespace EECacheHelper;

\ec_cf_maybe_load_vendor_autoloader();

use Cloudflare\API\Adapter\Guzzle;
use Cloudflare\API\Adapter\ResponseException;
use Cloudflare\API\Auth\Auth;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class Cloudflare_Adapter
 */
class Cloudflare_Adapter extends Guzzle {

	/**
	 * Seconds to wait for the connection to be established.
	 *
	 * @var integer
	 */
	const CONNECT_TIMEOUT = 5;

	/**
	 * Seconds to wait for the whole request to finish.
	 *
	 * @var integer
	 */
	const TIMEOUT = 30;

	/**
	 * HTTP client used for the requests.
	 *
	 * @var Client
	 */
	private $http;

	/**
	 * Create the adapter.
	 *
	 * The parent constructor is not called: its client is private and has no timeouts, and every
	 * method of the parent goes through request(), which is replaced below.
	 *
	 * @param Auth        $auth    Authentication.
	 * @param string|null $baseURI API base URI, defaults to the Cloudflare API.
	 */
	public function __construct( Auth $auth, ?string $baseURI = null ) {
		$this->http = new Client(
			[
				'base_uri'        => $baseURI ?? 'https://api.cloudflare.com/client/v4/',
				'headers'         => $auth->getHeaders(),
				'connect_timeout' => self::CONNECT_TIMEOUT,
				'timeout'         => self::TIMEOUT,
			]
		);
	}

	/**
	 * Send a request to the API.
	 *
	 * @param string $method  get, post, put, patch or delete.
	 * @param string $uri     API path.
	 * @param array  $data    Query (get) or JSON body (other methods).
	 * @param array  $headers Extra headers.
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 *
	 * @throws \InvalidArgumentException For an unknown method.
	 * @throws ResponseException         When the API answers with an error.
	 */
	public function request( string $method, string $uri, array $data = [], array $headers = [] ) {
		if ( ! in_array( $method, [ 'get', 'post', 'put', 'patch', 'delete' ], true ) ) {
			throw new \InvalidArgumentException( 'Request method must be get, post, put, patch, or delete' );
		}

		try {
			return $this->http->$method(
				$uri,
				[
					'headers'                          => $headers,
					( 'get' === $method ? 'query' : 'json' ) => $data,
				]
			);
		} catch ( RequestException $err ) {
			throw ResponseException::fromRequestException( $err );
		}
	}
}
