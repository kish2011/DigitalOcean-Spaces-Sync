=== Plugin Name ===
Contributors: keeross
Donate link: https://www.paypal.me/keeross
Tags: digitalocean, spaces, cloud, storage, object, s3
Requires at least: 4.6
Tested up to: 4.9
Stable tag: 1.0.7.1
License: MIT
License URI: https://opensource.org/licenses/MIT

This WordPress plugin syncs your media library with DigitalOcean Spaces Container.

== Description ==

DO Spaces Sync plugin connects your Media Library to a container in DigitalOcean Spaces. It syncs data from your website to cloud storage
and replaces links to images (optional). You may keep the media locally (on your server) and make backup copy to cloud storage, or just serve it all 
from DigitalOcean Spaces.

In order to use this plugin, you have to create a DigitalOcean Spaces API key.

P.S. Basically it works with all AWS S3 compatible cloud storages.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/do-spaces-sync` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->DO Spaces Sync screen to configure the plugin
4. Create a DigitalOcean Spaces API key and container

== Screenshots ==

1. Configuration screen

== Changelog ==

= 1.0.7.1 =
* A hotfix for logger.

= 1.0.7 =
* Updated methods to fix non-images uploads.

= 1.0.6 =
* Removed useless log messages.

= 1.0.3 =
* Fixed upload path param.

= 1.0.2 =
* Nothing really special, added icons and tested with WP 4.9.

= 1.0.1 =
* Initial releasse.
