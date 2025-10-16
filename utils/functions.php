<?php
/**
 * Utility functions used in the plugin.
 *
 * @package nginx-helper
 */

/**
 * Prefix cache tags with the blog ID to provide compatibility with WPMS.
 *
 * @param array $keys Keys to be prefixed.
 */
function ec_cf_prefix_cache_tags_with_blog_id( $keys ) {
	// Do not prefix keys if this is not a multisite install.
	if ( ! is_multisite() ) {
		return $keys;
	}

	// Array that will hold the new keys.
	$prefixed_keys = [];

	$prefix = 'blog-' . get_current_blog_id() . '-';
	$prefix = apply_filters( 'ec_cache_tag_prepend', $prefix );
	foreach ( $keys as $key ) {
		$prefixed_keys[] = $prefix . $key;
	}

	return $prefixed_keys;
}