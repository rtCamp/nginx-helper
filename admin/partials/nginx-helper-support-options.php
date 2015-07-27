<?php
/**
 * Display support options of the plugin.
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://example.com
 * @since      2.0.0
 *
 * @package    nginx-helper
 * @subpackage nginx-helper/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="postbox">
    <h3 class="hndle">
        <span><?php _e( 'Support Forums', 'nginx-helper' ); ?></span>
    </h3>
    <div class="inside">
        <table class="form-table">
            <tr valign="top">
                <th>
                    <?php _e( 'Free Support', 'nginx-helper' ); ?>
                </th>
                <td>
                    <a href="https://rtcamp.com/support/forum/wordpress-nginx/" title="<?php _e( 'Free Support Forum', 'nginx-helper' ); ?>" target="_blank">
                        <?php _e( 'Link to forum', 'nginx-helper' ); ?>
                    </a>
                </td>
            </tr>
            <tr valign="top">
                <th>
                    <?php _e( 'Premium Support', 'nginx-helper' ); ?>
                </th>
                <td>
                    <a href="https://rtcamp.com/wordpress-nginx/pricing/" title="<?php _e( 'Premium Support Forum', 'nginx-helper' ); ?>" target="_blank">
                        <?php _e( 'Link to forum', 'nginx-helper' ); ?>
                    </a>
                </td>
            </tr>
        </table>
    </div>
</div>