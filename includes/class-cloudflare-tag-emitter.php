<?php
/**
 * Adapted from Pantheon Advanced Page Cache (GPLv2 or later), modified by rtCamp.
 *
 * Generates and emits cache tags based on the current request.
 *
 * @package nginx-helper
 */

namespace EECacheHelper;

/**
 * Generates and emits cache tags based on the current request.
 */
class CloudFlare_Tag_Emitter {
	/**
	 * Current instance when set.
	 *
	 * @var Emitter
	 */
	private static $instance;

	/**
	 * REST API cache tags to emit.
	 *
	 * @var array
	 */
	private $rest_api_cache_tags = [];

	/**
	 * GraphQL cache tags to emit.
	 *
	 * @var array
	 */
	private $graphql_cache_tags = [];

	/**
	 * REST API collection endpoints.
	 *
	 * @var array
	 */
	private $rest_api_collection_endpoints = [];

	/**
	 * Header key.
	 *
	 * @var string
	 */
	const HEADER_KEY = 'Cache-Tag';

	/**
	 * Maximum header length.
	 *
	 * @var integer
	 */
	const HEADER_MAX_LENGTH = 16128; // 16 KB Cloudflare Cache-Tag limit, minus 256 for header name.

	/**
	 * Get a copy of the current instance.
	 *
	 * @return CloudFlare_Tag_Emitter
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Render cache tags after the main query has run
	 */
	public function action_wp() {
		$keys = self::get_main_query_cache_tags();
		if ( ! empty( $keys ) ) {
			@header( self::HEADER_KEY . ': ' . implode( ',', $keys ) ); // phpcs:ignore
		}
	}

	/**
	 * Register filters to sniff cache tags out of REST API responses.
	 */
	public function action_rest_api_init() {
		foreach ( get_post_types( [ 'show_in_rest' => true ], 'objects' ) as $post_type ) {
			add_filter( "rest_prepare_{$post_type->name}", [ $this, 'filter_rest_prepare_post' ], 10, 3 );

			$base      = ! empty( $post_type->rest_base ) ? $post_type->rest_base : $post_type->name;
 			$namespace = ! empty( $post_type->rest_namespace ) ? trim( $post_type->rest_namespace, '/' ) : 'wp/v2';

 			self::get_instance()->rest_api_collection_endpoints[ '/' . $namespace . '/' . $base ] = $post_type->name;
		}


		foreach ( get_taxonomies( [ 'show_in_rest' => true ], 'objects' ) as $taxonomy ) {
			add_filter( "rest_prepare_{$taxonomy->name}", [ $this, 'filter_rest_prepare_term' ], 10, 3 );
			$base      = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;
			$namespace = ! empty( $taxonomy->rest_namespace ) ? trim( $taxonomy->rest_namespace, '/' ) : 'wp/v2';
			self::get_instance()->rest_api_collection_endpoints[ '/' . $namespace . '/' . $base ] = $taxonomy->name;
		}
		add_filter( 'rest_prepare_comment', [ $this, 'filter_rest_prepare_comment' ], 10, 3 );
		self::get_instance()->rest_api_collection_endpoints['/wp/v2/comments'] = 'comment';
		add_filter( 'rest_prepare_user', [ $this, 'filter_rest_prepare_user' ], 10, 3 );
		add_filter( 'rest_pre_get_setting', [ $this, 'filter_rest_pre_get_setting' ], 10, 2 );
		self::get_instance()->rest_api_collection_endpoints['/wp/v2/users'] = 'user';
	}

	/**
	 * Reset cache tags before a REST API response is generated.
	 *
	 * @param mixed $result            Response to replace the requested version with.
	 * @param WP_REST_Server $server   Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 */
	public function filter_rest_pre_dispatch( $result, $server, $request ) {
		if ( isset( self::get_instance()->rest_api_collection_endpoints[ $request->get_route() ] ) ) {
			self::get_instance()->rest_api_cache_tags[] = 'rest-' . self::get_instance()->rest_api_collection_endpoints[ $request->get_route() ] . '-collection';
		}

		return $result;
	}

