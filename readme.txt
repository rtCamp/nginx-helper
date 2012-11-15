=== Nginx ===

Contributors: rtcamp, rahul286, saurabhshukla, mjbrown
Tags: nginx, cache, purge, nginx map, nginx cache, maps, fastcgi, proxy, rewrite, permalinks
Requires at least: 3.0
Tested up to: 3.4.2
Stable tag: 1.6.4
License: GPLv2 or later (of-course)
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate Link: http://rtcamp.com/donate/

Helps WordPress-Nginx work together nicely using fastcgi/proxy cache purging, nginx map{}, rewrite  support for permalinks & more

== Description ==

1. Removes `index.php` from permalinks when using WordPress with nginx.
1. Adds support for nginx fastcgi_cache_purge & proxy_cache_purge directive from [module](https://github.com/FRiCKLE/ngx_cache_purge "ngx_cache_purge module"). Provides settings so you can customize purging rules.
1. Adds support for nginx `map{..}` on a WordPress-multisite network installation. Using it Nginx can serve PHP file uploads even if PHP/MySQL crashes. Please check tutorials list below for related Nginx config.

= Tutorials =

You will need to follow one ore more tutorials below to get desired functionality:

* [Nginx Map + WordPress-Multisite + Static Files Handling](http://rtcamp.com/tutorials/nginx-maps-wordpress-multisite-static-files-handling/)
* [Nginx + WordPress + fastcgi_purge_cache](http://rtcamp.com/tutorials/wordpress-nginx-fastcgi-cache-purge-conditional/)
* [Nginx + WordPress-Multisite (Subdirectories) + fastcgi_purge_cache](http://rtcamp.com/tutorials/wordpress-multisite-subdirectories-nginx-fastcgi-cache-purge/)
* [Nginx + WordPress-Multisite (Subdomains/domain-mapping) + fastcgi_purge_cache](http://rtcamp.com/tutorials/wordpress-multisite-subdomains-domain-mapping-nginx-fastcgi-cache-purge/)
* [Other WordPress-Nginx Tutorials](http://rtcamp.com/wordpress-nginx/tutorials/)


== Installation ==

Automatic Installation

1. Log in to your WordPress admin panel, navigate to the Plugins menu and click Add New.
1. In the search field type “Nginx Helper” and click Search Plugins. From the search results, pick Nginx Helper and click Install Now. Wordpress will ask you to confirm to complete the installation.

Manual Installation

1. Extract the zip file.
1. Upload them to `/wp-content/plugins/` directory on your WordPress installation.
1. Then activate the Plugin from Plugins page.

For proper configuration, check **tutorial list** of [Description tab](http://wordpress.org/extend/plugins/nginx-helper/)

== Frequently Asked Questions ==

= FAQ - Installation/Comptability =

**Q. Will this work out of the box?**

No. You need to make some changes at the Nginx end. Please check **tutorial list** of [Description tab](http://wordpress.org/extend/plugins/nginx-helper/)

= FAQ - Nginx Map =

**Q. My multisite already uses `WPMU_ACCEL_REDIRECT`. Do I still need Nginx Map?**

Definitely. `WPMU_ACCEL_REDIRECT` reduces the load on PHP, but it still ask WordPress i.e. PHP/MySQL to do some work for static files e.g. images in your post. Nginx map lets nginx handle files on its own bypassing wordpress which gives you much better performance without using a CDN.

= FAQ - Nginx Fastcgi Cache Purge =

**Q. Does it work for custom posts and taxonomies?**
Yes. It handles all post-types same way.

**Q. How do I know my Nginx config is correct for fastcgi purging?**

Manually purging any page from the cache, by following instructions in the previous answer.

Version 1.3.4 onwards, Nginx Helper adds a comment at the end of the html source ('view source' in your favourite browser):
&lt;!--Cached using Nginx-Helper on 2012-10-08 07:01:45. It took 42 queries executed in 0.280 seconds.--&gt;
This shows the time when the page was last cached. This date/time will be reset whenever this page is purged and refreshed in the cache.

Just check this comment before and after a manual purge.
As long as you don't purge the page (or make changes that purge it from the cache), the timestamp will remain as it is, even if you keep refreshing the page. This means the page was served from the cache and it's working!

The rest shows you the database queries and time saved on loading this page. (This would have been the additional resource load, if you weren't using fast-cgi-cache.)


**Q. I need to flush a cached page immediately! How do I do that?**

Nginx helper plugin handles usual scenarios, when a page in the cache will need purging. For example, when a post is edited or a comment is approved on a post.

To purge a page immediately, follow these instructions:
(eg. http://yoursite.com/about/)
Between the domain name and the rest of the url, insert '/purge/'.
So, in the above eg, the purge url will be http://yoursite.com/purge/about/
Just open this in a browser and the page will be purged instantly.
Needless to say, this won't work, if you have a page or taxonomy called 'purge'.

= FAQ - Nginx Map =

**Q. I am using X plugin. Will it work on Nginx?**

Most likely yes. A wordpress plugin, if not using explictly any Apache-only mod, should work on Nginx. Some plugin may need some extra work.


= Still need help! =

Post your problem in [our free support forum](http://rtcamp.com/support/forum/wordpress-nginx/) or wordpress.org forum here. We answer questions everywhere. Including Nginx official forum, serverfault, stackoverflow, etc.
Its just that we are hyperactive on our own forum!


== Screenshots ==
1. Nginx plugin settings
2. Remaining settings

== Changelog ==

= 1.6.4a =
* Added option to prefix the map pattern with the server name (plus ':' delimter) to avert mapping conflicts with multiple nginx multisite network servers.

= 1.6.4 =
* Improved code for map generation to better conventions since the nesting confused some servers.
* Added map update process to admin_init for frequent refreshes.

= 1.6.3 =
* Fixed duplicate entries.

= 1.6.2 =
* Another bug fix in the revised code for improved multisite and multidomain mapping.

= 1.6.1 =
* Fixed bug in the revised code for improved multisite and multidomain mapping.

= 1.6 =
* Revised code for improved multisite and multidomain mapping.

= 1.5 =
* Timestamp now only gets added to content-type text/html
* Added option to toggle timestamp creation

= 1.4 =
* Fixed bug related to nomenclature of comment status that caused purge to fail.

= 1.3.9 =
* Removed extraneous headers.

= 1.3.8 =

* Fixed bug in single post/page/post-type purging code. Thanks to Greg for pointing this out here: http://rtcamp.com/support/topic/updating-post-nginx-helper-purge-cache-post/.

= 1.3.7 =

* Changed the action hook, back to 'shutdown' from 'wp_footer' to add verification comments.
* Added a check to prevent adding comments to ajax requests,

= 1.3.6 =

* Changed the action hook, from 'shutdown' to 'wp_footer' to add verification comments. This was interfering with other plugins.

= 1.3.5 =

* Improved Readme.
* Improved cache verification comments.

= 1.3.4 =

* Fixed duplicate entries generated for maps (Harmless, but doesn't look good!)
* Added timestamp html comments for cache verification, as described here: http://rtcamp.com/tutorials/checklist-verify-wordpress-nginx-setup/

= 1.3.3 =

* Fixed map generation for multi domain installs using domain mapping plugin, where blog ids were not displayed.

= 1.3.2 =

* Fixed map generation for multi domain installs with domain mapping plugin.

= 1.3.1 =

* Minor fixes for directory structure and file names.

= 1.3 =

* Improved Readme.


= 1.2 =

* Fixed map generation error.
* Fixed purging logic.
* Fixed UI where purge settings were lost on disabling and re-enabling purge.
* Minor Ui rearrangement.

= 1.1 =

* Improved readme.txt. Added Screenshots.

= 1.0 =

* First release


== Upgrade Notice ==

= 1.6.4 =
Revised map generation.
