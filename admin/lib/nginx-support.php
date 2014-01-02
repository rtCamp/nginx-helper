<?php

namespace rtCamp\WP\Nginx {
    function nginx_support_options_page() { ?>
        <form id="support" action="" method="post" class="clearfix">
            <div class="postbox">
                <h3 class="hndle">
                    <span><?php _e( 'Support Forums', 'nginx-helper' ); ?></span>
                </h3>
                <div class="inside">
                    <table class="form-table">
                        <tr valign="top">
                            <th><?php _e( 'Free Support', 'nginx-helper' ); ?></th>
                            <td>
                                <a href="https://rtcamp.com/support/forum/wordpress-nginx/" title="<?php _e( 'Free Support Forum', 'nginx-helper' ); ?>" target="_blank"><?php _e( 'Link to forum', 'nginx-helper' ); ?></a>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th><?php _e( 'Premium Support', 'nginx-helper' ); ?></th>
                            <td>
                                <a href="https://rtcamp.com/wordpress-nginx/pricing/" title="<?php _e( 'Premium Support Forum', 'nginx-helper' ); ?>" target="_blank"><?php _e( 'Link to forum', 'nginx-helper' ); ?></a>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </form><?php
    }
}