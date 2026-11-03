<?php
/**
 * Display support options of the plugin.
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @since      2.0.0
 *
 * @package    nginx-helper
 * @subpackage nginx-helper/admin/partials
 */

?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="postbox">
	<h3 class="hndle">
		<span><?php esc_html_e( 'Support Forums', 'nginx-helper' ); ?></span>
	</h3>
	<div class="inside">
		<table class="form-table">
			<tr valign="top">
				<th>
					<?php esc_html_e( 'Free Support', 'nginx-helper' ); ?>
				</th>
				<td>
					<a href="https://community.easyengine.io/c/wordpress-nginx/" title="<?php esc_attr_e( 'Free Support Forum', 'nginx-helper' ); ?>" target="_blank">
						<?php esc_html_e( 'Link to forum', 'nginx-helper' ); ?>
					</a>
				</td>
			</tr>
			<tr valign="top">
				<th>
					<?php esc_html_e( 'Premium Support', 'nginx-helper' ); ?>
				</th>
				<td>
					<a href="https://easyengine.io/contact/" title="<?php esc_attr_e( 'Premium Support Forum', 'nginx-helper' ); ?>" target="_blank">
						<?php esc_html_e( 'Link to forum', 'nginx-helper' ); ?>
					</a>
				</td>
			</tr>
		</table>
	</div>
</div>
