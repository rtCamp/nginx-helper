=== Nginx ===
Contributors: rtcamp, rahul286, saurabhshukla
Tags: nginx, cache, purge, nginx map, nginx cache, maps, fastcgi, proxy, rewrite, permalinks
Requires at least: 3.0
Tested up to: 3.4.2
Stable tag: 1.3
License: GPLv2 or later (of-course)
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate Link: http://rtcamp.com/donate/

Helps WordPress-Nginx work together nicely using fastcgi/proxy cache purging, nginx map{}, rewrite  support for permalinks & more

== Description ==

1. Removes `index.php` from permalinks when using WordPress with nginx.
1. Add support for nginx fastcgi_cache_purge & proxy_cache_purge directive from [module](https://github.com/FRiCKLE/ngx_cache_purge "ngx_cache_purge module"). Provides settings so you can customize purging rules.
1. Adds support for nginx `map{..}` on a WordPress-multisite network installation. Using it Nginx can serve PHP file uploads even if PHP/MySQL crashes. Please check tutorials list below for related Nginx config.

= Tutorials =

You will need to follow one ore more tutorials below to get desired fundtionality:
* [Nginx Map + WordPress-Multisite + Static Files Handling](http://rtcamp.com/tutorials/nginx-maps-wordpress-multisite-static-files-handling/)
* [Nginx + WordPress + fastcgi_purge_cache](http://rtcamp.com/tutorials/wordpress-nginx-fastcgi-cache-purge-conditional/)
* [Nginx + WordPress-Multisite (Subdirectories) + fastcgi_purge_cache](http://rtcamp.com/tutorials/wordpress-multisite-subdirectories-nginx-fastcgi-cache-purge/)
* [Nginx + WordPress-Multisite (Subdomains/domain-mapping) + fastcgi_purge_cache](http://rtcamp.com/tutorials/wordpress-multisite-subdomains-domain-mapping-nginx-fastcgi-cache-purge/)
* [Other WordPress-Nginx Tutorials](http://rtcamp.com/wordpress-nginx/tutorials/)


== Installation ==

1. Extract the zip file.
1. Upload them to `/wp-content/plugins/` directory on your WordPress installation.
1. Then activate the Plugin from Plugins page.

For proper configuration, check **tutorial list** of [Description tab](http://wordpress.org/extend/plugins/nginx-helper/)

== Frequently Asked Questions ==

= FAQ - Installation/Comptability =

**Q. Will this work out of the box?**

No. You need to make some changes at Nginx end. Please check **tutorial list** of [Description tab](http://wordpress.org/extend/plugins/nginx-helper/)

= FAQ - Nginx Map =

**Q. On my Multisite, I am alredy using `WPMU_ACCEL_REDIRECT`. Do I still need Nginx Map?**

Definietly yes. `WPMU_ACCEL_REDIRECT` reduceds load on PHP, but it still ask WordPress i.e. PHP/MySQL to do some work for static files e.g. images in your post. Nginx map handles files by itself which gives you much better performance without using a CDN.

= FAQ - Nginx Fastcgi Cache Purge =

**Q. How do I know my Nginx config is correct for fastcgi purging?**
For any page on your site, try purging cache manullay for it, by following instructions mentioned in next answer.

**Q. I need to flush a cached page immediately! How do I do that?**
Nginx helper plugin try to handle all common cases when you need to purge a cached copy of page. e.g. a post is edited, a comment is approved on a post, etc.

If you come across any URL like `http://example.com/hello-world/' you can simply 'purge' before path-part of URL.
It will become like `http://example.com/purge/hello-world/'
Just open in browser and cache for `/hello-world/` will be flushed immeditaley!


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

= 1.3 =

* Improved Readme


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

= 1.3 =

Improved Readme.


= 1.2 =

This release fixes a couple of bugs with nginx maps and purging logic.
