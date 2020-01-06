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
<div class="postbox" id="support">
	<h3 class="hndle">
		<span><?php esc_html_e( 'Need Help?', 'nginx-helper' ); ?></span>
	</h3>
	<div class="inside">
		<p>
			<?php
			printf(
				'%s <a href=\'%s\'>%s</a>.',
				esc_html__( 'Please use our', 'nginx-helper' ),
				esc_url( 'http://rtcamp.com/support/forum/wordpress-nginx/' ),
				esc_html__( 'free support forum', 'nginx-helper' )
			);
			?>
		</p>
	</div>
</div>

<div class="postbox" id="social">
	<h3 class="hndle">
		<span>
			<?php esc_html_e( 'Getting Social is Good', 'nginx-helper' ); ?>
		</span>
	</h3>
	<div style="text-align:center;" class="inside">
		<a class="nginx-helper-facebook" title="<?php esc_attr_e( 'Become a fan on Facebook', 'nginx-helper' ); ?>" target="_blank" href="http://www.facebook.com/rtCamp.solutions/"></a>
		<a class="nginx-helper-twitter" title="<?php esc_attr_e( 'Follow us on Twitter', 'nginx-helper' ); ?>" target="_blank" href="https://twitter.com/rtcamp/"></a>
	</div>
</div>

<div class="postbox" id="useful-links">
	<h3 class="hndle">
		<span><?php esc_html_e( 'Useful Links', 'nginx-helper' ); ?></span>
	</h3>
	<div class="inside">
		<ul role="list">
			<li role="listitem">
				<a href="https://rtcamp.com/wordpress-nginx/" title="<?php esc_attr_e( 'WordPress-Nginx Solutions', 'nginx-helper' ); ?>"><?php esc_html_e( 'WordPress-Nginx Solutions', 'nginx-helper' ); ?></a>
			</li>
			<li role="listitem">
				<a href="https://rtcamp.com/services/wordPress-themes-design-development/" title="<?php esc_attr_e( 'WordPress Theme Devleopment', 'nginx-helper' ); ?>"><?php esc_html_e( 'WordPress Theme Devleopment', 'nginx-helper' ); ?></a>
			</li>
			<li role="listitem">
				<a href="http://rtcamp.com/services/wordpress-plugins/" title="<?php esc_attr_e( 'WordPress Plugin Development', 'nginx-helper' ); ?>"><?php esc_html_e( 'WordPress Plugin Development', 'nginx-helper' ); ?></a>
			</li>
			<li role="listitem">
				<a href="http://rtcamp.com/services/custom-wordpress-solutions/" title="<?php esc_attr_e( 'WordPress Consultancy', 'nginx-helper' ); ?>"><?php esc_html_e( 'WordPress Consultancy', 'nginx-helper' ); ?></a>
			</li>
			<li role="listitem">
				<a href="https://rtcamp.com/easyengine/" title="<?php esc_attr_e( 'easyengine (ee)', 'nginx-helper' ); ?>"><?php esc_html_e( 'easyengine (ee)', 'nginx-helper' ); ?></a>
			</li>
		</ul>
	</div>
</div>

<div class="postbox" id="latest_news">
	<div title="<?php esc_attr_e( 'Click to toggle', 'nginx-helper' ); ?>" class="handlediv"><br /></div>
	<h3 class="hndle"><span><?php esc_html_e( 'Latest News', 'nginx-helper' ); ?></span></h3>
	<div class="inside"><img src ="<?php echo esc_url( admin_url() ); ?>/images/wpspin_light.gif" /><?php esc_html_e( 'Loading...', 'nginx-helper' ); ?></div>
</div>
