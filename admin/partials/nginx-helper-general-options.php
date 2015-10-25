<?php
/**
 * Display general options of the plugin.
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://example.com
 * @since      2.0.0
 *
 * @package    nginx-helper
 * @subpackage nginx-helper/admin/partials
 */
global $nginx_helper_admin;

$error_log_filesize = false;

if( isset( $_POST['smart_http_expire_save'] ) ) {
    unset( $_POST['smart_http_expire_save'] );
    unset( $_POST['is_submit'] );
    
    $nginx_settings = wp_parse_args(
        $_POST,
        $nginx_helper_admin->nginx_helper_default_settings()
    );
    
    if ( ( ! is_numeric( $nginx_settings['log_filesize'] ) ) || ( empty( $nginx_settings['log_filesize'] ) ) ) {
        $error_log_filesize = __( 'Log file size must be a number.', 'nginx-helper' );
        unset( $nginx_settings['log_filesize'] );
    }
    
    if ( $nginx_settings['enable_map'] ) {
        $nginx_helper_admin->update_map();
    }
    
    update_site_option( 'rt_wp_nginx_helper_options', $nginx_settings );
    
    echo '<div class="updated"><p>' . __( 'Settings saved.', 'nginx-helper' ) . '</p></div>';
}

$nginx_helper_settings = $nginx_helper_admin->nginx_helper_settings();
$log_path = $nginx_helper_admin->get_log_path();
$log_url = $nginx_helper_admin->get_log_url();

/**
 * Get setting url for single multiple with subdomain OR multiple with subdirectory site.
 */
