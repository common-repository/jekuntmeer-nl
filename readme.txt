=== Jekuntmeer.nl ===
Contributors: Piipol, excellente whizzkids
Donate link: http://www.piipol.nl/
Tags: content, database, dashboard, feed, shortcode, widget, social
Requires at least: 4.4.0
Tested up to: 5.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Stable Tag: 1.2.1

This plugin allows you to display projects/activities that are listed on Jekuntmeer.nl. 
Making it possible to list your own activities on your own websites and only have to maintain them in Jekuntmeer.nl

== Description ==

This plugin allows you to display projects/activities that are listed on Jekuntmeer.nl. 
This allows you to list your own activities on your own websites and only have to maintain them in Jekuntmeer.nl

It uses o SOAP conenction from Jekuntmeer to retrieve the projects. Using an automated (cron)job uodates of the projects 
will be retrieved making sure your website is always up to date. 

To be able to use the SOAP connection of Jekuntmeer.nl you nee a user account of Jekuntmeer. The section Settings->Jekuntmeer 
shows how you can request an user account.


== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/jekuntmeer` directory, or install the plugin using the WordPress plugins screen directly.
2. Activate the plugin using the 'Plugins' screen in WordPress
3. Use the Settings->Jekuntmeer screen to login with your Jekuntmeer account
4. In the Settings->Filter section you can configure what kind of projects you want to retrieve from Jekuntmeer.nl 
5. In the Settings->Config section you can configure how things look on your website and if visitors can use a search form or not
6. In the Settings->Custom CSS section you can add CSS to change the looks of the listed projects 
7. Use the shortcode [jekuntmeer] on a page to show the projects of Jekuntmeer. With the parameter itemsperpage you can control how many projects
   on each page will be shown. For example: [jekuntmeer itemsperpage="8"]

== Changelog ==

= 1.2.1 =
WordPress 5.0 Testing

= 1.2.0 =
WordPress 4.9 Testing
Check if SOAP is enabled

= 1.1.0 =
First Release