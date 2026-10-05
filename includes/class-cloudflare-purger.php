<?php
/**
 * Adapted from Pantheon Advanced Page Cache (GPLv2 or later), modified by rtCamp.
 *
 * Purges the cache based on a variety of WordPress events.
 *
 * @package nginx-helper
 */

namespace EECacheHelper;

use EECacheHelper\Cloudflare_Client;

/**
 * Purges the appropriate cache tag based on the event.
 */
class Cloudflare_Purger {
	/**
	 * Columns of the users table that are never shown publicly. Any other column changing purges the author's pages.
	 *
	 * @var string[]
	 */
	const NON_PUBLIC_USER_FIELDS = [ 'user_pass', 'user_activation_key' ];

	/**
	 * User meta keys that are shown publicly. A change purges the author's pages.
	 * Sites can add their own keys with the ec_purge_user_meta_keys filter.
	 *
	 * @var string[]
	 */
	const PUBLIC_USER_META_KEYS = [ 'description', 'first_name', 'last_name', 'nickname' ];

	/**
	 * Current instance when set.
	 *
	 * @var Emitter
	 */
	private static $instance;

	/**
	 * Get a copy of the current instance.
	 *
	 * @return Cloudflare_Purger
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Purge cache tags associated with a post once it is fully saved (terms and meta included).
	 *
	 * Mirrors the Nginx purger: purge when the old or new status is publish or future,
	 * and when a post is moved to trash from any status. Also runs for wp_publish_post(),
	 * which does not fire wp_insert_post. Requires WordPress 5.6+.
	 *
	 * @param integer      $post_id     ID for the saved post.
	 * @param WP_Post      $post        Post object.
	 * @param bool         $update      Whether this is an update.
	 * @param WP_Post|null $post_before Post object before the update, null for new posts.
	 */
	public function action_wp_after_insert_post( $post_id, $post, $update, $post_before ) {
		$new_status   = $post->post_status;
		$old_status   = $post_before ? $post_before->post_status : 'new';
		$purge_status = [ 'publish', 'future' ];

		if ( ! in_array( $new_status, $purge_status, true )
			&& ! in_array( $old_status, $purge_status, true )
			&& 'trash' !== $new_status ) {
			return;
		}
		self::purge_post_with_related( $post );
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}
		// Targets 404 pages that could be cached with no cache tags (i.e.
		// a drafted post going live after the 404 has been cached).
		self::clear_post_path( $post );
	}


	/**
	 * Purge the cache for a given post's path
	 *
	 * @param WP_Post $post Post object.
	 *
	 * @since 1.0.0
	 */
	public function clear_post_path( $post ) {
		if ( in_array( $post->post_type, self::get_ignored_post_types(), true ) ) {
			return;
		}

		$permalink = get_permalink( $post->ID );

		// A post without a permalink (e.g. a type that is not public) has no page to purge.
		if ( ! $permalink ) {
			return;
		}

		$paths = [ $permalink ];

		// If the permalink does not use query string, we standardize the url to both cases.
		if ( false === strpos( $permalink, '?' ) ) {
			$paths = [ trailingslashit( $permalink ), untrailingslashit( $permalink ) ];
		}

		/**
		 * Paths possibly without cache tags purges
		 *
		 * @param array $paths Full URLs to clear.
		 */
		$paths = apply_filters( 'ec_clear_post_path', $paths );
		Cloudflare_Client::queue_urls( $paths );
	}

	/**
	 * Purge cache tags associated with a post being deleted.
	 *
	 * Mirrors the Nginx purger: a post already in trash was purged when it was trashed.
	 *
	 * @param integer $post_id ID for the post to be deleted.
	 */
	public function action_before_delete_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post || 'trash' === $post->post_status ) {
			return;
		}
		self::purge_post_with_related( $post );
	}

	/**
	 * Purge cache tags associated with an attachment being deleted.
	 *
	 * @param integer $post_id ID for the modified attachment.
	 */
	public function action_delete_attachment( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return;
		}
		self::purge_post_with_related( $post );
	}

	/**
	 * Purge the post's own cache tags when its cache is cleared.
	 *
	 * Catches changes that do not go through wp_insert_post, such as WooCommerce price and stock
	 * updates or a comment edit changing the comment count. Tags are queued and sent once at
	 * shutdown, so saves that also reach action_wp_after_insert_post do not cost an extra call.
	 *
	 * @param integer $post_id ID for the modified post.
	 */
	public function action_clean_post_cache( $post_id ) {
		$post = get_post( $post_id );
		$type = $post ? $post->post_type : get_post_type( $post_id );

		if ( $type && in_array( $type, self::get_ignored_post_types(), true ) ) {
			return;
		}

		// Do not purge for drafts, pending or private posts. Attachments use the 'inherit' status.
		// A deleted post has no status and is still purged.
		$status = get_post_status( $post_id );
		if ( $status && ! in_array( $status, [ 'publish', 'future', 'inherit' ], true ) ) {
			return;
		}

		$ids = [ $post_id ];

		// Posts without a page of their own (e.g. product variations) show up on their parent's page.
		if ( $post && $post->post_parent && ! is_post_type_viewable( $post->post_type ) ) {
			$ids[] = $post->post_parent;
		}

		$keys = [];
		foreach ( $ids as $id ) {
			$keys[] = 'post-' . $id;
			$keys[] = 'rest-post-' . $id;
		}

		$keys = ec_cf_prefix_cache_tags_with_blog_id( $keys );
		/**
		 * cache tags purged when clearing post cache.
		 *
		 * @param array $keys      cache tags.
		 * @param integer $post_id ID for purged post.
		 */
		$keys = apply_filters( 'ec_purge_clean_post_cache', $keys, $post_id );
		Cloudflare_Client::queue_tags( $keys );
	}

	/**
	 * Purge the cache tags of an attachment being edited.
	 *
	 * @param integer $post_id ID for the edited attachment.
	 */
	public function action_edit_attachment( $post_id ) {
		$keys = [
			'post-' . $post_id,
			'rest-post-' . $post_id,
		];
		$keys = ec_cf_prefix_cache_tags_with_blog_id( $keys );
		/**
		 * cache tags purged when editing an attachment.
		 *
		 * @param array $keys      cache tags.
		 * @param integer $post_id ID for the edited attachment.
		 */
		$keys = apply_filters( 'ec_purge_edit_attachment', $keys, $post_id );
		Cloudflare_Client::queue_tags( $keys );
	}

	/**
	 * Purge cache tags associated with a term being created.
	 *
	 * @param integer $term_id ID for the created term.
	 * @param int $tt_id       Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function action_created_term( $term_id, $tt_id, $taxonomy ) {
		self::purge_term( $term_id );
		$keys = [ 'rest-' . $taxonomy . '-collection' ];
		$keys = ec_cf_prefix_cache_tags_with_blog_id( $keys );
		/**
		 * cache tags purged when creating a new term.
		 *
		 * @param array $keys      cache tags.
		 * @param array $term_id   ID for new term.
		 * @param array $tt_id     Term taxonomy ID for new term.
		 * @param string $taxonomy Taxonomy for the new term.
		 */
		$keys = apply_filters( 'ec_purge_create_term', $keys, $term_id, $tt_id, $taxonomy );
		Cloudflare_Client::queue_tags( $keys );
	}

	/**
	 * Purge cache tags associated with a term being edited.
	 *
	 * @param integer $term_id ID for the edited term.
	 */
	public function action_edited_term( $term_id ) {
		self::purge_term( $term_id );
	}

	/**
	 * Purge cache tags associated with a term being deleted.
	 *
	 * @param integer $term_id ID for the deleted term.
	 */
	public function action_delete_term( $term_id ) {
		self::purge_term( $term_id );
	}

	/**
	 * Purge the term's archive cache tag when the term is modified.
	 *
	 * @param integer $term_ids One or more IDs of modified terms.
	 */
	public function action_clean_term_cache( $term_ids ) {
		$keys     = [];
		$term_ids = is_array( $term_ids ) ? $term_ids : [ $term_ids ];
		foreach ( $term_ids as $term_id ) {
			$keys[] = 'term-' . $term_id;
			$keys[] = 'rest-term-' . $term_id;
		}
		$keys[] = 'term-huge';
		$keys[] = 'rest-term-huge';
		$keys   = ec_cf_prefix_cache_tags_with_blog_id( $keys );
		/**
		 * cache tags purged when clearing term cache.
		 *
		 * @param array $keys     cache tags.
		 * @param array $term_ids IDs for purged terms.
		 */
		$keys = apply_filters( 'ec_purge_clean_term_cache', $keys, $term_ids );
		Cloudflare_Client::queue_tags( $keys );
	}

	/**
	 * Purge cache tags when an approved comment is updated.
	 *
	 * @param integer $id         The comment ID.
	 * @param WP_Comment $comment Comment object.
	 */
	public function action_wp_insert_comment( $id, $comment ) {
		if ( 1 !== (int) $comment->comment_approved ) {
			return;
		}
		$keys = [
			'rest-comment-' . $comment->comment_ID,
			'rest-comment-collection',
			'rest-comment-huge',
			'post-' . $comment->comment_post_ID,
			'rest-comment-post-' . $comment->comment_post_ID,
		];
		$keys = ec_cf_prefix_cache_tags_with_blog_id( $keys );
		/**
		 * cache tags purged when inserting a new comment.
		 *
		 * @param array $keys         cache tags.
		 * @param integer $id         Comment ID.
		 * @param WP_Comment $comment Comment to be inserted.
		 */
		$keys = apply_filters( 'ec_purge_insert_comment', $keys, $id, $comment );
		Cloudflare_Client::queue_tags( $keys );
	}

	/**
	 * Purge cache tags when a comment is approved or unapproved.
	 *
	 * @param int|string $new_status The new comment status.
	 * @param int|string $old_status The old comment status.
	 * @param object $comment        The comment data.
	 */
	public function action_transition_comment_status( $new_status, $old_status, $comment ) {
		$keys = [
			'rest-comment-' . $comment->comment_ID,
			'rest-comment-collection',
			'rest-comment-huge',
		];
		// Like the Nginx purger, the post page only changes when an approved comment is added or removed.
		if ( 'approved' === $new_status || 'approved' === $old_status ) {
			$keys[] = 'post-' . $comment->comment_post_ID;
			$keys[] = 'rest-comment-post-' . $comment->comment_post_ID;
		}
		$keys = ec_cf_prefix_cache_tags_with_blog_id( $keys );
		/**
		 * cache tags purged when transitioning a comment status.
		 *
		 * @param array $keys         cache tags.
		 * @param string $new_status  New comment status.
		 * @param string $old_status  Old comment status.
		 * @param WP_Comment $comment Comment being transitioned.
		 */
		$keys = apply_filters( 'ec_purge_transition_comment_status', $keys, $new_status, $old_status, $comment );
		Cloudflare_Client::queue_tags( $keys );
	}

	/**
	 * Purge the comment's cache tag when the comment is modified.
	 *
	 * @param integer $comment_id Modified comment id.
	 */
	public function action_clean_comment_cache( $comment_id ) {
		// Pending and spam comments were never public, so nothing cached can reference them.
		if ( in_array( wp_get_comment_status( $comment_id ), [ 'unapproved', 'spam' ], true ) ) {
			return;
		}

		$keys = [
			'rest-comment-' . $comment_id,
			'rest-comment-huge',
		];
		$keys = ec_cf_prefix_cache_tags_with_blog_id( $keys );
		/**
		 * cache tags purged when cleaning comment cache.
		 *
		 * @param array $keys cache tags.
		 * @param integer $id Comment ID.
		 */
		$keys = apply_filters( 'ec_purge_clean_comment_cache', $keys, $comment_id );
		Cloudflare_Client::queue_tags( $keys );
	}

	/**
	 * Get the post types for which purging is skipped.
	 *
	 * Defaults to revisions and internal types WordPress creates behind the scenes during page views.
	 *
	 * @return string[]
	 */
	private static function get_ignored_post_types() {
		/**
		 * Allow specific post types to ignore the purge process.
		 *
		 * @param array $ignored_post_types Post types to ignore.
		 *
		 * @return array
		 * @since 1.0.0
		 */
		return (array) apply_filters( 'ec_purge_post_type_ignored', [ 'revision', 'oembed_cache', 'scheduled-action' ] );
	}

	/**
	 * Purge the cache tags associated with a post being modified.
	 *
	 * @param object $post Object representing the modified post.
	 */
	private function purge_post_with_related( $post ) {
		if ( in_array( $post->post_type, self::get_ignored_post_types(), true ) ) {
			return;
		}

		$keys = [
			'post-' . $post->ID,
			'rest-post-' . $post->ID,
			$post->post_type . '-archive',
			'rest-' . $post->post_type . '-collection',
			'home',
			'front',
			'404',
			'feed',
			'date',
			'graphql-collection',
			'post-huge',
			'rest-post-huge',
		];

		if ( post_type_supports( $post->post_type, 'author' ) ) {
			$keys[] = 'user-' . $post->post_author;
			$keys[] = 'user-huge';
			// The users endpoint lists only authors with published posts.
			$keys[] = 'rest-user-collection';
		}

		if ( post_type_supports( $post->post_type, 'comments' ) ) {
			$keys[] = 'rest-comment-post-' . $post->ID;
			$keys[] = 'rest-comment-post-huge';
		}

		$taxonomies = wp_list_filter(
			get_object_taxonomies( $post->post_type, 'objects' ),
			[ 'public' => true ]
		);

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $post, $taxonomy->name );
			if ( $terms && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$keys[] = 'term-' . $term->term_id;
					// Parent term archives also list posts from child terms.
					foreach ( get_ancestors( $term->term_id, $taxonomy->name, 'taxonomy' ) as $ancestor_id ) {
						$keys[] = 'term-' . $ancestor_id;
					}
				}
				$keys[] = 'term-huge';
			}
		}

		$keys = ec_cf_prefix_cache_tags_with_blog_id( $keys );
		/**
		 * Related cache tags purged when purging a post.
		 *
		 * @param array $keys   cache tags.
		 * @param WP_Post $post Post object.
		 */
		$keys = apply_filters( 'ec_purge_post_with_related', $keys, $post );
		Cloudflare_Client::queue_tags( $keys );
	}

	/**
	 * Purge the cache tags associated with a term being modified.
	 *
	 * @param integer $term_id ID for the modified term.
	 */
	private function purge_term( $term_id ) {
		$keys = [
			'term-' . $term_id,
			'rest-term-' . $term_id,
			'post-term-' . $term_id,
			'term-huge',
			'rest-term-huge',
			'post-term-huge',
		];
		$keys = ec_cf_prefix_cache_tags_with_blog_id( $keys );
		/**
		 * cache tags purged when purging a term.
		 *
		 * @param array $keys      cache tags.
		 * @param integer $term_id Term ID.
		 */
		$keys = apply_filters( 'ec_purge_term', $keys, $term_id );
		Cloudflare_Client::queue_tags( $keys );
	}


	/**
	 * Purge post pages that show an author when the author's public profile changes.
	 *
	 * @param integer $user_id       ID for the updated user.
	 * @param WP_User $old_user_data User object before the update.
	 */
	public function action_profile_update( $user_id, $old_user_data ) {
		$user = get_userdata( $user_id );
		if ( ! $user || ! $old_user_data ) {
			return;
		}

		// Columns of the users table. Meta such as the bio is handled in action_user_meta_changed().
		$changed = array_diff_assoc( (array) $user->data, (array) $old_user_data->data );
		$changed = array_diff_key( $changed, array_flip( self::NON_PUBLIC_USER_FIELDS ) );

		if ( ! empty( $changed ) ) {
			$this->queue_author_tags( $user_id );
		}
	}

	/**
	 * Purge the author tags when a public user meta value changes.
	 *
	 * Runs on added_user_meta, updated_user_meta and deleted_user_meta. WordPress only fires the
	 * updated hook when the stored value really changed.
	 *
	 * @param int|int[] $meta_id    Meta ID (or IDs when deleted).
	 * @param integer   $user_id    ID of the user the meta belongs to.
	 * @param string    $meta_key   Meta key.
	 * @param mixed     $meta_value Meta value.
	 */
	public function action_user_meta_changed( $meta_id, $user_id, $meta_key, $meta_value = null ) {
		/**
		 * Filters the user meta keys that are shown publicly, so a change purges the author's pages.
		 *
		 * Add keys for custom profile fields, avatars or social links.
		 *
		 * @param string[] $keys Meta keys.
		 */
		$public_keys = (array) apply_filters( 'ec_purge_user_meta_keys', self::PUBLIC_USER_META_KEYS );

		if ( ! in_array( $meta_key, $public_keys, true ) ) {
			return;
		}

		// Adding an empty value (new accounts get empty name and bio rows) changes nothing public.
		// Clearing an existing value still purges, as that goes through updated_user_meta.
		if ( 'added_user_meta' === current_filter() && '' === $meta_value ) {
			return;
		}

		$this->queue_author_tags( $user_id );
	}

	/**
	 * Queue the cache tags of an author's archive, REST user and post pages.
	 *
	 * @param integer $user_id User ID.
	 */
	private function queue_author_tags( $user_id ) {
		$keys = [
			'user-' . $user_id,
			'rest-user-' . $user_id,
			'user-huge',
			'rest-user-huge',
			'post-user-' . $user_id,
			'post-user-huge',
		];
		if ( ! is_multisite() ) {
			$this->queue_author_tag_keys( $keys, $user_id );

			return;
		}

		// Users are network-wide, so purge their tags on every site they belong to. Each site's tags are queued
		// under that site, as it can use its own Cloudflare credentials.
		$blog_ids = array_unique( array_merge( [ get_current_blog_id() ], array_keys( get_blogs_of_user( $user_id ) ) ) );

		foreach ( $blog_ids as $blog_id ) {
			if ( get_current_blog_id() === (int) $blog_id ) {
				$this->queue_author_tag_keys( $keys, $user_id );
				continue;
			}

			switch_to_blog( $blog_id );
			$this->queue_author_tag_keys( $keys, $user_id );
			restore_current_blog();
		}
	}

	/**
	 * Prefix an author's cache tags for the current site and queue them.
	 *
	 * @param array   $keys    Cache tags, not prefixed yet.
	 * @param integer $user_id ID for the updated user.
	 */
	private function queue_author_tag_keys( array $keys, $user_id ) {
		$keys = ec_cf_prefix_cache_tags_with_blog_id( $keys );
		/**
		 * cache tags purged when an author's public profile changes.
		 *
		 * On multisite this runs once per site the user belongs to.
		 *
		 * @param array $keys      cache tags.
		 * @param integer $user_id ID for the updated user.
		 */
		$keys = apply_filters( 'ec_purge_profile_update', $keys, $user_id );
		Cloudflare_Client::queue_tags( $keys );
	}

	/**
	 * Purge post pages and author archives after a user is deleted and their posts reassigned.
	 *
	 * @param integer      $user_id  ID for the deleted user.
	 * @param integer|null $reassign ID the posts were reassigned to, if any.
	 */
	public function action_deleted_user( $user_id, $reassign ) {
		$keys = [
			'user-' . $user_id,
			'rest-user-' . $user_id,
			'user-huge',
			'rest-user-huge',
			'post-user-' . $user_id,
			'post-user-huge',
		];
		if ( $reassign ) {
			// The new author's archive now lists the reassigned posts.
			$keys[] = 'user-' . (int) $reassign;
			$keys[] = 'user-huge';
		}
		$keys = ec_cf_prefix_cache_tags_with_blog_id( $keys );
		/**
		 * cache tags purged when a user is deleted.
		 *
		 * @param array $keys            cache tags.
		 * @param integer $user_id       ID for the deleted user.
		 * @param integer|null $reassign ID the posts were reassigned to.
		 */
		$keys = apply_filters( 'ec_purge_deleted_user', $keys, $user_id, $reassign );
		Cloudflare_Client::queue_tags( $keys );
	}

	/**
	 * Purge a variety of cache tags when an option is modified.
	 *
	 * @param string $option Name of the updated option.
	 */
	public function action_updated_option( $option ) {
		if ( ! function_exists( 'get_registered_settings' ) ) {
			return;
		}
		$settings = get_registered_settings();
		if ( empty( $settings[ $option ] ) || empty( $settings[ $option ]['show_in_rest'] ) ) {
			return;
		}
		$rest_name = ! empty( $settings[ $option ]['show_in_rest']['name'] ) ? $settings[ $option ]['show_in_rest']['name'] : $option;
		$keys      = [
			'rest-setting-' . $rest_name,
			'rest-setting-huge',
		];
		$keys      = ec_cf_prefix_cache_tags_with_blog_id( $keys );
		/**
		 * cache tags purged when updating an option cache.
		 *
		 * @param array $keys    cache tags.
		 * @param string $option Option name.
		 */
		$keys = apply_filters( 'ec_purge_updated_option', $keys, $option );
		Cloudflare_Client::queue_tags( $keys );
	}
}
