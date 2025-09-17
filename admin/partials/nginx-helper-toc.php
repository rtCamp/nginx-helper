<div id="navigation" class="table-of-content">
    <nav>
        <ul class="nav-list toc-lis general-tab" <?php echo ( 'general' !== $current_setting_tab ) ? ' style="display: none;"' : ''; ?>>
            
            <li class="nav-active outer-toc"><a href="#purging_options">Purging Options</a></li>
            <li class="inner-toc"><a href="#enable_purge">Enable Purge</a></li>
            <li class="inner-toc"><a href="#preload_cache">Preload Cache</a></li>

            <li class="outer-toc hide-on-purge-disabled"><a href="#caching_method">Caching Method</a></li>
            <li class="inner-toc hide-on-purge-disabled"><a href="#cache_method_fastcgi">FastCGI</a></li>
            <li class="inner-toc hide-on-purge-disabled"><a href="#cache_method_redis">Redis</a></li>

            <li class="outer-toc hide-on-purge-disabled hide-fastcgi"><a href="#purge_method">Purge Method</a></li>
            <li class="inner-toc hide-on-purge-disabled hide-fastcgi"><a href="#purge_method_get_request">GET Request</a></li>
            <li class="inner-toc hide-on-purge-disabled hide-fastcgi"><a href="#purge_method_unlink_files">Unlink Files</a></li>

            <li class="outer-toc hide-on-purge-disabled hide-redis"><a href="#redis_settings">Redis Settings</a></li>
            <li class="inner-toc hide-on-purge-disabled hide-redis"><a href="#redis_hostname">Hostname</a></li>
            <li class="inner-toc hide-on-purge-disabled hide-redis"><a href="#redis_port">Port</a></li>
            <li class="inner-toc hide-on-purge-disabled hide-redis"><a href="#redis_unix_socket">Socket Path</a></li>
            <li class="inner-toc hide-on-purge-disabled hide-redis"><a href="#redis_prefix">Prefix</a></li>
            <li class="inner-toc hide-on-purge-disabled hide-redis"><a href="#redis_database">Database</a></li>
            <li class="inner-toc hide-on-purge-disabled hide-redis"><a href="#redis_username">Username</a></li>
            <li class="inner-toc hide-on-purge-disabled hide-redis"><a href="#redis_password">Password</a></li>
        </ul>

        <ul class="nav-list purging-tab toc-list" <?php echo ( 'purging' !== $current_setting_tab ) ? ' style="display: none;"' : ''; ?>>

            <li class="nav-active outer-toc"><a href="#purging_condition">Purging Conditions</a></li>
            <li class="inner-toc"><a href="#purge_homepage_on_edit">Purge Homepage</a></li>
            <li class="inner-toc"><a href="#purge_page_on_mod">Purge Post</a></li>
            <li class="inner-toc"><a href="#purge_archive_on_edit">Purge Archives</a></li>
            <li class="inner-toc"><a href="#purge_feeds">Purge Feeds</a></li>
            <li class="inner-toc"><a href="#purge_amp_urls">Purge AMP URLs</a></li>

            <li class="outer-toc"><a href="#custom_purge">Custom Purge</a></li>
            <li class="inner-toc"><a href="#purge_url">Custom Purge URL</a></li>

            <li class="outer-toc"><a href="#access_control">Access Control</a></li>
            <li class="inner-toc"><a href="#roles_with_purge_cap">Purge Access</a></li>

            <li class="outer-toc"><a href="#plugin_integration">Plugin Integrations</a></li>
            <li class="inner-toc"><a href="#enable_auto_purge">Purge on Update</a></li>
            <li class="inner-toc"><a href="#purge_woo_products">WooCommerce</a></li>
        </ul>

        <ul class="nav-list logging-tab toc-list" <?php echo ( 'logging_tools' !== $current_setting_tab ) ? ' style="display: none;"' : ''; ?>>
            
            <li class="nav-active outer-toc"><a href="#debug_options">Debug Options</a></li>
            <li class="inner-toc"><a href="#enable_log">Enable Logging</a></li>
            <li class="inner-toc"><a href="#enable_stamp">Enable Timestamp</a></li>

            <li class="outer-toc"><a href="#logging_option">Logging Options</a></li>
            <li class="inner-toc"><a href="#logs_path">Logs Path</a></li>
            <li class="inner-toc"><a href="#view_log">View Log</a></li>
            <li class="inner-toc"><a href="#select-button">Log Level</a></li>
            <li class="inner-toc"><a href="#log_filesize">File Size</a></li>
        </ul>

        <ul class="nav-list support-tab toc-list" <?php echo ( 'support' !== $current_setting_tab ) ? ' style="display: none;"' : ''; ?>>
            
            <li class="nav-active outer-toc"><a href="#support_forum">Support Forums</a></li>
            <li class="inner-toc"><a href="#free_support">Free Support</a></li>
            <li class="inner-toc"><a href="#premium_support">Premium Support</a></li>
        </ul>
    </nav>
</div>