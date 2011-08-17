=== Plugin Name ===
Contributors: kundi
Donate link: http://kundi.si
Tags: social, sharing, facebook, twitter, google, google+, google plus, tweet
Requires at least: 2.0.2
Tested up to: 3.2.1
Stable tag: 1.0

Elegant and simple social sharing solution for your blog. Supports Facebook, Twitter and Google+.

== Description ==

Social Kundi is the most powerful, simple and elegant wordpress social sharing solution. It adds Facebook Open Graph meta tags to your blog and allows you to call simple function from you template files, which places sharing functions on your blog (Facebook, Twitter and Google + 1).

Unlike the other WordPress social-sharing plugins it requires minimal options to be set in order to work. It does not support social sites, which won’t have any effect on your site.

If you want to have a long list of time-consuming, unnecessary set of options with loads of other features to be set, such as setting social sharing sites, icon sizes, colors, positions and so on, then this plugin is not suitable for you.
Social Kundi is intended for effective social sharing and it was carefully designed to be plain and simple.

== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php social_kundi(); ?>` in your templates

== Frequently Asked Questions ==

= Where to place social_kundi() function? =

Usually it is placed in the single-post.php file under or above the content() function.

== Screenshots ==

1. Sharing options
2. Social Kundi configuration

== Changelog ==

= 0.5 =
* Initial version

= 1.0 =
* Added auto embed after content option
* Thumbnail function check (prevents older themes throwing error when trying to get thumbnail of post)