<?php

namespace rtCamp\WP\Nginx {

    class Admin {

        function __construct() {
            if (is_multisite()) {
                add_action('network_admin_menu', array(&$this, 'add_network_menu'));
            } else {
                add_action('admin_menu', array(&$this, 'add_menu'));
            }
            add_action('admin_print_scripts-settings_page_nginx', array(&$this, 'load_scripts'));
            add_action('admin_print_styles-settings_page_nginx', array(&$this, 'load_styles'));
            add_action('admin_bar_menu', array(&$this, 'add_toolbar_purge_item'), 100);
        }

        function add_menu() {
            add_submenu_page('options-general.php', 'Nginx Helper', __('Nginx Helper', 'nginx-helper'), 'install_plugins', 'nginx', array(&$this, 'show_menu'));
            //add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function)
        }

        function add_network_menu() {
            add_submenu_page('settings.php', 'Nginx Helper', __('Nginx Helper', 'nginx-helper'), 'install_plugins', 'nginx', array(&$this, 'show_menu'));
        }

        // load the script for the defined page and load only this code
        function show_menu() {

            global $rt_wp_nginx_helper, $rt_wp_nginx_purger;
            ;

            $required_page = $_GET['page'];
            switch ($required_page) {

                default :

                    $update = 0;
                    $error_time = false;
                    $error_log_filesize = false;
                    $rt_wp_nginx_helper->options['enable_purge'] = (isset($_POST['enable_purge']) and ($_POST['enable_purge'] == 1) ) ? 1 : 0;
                    $rt_wp_nginx_helper->options['enable_map'] = (isset($_POST['enable_map']) and ($_POST['enable_map'] == 1) ) ? 1 : 0;
                    $rt_wp_nginx_helper->options['enable_log'] = (isset($_POST['enable_log']) and ($_POST['enable_log'] == 1) ) ? 1 : 0;
                    $rt_wp_nginx_helper->options['enable_stamp'] = (isset($_POST['enable_stamp']) and ($_POST['enable_stamp'] == 1) ) ? 1 : 0;

                    if (isset($_POST['is_submit']) && ($_POST['is_submit'] == 1)) {
                        if (!(!is_network_admin() && is_multisite())) {
                            if ($rt_wp_nginx_helper->options['enable_log']) {
                                if (isset($_POST['log_level']) && !empty($_POST['log_level']) && $_POST['log_level'] != '') {
                                    $rt_wp_nginx_helper->options['log_level'] = $_POST['log_level'];
                                } else {
                                    $rt_wp_nginx_helper->options['log_level'] = 'INFO';
                                }
                                if (isset($_POST['log_filesize']) && !empty($_POST['log_filesize']) && $_POST['log_filesize'] != '') {
                                    if ((!is_numeric($_POST['log_filesize'])) || (empty($_POST['log_filesize']))) {
                                        $error_log_filesize = "Log file size must be a number";
                                    } else {
                                        $rt_wp_nginx_helper->options['log_filesize'] = $_POST['log_filesize'];
                                    }
                                } else {
                                    $rt_wp_nginx_helper->options['log_filesize'] = 5;
                                }
                            }
                            if ($rt_wp_nginx_helper->options['enable_map']) {
                                $rt_wp_nginx_helper->update_map();
                            }
                        }
                        if (isset($_POST['enable_purge'])) {

                            $rt_wp_nginx_helper->options['purge_homepage_on_edit'] = (isset($_POST['purge_homepage_on_edit']) and ($_POST['purge_homepage_on_edit'] == 1) ) ? 1 : 0;
                            $rt_wp_nginx_helper->options['purge_homepage_on_del'] = (isset($_POST['purge_homepage_on_del']) and ($_POST['purge_homepage_on_del'] == 1) ) ? 1 : 0;

                            $rt_wp_nginx_helper->options['purge_archive_on_edit'] = (isset($_POST['purge_archive_on_edit']) and ($_POST['purge_archive_on_edit'] == 1) ) ? 1 : 0;
                            $rt_wp_nginx_helper->options['purge_archive_on_del'] = (isset($_POST['purge_archive_on_del']) and ($_POST['purge_archive_on_del'] == 1) ) ? 1 : 0;

                            $rt_wp_nginx_helper->options['purge_archive_on_new_comment'] = (isset($_POST['purge_archive_on_new_comment']) and ($_POST['purge_archive_on_new_comment'] == 1) ) ? 1 : 0;
                            $rt_wp_nginx_helper->options['purge_archive_on_deleted_comment'] = (isset($_POST['purge_archive_on_deleted_comment']) and ($_POST['purge_archive_on_deleted_comment'] == 1) ) ? 1 : 0;

                            $rt_wp_nginx_helper->options['purge_page_on_mod'] = (isset($_POST['purge_page_on_mod']) and ($_POST['purge_page_on_mod'] == 1) ) ? 1 : 0;
                            $rt_wp_nginx_helper->options['purge_page_on_new_comment'] = (isset($_POST['purge_page_on_new_comment']) and ($_POST['purge_page_on_new_comment'] == 1) ) ? 1 : 0;
                            $rt_wp_nginx_helper->options['purge_page_on_deleted_comment'] = (isset($_POST['purge_page_on_deleted_comment']) and ($_POST['purge_page_on_deleted_comment'] == 1) ) ? 1 : 0;
                        }
                        update_site_option("rt_wp_nginx_helper_options", $rt_wp_nginx_helper->options);


                        $update = 1;
                    }
                    $rt_wp_nginx_helper->options = get_site_option("rt_wp_nginx_helper_options");
                    ?>

                    <div class="wrap rt-nginx-wrapper">
                        <h2><?php _e('Nginx Settings', 'nginx-helper') ?></h2>
                        <div id="poststuff">
                            <div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
                                <div id="post-body-content" class="postbox">
                                    <h3 class="hndle">
                                        <span><?php _e('Plugin Options', 'nginx-helper'); ?></span>
                                    </h3>
                                    <form id="purgeall" action="" method="post" class="clearfix">
                                        <div class="inside">
                                            <?php $purge_url = add_query_arg(array('nginx_helper_action' => 'purge', 'nginx_helper_urls' => 'all')); ?>
                                            <?php $nonced_url = wp_nonce_url($purge_url, 'nginx_helper-purge_all'); ?>
                                            <a href="<?php echo $nonced_url; ?>" class="button-primary"><?php _e('Purge Cache', 'nginx-helper'); ?></a>
                                        </div>
                                    </form>
                                    <form id="post_form" method="post" action="#" name="smart_http_expire_form" class="clearfix">
                                            <?php if ( !( !is_network_admin() && is_multisite() ) ) { ?>
                                        <div class="inside">
                                                <input type="hidden" name="is_submit" value="1" />
                                                <table class="form-table">
                                                    <tr valign="top">
                                                        <td>
                                                            <input type="checkbox" value="1" id="enable_purge" name="enable_purge" <?php checked($rt_wp_nginx_helper->options['enable_purge'], 1); ?> />
                                                            <label for="enable_purge"><?php _e('Enable Cache Purge (requires external settings for nginx)', 'nginx-helper'); ?></label>
                                                        </td>
                                                    </tr>
                                                    <?php if (is_network_admin()) { ?>
                                                        <tr valign="top">
                                                            <td>
                                                                <input type="checkbox" value="1" id="enable_map" name="enable_map"<?php checked($rt_wp_nginx_helper->options['enable_map'], 1); ?> />
                                                                <label for="enable_map"><?php _e('Enable Nginx Map.', 'nginx-helper'); ?></label>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                    <tr valign="top">
                                                        <td>
                                                            <input type="checkbox" value="1" id="enable_log" name="enable_log"<?php checked($rt_wp_nginx_helper->options['enable_log'], 1); ?> />
                                                            <label for="enable_log"><?php _e('Enable Logging', 'nginx-helper'); ?></label>
                                                        </td>
                                                    </tr>
                                                    <tr valign="top">
                                                        <td>
                                                            <input type="checkbox" value="1" id="enable_stamp" name="enable_stamp"<?php checked($rt_wp_nginx_helper->options['enable_stamp'], 1); ?> />
                                                            <label for="enable_stamp"><?php _e('Enable Nginx Timestamp in HTML', 'nginx-helper'); ?></label>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div> <!-- End of .inside -->
                                            <?php
                                            $displayvar = '';
                                            if ($rt_wp_nginx_helper->options['enable_purge'] == false) {
                                                $displayvar = ' style="display:none"';
                                            }
                                            ?>
                                            <h3 class="hndle"<?php echo $displayvar; ?>>
                                                <span><?php _e('Purging Options', 'nginx-helper'); ?></span>
                                            </h3>
                                            <div class="inside">

                                                <table class="form-table rtnginx-table"<?php echo $displayvar; ?>>
                                                    <tr valign="top">
                                                        <th scope="row"><h4><?php _e('Purge Homepage:', 'nginx-helper'); ?></h4></th>
                                                    <td>
                                                        <fieldset>
                                                            <legend class="screen-reader-text">
                                                                <span>&nbsp;<?php _e('when a post/page/custom post is modified or added.', 'nginx-helper'); ?></span>
                                                            </legend>
                                                            <label for="purge_homepage_on_edit">
                                                                <input type="checkbox" value="1" id="purge_homepage_on_edit" name="purge_homepage_on_edit"<?php checked($rt_wp_nginx_helper->options['purge_homepage_on_edit'], 1); ?> />
                                                                &nbsp;<?php _e('when a <strong>post</strong> (or page/custom post) is <strong>modified</strong> or <strong>added</strong>.', 'nginx-helper'); ?>
                                                            </label><br />
                                                        </fieldset>
                                                        <fieldset>
                                                            <legend class="screen-reader-text">
                                                                <span>&nbsp;<?php _e('when an existing post/page/custom post is modified.', 'nginx-helper'); ?></span>
                                                            </legend>
                                                            <label for="purge_homepage_on_del">
                                                                <input type="checkbox" value="1" id="purge_homepage_on_del" name="purge_homepage_on_del"<?php checked($rt_wp_nginx_helper->options['purge_homepage_on_del'], 1); ?> />
                                                                &nbsp;<?php _e('when a <strong>published post</strong> (or page/custom post) is <strong>trashed</strong>.', 'nginx-helper'); ?></label><br />
                                                        </fieldset>
                                                    </td>
                                                    </tr>
                                                </table>
                                                <table class="form-table rtnginx-table"<?php echo $displayvar; ?>>
                                                    <tr valign="top">
                                                        <th scope="row">
                                                    <h4><?php _e('Purge Post/Page/Custom Post Type:', 'nginx-helper'); ?></h4>
                                                    </th>
                                                    <td>
                                                        <fieldset>
                                                            <legend class="screen-reader-text">
                                                                <span>&nbsp;<?php _e('when a post/page/custom post is published.', 'nginx-helper'); ?></span>
                                                            </legend>
                                                            <label for="purge_page_on_mod">
                                                                <input type="checkbox" value="1" id="purge_page_on_mod" name="purge_page_on_mod"<?php checked($rt_wp_nginx_helper->options['purge_page_on_mod'], 1); ?>>
                                                                &nbsp;<?php _e('when a <strong>post</strong> is <strong>published</strong>.', 'nginx-helper'); ?>
                                                            </label><br />
                                                        </fieldset>
                                                        <fieldset>
                                                            <legend class="screen-reader-text">
                                                                <span>&nbsp;<?php _e('when a comment is approved/published.', 'nginx-helper'); ?></span>
                                                            </legend>
                                                            <label for="purge_page_on_new_comment">
                                                                <input type="checkbox" value="1" id="purge_page_on_new_comment" name="purge_page_on_new_comment"<?php checked($rt_wp_nginx_helper->options['purge_page_on_new_comment'], 1); ?>>
                                                                &nbsp;<?php _e('when a <strong>comment</strong> is <strong>approved/published</strong>.', 'nginx-helper'); ?>
                                                            </label><br />
                                                        </fieldset>
                                                        <fieldset>
                                                            <legend class="screen-reader-text">
                                                                <span>&nbsp;<?php _e('when a comment is unapproved/deleted.', 'nginx-helper'); ?></span>
                                                            </legend>
                                                            <label for="purge_page_on_deleted_comment">
                                                                <input type="checkbox" value="1" id="purge_page_on_deleted_comment" name="purge_page_on_deleted_comment"<?php checked($rt_wp_nginx_helper->options['purge_page_on_deleted_comment'], 1); ?>>
                                                                &nbsp;<?php _e('when a <strong>comment</strong> is <strong>unapproved/deleted</strong>.', 'nginx-helper'); ?>
                                                            </label><br />
                                                        </fieldset>
                                                    </td>
                                                    </tr>
                                                </table>
                                                <table class="form-table rtnginx-table"<?php echo $displayvar; ?>>
                                                    <tr valign="top">
                                                        <th scope="row">
                                                    <h4><?php _e('Purge Archives:', 'nginx-helper'); ?></h4>
                                                    <small><?php _e('(date, category, tag, author, custom taxonomies)', 'nginx-helper'); ?></small>
                                                    </th>
                                                    <td>
                                                        <fieldset>
                                                            <legend class="screen-reader-text">
                                                                <span>&nbsp;<?php _e('when an post/page/custom post is modified or added.</span>', 'nginx-helper'); ?>
                                                            </legend>
                                                            <label for="purge_archive_on_edit">
                                                                <input type="checkbox" value="1" id="purge_archive_on_edit" name="purge_archive_on_edit"<?php checked($rt_wp_nginx_helper->options['purge_archive_on_edit'], 1); ?> />
                                                                &nbsp;<?php _e('when a <strong>post</strong> (or page/custom post) is <strong>modified</strong> or <strong>added</strong>.', 'nginx-helper'); ?>
                                                            </label><br />
                                                        </fieldset>
                                                        <fieldset>
                                                            <legend class="screen-reader-text">
                                                                <span>&nbsp;<?php _e('when an existing post/page/custom post is trashed.</span>', 'nginx-helper'); ?>
                                                            </legend>
                                                            <label for="purge_archive_on_del">
                                                                <input type="checkbox" value="1" id="purge_archive_on_del" name="purge_archive_on_del"<?php checked($rt_wp_nginx_helper->options['purge_archive_on_del'], 1); ?> />
                                                                &nbsp;<?php _e('when a <strong>published post</strong> (or page/custom post) is <strong>trashed</strong>.', 'nginx-helper'); ?>
                                                            </label><br />
                                                        </fieldset>
                                                        <br />
                                                        <fieldset>
                                                            <legend class="screen-reader-text">
                                                                <span>&nbsp;<?php _e('when a comment is approved/published.</span>', 'nginx-helper'); ?>
                                                            </legend>
                                                            <label for="purge_archive_on_new_comment">
                                                                <input type="checkbox" value="1" id="purge_archive_on_new_comment" name="purge_archive_on_new_comment"<?php checked($rt_wp_nginx_helper->options['purge_archive_on_new_comment'], 1); ?> />
                                                                &nbsp;<?php _e('when a <strong>comment</strong> is <strong>approved/published</strong>.', 'nginx-helper'); ?>
                                                            </label><br />
                                                        </fieldset>
                                                        <fieldset>
                                                            <legend class="screen-reader-text">
                                                                <span>&nbsp;<?php _e('when a comment is unapproved/deleted.</span>', 'nginx-helper'); ?>
                                                            </legend>
                                                            <label for="purge_archive_on_deleted_comment">
                                                                <input type="checkbox" value="1" id="purge_archive_on_deleted_comment" name="purge_archive_on_deleted_comment"<?php checked($rt_wp_nginx_helper->options['purge_archive_on_deleted_comment'], 1); ?> />
                                                                &nbsp;<?php _e('when a <strong>comment</strong> is <strong>unapproved/deleted</strong>.', 'nginx-helper'); ?>
                                                            </label><br />
                                                        </fieldset>

                                                    </td>
                                                    </tr>
                                                </table>
                                            </div> <!-- End of .inside --><?php
                                        } // End of if ( !( !is_network_admin() && is_multisite() ) )
                                        
                                        
                                        if (is_network_admin() && $rt_wp_nginx_helper->options['enable_map'] != false) { ?>
                                            <h3 class="hndle">
                                                <span><?php _e('Nginx Map', 'nginx-helper'); ?></span>
                                            </h3>
                                            <div class="inside">
                                                <?php if ( !is_writable( $rt_wp_nginx_helper->functional_asset_path() . 'map.conf' ) ) { ?>
                                                    <span class="error fade" style="display : block"><p><?php printf( __( 'Can\'t write on map file.<br /><br />Check you have write permission on <strong>%s</strong>', 'nginx-helper' ), $rt_wp_nginx_helper->functional_asset_path() . 'map.conf' ); ?></p></span>
                                                <?php } ?>

                                                <table class="form-table rtnginx-table">
                                                    <tr>
                                                        <th><?php _e('Nginx Map path to include in nginx settings<br /><small>(recommended)</small>', 'nginx-helper'); ?></th>
                                                        <td>
                                                            <?php echo $rt_wp_nginx_helper->functional_asset_path() . 'map.conf'; ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th><?php _e('Or,<br />Text to manually copy and paste in nginx settings<br /><small>(if your network is small and new sites are not added frequently)</small>', 'nginx-helper'); ?></th>
                                                        <td>
                                                            <pre id="map"><?php echo $rt_wp_nginx_helper->get_map() ?></pre>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div> <!-- End of .inside -->
                                        <?php } ?>

                                        <?php if ($rt_wp_nginx_helper->options['enable_log'] != false) { ?>
                                            <h3 class="hndle">
                                                <span><?php _e('Logging', 'nginx-helper'); ?></span>
                                            </h3>
                                            <div class="inside">
                                                <?php
                                                $path = $rt_wp_nginx_helper->functional_asset_path();
                                                if (!is_dir($path)) {
                                                    mkdir($path);
                                                }
                                                if (!file_exists($path . 'nginx.log')) {
                                                    $log = fopen($path . 'nginx.log', 'w');
                                                    fclose($log);
                                                }
                                                if (is_writable($path . 'nginx.log')) {
                                                    $rt_wp_nginx_purger->log("+++++++++");
                                                    $rt_wp_nginx_purger->log("+Log Test");
                                                    $rt_wp_nginx_purger->log("+++++++++");
                                                }
                                                if (!is_writable($path . 'nginx.log')) {
                                                    ?>
                                                    <span class="error fade" style="display : block"><p><?php printf(__('Can\'t write on log file.<br /><br />Check you have write permission on <strong>%s</strong>', 'nginx-helper'), $rt_wp_nginx_helper->functional_asset_path() . 'nginx.log'); ?></p></span>
                                                <?php } ?>

                                                <table class="form-table rtnginx-table">
                                                    <tbody>
                                                        <tr>
                                                            <th><label for="rt_wp_nginx_helper_logs_path"><?php _e('Logs path', 'nginx-helper'); ?></label></th>
                                                            <td><?php echo $rt_wp_nginx_helper->functional_asset_path(); ?>nginx.log</td>
                                                        </tr>
                                                        <tr>
                                                            <th><label for="rt_wp_nginx_helper_logs_link"><?php _e('View Log', 'nginx-helper'); ?></label></th>
                                                            <td><a target="_blank" href="<?php echo $rt_wp_nginx_helper->functional_asset_url(); ?>nginx.log"><?php _e('Log', 'nginx-helper'); ?></a></td>
                                                        </tr>
                                                        <tr>
                                                            <th><label for="rt_wp_nginx_helper_log_level"><?php _e('Log level', 'nginx-helper'); ?></label></th>
                                                            <td>
                                                                <select name="log_level">
                                                                    <option value="NONE"<?php selected($rt_wp_nginx_helper->options['log_level'], 'NONE'); ?>><?php _e('None', 'nginx-helper'); ?></option>
                                                                    <option value="INFO"<?php selected($rt_wp_nginx_helper->options['log_level'], 'INFO'); ?>><?php _e('Info', 'nginx-helper'); ?></option>
                                                                    <option value="WARNING"<?php selected($rt_wp_nginx_helper->options['log_level'], 'WARNING'); ?>><?php _e('Warning', 'nginx-helper'); ?></option>
                                                                    <option value="ERROR"<?php selected($rt_wp_nginx_helper->options['log_level'], 'ERROR'); ?>><?php _e('Error', 'nginx-helper'); ?></option>
                                                                </select>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th><label for="log_filesize"><?php _e('Max log file size', 'nginx-helper'); ?></label></th>
                                                            <td>
                                                                <input id="log_filesize" class="small-text" type="text" name="log_filesize" value="<?php echo $rt_wp_nginx_helper->options['log_filesize'] ?>" /> <?php _e('Mb', 'nginx-helper');
                                                if ($error_log_filesize) {
                                                    ?>
                                                                    <span class="error fade" style="display: block;">
                                                                        <p>
                                                                            <strong><?php echo $error_log_filesize; ?></strong>
                                                                        </p>
                                                                    </span><?php }
                                                ?>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div> <!-- End of .inside -->
                                        <?php } ?>
                                        <div class="inside">
                                            <p class="submit">
                                                <input type="submit" name="smart_http_expire_save" class="button-primary" value="<?php _e('Save', 'nginx-helper'); ?>" />
                                            </p>
                                        </div>
                                    </form>

                                </div>
                                <div id="postbox-container-1" class="postbox-container">
                                    <?php $this->default_admin_sidebar(); ?>
                                </div>
                            </div> <!-- End of #post-body -->
                        </div> <!-- End of #poststuff -->
                    </div> <!-- End of .wrap -->
                    <?php
                    break;
            }
        }

        function add_toolbar_purge_item($admin_bar) {
            if (!current_user_can('manage_options')) {
                return;
            }
            $purge_url = add_query_arg(array('nginx_helper_action' => 'purge', 'nginx_helper_urls' => 'all'));
            $nonced_url = wp_nonce_url($purge_url, 'nginx_helper-purge_all');
            $admin_bar->add_menu(array('id' => 'nginx-helper-purge-all', 'title' => __('Purge Cache', 'nginx-helper'), 'href' => $nonced_url, 'meta' => array('title' => __('Purge Cache', 'nginx-helper'),),));
        }

        function default_admin_sidebar() {
            ?>
            <div class="postbox" id="support">
                <div title="<?php _e('Click to toggle', 'nginx-helper'); ?>" class="handlediv"><br /></div>
                <h3 class="hndle"><span><?php _e('Need Help?', 'nginx-helper'); ?></span></h3>
                <div class="inside">
                    <p>
            <?php printf(__('Please use our <a href="%s">free support forum</a>.<span class="nh-aligncenter">OR</span><a href="%s">Hire us!</a> for wordpress on nginx solutions.', 'nginx-helper'), 'http://rtcamp.com/support/forum/wordpress-nginx/', 'http://rtcamp.com/wordpress-nginx/'); ?>
                    </p>
                </div>
            </div>
            <div class="postbox" id="social">
                <div title="<?php _e('Click to toggle', 'nginx-helper'); ?>" class="handlediv"><br /></div>
                <h3 class="hndle">
                    <span><?php _e('Getting Social is Good', 'nginx-helper'); ?></span>
                </h3>
                <div style="text-align:center;" class="inside">
                    <a class="nginx-helper-facebook" title="<?php _e('Become a fan on Facebook', 'nginx-helper'); ?>" target="_blank" href="http://www.facebook.com/rtCamp.solutions/"></a>
                    <a class="nginx-helper-twitter" title="<?php _e('Follow us on Twitter', 'nginx-helper'); ?>" target="_blank" href="https://twitter.com/rtcamp/"></a>
                    <a class="nginx-helper-rss" title="<?php _e('Subscribe to our feeds', 'nginx-helper'); ?>" target="_blank" href="http://feeds.feedburner.com/rtcamp/"></a>
                </div>
            </div>

            <div class="postbox" id="latest_news">
                <div title="<?php _e('Click to toggle', 'nginx-helper'); ?>" class="handlediv"><br /></div>
                <h3 class="hndle"><span><?php _e('Latest News', 'nginx-helper'); ?></span></h3>
                <div class="inside"><img src ="<?php echo admin_url(); ?>/images/wpspin_light.gif" /><?php _e('Loading...', 'nginx-helper'); ?></div>
            </div><?php
        }

        function load_styles() {
            wp_enqueue_style('rt-nginx-admin-icon', plugins_url('admin/assets/nginx-helper-icons/css/nginx-fontello.css', dirname(__FILE__)));
            wp_enqueue_style('rt-nginx-admin-css', plugins_url('admin/assets/style.css', dirname(__FILE__)));
        }

        function load_scripts() {
            $admin_js = trailingslashit(site_url()) . '?get_feeds=1';
            wp_enqueue_script('nginx-js', plugins_url('admin/assets/nginx.js', dirname(__FILE__)));
            wp_localize_script('nginx-js', 'news_url', $admin_js);
        }

    }

}