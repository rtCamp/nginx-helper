<?php
/**
 * Display sidebar.
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @since      2.0.0
 *
 * @package    nginx-helper
 * @subpackage nginx-helper/admin/partials
 */

$purge_url  = add_query_arg(
	array(
		'nginx_helper_action' => 'purge',
		'nginx_helper_urls'   => 'all',
	)
);
$nonced_url = wp_nonce_url( $purge_url, 'nginx_helper-purge_all' );
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<form id="purgeall" action="" method="post" class="clearfix">
	<a href="<?php echo esc_url( $nonced_url ); ?>" class="button-primary">
		<?php esc_html_e( 'Purge Entire Cache', 'nginx-helper' ); ?>
	</a>
</form>
