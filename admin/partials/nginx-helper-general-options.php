<?php
/**
 * Display general options of the plugin.
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @since      2.0.0
 *
 * @package    nginx-helper
 * @subpackage nginx-helper/admin/partials
 */

global $nginx_helper_admin;

$error_log_filesize = false;

$args = array(
	'enable_purge',
	'enable_stamp',
	'purge_method',
	'is_submit',
	'redis_hostname',
	'redis_port',
	'redis_prefix',
	'redis_database',
	'redis_username',
	'redis_password',
	'redis_unix_socket',
	'redis_socket_enabled_by_constant',
	'redis_acl_enabled_by_constant',
	'purge_homepage_on_edit',
	'purge_homepage_on_del',
	'purge_url',
	'log_level',
	'log_filesize',
	'smart_http_expire_save',
	'cache_method',
	'enable_map',
	'enable_log',
	'purge_archive_on_edit',
	'purge_archive_on_del',
	'purge_archive_on_new_comment',
	'purge_archive_on_deleted_comment',
	'purge_page_on_mod',
	'purge_page_on_new_comment',
	'purge_page_on_deleted_comment',
	'purge_feeds',
	'smart_http_expire_form_nonce',
	'purge_amp_urls',
	'preload_cache',
);

$all_inputs = array();

foreach ( $args as $val ) {
	if ( isset( $_POST[ $val ] ) ) {
		$all_inputs[ $val ] = wp_strip_all_tags( $_POST[ $val ] );
	}
}

if ( isset( $all_inputs['smart_http_expire_save'] ) && wp_verify_nonce( $all_inputs['smart_http_expire_form_nonce'], 'smart-http-expire-form-nonce' ) ) {
	unset( $all_inputs['smart_http_expire_save'] );
	unset( $all_inputs['is_submit'] );

	$nginx_settings = wp_parse_args(
		$all_inputs,
		$nginx_helper_admin->nginx_helper_default_settings()
	);

	$site_options = get_site_option( 'rt_wp_nginx_helper_options', array() );

	foreach ( $nginx_helper_admin->nginx_helper_default_settings() as $default_setting_field => $default_setting_value ) {

		// Uncheck checkbox fields whose default value is `1` but user has unchecked.
		if ( 1 === $default_setting_value && isset( $site_options[ $default_setting_field ] ) && empty( $all_inputs[ $default_setting_field ] ) ) {

			$nginx_settings[ $default_setting_field ] = 0;

		}

		// Populate the setting field with default value when it is empty.
		if ( '' === $nginx_settings[ $default_setting_field ] ) {

			$nginx_settings[ $default_setting_field ] = $default_setting_value;

		}
	}

	if ( ( ! is_numeric( $nginx_settings['log_filesize'] ) ) || ( empty( $nginx_settings['log_filesize'] ) ) ) {
		$error_log_filesize = __( 'Log file size must be a number.', 'nginx-helper' );
		unset( $nginx_settings['log_filesize'] );
	}

	if ( $nginx_settings['enable_map'] ) {
		$nginx_helper_admin->update_map();
	}

	update_site_option( 'rt_wp_nginx_helper_options', $nginx_settings );

	echo '<div class="updated"><p>' . esc_html__( 'Settings saved.', 'nginx-helper' ) . '</p></div>';

}

$nginx_helper_settings = $nginx_helper_admin->nginx_helper_settings();
$log_path              = $nginx_helper_admin->functional_asset_path();
$log_url               = $nginx_helper_admin->functional_asset_url();

/**
 * Get setting url for single multiple with subdomain OR multiple with subdirectory site.
 */
$nginx_setting_link = '#';
if ( is_multisite() ) {
	if ( SUBDOMAIN_INSTALL === false ) {
		$nginx_setting_link = 'https://easyengine.io/wordpress-nginx/tutorials/multisite/subdirectories/fastcgi-cache-with-purging/';
	} else {
		$nginx_setting_link = 'https://easyengine.io/wordpress-nginx/tutorials/multisite/subdomains/fastcgi-cache-with-purging/';
	}
} else {
	$nginx_setting_link = 'https://easyengine.io/wordpress-nginx/tutorials/single-site/fastcgi-cache-with-purging/';
}
?>