	/**
	 * Render cache tags after a REST API response is prepared
	 *
	 * @param WP_HTTP_Response $result Result to send to the client. Usually a WP_REST_Response.
	 * @param WP_REST_Server $server   Server instance.
	 */
	public function filter_rest_post_dispatch( $result, $server ) {
		$keys = self::get_rest_api_cache_tags();
		if ( ! empty( $keys ) && $result instanceof \WP_REST_Response ) {
			$result->header( self::HEADER_KEY, implode( ',', $keys ) );
		}

		return $result;
	}

	/**
	 * Determine which posts are present in a REST API response.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Post $post              Post object.
	 * @param WP_REST_Request $request   Request object.
	 */
	public function filter_rest_prepare_post( $response, $post, $request ) {
		self::get_instance()->rest_api_cache_tags[] = 'rest-post-' . $post->ID;

		return $response;
	}

	/**
	 * Determine which terms are present in a REST API response.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Post $term              Term object.
	 * @param WP_REST_Request $request   Request object.
	 */
	public function filter_rest_prepare_term( $response, $term, $request ) {
		self::get_instance()->rest_api_cache_tags[] = 'rest-term-' . $term->term_id;

		return $response;
	}

	/**
	 * Determine which comments are present in a REST API response.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Comment $comment        The original comment object.
	 * @param WP_REST_Request $request   Request used to generate the response.
	 */
	public function filter_rest_prepare_comment( $response, $comment, $request ) {
		self::get_instance()->rest_api_cache_tags[] = 'rest-comment-' . $comment->comment_ID;
		self::get_instance()->rest_api_cache_tags[] = 'rest-comment-post-' . $comment->comment_post_ID;

		return $response;
	}

	/**
	 * Determine which users are present in a REST API response.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Post $user              User object.
	 * @param WP_REST_Request $request   Request object.
	 */
	public function filter_rest_prepare_user( $response, $user, $request ) {
		self::get_instance()->rest_api_cache_tags[] = 'rest-user-' . $user->ID;

		return $response;
	}

	/**
	 * Determine which settings are present in a REST API request
	 *
	 * @param mixed $result  Value to use for the requested setting. Can be a scalar
	 *                       matching the registered schema for the setting, or null to
	 *                       follow the default get_option() behavior.
	 * @param string $name   Setting name (as shown in REST API responses).
	 */
	public function filter_rest_pre_get_setting( $result, $name ) {
		self::get_instance()->rest_api_cache_tags[] = 'rest-setting-' . $name;

		return $result;
	}

	/**
	 * Get the cache tag of a date archive: its year, month or day, whichever is the most specific.
	 *
	 * Matches the tags the purger builds from a post's date (date-2026, date-2026-10, date-2026-10-05).
	 * Odd archives that have no year (e.g. only a month) get date-misc, which is purged on every post change.
	 *
	 * @param WP_Query $wp_query The main query.
	 *
	 * @return string
	 */
	private static function get_date_archive_tag( $wp_query ) {
		$year = (int) $wp_query->get( 'year' );

		if ( ! $year ) {
			return 'date-misc';
		}

		$month = (int) $wp_query->get( 'monthnum' );

		if ( ! $month ) {
			return sprintf( 'date-%04d', $year );
		}

		$day = (int) $wp_query->get( 'day' );

		if ( ! $day ) {
			return sprintf( 'date-%04d-%02d', $year, $month );
		}

		return sprintf( 'date-%04d-%02d-%02d', $year, $month, $day );
	}

