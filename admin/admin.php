<?php

namespace rtCamp\WP\Nginx {

	class Admin {

		function __construct() {
			if ( is_multisite() ) {
				add_action( 'network_admin_menu', array( &$this, 'add_network_menu' ) );
			} else {
				add_action( 'admin_menu', array( &$this, 'add_menu' ) );
			}
			add_action( 'admin_print_scripts', array( &$this, 'load_scripts' ) );
			add_action( 'admin_print_styles', array( &$this, 'load_styles' ) );
			add_action( 'admin_bar_menu', array( &$this, 'add_toolbar_purge_item' ), 100 );
		}

		function add_menu() {
			add_submenu_page( 'options-general.php', 'Nginx Helper', __( 'Nginx Helper', 'rt_wp_nginx_helper' ), 'install_plugins', 'nginx', array( &$this, 'show_menu' ) );
			//add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function)
		}

		function add_network_menu() {
			add_submenu_page( 'settings.php', 'Nginx Helper', __( 'Nginx Helper', 'rt_wp_nginx_helper' ), 'install_plugins', 'nginx', array( &$this, 'show_menu' ) );
		}

		// load the script for the defined page and load only this code
		function show_menu() {

			global $rt_wp_nginx_helper, $rt_wp_nginx_purger;;

			$required_page = $_GET[ 'page' ];
			switch ( $required_page ) {

				default :

					$update = 0;
					$error_time = false;
					$error_log_filesize = false;
					$rt_wp_nginx_helper->options[ 'enable_purge' ] = (isset( $_POST[ 'enable_purge' ] ) and ($_POST[ 'enable_purge' ] == 1) ) ? 1 : 0;
					$rt_wp_nginx_helper->options[ 'enable_map' ] = (isset( $_POST[ 'enable_map' ] ) and ($_POST[ 'enable_map' ] == 1) ) ? 1 : 0;
					$rt_wp_nginx_helper->options[ 'enable_log' ] = (isset( $_POST[ 'enable_log' ] ) and ($_POST[ 'enable_log' ] == 1) ) ? 1 : 0;
					$rt_wp_nginx_helper->options[ 'enable_stamp' ] = (isset( $_POST[ 'enable_stamp' ] ) and ($_POST[ 'enable_stamp' ] == 1) ) ? 1 : 0;

					if ( isset( $_POST[ 'is_submit' ] ) && ($_POST[ 'is_submit' ] == 1) ) {
						if ( ! ( ! is_network_admin() && is_multisite()) ) {
							if ( $rt_wp_nginx_helper->options[ 'enable_log' ] ) {
								if ( isset( $_POST[ 'log_level' ] ) && ! empty( $_POST[ 'log_level' ] ) && $_POST[ 'log_level' ] != '' ) {
									$rt_wp_nginx_helper->options[ 'log_level' ] = $_POST[ 'log_level' ];
								} else {
									$rt_wp_nginx_helper->options[ 'log_level' ] = 'INFO';
								}
								if ( isset( $_POST[ 'log_filesize' ] ) && ! empty( $_POST[ 'log_filesize' ] ) && $_POST[ 'log_filesize' ] != '' ) {
									if ( ( ! is_numeric( $_POST[ 'log_filesize' ] )) || (empty( $_POST[ 'log_filesize' ] )) ) {
										$error_log_filesize = "Log file size must be a number";
									} else {
										$rt_wp_nginx_helper->options[ 'log_filesize' ] = $_POST[ 'log_filesize' ];
									}
								} else {
									$rt_wp_nginx_helper->options[ 'log_filesize' ] = 5;
								}
							}
							if ( $rt_wp_nginx_helper->options[ 'enable_map' ] ) {
								$rt_wp_nginx_helper->update_map();
							}
						}
						if ( isset( $_POST[ 'enable_purge' ] ) ) {

							$rt_wp_nginx_helper->options[ 'purge_homepage_on_edit' ] = (isset( $_POST[ 'purge_homepage_on_edit' ] ) and ($_POST[ 'purge_homepage_on_edit' ] == 1) ) ? 1 : 0;
							$rt_wp_nginx_helper->options[ 'purge_homepage_on_del' ] = (isset( $_POST[ 'purge_homepage_on_del' ] ) and ($_POST[ 'purge_homepage_on_del' ] == 1) ) ? 1 : 0;

							$rt_wp_nginx_helper->options[ 'purge_archive_on_edit' ] = (isset( $_POST[ 'purge_archive_on_edit' ] ) and ($_POST[ 'purge_archive_on_edit' ] == 1) ) ? 1 : 0;
							$rt_wp_nginx_helper->options[ 'purge_archive_on_del' ] = (isset( $_POST[ 'purge_archive_on_del' ] ) and ($_POST[ 'purge_archive_on_del' ] == 1) ) ? 1 : 0;

							$rt_wp_nginx_helper->options[ 'purge_archive_on_new_comment' ] = (isset( $_POST[ 'purge_archive_on_new_comment' ] ) and ($_POST[ 'purge_archive_on_new_comment' ] == 1) ) ? 1 : 0;
							$rt_wp_nginx_helper->options[ 'purge_archive_on_deleted_comment' ] = (isset( $_POST[ 'purge_archive_on_deleted_comment' ] ) and ($_POST[ 'purge_archive_on_deleted_comment' ] == 1) ) ? 1 : 0;

							$rt_wp_nginx_helper->options[ 'purge_page_on_mod' ] = (isset( $_POST[ 'purge_page_on_mod' ] ) and ($_POST[ 'purge_page_on_mod' ] == 1) ) ? 1 : 0;
							$rt_wp_nginx_helper->options[ 'purge_page_on_new_comment' ] = (isset( $_POST[ 'purge_page_on_new_comment' ] ) and ($_POST[ 'purge_page_on_new_comment' ] == 1) ) ? 1 : 0;
							$rt_wp_nginx_helper->options[ 'purge_page_on_deleted_comment' ] = (isset( $_POST[ 'purge_page_on_deleted_comment' ] ) and ($_POST[ 'purge_page_on_deleted_comment' ] == 1) ) ? 1 : 0;
						}
						update_site_option( "rt_wp_nginx_helper_options", $rt_wp_nginx_helper->options );


						$update = 1;
					}
					$rt_wp_nginx_helper->options = get_site_option( "rt_wp_nginx_helper_options" );
					?>

					<div class="wrap">

						<div class="icon32" id="icon-options-nginx"><br /></div>
						<h2>Nginx Settings</h2>
						<div id="content_block" class="align_left">
							<form id="purgeall" action="" method="post">
										<?php $purge_url = add_query_arg( array( 'nginx_helper_action' => 'purge', 'nginx_helper_urls' => 'all' ) ); ?>
										<?php $nonced_url = wp_nonce_url( $purge_url, 'nginx_helper-purge_all' ); ?>
										<a href="<?php echo $nonced_url; ?>" class="button-primary">Purge Cache</a>
							</form>
							<form id="post_form" method="post" action="#" name="smart_http_expire_form">
								<?php if ( ! ( ! is_network_admin() && is_multisite()) ) { ?>

									<input type="hidden" name="is_submit" value="1" />

									<h3>Plugin Options</h3>

									<table class="form-table">
										<tr valign="top">
											<td>
												<label for="enable_purge"><input type="checkbox" value="1" id="enable_purge" name="enable_purge"<?php checked( $rt_wp_nginx_helper->options[ 'enable_purge' ], 1 ); ?>>&nbsp;Enable Cache Purge (requires external settings for nginx).</label><br />
												<?php if ( is_network_admin() ) { ?>
													<label for="enable_map"><input type="checkbox" value="1" id="enable_map" name="enable_map"<?php checked( $rt_wp_nginx_helper->options[ 'enable_map' ], 1 ); ?>>&nbsp;Enable Nginx Map.</label><br />
												<?php } ?>
												<label for="enable_log"><input type="checkbox" value="1" id="enable_log" name="enable_log"<?php checked( $rt_wp_nginx_helper->options[ 'enable_log' ], 1 ); ?>>&nbsp;Enable Logging</label><br />
												<label for="enable_stamp"><input type="checkbox" value="1" id="enable_stamp" name="enable_stamp"<?php checked( $rt_wp_nginx_helper->options[ 'enable_stamp' ], 1 ); ?>>&nbsp;Enable Nginx Timestamp in HTML</label>
											</td>
										</tr>
									</table>

									<?php
									$displayvar = '';
									if ( $rt_wp_nginx_helper->options[ 'enable_purge' ] == false ) {
										$displayvar = ' style="display:none"';
									}
									?>
									<h3<?php echo $displayvar; ?>>Purging Options</h3>

									<table class="form-table rtnginx-table"<?php echo $displayvar; ?>>
										<tr valign="top">
											<th scope="row"><h4>Purge Homepage:</h4></th>
										<td>
											<fieldset>
												<legend class="screen-reader-text"><span>&nbsp;when a post/page/custom post is modified or added.</span></legend>
												<label for="purge_homepage_on_edit"><input type="checkbox" value="1" id="purge_homepage_on_edit" name="purge_homepage_on_edit"<?php checked( $rt_wp_nginx_helper->options[ 'purge_homepage_on_edit' ], 1 ); ?>>&nbsp;when a <strong>post</strong> (or page/custom post) is <strong>modified</strong> or <strong>added</strong>.</label><br />
											</fieldset>
											<fieldset>
												<legend class="screen-reader-text"><span>&nbsp;when an existing post/page/custom post is modified.</span></legend>
												<label for="purge_homepage_on_del"><input type="checkbox" value="1" id="purge_homepage_on_del" name="purge_homepage_on_del"<?php checked( $rt_wp_nginx_helper->options[ 'purge_homepage_on_del' ], 1 ); ?>>&nbsp;when a <strong>published post</strong> (or page/custom post) is <strong>trashed</strong>.</label><br />
											</fieldset>
										</td>
										</tr>
									</table>
									<table class="form-table rtnginx-table"<?php echo $displayvar; ?>>
										<tr valign="top">
											<th scope="row">
										<h4>Purge Post/Page/Custom Post Type:</h4>
										</th>
										<td>
											<fieldset>
												<legend class="screen-reader-text"><span>&nbsp;when a post/page/custom post is published.</span></legend>
												<label for="purge_page_on_mod"><input type="checkbox" value="1" id="purge_page_on_mod" name="purge_page_on_mod"<?php checked( $rt_wp_nginx_helper->options[ 'purge_page_on_mod' ], 1 ); ?>>&nbsp;when a <strong>post</strong> is <strong>published</strong>.</label><br />
											</fieldset>
											<fieldset>
												<legend class="screen-reader-text"><span>&nbsp;when a comment is approved/published.</span></legend>
												<label for="purge_page_on_new_comment"><input type="checkbox" value="1" id="purge_page_on_new_comment" name="purge_page_on_new_comment"<?php checked( $rt_wp_nginx_helper->options[ 'purge_page_on_new_comment' ], 1 ); ?>>&nbsp;when a <strong>comment</strong> is <strong>approved/published</strong>.</label><br />
											</fieldset>
											<fieldset>
												<legend class="screen-reader-text"><span>&nbsp;when a comment is unapproved/deleted.</span></legend>
												<label for="purge_page_on_deleted_comment"><input type="checkbox" value="1" id="purge_page_on_deleted_comment" name="purge_page_on_deleted_comment"<?php checked( $rt_wp_nginx_helper->options[ 'purge_page_on_deleted_comment' ], 1 ); ?>>&nbsp;when a <strong>comment</strong> is <strong>unapproved/deleted</strong>.</label><br />
											</fieldset>
										</td>
										</tr>
									</table>
									<table class="form-table rtnginx-table"<?php echo $displayvar; ?>>
										<tr valign="top">
											<th scope="row">
										<h4>Purge Archives:</h4>
										<small>(date, category, tag, author, custom taxonomies)</small>
										</th>
										<td>
											<fieldset>
												<legend class="screen-reader-text"><span>&nbsp;when an post/page/custom post is modified or added.</span></legend>
												<label for="purge_archive_on_edit"><input type="checkbox" value="1" id="purge_archive_on_edit" name="purge_archive_on_edit"<?php checked( $rt_wp_nginx_helper->options[ 'purge_archive_on_edit' ], 1 ); ?>>&nbsp;when a <strong>post</strong> (or page/custom post) is <strong>modified</strong> or <strong>added</strong>.</label><br />
											</fieldset>
											<fieldset>
												<legend class="screen-reader-text"><span>&nbsp;when an existing post/page/custom post is trashed.</span></legend>
												<label for="purge_archive_on_del"><input type="checkbox" value="1" id="purge_archive_on_del" name="purge_archive_on_del"<?php checked( $rt_wp_nginx_helper->options[ 'purge_archive_on_del' ], 1 ); ?>>&nbsp;when a <strong>published post</strong> (or page/custom post) is <strong>trashed</strong>.</label><br />
											</fieldset>
											<br />
											<fieldset>
												<legend class="screen-reader-text"><span>&nbsp;when a comment is approved/published.</span></legend>
												<label for="purge_archive_on_new_comment"><input type="checkbox" value="1" id="purge_archive_on_new_comment" name="purge_archive_on_new_comment"<?php checked( $rt_wp_nginx_helper->options[ 'purge_archive_on_new_comment' ], 1 ); ?>>&nbsp;when a <strong>comment</strong> is <strong>approved/published</strong>.</label><br />
											</fieldset>
											<fieldset>
												<legend class="screen-reader-text"><span>&nbsp;when a comment is unapproved/deleted.</span></legend>
												<label for="purge_archive_on_deleted_comment"><input type="checkbox" value="1" id="purge_archive_on_deleted_comment" name="purge_archive_on_deleted_comment"<?php checked( $rt_wp_nginx_helper->options[ 'purge_archive_on_deleted_comment' ], 1 ); ?>>&nbsp;when a <strong>comment</strong> is <strong>unapproved/deleted</strong>.</label><br />
											</fieldset>

										</td>
										</tr>
									</table>

									<?php
								}
								if ( is_network_admin() && $rt_wp_nginx_helper->options[ 'enable_map' ] != false ) {
									?>
									<h3>Nginx Map</h3>
									<?php if ( ! is_writable( $rt_wp_nginx_helper->functional_asset_path() . 'map.conf' ) ) { ?>
										<span class="error fade" style="display : block"><p><?php printf( __( "Can't write on map file.<br /><br />Check you have write permission on <strong>%s</strong>", "rt_wp_nginx_helper" ), $rt_wp_nginx_helper->functional_asset_path() . 'map.conf' ); ?></p></span>
									<?php } ?>

									<table class="form-table rtnginx-table">
										<tr>
											<th>
												Nginx Map path to include in nginx settings<br />
												<small>(recommended)</small>
											</th>
											<td>
												<?php echo $rt_wp_nginx_helper->functional_asset_path() . 'map.conf'; ?>
											</td>
										</tr>
										<tr>
											<th>
												Or,<br />
												Text to manually copy and paste in nginx settings<br />
												<small>(if your network is small and new sites are not added frequently)</small>
											</th>
											<td>
												<pre id="map"><?php echo $rt_wp_nginx_helper->get_map() ?></pre>
											</td>
										</tr>
									</table>
									<?php } ?>

								<?php
								if ( $rt_wp_nginx_helper->options[ 'enable_log' ] != false ) {
									?>
									<h3>Logging</h3>

									<?php
									$path = $rt_wp_nginx_helper->functional_asset_path();
									if (!is_dir($path)){
										mkdir($path);
									}
									if (!file_exists($path . 'nginx.log')) {
										$log = fopen($path . 'nginx.log', 'w');
										fclose($log);
									}
									if ( is_writable( $path . 'nginx.log' ) ) {
										$rt_wp_nginx_purger->log( "+++++++++" );
										$rt_wp_nginx_purger->log( "+Log Test" );
										$rt_wp_nginx_purger->log( "+++++++++" );
									}
									if ( ! is_writable( $path . 'nginx.log' ) ) {
									?>
										<span class="error fade" style="display : block"><p><?php printf( __( "Can't write on log file.<br /><br />Check you have write permission on <strong>%s</strong>", "rt_wp_nginx_helper" ), $rt_wp_nginx_helper->functional_asset_path() . 'nginx.log' ); ?></p></span>
									<?php } ?>

									<table class="form-table rtnginx-table">
										<tbody>
											<tr>
												<th><label for="rt_wp_nginx_helper_logs_path"><?php _e( 'Logs path', 'rt_wp_nginx_helper' ); ?></label></th>
												<td><?php echo $rt_wp_nginx_helper->functional_asset_path(); ?>nginx.log</td>
											</tr>
											<tr>
												<th><label for="rt_wp_nginx_helper_logs_link"><?php _e( 'View Log', 'rt_wp_nginx_helper' ); ?></label></th>
												<td><a target="_blank" href="<?php echo $rt_wp_nginx_helper->functional_asset_url(); ?>nginx.log">Log</a></td>
											</tr>

											<tr>
												<th><label for="rt_wp_nginx_helper_log_level"><?php _e( 'Log level', 'rt_wp_nginx_helper' ); ?></label></th>
												<td>
													<select name="log_level">
														<option value="NONE"<?php selected( $rt_wp_nginx_helper->options[ 'log_level' ], 'NONE' ); ?>><?php _e( 'None', 'rt_wp_nginx_helper' ); ?></option>
														<option value="INFO"<?php selected( $rt_wp_nginx_helper->options[ 'log_level' ], 'INFO' ); ?>><?php _e( 'Info', 'rt_wp_nginx_helper' ); ?></option>
														<option value="WARNING"<?php selected( $rt_wp_nginx_helper->options[ 'log_level' ], 'WARNING' ); ?>><?php _e( 'Warning', 'rt_wp_nginx_helper' ); ?></option>
														<option value="ERROR"<?php selected( $rt_wp_nginx_helper->options[ 'log_level' ], 'ERROR' ); ?>><?php _e( 'Error', 'rt_wp_nginx_helper' ); ?></option>
													</select>
												</td>
											</tr>

											<tr>
												<th><label for="log_filesize"><?php _e( 'Max log file size', 'rt_wp_nginx_helper' ); ?></label></th>
												<td>
													<input id="log_filesize" class="small-text" type="text" name="log_filesize" value="<?php echo $rt_wp_nginx_helper->options[ 'log_filesize' ] ?>" /> Mb
													<?php if ( $error_log_filesize ) { ?>
														<span class="error fade" style="display : block"><p><strong><?php echo $error_log_filesize; ?></strong></p></span>
													<?php } ?>
												</td>
											</tr>
										</tbody>
									</table>

									<br />
								<?php } ?>

								<p class="submit">
									<input type="submit" name="smart_http_expire_save" class="button-primary" value="Save" />
								</p>
							</form>

						</div>
						<div id="rtads" class="metabox-holder align_left">
							<?php $this->default_admin_sidebar(); ?>
						</div>
					</div>
					<?php
					break;
			}
		}

