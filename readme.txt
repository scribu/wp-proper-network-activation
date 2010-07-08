=== Proper Network Activation ===
Contributors: scribu
Donate link: http://scribu.net/paypal
Tags: activation, admin, plugins, multisite
Requires at least: 3.0
Tested up to: 3.0
Stable tag: trunk

Avoid errors when using WordPress MultiSite network activation

== Description ==

When running WordPress MultiSite, you have a very handy feature called network activation. It allows you to activate a plugin for the entire network of sites. The trouble is that it only does half the job.

Some plugins have an install procedure that is meant to be run only on activation. However, when you do a network activation, that install procedure is only run for the current site. So, you end up with plugins not working properly on all the other sites.

What this plugin does:

* when doing a network de/activation, it triggers the de/activation hook on all sites in the network
* when creating a new site, it triggers the activation hook for all active network plugins on that site

**Note:** this plugin is not recommended for very large networks of sites.

== Installation ==

You can either install it automatically from the WordPress admin, or do it manually:

1. Unzip "Front-end Editor" archive and put the folder into your plugins folder (/wp-content/plugins/).
1. Activate the plugin from the Plugins menu.

== Frequently Asked Questions ==

= Error on activation: "Parse error: syntax error, unexpected..." =

Make sure your host is running PHP 5. The only foolproof way to do this is to add this line to wp-config.php (after the opening `<?php` tag):

`var_dump(PHP_VERSION);`
<br>

== Changelog ==

= 1.0 =
* initial release
* [more info](http://scribu.net/wordpress/proper-network-activation/pna-1-0.html)