	/**
	 * Get the cache tags to be included in this view.
	 *
	 * cache tags are generated based on the main WP_Query.
	 *
	 * @return array
	 */
	public function get_main_query_cache_tags() {
		global $wp_query;

		$keys = [];
		if ( is_front_page() ) {
			$keys[] = 'front';
		}
		if ( is_home() ) {
			$keys[] = 'home';
		}
		if ( is_404() ) {
			$keys[] = '404';
		}
		if ( is_feed() ) {
			$keys[] = 'feed';
		}
		if ( is_date() ) {
			$keys[] = 'date';
			// The tag of this archive's own period, so a post change only purges the archives it appears on.
			$keys[] = self::get_date_archive_tag( $wp_query );
		}
		if ( is_paged() ) {
			$keys[] = 'paged';
		}
		if ( is_search() ) {
			$keys[] = 'search';
			if ( $wp_query->found_posts ) {
				$keys[] = 'search-results';
			} else {
				$keys[] = 'search-no-results';
			}
		}

		if ( ! empty( $wp_query->posts ) ) {
			foreach ( $wp_query->posts as $p ) {
				// The main query does not always hold full posts. Queries with 'fields' set hold IDs or partial objects,
				// and plugins and themes put placeholder objects in it (virtual pages, "no results" screens) that have no
				// ID or post type. An entry without an ID is not a post, so it gets no tags.
				$post_id = is_object( $p ) ? (int) ( $p->ID ?? 0 ) : (int) $p;
				if ( $post_id <= 0 ) {
					continue;
				}

				$post_type = is_object( $p ) ? (string) ( $p->post_type ?? '' ) : '';

				$keys[] = 'post-' . $post_id;
				// Listings and feeds print the author name too.
				if ( '' !== $post_type && isset( $p->post_author ) && post_type_supports( $post_type, 'author' ) ) {
					$keys[] = 'post-user-' . $p->post_author;
				}
				if ( $wp_query->is_singular() ) {

					/**
					 * Filter ec_should_add_terms
					 * Gives the option to skip taxonomy terms for a given post
					 *
					 * @param $add_terms whether or not to create cache tags for a given post's taxonomy terms.
					 * @param $wp_query  the full WP_Query object.
					 *
					 * @return bool
					 * usage: add_filter( 'ec_should_add_terms',"__return_false", 10, 2);
					 */
					$add_terms = apply_filters( 'ec_should_add_terms', true, $wp_query );
					if ( ! $add_terms ) {
						continue;
					}

					// Without a post type there is no telling which taxonomies apply.
					foreach ( '' !== $post_type ? get_object_taxonomies( $post_type ) : [] as $tax ) {
						$terms = get_the_terms( $post_id, $tax );
						if ( $terms && ! is_wp_error( $terms ) ) {
							foreach ( $terms as $t ) {
								$keys[] = 'post-term-' . $t->term_id;
							}
						}
					}
				}
			}
		}

		if ( is_singular() ) {
			$keys[] = 'single';
			if ( is_attachment() ) {
				$keys[] = 'attachment';
			}
		} elseif ( is_archive() ) {
			$keys[] = 'archive';
			if ( is_post_type_archive() ) {
				$keys[]     = 'post-type-archive';
				$post_types = get_query_var( 'post_type' );
				// If multiple post types are queried, create a surrogate key for each.
				if ( is_array( $post_types ) ) {
					foreach ( $post_types as $post_type ) {
						$keys[] = "$post_type-archive";
					}
				} else {
					$keys[] = "$post_types-archive";
				}
			} elseif ( is_author() ) {
				$user_id = get_queried_object_id();
				if ( $user_id ) {
					$keys[] = 'user-' . $user_id;
				}
			} elseif ( is_category() || is_tag() || is_tax() ) {
				$term_id = get_queried_object_id();
				if ( $term_id ) {
					$keys[] = 'term-' . $term_id;
				}
			}
		}

		// Don't emit cache tags in the admin, unless defined by the filter.
		if ( is_admin() ) {
			$keys = [];
		}

		/**
		 * Customize cache tags sent in the header.
		 *
		 * @param array $keys Existing cache tags generated by the plugin.
		 */
		$keys = ec_cf_prefix_cache_tags_with_blog_id( $keys );
		$keys = apply_filters( 'ec_main_query_cache_tags', $keys );
		$keys = array_unique( $keys );
		$keys = self::filter_huge_cache_tags_list( $keys );

		return $keys;
	}