		function add_toolbar_purge_item( $admin_bar ) {
			$purge_url = add_query_arg( array( 'nginx_helper_action' => 'purge', 'nginx_helper_urls' => 'all' ) );
			$nonced_url = wp_nonce_url( $purge_url, 'nginx_helper-purge_all' );
			$admin_bar->add_menu( array(
					'id'    => 'nginx-helper-purge-all',
					'title' => __( 'Purge Cache', 'rt-nginx' ),
					'href'  => $nonced_url,
					'meta'  => array(
						'title' => __( 'Purge Cache', 'rt-nginx' ),
					),
				)
			);
		}

		function default_admin_sidebar() {
			?>
			<div class="postbox" id="support">
				<div title="<?php _e( 'Click to toggle', 'bp-media' ); ?>" class="handlediv"><br /></div>
				<h3 class="hndle"><span><?php _e( 'Need Help?', 'bp-media' ); ?></span></h3>
				<div class="inside"><p><?php printf( __( ' Please use our <a href="%s">free support forum</a>.<br/><span class="bpm-aligncenter">OR</span><br/>
                    <a href="%s">Hire us!</a> for wordpress on nginx solutions ', 'rt-nginx' ), 'http://rtcamp.com/support/forum/wordpress-nginx/', 'http://rtcamp.com/wordpress-nginx/' ); ?>.</p></div>
			</div>
			<div class="postbox" id="social">
				<div title="<?php _e( 'Click to toggle', 'bp-media' ); ?>" class="handlediv"><br /></div>
				<h3 class="hndle"><span><?php _e( 'Getting Social is Good', 'bp-media' ); ?></span></h3>
				<div class="inside" style="text-align:center;">
					<a href="<?php printf( '%s', 'http://www.facebook.com/rtCamp.solutions/' ); ?>" target="_blank" title="<?php _e( 'Become a fan on Facebook', 'bp-media' ); ?>" class="rt-nginx-facebook rt-nginx-social"><?php _e( 'Facebook', 'bp-media' ); ?></a>
					<a href="<?php printf( '%s', 'https://twitter.com/rtcamp/' ); ?>" target="_blank" title="<?php _e( 'Follow us on Twitter', 'bp-media' ); ?>" class="rt-nginx-twitter rt-nginx-social"><?php _e( 'Twitter', 'bp-media' ); ?></a>
					<a href="<?php printf( '%s', 'http://feeds.feedburner.com/rtcamp/' ); ?>" target="_blank" title="<?php _e( 'Subscribe to our feeds', 'bp-media' ); ?>" class="rt-nginx-rss rt-nginx-social"><?php _e( 'RSS Feed', 'bp-media' ); ?></a>
				</div>
			</div>

			<div class="postbox" id="latest_news">
				<div title="<?php _e( 'Click to toggle', 'bp-media' ); ?>" class="handlediv"><br /></div>
				<h3 class="hndle"><span><?php _e( 'Latest News', 'bp-media' ); ?></span></h3>
				<div class="inside"><img src ="<?php echo admin_url(); ?>/images/wpspin_light.gif" /> Loading...</div>
			</div><?php
		}

		function load_styles() {
			wp_enqueue_style( 'rt-nginx-admin-css', plugins_url( 'admin/assets/style.css', dirname( __FILE__ ) ) );
		}

		function load_scripts() {
			$admin_js = trailingslashit( site_url() ) . '?get_feeds=1';
			wp_enqueue_script( 'nginx-js', plugins_url( 'admin/assets/nginx.js', dirname( __FILE__ ) ) );
			wp_localize_script( 'nginx-js', 'news_url', $admin_js );
		}

	}

}
?>
