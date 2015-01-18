=== Content Syndication Toolkit Reader ===
Contributors: ben.moody
Tags: content syndicator,content syndication,content aggregator,content aggregation,content publisher,syndication network,aggregator network,seo,content publishing
Requires at least: 3.0
Tested up to: 4.1
Stable tag: 1.0.3
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html

Allows clients to subscribe to content created using the 'Content Syndication Toolkit' plugin.

== Description ==
Simply add your content syndication account details from your content provider in the plugin options page and start receiving content automatically.

= Note =
This plugin simply 'pulls' content from a content creator using the 'Content Syndication Toolkit' plugin.

You will require an account with a content creator using 'Content Syndication Toolkit' to pull content using this plugin.

== Installation ==

1. Upload to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the 'Content Syndication' option under the 'Settings' menu in Wordpress
4. Under 'Account Settings' tab add the URL provided by your content provider
5. Also under 'Account Settings' tab add your account username and password
6. Click 'Save Changes'
7. Perform a manual sync to get your content up to date
8. Click the 'Tools' tab, then click the 'Sync Content' button and wait until it completes
9. Great, you should be up to date and new content will be pushed to your site automatically
10. If you have any issues with new content not importing
11. Click the 'Tools' tab, then click 'Reset Index', then click the 'Sync Content' button and wait until it completes

== Frequently Asked Questions ==

= I received this error message in my email 'Problem contacting the server. Please confirm your API Username and Password are correct.' =
This is probably due to an error in your account username and/or password. It could also be that the API URL the content provider gave you is wrong.

Check the username, password, and API URL in the plugin settings and try again. If the problem persists contact your content provider.

= I received and error message in my email 'Failed to Import xxxx' =
The plugin is saying there was an issue while importing content from your provider. This can be caused by the provider server going down or it could be under heavy load.

**Wait a while then try this:**

1. Click the 'Tools' tab under the 'Content Syndication' option under the 'Settings' menu in Wordpress
2. Click the 'Sync Content' button and wait until it completes, 3. If you get the same error email again, contact your content provider

= I think that I'm missing content? =
Not a problem, try and resync with your content provider:

1. Click the 'Tools' tab under the 'Content Syndication' option under the 'Settings' menu in Wordpress
2. Click 'Reset Index', then click the 'Sync Content' button and wait until it completes

== Changelog ==

= 1.0 =
Initial plugin launch.

= 1.0.1 =
Added canonical link generation for client posts.

= 1.0.2 =
Improved update speed, some backend minor fixes.

= 1.0.3 =
Adds support for advanced SEO options in Content Syndication Toolkit PRO

== Upgrade Notice ==

= 1.0.2 =
Improved update speed, some backend minor fixes.

= 1.0.3 =
Adds support for advanced SEO options in Content Syndication Toolkit PRO