	/**
	 * Get the cache tags to be included in this view.
	 *
	 * cache tags are generated based on filters added to REST API controllers.
	 *
	 * @return array
	 */
	public function get_rest_api_cache_tags() {

		/**
		 * Customize cache tags sent in the REST API header.
		 *
		 * @param array $keys Existing cache tags generated by the plugin.
		 */
		$keys = self::get_instance()->rest_api_cache_tags;
		$keys = ec_cf_prefix_cache_tags_with_blog_id( $keys );
		$keys = apply_filters( 'ec_rest_api_cache_tags', $keys );
		$keys = array_unique( $keys );
		$keys = self::filter_huge_cache_tags_list( $keys );

		return $keys;
	}

	/**
	 * Reset cache tags stored on the instance.
	 */
	public function reset_rest_api_cache_tags() {
		self::get_instance()->rest_api_cache_tags = [];
	}

	/**
	 * Filter the cache tags to ensure that the length doesn't exceed what nginx can handle.
	 *
	 * @param array $keys Existing cache tags generated by the plugin.
	 *
	 * @return array
	 */
	public function filter_huge_cache_tags_list( $keys ) {
		$output = implode( ' ', $keys );
		if ( strlen( $output ) <= self::HEADER_MAX_LENGTH ) {
			return $keys;
		}

		$keycats = [];
		foreach ( $keys as $k ) {
			$p = strrpos( $k, '-' );
			if ( false === $p ) {
				$keycats[ $k ][] = $k;
				continue;
			}
			$cat               = substr( $k, 0, $p + 1 );
			$keycats[ $cat ][] = $k;
		}

		// Sort by the output length of the key category.
		uasort(
			$keycats,
			function ( $a, $b ) {
				$ca = strlen( implode( ' ', $a ) );
				$cb = strlen( implode( ' ', $b ) );
				if ( $ca === $cb ) {
					return 0;
				}

				return $ca > $cb ? - 1 : 1;
			}
		);

		$cats = array_keys( $keycats );
		foreach ( $cats as $c ) {
			$keycats[ $c ] = [ $c . 'huge' ];
			$keyout        = [];
			foreach ( $keycats as $v ) {
				$keyout = array_merge( $keyout, $v );
			}
			$output = implode( ' ', $keyout );
			if ( strlen( $output ) <= self::HEADER_MAX_LENGTH ) {
				return $keyout;
			}
		}

		return $keyout;
	}

	/**
	 * Inspect the model and get the right cache tags.
	 *
	 * @param WPGraphQL\Model\Model|mixed $model Model object, array, etc.
	 */
	public function filter_graphql_dataloader_get_model( $model ) {
		if ( ! $model instanceof \WPGraphQL\Model\Model ) {
			return $model;
		}

		$reflect          = new \ReflectionClass( $model );
		$class_short_name = $reflect->getShortName();
		$cache_tag_prefix = strtolower( $class_short_name );
		if ( isset( $model->id ) ) {
			if ( ! empty( $model->databaseId ) ) {
				self::get_instance()->graphql_cache_tags[] = $cache_tag_prefix . '-' . $model->databaseId;
			}
		}

		return $model;
	}

	/**
	 * Get the cache tags to be included in this view.
	 *
	 * cache tags are generated based on filters added to GraphQL controllers.
	 *
	 * @return array
	 */
	public function get_graphql_cache_tags() {

		/**
		 * Customize cache tags sent in the GraphQL header.
		 *
		 * @param array $keys Existing cache tags generated by the plugin.
		 */
		$keys   = self::get_instance()->graphql_cache_tags;
		$keys[] = 'graphql-collection';
		$keys   = ec_cf_prefix_cache_tags_with_blog_id( $keys );
		$keys   = apply_filters( 'ec_graphql_cache_tags', $keys );
		$keys   = array_unique( $keys );
		$keys   = self::filter_huge_cache_tags_list( $keys );

		return $keys;
	}

	/**
	 * Send additional headers to graphql response.
	 *
	 * @param array $headers Existing headers as set by graphql plugin.
	 */
	public function filter_graphql_response_headers_to_send( $headers ) {
		$keys = self::get_graphql_cache_tags();
		if ( ! empty( $keys ) ) {
			$headers[ self::HEADER_KEY ] = implode( ',', $keys );
		}

		return $headers;
	}
}