$nginx_setting_link = '#';
if ( is_multisite() ) {
    if ( SUBDOMAIN_INSTALL == false ) {
        $nginx_setting_link = 'https://rtcamp.com/wordpress-nginx/tutorials/multisite/subdirectories/fastcgi-cache-with-purging/';
    } else {
        $nginx_setting_link = 'https://rtcamp.com/wordpress-nginx/tutorials/multisite/subdomains/fastcgi-cache-with-purging/';
    }
} else {
    $nginx_setting_link = 'https://rtcamp.com/wordpress-nginx/tutorials/single-site/fastcgi-cache-with-purging/';
}
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<form id="post_form" method="post" action="#" name="smart_http_expire_form" class="clearfix">
    <div class="postbox">
        <h3 class="hndle">
            <span><?php _e('Purging Options', 'nginx-helper'); ?></span>
        </h3>
        <div class="inside">
            <table class="form-table">
                <tr valign="top">
                    <td>
                        <input type="checkbox" value="1" id="enable_purge" name="enable_purge" <?php checked( $nginx_helper_settings['enable_purge'], 1 ); ?> />
                        <label for="enable_purge"><?php _e('Enable Purge', 'nginx-helper'); ?></label>
                    </td>
                </tr>
            </table>
        </div> <!-- End of .inside -->
    </div>
    
    <?php if ( ! ( ! is_network_admin() && is_multisite() ) ) { ?>
        <div class="postbox enable_purge"<?php echo ( $nginx_helper_settings['enable_purge'] == false ) ? ' style="display: none;"' : ''; ?>>
            <h3 class="hndle">
                <span><?php _e('Caching Method', 'nginx-helper'); ?></span>
            </h3>
            <div class="inside">
                <input type="hidden" name="is_submit" value="1" />
                <table class="form-table">
                    <tr valign="top">
                        <td>
                            <input type="radio" value="enable_fastcgi" id="cache_method_fastcgi" name="cache_method" <?php checked( $nginx_helper_settings['cache_method'], "enable_fastcgi" ); ?> />
                            <label for="cache_method_fastcgi">
                                <?php 
                                printf(
                                    __('nginx Fastcgi cache (<a target="_blank" href="%s" title="External settings for nginx">requires external settings for nginx</a>)', 'nginx-helper'), 
                                    $nginx_setting_link
                                ); 
                                ?>
                            </label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <td>
                            <input type="radio" value="enable_redis" id="cache_method_redis" name="cache_method" <?php checked( $nginx_helper_settings['cache_method'], "enable_redis" ); ?> />
                            <label for="cache_method_redis">
                                <?php printf( __( 'Redis cache', 'nginx-helper' ) ); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div> <!-- End of .inside -->
        </div>
        <div class="enable_purge">
            <div class="postbox cache_method_fastcgi"<?php echo ( $nginx_helper_settings['enable_purge'] == true && $nginx_helper_settings['cache_method'] == "enable_fastcgi" ) ? '' : ' style="display: none;"'; ?>>
                <h3 class="hndle">
                    <span><?php _e( 'Purge Method', 'nginx-helper' ); ?></span>
                </h3>
                <div class="inside">
                    <table class="form-table rtnginx-table">
                        <tr valign="top">
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text">
                                        <span>
                                            &nbsp;
                                            <?php _e( 'when a post/page/custom post is published.', 'nginx-helper' ); ?>
                                        </span>
                                    </legend>
                                    <label for="purge_method_get_request">
                                        <input type="radio" value="get_request" id="purge_method_get_request" name="purge_method" <?php checked( $nginx_helper_settings['purge_method'], 'get_request' ); ?>>
                                        &nbsp;
                                        <?php 
                                            _e( 'Using a GET request to <strong>PURGE/url</strong> (Default option)', 'nginx-helper' );
                                        ?>
                                        <br />
                                        <small>
                                            <?php 
                                                _e( 'Uses the <strong><a href="https://github.com/FRiCKLE/ngx_cache_purge">ngx_cache_purge</a></strong> module. ', 'nginx-helper' ); 
                                            ?>
                                        </small>
                                    </label>
                                    <br />
                                    <label for="purge_method_unlink_files">
                                        <input type="radio" value="unlink_files" id="purge_method_unlink_files" name="purge_method" <?php checked( $nginx_helper_settings['purge_method'], 'unlink_files' ); ?>>
                                        &nbsp;
                                        <?php 
                                            _e( 'Delete local server cache files', 'nginx-helper' ); 
                                        ?>
                                        <br />
                                        <small>
                                            <?php 
                                                _e( 'Checks for matching cache file in <strong>RT_WP_NGINX_HELPER_CACHE_PATH</strong>. Does not require any other modules. Requires that the cache be stored on the same server as WordPress. You must also be using the default nginx cache options (levels=1:2) and (fastcgi_cache_key "$scheme$request_method$host$request_uri"). ', 'nginx-helper' ); 
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
            <div class="postbox cache_method_redis"<?php echo ( $nginx_helper_settings['enable_purge'] == true && $nginx_helper_settings['cache_method'] == "enable_redis" ) ? '' : ' style="display: none;"'; ?>>
                <h3 class="hndle">
                    <span><?php _e('Redis Settings', 'nginx-helper'); ?></span>
                </h3>
                <div class="inside">
                    <table class="form-table rtnginx-table">
                        <tr>
                            <th><label for="redis_hostname"><?php _e('Hostname', 'nginx-helper'); ?></label></th>
                            <td>
                                <input id="redis_hostname" class="medium-text" type="text" name="redis_hostname" value="<?php echo $nginx_helper_settings['redis_hostname']; ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th><label for="redis_port"><?php _e('Port', 'nginx-helper'); ?></label></th>
                            <td>
                                <input id="redis_port" class="medium-text" type="text" name="redis_port" value="<?php echo $nginx_helper_settings['redis_port']; ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th><label for="redis_prefix"><?php _e('Prefix', 'nginx-helper'); ?></label></th>
                            <td>
                                <input id="redis_prefix" class="medium-text" type="text" name="redis_prefix" value="<?php echo $nginx_helper_settings['redis_prefix']; ?>" />
                            </td>
                        </tr>
                    </table>
                </div> <!-- End of .inside -->
            </div>
        </div>
        <div class="postbox enable_purge"<?php echo ( $nginx_helper_settings['enable_purge'] == false ) ? ' style="display: none;"' : ''; ?>>
            <h3 class="hndle">
                <span><?php _e( 'Purging Conditions', 'nginx-helper' ); ?></span>
            </h3>
            <div class="inside">
                <table class="form-table rtnginx-table">
                    <tr valign="top">
                        <th scope="row"><h4><?php _e( 'Purge Homepage:', 'nginx-helper' ); ?></h4></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span>
                                        &nbsp;
                                        <?php 
                                            _e( 'when a post/page/custom post is modified or added.', 'nginx-helper' ); 
                                        ?>
                                    </span>
                                </legend>
                                <label for="purge_homepage_on_edit">
                                    <input type="checkbox" value="1" id="purge_homepage_on_edit" name="purge_homepage_on_edit" <?php checked( $nginx_helper_settings['purge_homepage_on_edit'], 1 ); ?> />
                                    &nbsp;
                                    <?php 
                                        _e( 'when a <strong>post</strong> (or page/custom post) is <strong>modified</strong> or <strong>added</strong>.', 'nginx-helper' ); 
                                    ?>
                                </label>
                                <br />
                            </fieldset>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span>
                                        &nbsp;
                                        <?php 
                                            _e( 'when an existing post/page/custom post is modified.', 'nginx-helper' ); 
                                        ?>
                                    </span>
                                </legend>
                                <label for="purge_homepage_on_del">
                                    <input type="checkbox" value="1" id="purge_homepage_on_del" name="purge_homepage_on_del" <?php checked( $nginx_helper_settings['purge_homepage_on_del'], 1 ); ?> />
                                    &nbsp;
                                    <?php 
                                        _e( 'when a <strong>published post</strong> (or page/custom post) is <strong>trashed</strong>.', 'nginx-helper' ); 
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
                                <?php _e( 'Purge Post/Page/Custom Post Type:', 'nginx-helper' ); ?>
                            </h4>
                        </th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span>&nbsp;
                                        <?php 
                                            _e( 'when a post/page/custom post is published.', 'nginx-helper'); 
                                        ?>
                                    </span>
                                </legend>
                                <label for="purge_page_on_mod">
                                    <input type="checkbox" value="1" id="purge_page_on_mod" name="purge_page_on_mod" <?php checked( $nginx_helper_settings['purge_page_on_mod'], 1 ); ?>>
                                    &nbsp;
                                    <?php 
                                        _e( 'when a <strong>post</strong> is <strong>published</strong>.', 'nginx-helper' ); 
                                    ?>
                                </label>
                                <br />
                            </fieldset>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span>
                                        &nbsp;
                                        <?php 
                                            _e( 'when a comment is approved/published.', 'nginx-helper' ); 
                                        ?>
                                    </span>
                                </legend>
                                <label for="purge_page_on_new_comment">
                                    <input type="checkbox" value="1" id="purge_page_on_new_comment" name="purge_page_on_new_comment" <?php checked( $nginx_helper_settings['purge_page_on_new_comment'], 1 ); ?>>
                                    &nbsp;
                                    <?php 
                                        _e( 'when a <strong>comment</strong> is <strong>approved/published</strong>.', 'nginx-helper' ); 
                                    ?>
                                </label>
                                <br />
                            </fieldset>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span>
                                        &nbsp;
                                        <?php 
                                            _e('when a comment is unapproved/deleted.', 'nginx-helper'); 
                                        ?>
                                    </span>
                                </legend>
                                <label for="purge_page_on_deleted_comment">
                                    <input type="checkbox" value="1" id="purge_page_on_deleted_comment" name="purge_page_on_deleted_comment" <?php checked( $nginx_helper_settings['purge_page_on_deleted_comment'], 1 ); ?>>
                                    &nbsp;
                                    <?php 
                                        _e( 'when a <strong>comment</strong> is <strong>unapproved/deleted</strong>.', 'nginx-helper' ); 
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
                                <?php _e( 'Purge Archives:', 'nginx-helper' ); ?>
                            </h4>
                            <small><?php _e( '(date, category, tag, author, custom taxonomies)', 'nginx-helper' ); ?></small>
                        </th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span>
                                        &nbsp;
                                        <?php 
                                            _e( 'when an post/page/custom post is modified or added.</span>', 'nginx-helper' ); 
                                        ?>
                                    </span>
                                </legend>
                                <label for="purge_archive_on_edit">
                                    <input type="checkbox" value="1" id="purge_archive_on_edit" name="purge_archive_on_edit" <?php checked( $nginx_helper_settings['purge_archive_on_edit'], 1 ); ?> />
                                    &nbsp;
                                    <?php 
                                        _e( 'when a <strong>post</strong> (or page/custom post) is <strong>modified</strong> or <strong>added</strong>.', 'nginx-helper' ); 
                                    ?>
                                </label>
                                <br />
                            </fieldset>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span>
                                        &nbsp;
                                        <?php 
                                            _e( 'when an existing post/page/custom post is trashed.</span>', 'nginx-helper' ); 
                                        ?>
                                    </span>
                                </legend>
                                <label for="purge_archive_on_del">
                                    <input type="checkbox" value="1" id="purge_archive_on_del" name="purge_archive_on_del"<?php checked( $nginx_helper_settings['purge_archive_on_del'], 1 ); ?> />
                                    &nbsp;
                                    <?php 
                                        _e( 'when a <strong>published post</strong> (or page/custom post) is <strong>trashed</strong>.', 'nginx-helper' ); 
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
                                            _e( 'when a comment is approved/published.</span>', 'nginx-helper' ); 
                                        ?>
                                    </span>
                                </legend>
                                <label for="purge_archive_on_new_comment">
                                    <input type="checkbox" value="1" id="purge_archive_on_new_comment" name="purge_archive_on_new_comment" <?php checked( $nginx_helper_settings['purge_archive_on_new_comment'], 1 ); ?> />
                                    &nbsp;
                                    <?php 
                                        _e( 'when a <strong>comment</strong> is <strong>approved/published</strong>.', 'nginx-helper' ); 
                                    ?>
                                </label>
                                <br />
                            </fieldset>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span>
                                        &nbsp;
                                        <?php 
                                            _e( 'when a comment is unapproved/deleted.</span>', 'nginx-helper' ); 
                                        ?>
                                    </span>
                                </legend>
                                <label for="purge_archive_on_deleted_comment">
                                    <input type="checkbox" value="1" id="purge_archive_on_deleted_comment" name="purge_archive_on_deleted_comment" <?php checked( $nginx_helper_settings['purge_archive_on_deleted_comment'], 1 ); ?> />
                                    &nbsp;
                                    <?php 
                                        _e( 'when a <strong>comment</strong> is <strong>unapproved/deleted</strong>.', 'nginx-helper' ); 
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
                            <h4><?php _e('Custom Purge URL:', 'nginx-helper'); ?></h4>
                        </th>
                        <td>
                            <textarea rows="5"class="rt-purge_url" id="purge_url" name="purge_url"><?php echo $nginx_helper_admin->options['purge_url']; ?></textarea>
                            <p class="description">
                                Add one URL per line. URL should not contain domain name.
                                <br>
                                Eg: To purge http://example.com/sample-page/ add <strong>/sample-page/</strong> in above textarea.
                                <br>
                                '*' will only work with redis cache server.
                            </p>
                        </td>
                    </tr>
                </table>
            </div> <!-- End of .inside -->
        </div>
        <div class="postbox">
            <h3 class="hndle">
                <span><?php _e( 'Debug Options', 'nginx-helper' ); ?></span>
            </h3>
            <div class="inside">
                <input type="hidden" name="is_submit" value="1" />
                <table class="form-table">
                <?php if ( is_network_admin() ) { ?>
                    <tr valign="top">
                        <td>
                            <input type="checkbox" value="1" id="enable_map" name="enable_map" <?php checked( $nginx_helper_settings['enable_map'], 1 ); ?> />
                            <label for="enable_map">
                                <?php _e( 'Enable Nginx Map.', 'nginx-helper' ); ?>
                            </label>
                        </td>
                    </tr>
                <?php } ?>
                    <tr valign="top">
                        <td>
                            <input type="checkbox" value="1" id="enable_log" name="enable_log"<?php checked( $nginx_helper_settings['enable_log'], 1 ); ?> />
                            <label for="enable_log">
                                <?php _e( 'Enable Logging', 'nginx-helper' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <td>
                            <input type="checkbox" value="1" id="enable_stamp" name="enable_stamp"<?php checked( $nginx_helper_settings['enable_stamp'], 1); ?> />
                            <label for="enable_stamp">
                                <?php _e( 'Enable Nginx Timestamp in HTML', 'nginx-helper' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div> <!-- End of .inside -->
        </div>
    <?php
    } // End of if ( ! ( ! is_network_admin() && is_multisite() ) )

    if ( is_network_admin() ) {
    ?>
        <div class="postbox enable_map"<?php echo ( $nginx_helper_settings['enable_map'] == false ) ? ' style="display: none;"' : ''; ?>>
            <h3 class="hndle">
                <span><?php _e( 'Nginx Map', 'nginx-helper' ); ?></span>
            </h3>
            <div class="inside">
                <?php 
                if ( ! is_writable( $log_path . 'map.conf' ) ) { ?>
                    <span class="error fade" style="display: block">
                        <p>
                            <?php 
                                printf( 
                                    __( 'Can\'t write on map file.<br /><br />Check you have write permission on <strong>%s</strong>', 'nginx-helper'), 
                                    $log_path . 'map.conf'
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
                                _e( 'Nginx Map path to include in nginx settings<br /><small>(recommended)</small>', 'nginx-helper' ); 
                            ?>
                        </th>
                        <td>
                            <pre>
                                <?php echo $log_path . 'map.conf'; ?>
                            </pre>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <?php 
                                _e( 'Or,<br />Text to manually copy and paste in nginx settings<br /><small>(if your network is small and new sites are not added frequently)</small>', 'nginx-helper' ); 
                            ?>
                        </th>
                        <td>
                            <pre id="map">
                                <?php echo $nginx_helper_admin->get_map() ?>
                            </pre>
                        </td>
                    </tr>
                </table>
            </div> <!-- End of .inside -->
        </div>
    <?php 
    } 
    ?>
    <div class="postbox enable_log"<?php echo ( $nginx_helper_settings['enable_log'] == false ) ? ' style="display: none;"' : ''; ?>>
        <h3 class="hndle">
            <span><?php _e( 'Logging Options', 'nginx-helper' ); ?></span>
        </h3>
        <div class="inside">
            <?php
                if ( ! is_dir( $log_path ) ) {
                    mkdir( $log_path );
                }
                if ( ! file_exists( $log_path . 'nginx.log' ) ) {
                    $log = fopen( $log_path . 'nginx.log', 'w' );
                    fclose( $log );
                }
                if ( ! is_writable( $log_path . 'nginx.log' ) ) {
            ?>
                <span class="error fade" style="display : block">
                    <p>
                        <?php 
                            printf(
                                __( 'Can\'t write on log file.<br /><br />Check you have write permission on <strong>%s</strong>', 'nginx-helper' ),
                                $log_path . 'nginx.log' 
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
                                <?php _e( 'Logs path', 'nginx-helper' ); ?>
                            </label>
                        </th>
                        <td>
                            <code>
                                <?php echo $log_path . 'nginx.log'; ?>
                            </code>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="rt_wp_nginx_helper_logs_link">
                                <?php _e( 'View Log', 'nginx-helper' ); ?>
                            </label>
                        </th>
                        <td>
                            <a target="_blank" href="<?php echo $log_url . 'nginx.log'; ?>"> 
                                <?php _e( 'Log', 'nginx-helper' ); ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="rt_wp_nginx_helper_log_level">
                                <?php _e( 'Log level', 'nginx-helper' ); ?>
                            </label>
                        </th>
                        <td>
                            <select name="log_level">
                                <option value="NONE" <?php selected( $nginx_helper_settings['log_level'], 'NONE' ); ?>> <?php _e( 'None', 'nginx-helper' ); ?> </option>
                                <option value="INFO" <?php selected( $nginx_helper_settings['log_level'], 'INFO' ); ?>> <?php _e( 'Info', 'nginx-helper' ); ?> </option>
                                <option value="WARNING" <?php selected( $nginx_helper_settings['log_level'], 'WARNING' ); ?>> <?php _e( 'Warning', 'nginx-helper' ); ?> </option>
                                <option value="ERROR" <?php selected( $nginx_helper_settings['log_level'], 'ERROR' ); ?>> <?php _e( 'Error', 'nginx-helper' ); ?> </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="log_filesize">
                                <?php _e( 'Max log file size', 'nginx-helper' ); ?>
                            </label>
                        </th>
                        <td>
                            <input id="log_filesize" class="small-text" type="text" name="log_filesize" value="<?php echo $nginx_helper_settings['log_filesize'] ?>" /> 
                            <?php 
                                _e( 'Mb', 'nginx-helper' );
                                if ( $error_log_filesize ) {
                            ?>
                                <p class="error fade" style="display: block;">
                                    <?php echo $error_log_filesize; ?>
                                </p>
                            <?php }
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div> <!-- End of .inside -->
    </div>
    <?php 
        submit_button( __( 'Save All Changes', 'nginx-helper' ), 'primary large', 'smart_http_expire_save', true ); 
    ?>
</form><!-- End of #post_form -->