=== Safe Ad Network by Kissy ===

Contributors: kissyone
Tags: ad block, adblock
Requires at least: 3.9
Tested up to: 4.7
Stable tag: 20161208a
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html

Bypass adblocks.

== Description ==

This plugin is a front-end of [Safe Ad Network](https://kissy.one), which is an ad network that is built for protecting websites’ revenue sources by bypassing adblocks, as well as high standard of control and openness for everyone.

== Installation ==

1. Sign up at [kissy.one](https://kissy.one) and register your website to Safe Ad Network. A website ID will be issued.
1. Upload the plugin files to `/wp-content/plugins/safeadnetwork` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress.
1. Go to Settings->Safe Ad Network, and paste your website ID.
1. At Safe Ad Network website, create an ad spot and get its ID.
1. Put an ad tag <!--#safead spot="SPOTID"--> in your WordPress site.

When you edit a post, the visual editor may automatically replace '<' with "&lt;". Use the text editor and confirm '<' is kept intact. For further information, please refer to our [Usage Guides](https://kissy.one/#usageguides "Safe Ad Network Usage Guides").

== Frequently Asked Questions ==

No question yet.

== Screenshots ==

1. Safe Ad Network settings have only one parameter, website ID.
2. Website registration at Safe Ad Network website (https://kissy.one).
3. Ad spot creation at Safe Ad Network website.
4. Inserting an ad spot tag to a post.

== Changelog ==

= 20161208a =

* Release date: 8th December, 2016

* Tested on WordPress 4.7. No change in functionality.

= 20160926a =

* Release date: 26th September, 2016

* Fixed wrong wordpress.org userid in readme.txt. No change in functionality.

= 20160909a =

* Release date: 9th September, 2016

* Eliminated dependency on wp-load.php.

* Asynchronous call to update_ads.php using cURL is removed. cURL is not used anymore, so cURL availability is not checked anymore.

* wp_schedule_single_event is used for asynchronously updating ads.

* Instead of random suffixes for avoiding naming colision, function names now have prefix safeadnetwork_.

* Fixed wrong option name in uninstall.php (safe_ad_network_account -> safe_ad_network_site).

= 20160904a =

* Release date: 4th September, 2016

This is the first version.

== Upgrade Notice ==

None yet.
