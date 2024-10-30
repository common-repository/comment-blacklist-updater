=== Comment Blacklist Updater ===
Contributors: apasionados
Donate link: https://apasionados.es/
Author URI: https://apasionados.es/
Tags: contact form 7, form spam, comments, spam, blacklist, comment spam, contact form spam
Requires at least: 4.0.1
Tested up to: 6.3
Requires PHP: 5.6
Stable tag: 1.2.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Update "Comment Blacklist" spam terms to manage spam in forms and comments

== Description ==

Updates the "Comment Blacklist" in Settings / Discussion with a list terms from a remote or local source. By default it get's the data from Github **("[wordpress-comment-blacklist](https://github.com/splorp/wordpress-comment-blacklist/)")** by [Grant Hutchinson](https://github.com/splorp)) but you can also get them **from any URL** or from a **local blacklist.txt file**.

This plugin is an enhanced version of the plugin [Comment Blacklist Manager](https://wordpress.org/plugins/comment-blacklist-manager/). We decided to create this enhanced version of the plugin, because we wanted to be able to add blacklists without using filters and directly from the WordPress administration. You can still use a filter to modify the blacklist sources if that is more convienient for you. And we also wanted to have more information about the plugin in SETTINGS / DISCUSSION; for example when the blacklist was updated and when it will be updated next time, when the blacklist sources were updated, etc.

You can configure three sources for your blacklists:

1. **Default blacklist** (which can be disabled): [wordpress-comment-blacklist](https://github.com/splorp/wordpress-comment-blacklist/) by [Grant Hutchinson](https://github.com/splorp)). Please keep in mind that if there is no other blacklist source defined, this will be used as default, even if it\'s not selected.
1. **Blacklist from remote URL**: You paste the URL to the blacklist and if the file exists and can be accesed (must return code 200) it will be used as a blacklist source.
1. If you want to include a **local blacklist** for the site, you can upload a blacklist.txt file to the UPLOADS folder and it will also be taken into account. The blacklist.txt file has to be in the root of the UPLOADS folder; it will not be recognized if it\'s for example in /uploads/2025/12/ and the file has to be accesible via http/https (if the access to the file is protected it can\'t be used).

And you can use the filter `cblm_sources` to replace all the blacklists or to add more. If you replace all blacklists with the filter, the settings done in the WordPress administration will be ignored. We decided to keep the same filter as used by "Comment Blacklist Manager" to make it easy to switch between both plugins.

> Please note: **After the September 2023 update only users with administrator privileges can use this plugin.** If you're not an admin you will get following error: "You do not have sufficient permissions to access this page".

= What can I do with this plugin? =

The plugin updates the "Comment Blacklist" in Settings / Discussion with a list terms from a remote or local source. By default it get's the data from Github ("[wordpress-comment-blacklist](https://github.com/splorp/wordpress-comment-blacklist/) by [Grant Hutchinson](https://github.com/splorp)) but you can also get them from any URL or from a local blacklist.txt file.

= Why do I want to update the "Comment Blacklist" in Settings / Discussion? =

If you want to reduce spam received in your comment forms but also in your contact forms (for example when using Contact Form 7), using blacklisted terms can help.

Contact Form 7 encourages to use: Akismet, reCaptcha and the comment blacklist to reduce contact form spam.
>*Contact Form 7 supports spam-filtering with Akismet. Intelligent reCAPTCHA blocks annoying spambots. Plus, using comment blacklist, you can block messages containing specified keywords or those sent from specified IP addresses.*

The best way to reduce the contact form 7 spam is to use a very extensive term database which is updated regulary with new spam terms. And this plugin does exactly this: Updating the blacklist regularly.

= Why are you using the "Comment Blacklist for WordPress" from Grant Hutchinson as default source for the blacklist? =

Since 2011 Grant Hutchinson has been identifying and compiling over 34,000 phrases, patterns, and keywords commonly used by spammers and comment bots in usernames, email addresses, link text, and URIs.

His blacklist is very extensive and that's why we love it.

As with all compilations, this blacklist is a work in progress and it is updated more or less every month. And each of these updates will be included automatically with the update process that runs every 24 hours.

*Sometimes simple is better.*

**If you know another source that is as extensive as this one, drop us a message and we will check if it's interesting to add it also as a default.**

= System requirements =

PHP version 5.6 or greater.

= Comment Blacklist Updater Plugin in your Language! =
This first release is avaliable in English and Spanish. In the "languages" folder we have included the necessary files to translate this plugin.

If you would like the plugin in your language and you're good at translating, please drop us a line at [Contact us](https://apasionados.es/contacto/index.php?desde=wordpress-org-apa-comment-blacklist-updater-home).

= Further Reading =
You can access the description of the plugin in Spanish at: [Actualizador lista negra de comentarios | WordPress Plugin](https://apasionados.es/blog/).

== Screenshots ==

1. Settings of the plugin in "Settings" / "Discussion".
1. Detail of the settings.

== Installation ==

1. First you will have to upload the plugin to the `/wp-content/plugins/` folder.
1. Then activate the plugin in the plugin panel.
1. Go to "Settings" / "Discussion" / "Blacklist source" to configure the plugin.

== Frequently Asked Questions ==

= Who can use this plugin? =
**After the September 2023 update only users with administrator privileges can use this plugin.** If you're not an admin you will get following error: "You do not have sufficient permissions to access this page".

= Why did you make this plugin?  =
This plugin is an enhanced version of the plugin [Comment Blacklist Manager](https://wordpress.org/plugins/comment-blacklist-manager/). We decided to create this enhanced version of the plugin, because we wanted to be able to add blacklists without using filters and directly from the WordPress administration. You can still use a filter to modify the blacklist sources if that is more convienient for you. And we also wanted to have more information about the plugin in SETTINGS / DISCUSSION; for example when the blacklist was updated and when it will be updated next time, when the blacklist sources were updated, etc.

= What ideas is this plugin based on? =
This plugin is based on the idea of the [Comment Blacklist Manager](https://wordpress.org/plugins/comment-blacklist-manager/) plugin.

= What is the default source of the blacklist? =
The default source for the blacklist is "[wordpress-comment-blacklist](https://github.com/splorp/wordpress-comment-blacklist/)" (a simple solution for WordPress comment spam) from [Grant Hutchinson](https://github.com/splorp).

If you don't make any configuration this source will be used.

= Can I use my own blacklist sources? =
Of course you can. You can configure them in SETTINGS / DISCUSSION or use the filter `cblm_sources`. It's easier to configure them using the WordPress adminsitration interface, but if you want more flexibility, you can use the filter.

= Can I use a filter to add my own blacklist sources? =
You can use the filter `cblm_sources` to add different source URLs. We decided to keep the same filter as used by "Comment Blacklist Manager" to make it easy to switch between both plugins.

*Replace sources completely (setting of the sources in the administration will be overwritten)*
`
add_filter( 'cblm_sources', 'rkv_cblm_replace_blacklist_sources' );
function rkv_cblm_replace_blacklist_sources( $list ) {
	return array(
		'https://example.com/blacklist.txt',
		'https://example.com/blacklist2.txt'
	);
}
`

*Add another source to the sources configured in the administration*
`
add_filter( 'cblm_sources', 'rkv_cblm_add_blacklist_source' );
function rkv_cblm_add_blacklist_source( $list ) {
	$list[]	= 'https://example.com/blacklist3.txt';
	return $list;
}
`

The blacklist expects the same format as the "Comment Blacklist" in "Settings" / "Discussion": One word or IP address per line.

= Can I change the update schedule? =
Yes you can change it using the 'cblm_update_schedule' filter. We decided to keep the same filter as used by "Comment Blacklist Manager" to make it easy to switch between both plugins.

The standard update schedule is set to update once every 24 hours.
`
add_filter( 'cblm_update_schedule', 'rkv_cblm_custom_schedule' );
function rkv_cblm_custom_schedule( $time ) {
	return DAY_IN_SECONDS;
}`

The return can be provided in seconds or using the [WordPress time contstants in transients](https://codex.wordpress.org/Transients_API#Using_Time_Constants):
1. MINUTE_IN_SECONDS  = 60 (seconds)
2. HOUR_IN_SECONDS    = 60 * MINUTE_IN_SECONDS
3. DAY_IN_SECONDS     = 24 * HOUR_IN_SECONDS
4. WEEK_IN_SECONDS    = 7 * DAY_IN_SECONDS
5. MONTH_IN_SECONDS   = 30 * DAY_IN_SECONDS
6. YEAR_IN_SECONDS    = 365 * DAY_IN_SECONDS

= Does Comment Blacklist Updater make changes to the database? =
Yes. The plugin adds information to the database. When the plugin is uninstalled via the WordPress administration, these settings (options and transients) are deleted.

Settings that the plugin adds:
1. Option: blacklist_exclude
2. Option: blacklist_last_update
3. Option: blacklist_github_source_updated
4. Option: use_wordpress_comment_blacklist_splorp
5. Option: apa_another_blacklist_url

Transientes used by the plugin:
1. Transient: blacklist_update_process
2. Transient: blacklist_github_update_check

= How can I remove Comment Blacklist Updater? =
You can simply activate, deactivate or delete it in your plugin management section. The options and transients of the plugin are deleted when you delete it through the WordPress administation. If you want to keep the options delte the plugin folder ('comment-blacklist-updater') via FTP.

= How can I check out if the plugin works for me? =
Install and activate. Navigate to "Comment Blacklist" in "Settings" / "Discussion" and see if the blacklist has been updated. And then go to "Blacklist Source" to configure it.
And from this moment on the amount of spam in the comment form and contact forms should be reduced.

= After installing the plugin the "Settings" / "Discussion" page loads very slow =
After updating the "Comment Blacklist" with the blacklists, the page load can slow down because of the large amount of data that has to be shown on the page in "Comment Blacklist". The default blacklist from Grant Hutchinson has over 34.000 lines that have to be shown on the settings page.
Unfortunately there is nothing that can be done about it.

= I made some configuration changes and run a manual update and the new settings are lost =
Please remember to SAVE the configuration changes before running a manual update. The manual update does not save changes.
If you make configuration changes, SAVE and after saving run the manual update.

= Why is the background color of your settings not the standard of WordPress? =
As we add setting to the standard DISCUSSION page of WordPress we wanted to make clear which settings have been added by another plugin and also make reference to which plugin has added these settings.
For us it's important that everyone that installs the plugin can find easily the plugin that added these settings, even months after having installed it.

= Are there any known incompatibilities? =
Please don't use it with *WordPress MultiSite*, as it has not been tested.
Please don't use it with the [Comment Blacklist Manager](https://wordpress.org/plugins/comment-blacklist-manager/) plugin.

= Which PHP version do I need? =
This plugin has been tested and works with PHP versions 5.6 and greater and we recommend using PHP version 7.1 or higher. The plugin has been tested with PHP up to 7.3. WordPress recommends using [PHP 7.3](https://wordpress.org/about/requirements/). If you're using a PHP version lower than 5.6 please upgrade your PHP version or contact your Server administrator.

= Are there any server requirements? =
Yes. The plugin requires a PHP version 5.6 or higher and we recommend using PHP version 7.1 or higher. The plugin has been tested with PHP up to 7.3. WordPress recommends using [PHP 7.3](https://wordpress.org/about/requirements/).

= Do you make use of Comment Blacklist Updater yourself? = 
Of course we do. That's why we created it. ;-)

== Changelog ==
= 1.2.1 & 1.2.2 (25/sep/2023) =
* Patched "You do not have sufficient permissions to access this page" error on front-end.

= 1.2.0 (23/sep/2023) =
* Security update. Cross Site Request Forgery (CSRF) Vulnerability discovered by Nguyen Xuan Chien and notified by patchstack.com. Please note that this update limits the usage of the plugin to Wordpress users with an administrator role.

= 1.1.0 (20/dec/2022) =
* Solved a fatal PHP error on a few installations: "Undefined constant _transient_timeout_blacklist_github_update_check"

= 1.0.5 (21/feb/2019) =
* Solved a problem with plugins that redirect 404 pages to the home (in this case with "404 to Start") and the local blacklist.txt file. Now the local blacklist.txt file in the uploads folder will only be detected if the file is really in the uploads folder.

= 1.0.4 (19/feb/2019) =
* Added back the timestamp of the latest update of the blacklist.txt file by Grant Hutchinson hosted on Github.

= 1.0.3 (19/feb/2019) =
* Changed curl calls to use the WordPress HTTP API (https://developer.wordpress.org/plugins/http-api/).
* Temporary removed timestamp of the latest update of the blacklist.txt file by Grant Hutchinson hosted on Github.

= 1.0.2 (18/feb/2019) =
* Improved check of time until next update.

= 1.0.1 (17/feb/2019) =
* First official release.

== Upgrade Notice ==

= 1.2.2 =
UPDATED: Patched "You do not have sufficient permissions to access this page" error on front-end.

== Contact ==

For further information please send us an [email](https://apasionados.es/contacto/index.php?desde=wordpress-org-apa-comment-blacklist-updater).