<!-- Forms containing nginx helper settings options. -->
<form id="post_form" method="post" action="#" name="smart_http_expire_form" class="clearfix">
	<div class="postbox">
		<h3 class="hndle">
			<span><?php esc_html_e( 'Purging Options', 'nginx-helper' ); ?></span>
		</h3>
		<div class="inside">
			<table class="form-table">
				<tr valign="top">
					<td>
						<input type="checkbox" value="1" id="enable_purge" name="enable_purge" <?php checked( $nginx_helper_settings['enable_purge'], 1 ); ?> />
						<label for="enable_purge"><?php esc_html_e( 'Enable Purge', 'nginx-helper' ); ?></label>
					</td>
				</tr>
				<tr valign="top">
					<td>
						<input type="checkbox" value="1" id="preload_cache" name="preload_cache" <?php checked( $nginx_helper_settings['preload_cache'], 1 ); ?> />
						<label for="preload_cache"><?php esc_html_e( 'Preload Cache', 'nginx-helper' ); ?></label>
					</td>
				</tr>
			</table>
		</div> <!-- End of .inside -->
	</div>

	<?php if ( ! ( ! is_network_admin() && is_multisite() ) ) { ?>
		<div class="postbox enable_purge"<?php echo ( empty( $nginx_helper_settings['enable_purge'] ) ) ? ' style="display: none;"' : ''; ?>>
			<h3 class="hndle">
				<span><?php esc_html_e( 'Caching Method', 'nginx-helper' ); ?></span>
			</h3>
			<div class="inside">
				<input type="hidden" name="is_submit" value="1" />
				<table class="form-table">
					<tr valign="top">
						<td>
							<input type="radio" value="enable_fastcgi" id="cache_method_fastcgi" name="cache_method" <?php echo checked( $nginx_helper_settings['cache_method'], 'enable_fastcgi' ); ?> />
							<label for="cache_method_fastcgi">
								<?php
								printf(
									'%s (<a target="_blank" href="%s" title="%s">%s</a>)',
									esc_html__( 'nginx Fastcgi cache', 'nginx-helper' ),
									esc_url( $nginx_setting_link ),
									esc_attr__( 'External settings for nginx', 'nginx-helper' ),
									esc_html__( 'requires external settings for nginx', 'nginx-helper' )
								);
								?>
							</label>
						</td>
					</tr>
					<tr valign="top">
						<td>
							<input type="radio" value="enable_redis" id="cache_method_redis" name="cache_method" <?php echo checked( $nginx_helper_settings['cache_method'], 'enable_redis' ); ?> />
							<label for="cache_method_redis">
								<?php printf( esc_html__( 'Redis cache', 'nginx-helper' ) ); ?>
							</label>
						</td>
					</tr>
				</table>
			</div> <!-- End of .inside -->
		</div>
		<div class="enable_purge">
			<div class="postbox cache_method_fastcgi"  <?php echo ( ! empty( $nginx_helper_settings['enable_purge'] ) && 'enable_fastcgi' === $nginx_helper_settings['cache_method'] ) ? '' : 'style="display: none;"'; ?> >
				<h3 class="hndle">
					<span><?php esc_html_e( 'Purge Method', 'nginx-helper' ); ?></span>
				</h3>
				<div class="inside">
					<table class="form-table rtnginx-table">
						<tr valign="top">
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span>
											&nbsp;
											<?php esc_html_e( 'when a post/page/custom post is published.', 'nginx-helper' ); ?>
										</span>
									</legend>
									<label for="purge_method_get_request">
										<input type="radio" value="get_request" id="purge_method_get_request" name="purge_method" <?php checked( $nginx_helper_settings['purge_method'], 'get_request' ); ?>>
										&nbsp;
										<?php
											echo wp_kses(
												sprintf(
													'%1$s <strong>PURGE/url</strong> %2$s',
													esc_html__( 'Using a GET request to', 'nginx-helper' ),
													esc_html__( '(Default option)', 'nginx-helper' )
												),
												array( 'strong' => array() )
											);
										?>
										<br />
										<small>
											<?php
												echo wp_kses(
													sprintf(
														// translators: %s Nginx cache purge module link.
														__( 'Uses the %s module.', 'nginx-helper' ),
														'<strong><a href="https://github.com/FRiCKLE/ngx_cache_purge">ngx_cache_purge</a></strong>'
													),
													array(
														'strong' => array(),
														'a'      => array(
															'href' => array(),
														),
													)
												);
											?>
										</small>
									</label>
									<br />
									<label for="purge_method_unlink_files">
										<input type="radio" value="unlink_files" id="purge_method_unlink_files" name="purge_method" <?php checked( $nginx_helper_settings['purge_method'], 'unlink_files' ); ?>>
										&nbsp;
										<?php
											esc_html_e( 'Delete local server cache files', 'nginx-helper' );
										?>
										<br />
										<small>
											<?php
												echo wp_kses(
													__( 'Checks for matching cache file in <strong>RT_WP_NGINX_HELPER_CACHE_PATH</strong>. Does not require any other modules. Requires that the cache be stored on the same server as WordPress. You must also be using the default nginx cache options (levels=1:2) and (fastcgi_cache_key "$scheme$request_method$host$request_uri").', 'nginx-helper' ),
													array( 'strong' => array() )
												);
											?>
										</small>
									</label>
									<br />
								</fieldset>
							</td>
						</tr>
					</table>
				</div> <!-- End of .inside -->
			</div>
			<div class="postbox cache_method_redis"<?php echo ( ! empty( $nginx_helper_settings['enable_purge'] ) && 'enable_redis' === $nginx_helper_settings['cache_method'] ) ? '' : ' style="display: none;"'; ?>>
				<h3 class="hndle">
					<span><?php esc_html_e( 'Redis Settings', 'nginx-helper' ); ?></span>
				</h3>
				<div class="inside">
					<table class="form-table rtnginx-table">
						<tr>
							<th><label for="redis_hostname"><?php esc_html_e( 'Hostname', 'nginx-helper' ); ?></label></th>
							<td>
								<input id="redis_hostname" class="medium-text" type="text" name="redis_hostname" value="<?php echo esc_attr( $nginx_helper_settings['redis_hostname'] ); ?>" <?php echo ( $nginx_helper_settings['redis_enabled_by_constant'] || $nginx_helper_settings['redis_unix_socket'] ) ? 'readonly="readonly"' : ''; ?> />
								<?php
								if ( $nginx_helper_settings['redis_enabled_by_constant'] ) {

									echo '<p class="description">';
									esc_html_e( 'Overridden by constant variables.', 'nginx-helper' );
									echo '</p>';

								}
								?>
								<?php
								if ( $nginx_helper_settings['redis_unix_socket'] ) {
									echo '<p class="description">';
									esc_html_e( 'Overridden by unix socket path.', 'nginx-helper' );
									echo '</p>';
								}
								?>
							</td>
						</tr>
						<tr>
							<th><label for="redis_port"><?php esc_html_e( 'Port', 'nginx-helper' ); ?></label></th>
							<td>
								<input id="redis_port" class="medium-text" type="text" name="redis_port" value="<?php echo esc_attr( $nginx_helper_settings['redis_port'] ); ?>" <?php echo ( $nginx_helper_settings['redis_enabled_by_constant'] || $nginx_helper_settings['redis_unix_socket'] ) ? 'readonly="readonly"' : ''; ?> />
								<?php
								if ( $nginx_helper_settings['redis_enabled_by_constant'] ) {

									echo '<p class="description">';
									esc_html_e( 'Overridden by constant variables.', 'nginx-helper' );
									echo '</p>';

								}
								?>
								<?php
								if ( $nginx_helper_settings['redis_unix_socket'] ) {
									
									echo '<p class="description">';
									esc_html_e( 'Overridden by unix socket path.', 'nginx-helper' );
									echo '</p>';
									
								}
								?>
							</td>
						</tr>
						<tr>
							<th><label for="redis_unix_socket"><?php esc_html_e( 'Socket Path', 'nginx-helper' ); ?></label></th>
							<td>
								<input id="redis_unix_socket" class="medium-text" type="text" name="redis_unix_socket" value="<?php echo esc_attr( $nginx_helper_settings['redis_unix_socket'] ); ?>" <?php echo ( $nginx_helper_settings['redis_socket_enabled_by_constant'] ) ? 'readonly="readonly"' : ''; ?> />
								<?php
								if ( $nginx_helper_settings['redis_socket_enabled_by_constant'] ) {
									
									echo '<p class="description">';
									esc_html_e( 'Overridden by constant variables.', 'nginx-helper' );
									echo '</p>';
									
								}
								?>
							</td>
						</tr>
						<tr>
							<th><label for="redis_prefix"><?php esc_html_e( 'Prefix', 'nginx-helper' ); ?></label></th>
							<td>
								<input id="redis_prefix" class="medium-text" type="text" name="redis_prefix" value="<?php echo esc_attr( $nginx_helper_settings['redis_prefix'] ); ?>" <?php echo ( $nginx_helper_settings['redis_enabled_by_constant'] ) ? 'readonly="readonly"' : ''; ?> />
								<?php
								if ( $nginx_helper_settings['redis_enabled_by_constant'] ) {

									echo '<p class="description">';
									esc_html_e( 'Overridden by constant variables.', 'nginx-helper' );
									echo '</p>';

								}
								?>
							</td>
						</tr>
						<tr>
							<th><label for="redis_database"><?php esc_html_e( 'Database', 'nginx-helper' ); ?></label></th>
							<td>
								<input id="redis_database" class="medium-text" type="text" name="redis_database" value="<?php echo esc_attr( $nginx_helper_settings['redis_database'] ); ?>" <?php echo ( $nginx_helper_settings['redis_enabled_by_constant'] ) ? 'readonly="readonly"' : ''; ?> />
								<?php
								if ( $nginx_helper_settings['redis_enabled_by_constant'] ) {
									
									echo '<p class="description">';
									esc_html_e( 'Overridden by constant variables.', 'nginx-helper' );
									echo '</p>';
									
								}
								?>
							</td>
						</tr>

						<tr>
							<th><label for="redis_username"><?php esc_html_e( 'Username', 'nginx-helper' ); ?></label></th>
							<td>
								<input id="redis_username" class="medium-text" type="text" name="redis_username" value="<?php echo esc_attr( $nginx_helper_settings['redis_username'] ); ?>" <?php echo ( $nginx_helper_settings['redis_enabled_by_constant'] ) ? 'readonly="readonly"' : ''; ?> />
								<?php
								if ( $nginx_helper_settings['redis_enabled_by_constant'] ) {
									
									echo '<p class="description">';
									esc_html_e( 'Overridden by constant variables.', 'nginx-helper' );
									echo '</p>';
									
								}
								?>
							</td>
						</tr>

						<tr>
							<th><label for="redis_password"><?php esc_html_e( 'Password', 'nginx-helper' ); ?></label></th>
							<td>
								<div class="password-wrapper">
									<input id="redis_password" class="medium-text password-input" type="password" name="redis_password" value="<?php echo esc_attr( $nginx_helper_settings['redis_password'] ); ?>" <?php echo ( $nginx_helper_settings['redis_enabled_by_constant'] ) ? 'readonly="readonly"' : ''; ?> />
									<button type="button" class="password-show-hide-btn"><span class="dashicons dashicons-hidden password-input-icon"></span></button>
								</div>
								<?php
								if ( $nginx_helper_settings['redis_enabled_by_constant'] ) {
									echo '<p class="description">';
									esc_html_e( 'Overridden by constant variables.', 'nginx-helper' );
									echo '</p>';
									
								}
								?>
							</td>
						</tr>
					</table>
				</div> <!-- End of .inside -->
			</div>
		</div>
		<div class="postbox enable_purge"<?php echo ( empty( $nginx_helper_settings['enable_purge'] ) ) ? ' style="display: none;"' : ''; ?>>
			<h3 class="hndle">
				<span><?php esc_html_e( 'Purging Conditions', 'nginx-helper' ); ?></span>
			</h3>
			<div class="inside">
				<table class="form-table rtnginx-table">
					<tr valign="top">
						<th scope="row"><h4><?php esc_html_e( 'Purge Homepage:', 'nginx-helper' ); ?></h4></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										&nbsp;
										<?php
											esc_html_e( 'when a post/page/custom post is modified or added.', 'nginx-helper' );
										?>
									</span>
								</legend>
								<label for="purge_homepage_on_edit">
									<input type="checkbox" value="1" id="purge_homepage_on_edit" name="purge_homepage_on_edit" <?php checked( $nginx_helper_settings['purge_homepage_on_edit'], 1 ); ?> />
									&nbsp;
									<?php
										echo wp_kses(
											__( 'when a <strong>post</strong> (or page/custom post) is <strong>modified</strong> or <strong>added</strong>.', 'nginx-helper' ),
											array( 'strong' => array() )
										);
									?>
								</label>
								<br />
							</fieldset>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										&nbsp;
										<?php
											esc_html_e( 'when an existing post/page/custom post is modified.', 'nginx-helper' );
										?>
									</span>
								</legend>
								<label for="purge_homepage_on_del">
									<input type="checkbox" value="1" id="purge_homepage_on_del" name="purge_homepage_on_del" <?php checked( $nginx_helper_settings['purge_homepage_on_del'], 1 ); ?> />
									&nbsp;
									<?php
										echo wp_kses(
											__( 'when a <strong>published post</strong> (or page/custom post) is <strong>trashed</strong>', 'nginx-helper' ),
											array( 'strong' => array() )
										);
									?>
								</label>
								<br />
							</fieldset>
						</td>
					</tr>
				</table>
				<table class="form-table rtnginx-table">
					<tr valign="top">
						<th scope="row">
							<h4>
								<?php esc_html_e( 'Purge Post/Page/Custom Post Type:', 'nginx-helper' ); ?>
							</h4>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>&nbsp;
										<?php
											esc_html_e( 'when a post/page/custom post is published.', 'nginx-helper' );
										?>
									</span>
								</legend>
								<label for="purge_page_on_mod">
									<input type="checkbox" value="1" id="purge_page_on_mod" name="purge_page_on_mod" <?php checked( $nginx_helper_settings['purge_page_on_mod'], 1 ); ?>>
									&nbsp;
									<?php
										echo wp_kses(
											__( 'when a <strong>post</strong> is <strong>published</strong>.', 'nginx-helper' ),
											array( 'strong' => array() )
										);
									?>
								</label>
								<br />
							</fieldset>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										&nbsp;
										<?php
											esc_html_e( 'when a comment is approved/published.', 'nginx-helper' );
										?>
									</span>
								</legend>
								<label for="purge_page_on_new_comment">
									<input type="checkbox" value="1" id="purge_page_on_new_comment" name="purge_page_on_new_comment" <?php checked( $nginx_helper_settings['purge_page_on_new_comment'], 1 ); ?>>
									&nbsp;
									<?php
										echo wp_kses(
											__( 'when a <strong>comment</strong> is <strong>approved/published</strong>.', 'nginx-helper' ),
											array( 'strong' => array() )
										);
									?>
								</label>
								<br />
							</fieldset>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										&nbsp;
										<?php
											esc_html_e( 'when a comment is unapproved/deleted.', 'nginx-helper' );
										?>
									</span>
								</legend>
								<label for="purge_page_on_deleted_comment">
									<input type="checkbox" value="1" id="purge_page_on_deleted_comment" name="purge_page_on_deleted_comment" <?php checked( $nginx_helper_settings['purge_page_on_deleted_comment'], 1 ); ?>>
									&nbsp;
									<?php
										echo wp_kses(
											__( 'when a <strong>comment</strong> is <strong>unapproved/deleted</strong>.', 'nginx-helper' ),
											array( 'strong' => array() )
										);
									?>
								</label>
								<br />
							</fieldset>
						</td>
					</tr>
				</table>
				<table class="form-table rtnginx-table">
					<tr valign="top">
						<th scope="row">
							<h4>
								<?php esc_html_e( 'Purge Archives:', 'nginx-helper' ); ?>
							</h4>
							<small><?php esc_html_e( '(date, category, tag, author, custom taxonomies)', 'nginx-helper' ); ?></small>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										&nbsp;
										<?php
											esc_html_e( 'when an post/page/custom post is modified or added', 'nginx-helper' );
										?>
									</span>
								</legend>
								<label for="purge_archive_on_edit">
									<input type="checkbox" value="1" id="purge_archive_on_edit" name="purge_archive_on_edit" <?php checked( $nginx_helper_settings['purge_archive_on_edit'], 1 ); ?> />
									&nbsp;
									<?php
										echo wp_kses(
											__( 'when a <strong>post</strong> (or page/custom post) is <strong>modified</strong> or <strong>added</strong>.', 'nginx-helper' ),
											array( 'strong' => array() )
										);
									?>
								</label>
								<br />
							</fieldset>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										&nbsp;
										<?php
											esc_html_e( 'when an existing post/page/custom post is trashed.', 'nginx-helper' );
										?>
									</span>
								</legend>
								<label for="purge_archive_on_del">
									<input type="checkbox" value="1" id="purge_archive_on_del" name="purge_archive_on_del"<?php checked( $nginx_helper_settings['purge_archive_on_del'], 1 ); ?> />
									&nbsp;
									<?php
										echo wp_kses(
											__( 'when a <strong>published post</strong> (or page/custom post) is <strong>trashed</strong>.', 'nginx-helper' ),
											array( 'strong' => array() )
										);
									?>
								</label>
								<br />
							</fieldset>
							<br />
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										&nbsp;
										<?php
											esc_html_e( 'when a comment is approved/published.', 'nginx-helper' );
										?>
									</span>
								</legend>
								<label for="purge_archive_on_new_comment">
									<input type="checkbox" value="1" id="purge_archive_on_new_comment" name="purge_archive_on_new_comment" <?php checked( $nginx_helper_settings['purge_archive_on_new_comment'], 1 ); ?> />
									&nbsp;
									<?php
										echo wp_kses(
											__( 'when a <strong>comment</strong> is <strong>approved/published</strong>.', 'nginx-helper' ),
											array( 'strong' => array() )
										);
									?>
								</label>
								<br />
							</fieldset>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										&nbsp;
										<?php
											esc_html_e( 'when a comment is unapproved/deleted.', 'nginx-helper' );
										?>
									</span>
								</legend>
								<label for="purge_archive_on_deleted_comment">
									<input type="checkbox" value="1" id="purge_archive_on_deleted_comment" name="purge_archive_on_deleted_comment" <?php checked( $nginx_helper_settings['purge_archive_on_deleted_comment'], 1 ); ?> />
									&nbsp;
									<?php
										echo wp_kses(
											__( 'when a <strong>comment</strong> is <strong>unapproved/deleted</strong>.', 'nginx-helper' ),
											array( 'strong' => array() )
										);
									?>
								</label>
								<br />
							</fieldset>
						</td>
					</tr>
				</table>
				<table class="form-table rtnginx-table">
					<tr valign="top">
						<th scope="row">
							<h4>
								<?php esc_html_e( 'Purge Feeds:', 'nginx-helper' ); ?>
							</h4>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										&nbsp;
										<?php
											esc_html_e( 'purge feeds', 'nginx-helper' );
										?>
									</span>
								</legend>
								<label for="purge_feeds">
									<input type="checkbox" value="1" id="purge_feeds" name="purge_feeds" <?php checked( $nginx_helper_settings['purge_feeds'], 1 ); ?> />
									&nbsp;
									<?php
										echo wp_kses(
											__( 'purge <strong>feeds</strong> along with <strong>posts</strong> & <strong>pages</strong>.', 'nginx-helper' ),
											array( 'strong' => array() )
										);
									?>
								</label>
								<br />
							</fieldset>
						</td>
					</tr>
				</table>
				<table class="form-table rtnginx-table">
					<tr valign="top">
						<th scope="row">
							<h4>
				<?php esc_html_e( 'Purge AMP URL:', 'nginx-helper' ); ?>
							</h4>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span>
										&nbsp;
										<?php
										esc_html_e( 'purge amp urls', 'nginx-helper' );
										?>
									</span>
								</legend>
								<label for="purge_amp_urls">
									<input type="checkbox" value="1" id="purge_amp_urls" name="purge_amp_urls" <?php checked( $nginx_helper_settings['purge_amp_urls'], 1 ); ?> />
									&nbsp;
									<?php
									echo wp_kses(
										__( 'purge <strong>amp urls</strong> along with <strong>posts</strong> & <strong>pages</strong>.', 'nginx-helper' ),
										array( 'strong' => array() )
									);
									?>
								</label>
								<br />
							</fieldset>
						</td>
					</tr>
				</table>
				<table class="form-table rtnginx-table">
					<tr valign="top">
						<th scope="row">
							<h4><?php esc_html_e( 'Custom Purge URL:', 'nginx-helper' ); ?></h4>
						</th>
						<td>
							<textarea rows="5"class="rt-purge_url" id="purge_url" name="purge_url"><?php echo esc_textarea( $nginx_helper_settings['purge_url'] ); ?></textarea>
							<p class="description">
								<?php
								esc_html_e( 'Add one URL per line. URL should not contain domain name.', 'nginx-helper' );
								echo '<br>';
								echo wp_kses(
									__( 'Eg: To purge http://example.com/sample-page/ add <strong>/sample-page/</strong> in above textarea.', 'nginx-helper' ),
									array( 'strong' => array() )
								);
								echo '<br>';
								esc_html_e( "'*' will only work with redis cache server.", 'nginx-helper' );
								?>
							</p>
						</td>
					</tr>
				</table>
			</div> <!-- End of .inside -->
		</div>
		<div class="postbox">
			<h3 class="hndle">
				<span><?php esc_html_e( 'Debug Options', 'nginx-helper' ); ?></span>
			</h3>
			<div class="inside">
				<input type="hidden" name="is_submit" value="1" />
				<table class="form-table">
				<?php if ( is_network_admin() ) { ?>
					<tr valign="top">
						<td>
							<input type="checkbox" value="1" id="enable_map" name="enable_map" <?php checked( $nginx_helper_settings['enable_map'], 1 ); ?> />
							<label for="enable_map">
								<?php esc_html_e( 'Enable Nginx Map.', 'nginx-helper' ); ?>
							</label>
						</td>
					</tr>
				<?php } ?>
					<tr valign="top">
						<td>
							<?php
							$is_checkbox_enabled = false;
							if ( 1 === (int) $nginx_helper_settings['enable_log'] ) {
								$is_checkbox_enabled = true;
							}
							?>
							<input
								type="checkbox" value="1" id="enable_log" name="enable_log"
								<?php checked( $nginx_helper_admin->is_nginx_log_enabled(), true ); ?>
								<?php echo esc_attr( $is_checkbox_enabled ? '' : ' disabled ' ); ?>
							/>
							<label for="enable_log">
								<?php esc_html_e( 'Enable Logging', 'nginx-helper' ); ?>
								<?php
								if ( ! $is_checkbox_enabled ) {

									$setting_message_detail = [
										'status' => __( 'disable', 'nginx-helper' ),
										'value'  => 'false',
									];

									if ( ! $nginx_helper_admin->is_nginx_log_enabled() ) {
										$setting_message_detail = [
											'status' => __( 'enable', 'nginx-helper' ),
											'value'  => 'true',
										];
									}

									printf(
										'<p class="enable-logging-message">(<b>%1$s:</b> %2$s %3$s %4$s <b>NGINX_HELPER_LOG</b> constant %5$s <b>%6$s</b> %7$s <b>wp-config.php</b>)</p>',
										esc_html__( 'NOTE', 'nginx-helper' ),
										esc_html__( 'To', 'nginx-helper' ),
										esc_html( $setting_message_detail['status'] ),
										esc_html__( 'the logging feature, you must define', 'nginx-helper' ),
										esc_html__( 'as', 'nginx-helper' ),
										esc_html( $setting_message_detail['value'] ),
										esc_html__( 'in your', 'nginx-helper' )
									);
								}
								?>
							</label>
						</td>
					</tr>
					<tr valign="top">
						<td>
							<input type="checkbox" value="1" id="enable_stamp" name="enable_stamp" <?php checked( $nginx_helper_settings['enable_stamp'], 1 ); ?> />
							<label for="enable_stamp">
								<?php esc_html_e( 'Enable Nginx Timestamp in HTML', 'nginx-helper' ); ?>
							</label>
						</td>
					</tr>
				</table>
			</div> <!-- End of .inside -->
		</div>
		<?php
	} // End of if.

	if ( is_network_admin() ) {
		?>
		<div class="postbox enable_map"<?php echo ( empty( $nginx_helper_settings['enable_map'] ) ) ? ' style="display: none;"' : ''; ?>>
			<h3 class="hndle">
				<span><?php esc_html_e( 'Nginx Map', 'nginx-helper' ); ?></span>
			</h3>
			<div class="inside">
			<?php
			if ( ! is_writable( $log_path . 'map.conf' ) ) {
				?>
					<span class="error fade" style="display: block">
						<p>
							<?php
								esc_html_e( 'Can\'t write on map file.', 'nginx-helper' );
								echo '<br /><br />';
								echo wp_kses(
									sprintf(
										// translators: %s file url.
										__( 'Check you have write permission on <strong>%s</strong>', 'nginx-helper' ),
										esc_url( $log_path . 'map.conf' )
									),
									array( 'strong' => array() )
								);
							?>
						</p>
					</span>
				<?php
			}
			?>
				<table class="form-table rtnginx-table">
					<tr>
						<th>
						<?php
						printf(
							'%1$s<br /><small>%2$s</small>',
							esc_html__( 'Nginx Map path to include in nginx settings', 'nginx-helper' ),
							esc_html__( '(recommended)', 'nginx-helper' )
						);
						?>
						</th>
						<td>
							<pre><?php echo esc_url( $log_path . 'map.conf' ); ?></pre>
						</td>
					</tr>
					<tr>
						<th>
							<?php
							printf(
								'%1$s<br />%2$s<br /><small>%3$s</small>',
								esc_html__( 'Or,', 'nginx-helper' ),
								esc_html__( 'Text to manually copy and paste in nginx settings', 'nginx-helper' ),
								esc_html__( '(if your network is small and new sites are not added frequently)', 'nginx-helper' )
							);
							?>
						</th>
						<td>
							<pre id="map">
							<?php echo esc_html( $nginx_helper_admin->get_map() ); ?>
							</pre>
						</td>
					</tr>
				</table>
			</div> <!-- End of .inside -->
		</div>
		<?php
	}
	?>
	<div class="postbox enable_log"<?php echo ( ! $nginx_helper_admin->is_nginx_log_enabled() ) ? ' style="display: none;"' : ''; ?>>
		<h3 class="hndle">
			<span><?php esc_html_e( 'Logging Options', 'nginx-helper' ); ?></span>
		</h3>
		<div class="inside">
			<?php
			if ( ! is_dir( $log_path ) ) {
				mkdir( $log_path );
			}
			if ( is_writable( $log_path ) && ! file_exists( $log_path . 'nginx.log' ) ) {
				$log = fopen( $log_path . 'nginx.log', 'w' );
				fclose( $log );
			}
			if ( ! is_writable( $log_path . 'nginx.log' ) ) {
				?>
				<span class="error fade" style="display : block">
					<p>
					<?php
					esc_html_e( 'Can\'t write on log file.', 'nginx-helper' );
					echo '<br /><br />';
					echo wp_kses(
						sprintf(
							// translators: %s file url.
							__( 'Check you have write permission on <strong>%s</strong>', 'nginx-helper' ),
							esc_url( $log_path . 'nginx.log' )
						),
						array( 'strong' => array() )
					);
					?>
					</p>
				</span>
				<?php
			}
			?>

			<table class="form-table rtnginx-table">
				<tbody>
					<tr>
						<th>
							<label for="rt_wp_nginx_helper_logs_path">
								<?php esc_html_e( 'Logs path', 'nginx-helper' ); ?>
							</label>
						</th>
						<td>
							<code>
								<?php echo esc_url( $log_path . 'nginx.log' ); ?>
							</code>
						</td>
					</tr>
					<tr>
						<th>
							<label for="rt_wp_nginx_helper_logs_link">
								<?php esc_html_e( 'View Log', 'nginx-helper' ); ?>
							</label>
						</th>
						<td>
							<a target="_blank" href="<?php echo esc_url( $log_url . 'nginx.log' ); ?>">
								<?php esc_html_e( 'Log', 'nginx-helper' ); ?>
							</a>
						</td>
					</tr>
					<tr>
						<th>
							<label for="rt_wp_nginx_helper_log_level">
								<?php esc_html_e( 'Log level', 'nginx-helper' ); ?>
							</label>
						</th>
						<td>
							<select name="log_level">
								<option value="NONE" <?php selected( $nginx_helper_settings['log_level'], 'NONE' ); ?>> <?php esc_html_e( 'None', 'nginx-helper' ); ?> </option>
								<option value="INFO" <?php selected( $nginx_helper_settings['log_level'], 'INFO' ); ?>> <?php esc_html_e( 'Info', 'nginx-helper' ); ?> </option>
								<option value="WARNING" <?php selected( $nginx_helper_settings['log_level'], 'WARNING' ); ?>> <?php esc_html_e( 'Warning', 'nginx-helper' ); ?> </option>
								<option value="ERROR" <?php selected( $nginx_helper_settings['log_level'], 'ERROR' ); ?>> <?php esc_html_e( 'Error', 'nginx-helper' ); ?> </option>
							</select>
						</td>
					</tr>
					<tr>
						<th>
							<label for="log_filesize">
								<?php esc_html_e( 'Max log file size', 'nginx-helper' ); ?>
							</label>
						</th>
						<td>
							<input id="log_filesize" class="small-text" type="text" name="log_filesize" value="<?php echo esc_attr( $nginx_helper_settings['log_filesize'] ); ?>" />
							<?php
								esc_html_e( 'Mb', 'nginx-helper' );
							if ( $error_log_filesize ) {
								?>
								<p class="error fade" style="display: block;">
								<?php echo esc_html( $error_log_filesize ); ?>
								</p>
								<?php
							}
							?>
						</td>
					</tr>
				</tbody>
			</table>
		</div> <!-- End of .inside -->
	</div>
	<input type="hidden" name="smart_http_expire_form_nonce" value="<?php echo esc_attr( wp_create_nonce( 'smart-http-expire-form-nonce' ) ); ?>" />
	<?php
		submit_button( __( 'Save All Changes', 'nginx-helper' ), 'primary large', 'smart_http_expire_save', true );
	?>
</form><!-- End of #post_form -->
