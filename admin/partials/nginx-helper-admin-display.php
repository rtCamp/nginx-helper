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
		<?php esc_html_e( 'Server Cache Settings', 'nginx-helper' ); ?>
	</h2>
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<?php
                    include plugin_dir_path( __FILE__ ) . 'nginx-helper-general-options.php';
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
