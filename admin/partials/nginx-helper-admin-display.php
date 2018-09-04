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
				$current_setting_tab = ( isset( $_GET['tab'] ) && ! empty( $_GET['tab'] ) ) ? $_GET['tab'] : 'general';

				echo '<h2 class="nav-tab-wrapper">';
				foreach ( $this->settings_tabs as $setting_tab => $setting_name ) {
					$class = ( $setting_tab === $current_setting_tab ) ? ' nav-tab-active' : '';
					echo '<a class="nav-tab' . $class . '" href="?page=nginx&tab=' . esc_attr( $setting_name['menu_slug'] ) . '">' . esc_html( $setting_name['menu_title'] ) . '</a>';
				}
				echo '</h2>';

				switch ( $current_setting_tab ) {
					case 'general':
						include 'nginx-helper-general-options.php';
						break;
					case 'support':
						include 'nginx-helper-support-options.php';
						break;
				}
				?>
			</div> <!-- End of #post-body-content -->
			<div id="postbox-container-1" class="postbox-container">
				<?php
					require 'nginx-helper-sidebar-display.php';
				?>
			</div> <!-- End of #postbox-container-1 -->
		</div> <!-- End of #post-body -->
	</div> <!-- End of #poststuff -->
</div> <!-- End of .wrap .rt-nginx-wrapper -->
