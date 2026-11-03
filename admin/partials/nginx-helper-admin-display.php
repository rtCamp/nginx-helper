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
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<?php
				/* Show settinhs tabs */
				$current_tab         = ( isset( $_GET['tab'] ) ? wp_strip_all_tags( $_GET['tab'] ) : '' );
				$current_setting_tab = ( ! empty( $current_tab ) ) ? $current_tab : 'general';

				echo '<h2 class="nav-tab-wrapper">';
				foreach ( $this->settings_tabs as $setting_tab => $setting_name ) {

					$class = ( $setting_tab === $current_setting_tab ) ? ' nav-tab-active' : '';
					printf(
						'<a class="%s" href="%s">%s</a>',
						esc_attr( 'nav-tab' . $class ),
						esc_url( '?page=nginx&tab=' . $setting_name['menu_slug'] ),
						esc_html( $setting_name['menu_title'] )
					);
				}
				echo '</h2>';

				switch ( $current_setting_tab ) {

					case 'general':
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
