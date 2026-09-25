<?php
/**
 * WP-CLI commands for managing the Advanced Cloudflare Cache.
 *
 * @package EasyCache
 *
 * @phpcs   :disable Squiz.Commenting.FunctionComment.MissingParamTag
 */

namespace EasyCache;

use WP_CLI;
use EasyCache\Cloudflare_Client;

/**
 * Manage the Advanced Cloudflare Cache.
 */
class CLI {
	/**
	 * Purge one or more cache tags from Cloudflare.
	 *
	 * ## OPTIONS
	 *
	 * <tag>...
	 * : One or more cache tags.
	 *
	 * ## EXAMPLES
	 *
	 *     # Purge the 'post-1' cache tag from Cloudflare.
	 *     $ wp cloudflare cache purge-tag post-1
	 *     Success: Purged tag.
	 *
	 * @subcommand purge-tag
	 */
	public function purge_tag( $args ) {
		$ret = Cloudflare_Client::purgeByTags( $args );
		if ( ! $ret ) {
			WP_CLI::error( 'Failed to purge tags.' );
		} else {
			$message = count( $args ) > 1 ? 'Purged tags.' : 'Purged tag.';
			WP_CLI::success( $message );
		}
	}

	/**
	 * Purge one or more paths from Cloudflare.
	 *
	 * ## OPTIONS
	 *
	 * <path>...
	 * : One or more paths.
	 *
	 * ## EXAMPLES
	 *
	 *     # Purge the homepage from Cloudflare.
	 *     $ wp cloudflare cache purge-path '/'
	 *     Success: Purged path.
	 *
	 * @subcommand purge-path
	 */
	public function purge_path( $args ) {
		$ret = Cloudflare_Client::purgeByUrls( $args );
		if ( ! $ret ) {
			WP_CLI::error( 'Failed to purge paths.' );
		} else {
			$message = count( $args ) > 1 ? 'Purged paths.' : 'Purged path.';
			WP_CLI::success( $message );
		}
	}

	/**
	 * Purge the entire Cloudflare cache for the zone.
	 *
	 * WARNING! Purging the entire page cache can have a severe performance
	 * impact on a high-traffic site. We encourage you to explore other options
	 * first.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     # Purging the entire page cache will display a confirmation prompt.
	 *     $ wp cloudflare cache purge-all
	 *     Are you sure you want to purge the entire page cache? [y/n] y
	 *     Success: Purged page cache.
	 *
	 * @subcommand purge-all
	 */
	public function purge_all( $_, $assoc_args ) {
		WP_CLI::confirm( 'Are you sure you want to purge the entire page cache?', $assoc_args );
		$ret = Cloudflare_Client::purgeEverything();
		if ( ! $ret ) {
			WP_CLI::error( 'Failed to purge all.' );
		} else {
			WP_CLI::success( 'Purged page cache.' );
		}
	}
}
