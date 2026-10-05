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
	 * Most sites an author change is purged on. See the ec_purge_author_blogs_limit filter.
	 *
	 * @var integer
	 */
	const AUTHOR_BLOGS_LIMIT = 100;

	/**
	 * Current instance when set.
	 *
	 * @var Emitter
	 */
	private static $instance;

	/**
	 * Term counts seen just before a recount, by term taxonomy ID. See action_edit_term_taxonomy().
	 *
	 * @var array
	 */
	private $term_counts = [];

	/**
	 * IDs of users that are being created right now. See filter_insert_user_meta().
	 *
	 * @var array
	 */
	private $new_users = [];

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
		self::purge_post_with_related( $post, $post_before );
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
		if ( in_array( $post->post_type, self::get_ignored_post_types(), true ) || ! self::is_purgeable_post_type( $post->post_type ) ) {
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

		// Only a post that was public has pages to purge. One in trash was purged when it was trashed, and
		// drafts, auto-drafts (WordPress deletes old ones daily), pending and private posts never had any.
		if ( ! $post instanceof \WP_Post || 'publish' !== $post->post_status ) {
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
	public function action_clean_post_cache( $post_id, $post = null ) {
		// The passed post can be the cached copy from before an update, so read it again. It is only needed once the row is deleted.
		$post = get_post( $post_id ) ?: ( $post instanceof \WP_Post ? $post : null );
		$type = $post ? $post->post_type : get_post_type( $post_id );

		if ( $type && in_array( $type, self::get_ignored_post_types(), true ) ) {
			return;
		}

		// Attachments have their own edit and delete handlers, and a new upload has no page to purge yet.
		if ( 'attachment' === $type ) {
			return;
		}

		// Do not purge for drafts, pending or private posts.
		if ( $post && ! in_array( $post->post_status, [ 'publish', 'future' ], true ) ) {
			return;
		}

		$ids = [];

		if ( ! $type || self::is_purgeable_post_type( $type ) ) {
			$ids[] = $post_id;
		} elseif ( $post && $post->post_parent ) {
			// Posts without a page of their own (e.g. product variations) show up on their parent's page.
			$ids[] = $post->post_parent;
		}

		if ( empty( $ids ) ) {
			return;
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
	 * Remember the count of a term before WordPress recounts it.
	 *
	 * Runs on edit_term_taxonomy, just before a term count update, which is when clean_term_cache() follows.
	 *
	 * @param integer $tt_id    Term taxonomy ID.
	 * @param string  $taxonomy Taxonomy name.
	 */
	public function action_edit_term_taxonomy( $tt_id, $taxonomy ) {
		$term = get_term_by( 'term_taxonomy_id', $tt_id, $taxonomy );

		if ( $term && ! is_wp_error( $term ) ) {
			// Keyed by term ID, which is what the clean_term_cache action passes (it differs from the tt_id on many sites).
			$this->term_counts[ get_current_blog_id() . ':' . $term->term_id ] = [
				'taxonomy' => $taxonomy,
				'count'    => (int) $term->count,
			];
		}
	}

	/**
	 * Whether a term was recounted without its count changing. Only counts the term was seen with beforehand.
	 *
	 * @param integer $term_id Term ID, as passed by the clean_term_cache action.
	 *
	 * @return bool
	 */
	private function term_count_unchanged( $term_id ) {
		$key = get_current_blog_id() . ':' . $term_id;

		if ( ! isset( $this->term_counts[ $key ] ) ) {
			return false;
		}

		$before = $this->term_counts[ $key ];
		unset( $this->term_counts[ $key ] );

		$term = get_term( (int) $term_id, $before['taxonomy'] );

		return $term && ! is_wp_error( $term ) && (int) $term->count === $before['count'];
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
			// WordPress clears the term cache after every term count update, which also happens when a draft
			// is saved. If this term's count did not change, nothing public did, so there is nothing to purge.
			if ( $this->term_count_unchanged( $term_id ) ) {
				continue;
			}

			$keys[] = 'term-' . $term_id;
			$keys[] = 'rest-term-' . $term_id;
		}

		if ( empty( $keys ) ) {
			return;
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
		// Only a comment that was or is approved can be on a cached page or in a cached REST response. Moving
		// pending and spam comments around, or deleting them (Akismet clears old spam daily), changes nothing public.
		// A deleted comment comes through here too, with the status it had as the old one.
		if ( 'approved' !== $new_status && 'approved' !== $old_status ) {
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
		// Only an approved comment can be in a cached page. One that is pending, spam, trashed or already
		// deleted (its status is then false) is not, and a comment that left the approved state or was
		// deleted is purged by action_transition_comment_status().
		if ( 'approved' !== wp_get_comment_status( $comment_id ) ) {
			return;
		}

		$comment = get_comment( $comment_id );
		$keys    = [
			'rest-comment-' . $comment_id,
			'rest-comment-huge',
		];
		if ( $comment ) {
			$keys[] = 'post-' . $comment->comment_post_ID;
			$keys[] = 'rest-comment-post-' . $comment->comment_post_ID;
		}
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
	 * Post types that have no page of their own but are shown on pages, so changing them still changes the site.
	 *
	 * @return string[]
	 */
	private static function get_rendered_everywhere_post_types() {
		/**
		 * Filters the post types that are not viewable on their own but whose changes are still purged.
		 *
		 * Posts of other types that are not viewable (form submissions saved as posts, orders and so on) are
		 * never shown to visitors, so changing them purges nothing.
		 *
		 * @param string[] $post_types Post types.
		 */
		return (array) apply_filters(
			'ec_purge_rendered_post_types',
			[ 'wp_template', 'wp_template_part', 'wp_navigation', 'wp_block', 'wp_global_styles', 'nav_menu_item', 'customize_changeset' ]
		);
	}

	/**
	 * Whether changes to posts of a type can change what visitors see.
	 *
	 * @param string $post_type Post type name.
	 *
	 * @return bool
	 */
	private static function is_purgeable_post_type( $post_type ) {
		return is_post_type_viewable( $post_type ) || in_array( $post_type, self::get_rendered_everywhere_post_types(), true );
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
	 * Get the cache tags of the date archives a post appears on: its year, month and day.
	 *
	 * Built from the post's local date, as WordPress builds the archives. The tag emitter puts the matching
	 * tag on each date archive (date-2026, date-2026-10, date-2026-10-05).
	 *
	 * @param object $post Post object.
	 *
	 * @return string[]
	 */
	private static function get_date_tags( $post ) {
		// Drafts and auto-drafts can have the zero date, which is no archive.
		if ( empty( $post->post_date ) || ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})/', $post->post_date, $parts ) || '0000' === $parts[1] ) {
			return [];
		}

		return [
			'date-' . $parts[1],
			'date-' . $parts[1] . '-' . $parts[2],
			'date-' . $parts[1] . '-' . $parts[2] . '-' . $parts[3],
		];
	}

	/**
	 * Purge the cache tags associated with a post being modified.
	 *
	 * @param object      $post        Object representing the modified post.
	 * @param object|null $post_before The post before the update, to purge the date archives it left.
	 */
	private function purge_post_with_related( $post, $post_before = null ) {
		if ( in_array( $post->post_type, self::get_ignored_post_types(), true ) || ! self::is_purgeable_post_type( $post->post_type ) ) {
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
			'graphql-collection',
			'post-huge',
			'rest-post-huge',
		];

		// Date archives: the post's own year, month and day, plus the ones it left when its date changed.
		$keys   = array_merge( $keys, self::get_date_tags( $post ), [ 'date-misc' ] );
		if ( $post_before ) {
			$keys = array_merge( $keys, self::get_date_tags( $post_before ) );
		}

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
	 * Runs on updated_user_meta and deleted_user_meta. WordPress only fires the updated hook when the
	 * stored value really changed. Added meta goes through action_user_meta_added().
	 *
	 * @param int|int[] $meta_id    Meta ID (or IDs when deleted).
	 * @param integer   $user_id    ID of the user the meta belongs to.
	 * @param string    $meta_key   Meta key.
	 * @param mixed     $meta_value Meta value.
	 */
	public function action_user_meta_changed( $meta_id, $user_id, $meta_key, $meta_value = null ) {
		$this->purge_for_user_meta( $user_id, $meta_key );
	}

	/**
	 * Purge the author tags when public user meta is added.
	 *
	 * Adding an empty value (new accounts get empty name and bio rows) changes nothing public.
	 * Clearing an existing value still purges, as that goes through updated_user_meta.
	 *
	 * @param int     $meta_id    Meta ID.
	 * @param integer $user_id    ID of the user the meta belongs to.
	 * @param string  $meta_key   Meta key.
	 * @param mixed   $meta_value Meta value.
	 */
	public function action_user_meta_added( $meta_id, $user_id, $meta_key, $meta_value = null ) {
		if ( '' === $meta_value ) {
			return;
		}

		$this->purge_for_user_meta( $user_id, $meta_key );
	}

	/**
	 * Queue the author tags if the meta key is a public one and the user is not being created.
	 *
	 * @param integer $user_id  User ID.
	 * @param string  $meta_key Meta key.
	 */
	private function purge_for_user_meta( $user_id, $meta_key ) {
		// A user that is being created has no pages yet. WordPress adds its name rows (nickname is the login) now.
		if ( isset( $this->new_users[ $user_id ] ) ) {
			return;
		}

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

		$this->queue_author_tags( $user_id );
	}

	/**
	 * Note that a user is being created, so the meta rows WordPress adds for it do not purge anything.
	 *
	 * Runs on insert_user_meta, before those rows are added. It only reads the arguments.
	 *
	 * @param array   $meta   Meta about to be added.
	 * @param WP_User $user   The user.
	 * @param bool    $update Whether the user is being updated and not created.
	 *
	 * @return array The meta, unchanged.
	 */
	public function filter_insert_user_meta( $meta, $user, $update ) {
		if ( ! $update && $user instanceof \WP_User ) {
			$this->new_users[ $user->ID ] = true;
		}

		return $meta;
	}

	/**
	 * The user is created, so changes to it are real changes from now on.
	 *
	 * @param integer $user_id User ID.
	 */
	public function action_user_register( $user_id ) {
		unset( $this->new_users[ $user_id ] );
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
			$this->queue_author_tag_keys( $keys, $user_id, get_current_blog_id() );

			return;
		}

		// Users are network-wide, so purge their tags on every site they belong to. Each site's tags are queued
		// under that site, as it can use its own Cloudflare credentials.
		$blog_ids = array_unique( array_merge( [ get_current_blog_id() ], array_keys( get_blogs_of_user( $user_id ) ) ) );

		/**
		 * Filters how many sites an author change is purged on.
		 *
		 * A user on hundreds of sites would otherwise queue thousands of tags at once, more than the purge rate
		 * limits allow. Sites beyond the limit keep their cached author pages until those expire.
		 *
		 * @param integer $limit   Most sites to purge.
		 * @param integer $user_id ID of the user.
		 */
		$limit = (int) apply_filters( 'ec_purge_author_blogs_limit', self::AUTHOR_BLOGS_LIMIT, $user_id );

		if ( $limit > 0 && count( $blog_ids ) > $limit ) {
			error_log( 'Advanced Cloudflare Cache: User ' . $user_id . ' is on ' . count( $blog_ids ) . ' sites, purging the author pages of the first ' . $limit . ' only.' );
			$blog_ids = array_slice( $blog_ids, 0, $limit );
		}

		foreach ( $blog_ids as $blog_id ) {
			$this->queue_author_tag_keys( $keys, $user_id, (int) $blog_id );
		}
	}

	/**
	 * Prefix an author's cache tags for a site and queue them for it.
	 *
	 * @param array   $keys    Cache tags, not prefixed yet.
	 * @param integer $user_id ID for the updated user.
	 * @param integer $blog_id Site the tags are for.
	 */
	private function queue_author_tag_keys( array $keys, $user_id, $blog_id ) {
		$keys = ec_cf_prefix_cache_tags_with_blog_id( $keys, $blog_id );
		/**
		 * cache tags purged when an author's public profile changes.
		 *
		 * On multisite this runs once per site the user belongs to.
		 *
		 * @param array $keys      cache tags.
		 * @param integer $user_id ID for the updated user.
		 * @param integer $blog_id Site the tags are for.
		 */
		$keys = apply_filters( 'ec_purge_profile_update', $keys, $user_id, $blog_id );
		Cloudflare_Client::queue_tags( $keys, $blog_id );
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
