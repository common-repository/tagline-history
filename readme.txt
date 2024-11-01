=== Tagline History ===
Contributors: mweston
Donate Link: http://www.mattyboy.net/
Tags: tagline history, status updates, status, blog description, tagline, plugin
Requires at least: 2.3.1
Tested up to: 2.3.2
Stable tag: 1.1.2

Tagline History stores your tagline changes and display a history of taglines to your users.

== Description ==

Tagline History plugin detects when you change your tagline and keeps track of all the changes that you make. These
changes can then be output onto a page of your choice for your users to look over.

**Features**

- Customizable template and style sheet for Tag History list.
- Customizable date format.

This is my first attempt at creating a Wordpress plugin and I welcome any feedback or comments about it.

== Change Log ==

29/01/08
- Fixed:   PHP allow_url_fopen issue, using file path by default instead of URL.
- Added:  Options to manually set template locations, to further fix fopen issue. 
- Changed: Moved configuration page into plugins sub menu.

20/01/08
- Added:   Templates for tagline history page. Making it easier to acheive the tagline page look you want.
- Removed: Tagline prepend/append HTML options. Replaced by Template files, much easier to use that way.
- Fixed:   Using WordPress date rather then database date when storing taglines into database.

== Installation ==

First Time:

1. Upload `tagline-history.php` to the `/wp-content/plugins/tagline-history` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
	- Upon activition it will create a table to store your tagline changes and registers actions and hooks.
3. After activating Tagline History you will need to configure the options and choose a page you want to display
your tagline-history on. 

Upgrade:

1. Upload `tagline-history.php` to the `/wp-content/plugins/tagline-history` directory
2. Deactivate and Activate pluging to complete upgrade.

== Frequently Asked Questions ==

=Where is the Tagline History list displayed?=

You can configure which page the Tagline History is shown on and the format it is displayed in. Currently the tagline 
history is added immediately after the page entry on the chosen page.

=If I change the tagline on other pages will Tagline History pick it up=

Yes, you can change the tagline anywhere and Tagline History will pick up the change. Tagline History uses the 
update_option_blogdescription action to determine when the tagline is changed.

=What the hell is the grace period?=

The grace period is used to prevent multiple changes in quick succesion from being added to your tagline history. It
defaults to 10 seconds, meaning if you make a mistake and correct it within 10 seconds it will overwrite the last change.

=How do I remove a Tagline from the History?=

After you have configured your Tagline History page in the options you will be able to click on the remove link next to
the taglines shown on the Tagline History page. You MUST BE logged in as an admin user to see the remove link.

=How do I change the layout/look of my taglines?=

Listed on the options page for this plugin is the template file names that are being used. Please feel free to modify
those files to acheive your desired look. It is probably worth making a backup of the originals, just in case you need them.

