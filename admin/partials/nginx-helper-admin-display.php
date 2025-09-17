<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://example.com
 * @since      2.0.0
 *
 * @package    nginx-helper
 * @subpackage nginx-helper/admin/partials
 */

global $pagenow;
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<div class="wrap rt-nginx-wrapper">
	<h2 class="rt_option_title">
		<?php esc_html_e( 'Nginx Settings', 'nginx-helper' ); ?>
	</h2>
	<div id="poststuff">
	<?php
		/* Show settings tabs */
		$current_tab         = ( isset( $_GET['tab'] ) ? wp_strip_all_tags( $_GET['tab'] ) : '' );
		$current_setting_tab = ( ! empty( $current_tab ) ) ? $current_tab : 'general';

		global $nginx_helper_admin;
		$nginx_helper_settings = $nginx_helper_admin->nginx_helper_settings();
		$purge_enabled = ! empty( $nginx_helper_settings['enable_purge'] );

		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $this->settings_tabs as $setting_tab => $setting_name ) {

			$class = ( $setting_tab === $current_setting_tab ) ? ' nav-tab-active' : '';
			$is_restricted = in_array( $setting_tab, array( 'purging', 'logging_tools' ), true ) && ! $purge_enabled;
			$disable = ( $is_restricted ) ? ' nav-tab-disabled' : '';
			printf(
				'<a class="%s" href="%s">%s</a>',
				esc_attr( 'nav-tab' . $class . $disable ),
				esc_url( '?page=nginx&tab=' . $setting_name['menu_slug'] ),
				esc_html( $setting_name['menu_title'] )
			);
		}
		echo '</h2>';
	?>

		<div id="post-body" class="metabox-holder rt-setting-section">
			<?php include plugin_dir_path( __FILE__ ) . 'nginx-helper-toc.php'; ?>
			<div id="post-body-content">
				<?php

				switch ( $current_setting_tab ) {

					case 'general':
					case 'purging':
					case 'logging_tools':
						include plugin_dir_path( __FILE__ ) . 'nginx-helper-general-options.php';
						break;
					case 'support':
						include plugin_dir_path( __FILE__ ) . 'nginx-helper-support-options.php';
						break;

				}
				?>
			</div> <!-- End of #post-body-content -->
			<div id="postbox-container-1" class="postbox-container">
				<?php
					require plugin_dir_path( __FILE__ ) . 'nginx-helper-sidebar-display.php';
				?>
			</div> <!-- End of #postbox-container-1 -->
		</div> <!-- End of #post-body -->
	</div> <!-- End of #poststuff -->
</div> <!-- End of .wrap .rt-nginx-wrapper -->
<?php
wp_localize_script( $nginx_helper_admin->plugin_name . 'extras', 'nginxHelperVars', array( 'currentTab' => $current_setting_tab ) );
?>