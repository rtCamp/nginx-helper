<?php
namespace rtCamp\WP\Nginx;

interface Purgeable {
	function purgePostOnComment( $comment_id, $comment );

	function purgePostOnCommentChange( $newstatus, $oldstatus, $comment );

	function purgePost( $_ID );

	function purgeUrl( $url, $feed = true );

	function log( $msg, $level = 'INFO' );

	function checkAndTruncateLogFile();

	function purgeImageOnEdit( $attachment_id );

	function purge_on_post_moved_to_trash( $new_status, $old_status, $post );

	function purge_them_all();

	function purge_on_term_taxonomy_edited( $term_id, $tt_id, $taxon );

	function purge_on_check_ajax_referer( $action, $result );

	function true_purge_all();

	function purge_urls();

	/**
	 * In a multisite setup, this will purge the cache of the current site.
	 */
	function purgeCurrentSite();

	/**
	 * Purges everything with $prefix + $pattern
	 * @param string $pattern Examples could be "*" or "/page/*"
	 */
	function purgeWildcard($pattern);
}