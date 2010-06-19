=== Plugin Name ===
Contributors: LinksAlpha
Tags: twitter, retweet, social, news, trackbacks, trackback, buddypress
Requires at least: 2.0.2
Tested up to: 2.9.2
Stable tag: 1.2.2

== Description ==

Get Support: support@linksalpha.com

Plugin shows Twitter users who recently tweeted links from your blog. In other words, quick and easy way to get your Twitter community rolling :)

Plugin shows Tweets linking your Blog posts. Tweets show up below the comment form on each blog post

Plugin shows Count of Tweets linking to your blog. Count can be configured to show next to "Comment Count" or "Blog Post Date" for each blog post.

**Check out our other plugins that you will find extremely useful:**

* Social Discussions: show popularity of your blog posts on Social Networks including Twitter, Facebook, Google Buzz, Yahoo, and bit.ly. http://wordpress.org/extend/plugins/social-discussions/
* Social Stats: track your blog activity on social networks - monthly/weekly/daily, and track your popular posts. http://wordpress.org/extend/plugins/social-stats/screenshots/
* Network Publisher: auto publish your blog posts to Twitter, Facebook, LinkedIn, Yahoo, Yammer, Identi.ca, and MySpace. http://wordpress.org/extend/plugins/network-publisher/
* Buzz Roll: let your users share your blog post on fastest growing social hub - Google Buzz. http://wordpress.org/extend/plugins/buzz-roll/screenshots/

== Installation ==

1. Upload retweeters.zip to '/wp-content/plugins/' directory and unzip it.
1. Activate the Plugin from "Manage Plugins" window
1. Retweeters Widget: From the "Widgets" Window, drag and drop the "Retweeters" widget onto the desired sidebar. This widget is not yet active. Go to Step 4.
1. Activate Features: From the Wordpress Plugins side-menu bar, click on the "Retweeters" Plugin link. Once there, choose the required options and click on "Update Options" button. If you are using the plugin for the first time and LinksAlpha.com is indexing your blog for the first time, content in the "Retweeters" sidebar widget will show up in 2-3 minutes. "Tweet" content for each blog post might take 10-15 minutes to show up.
1. Stats: To make your life easier, you can now easily tell if your blog is getting indexed by LinksAlpha.com. From the "Retweeters" plugin page, "Stats" column on the right hand side of the page should show your recent blog posts indexed by LinksAlpha.com. If nothing shows up in stats even after 30 minutes of activating the plugin, please contact support@linksalpha.com

Manual positioning of features on template:

1. Retweeters Widget: <code><?php load_retweeters(); ?></code>  (example: If you want to add to sidebar.php template)
1. Tweets for each blog post: <code><?php load_link_tweets(); ?></code>  (example: If you want to add to single.php and index.php template)
1. Tweet Count for each blog post: <code><?php load_tweet_count(); ?></code>  (example: If you want to add to index.php and sidebar.php template)

Note: by downloading this plugin, you agree to Linksalpha terms of service at: http://www.linksalpha.com/about/tos

== Screenshots ==

1. Wordpress Plugin activation window
2. Wordpress Widget and Sidebar management window
3. Retweeters feature/options window
4. Retweeters stats window
5. Retweeters sidebar widget on the front-end

== Changelog ==

= 1.2.2 =
* Added more UI options
* Added HTTP function to get around issues arising out of snoopy class

= 1.2.1 =
* Support for manual positioning of features

= 0.20 =
* Add: Ability to show Tweets for each blog post
* Add: Ability to show Tweet Count for each blog post
* Fix: Does not show Retweeters in sidebar if no retweets are detected for the blog
* Minor bug fixes

= 0.10 =
* First release