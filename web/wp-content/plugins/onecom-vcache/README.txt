=== one.com Performance Cache Plugin ===
Contributors: onecom
Link: https://www.one.com/
Requires at least: 4.6
Tested up to: 6.3
License: GPL v2 or later

== Description ==
With one.com Performance Cache your website loads a lot faster. We save a cached copy of your website on a Varnish server, that will then be served to your next visitors.

== Changelog ==

= v4.1.10
* Misc stats update

= v4.1.9
* Minor code cleanup

= v4.1.8
* Re-designed plugin admin pages
* Fixed wp-cli related warnings

= v4.1.7
* Added optional preference for sharing anonymous usage data to help improve the plugin

= v4.1.6
* Minor updates in validator

= v4.1.5
* Deprecated warning on login - fixed
* Misc translations update

= v4.1.4
* Translation hooks updated as per WP 6.7

= v4.1.3
* CDN URL edge cases fix - Revert regex to previous stable version

= v4.1.2
* Improved RegEx for minified CSS URLs detection for CDN purge
* Repetitive upgrade modal issue fix

= v4.1.1
* Login issue in some cases - Fixed

= v4.1.0
* SAST vuln fixed
* Code quality Improvements

= v4.0.1
* menu restructure

= v4.0.0
* oneHOP compatibility

= v3.1.0
* Add CDN urls for cluster model

= v3.0.23
* Improved condition for validating htaccess rules
* Misc Translations update

= v3.0.22
* PHPCS fixes
* Validator update
* Disable autoload options

= v3.0.21
* Multisite compatibility added for CDN & Pcache

= v3.0.20
* Translations and locales fallback update
* CDN purge string conflict with .elementor class - fixed

= v3.0.19
* CDN validation improvements
* stats fix in validator

= v3.0.18
* Settings UI & validation improvements
* Validator Update

= v3.0.17
* Code Cleanup

= v3.0.16
* Translations update

= v3.0.15
* Code cleanup

= v3.0.14
* Vulnerability Monitor text and translation update

= v3.0.13
* PHP deprecation notice fix

= v3.0.12
* PHP 8.2 compatibility fixes

= v3.0.11
* Update htaccess rules in case of subdirectories and remove duplication from htaccess files

= v3.0.10
* Update vulnerability monitor modal button text
* Added condition for locale fi, pt_BR

= v3.0.9
* Duplication of htaccess rules - Fixed

= v3.0.8
* CDN Dev mode - validation improvement
* Tooltip class UI conflict fix
* Misc text and translations update
* Unified logs

= v3.0.7
* Update Htaccess rules in case of subdirectories

= v3.0.6
* Intro banner - Improvement

= v3.0.5
* Warning on onecom plugin deletion - Fix
* Nested menu observer console error - Fix

= v3.0.4
* CDN hosted fonts URL change
* Change in visibility of upsell banners
* Skip CDN rewrite in yoast-schema-graph

= v3.0.3
* Removed brand name constant

= v3.0.2
* Self hosted fonts
* CDN purge added

= v3.0.1
* Security Fix
* CDN Purge- query string in URLS fixed

= v3.0.0
* Redesign PC, CDN & WP-rocket page

= v2.0.4
* API calls enhancement

= v2.0.3
* Htaccess rules update for existing file

= v2.0.2
* Fix purge cache for beginners

= v2.0.1
* WP CLI Error Fix
* Access-Control-Allow-Origin header allows origin instead of wildcard

= v2.0.0
* Redesigned admin settings UI

= v1.0.13
* Favicon http call fix
* Features access updated for Beginner package
* one.com migrator - 'view details' link fix

= v1.0.12
* Exclude page/post from cache feature

= v1.0.11
* CDN Purge compatiblity fix with brizy builder

= v1.0.10
* Fatal error fixed

= v1.0.9
* Improvements related to Login Protection

= v1.0.8
* Code refactoring

= v1.0.7
* Added multisite handling in login masking

= v1.0.6
* UTM tracking links

= v1.0.5
* One.com restructured menu

= v1.0.4
* Fixed blocked access to premium themes

= v1.0.3
* API performance optimisation

= v1.0.2
* Premium validator udpate

= v1.0.1
* Premium settings updated

= v0.3.1
* Premium settings added

= v0.2.11
* Plugin name translation fix

= v0.2.10
* Logger class conflict fix

= v0.2.9
* PHP8 compatibility
* Improved stats

= v0.2.8
* 1-click wp stats

= v0.2.7
* Fixed minor issues related to theme switching

= v0.2.6
* Shortcuts added to wp admin

= v0.2.5
* CS, PL & PTBR translations are being discontinued
* Minor improvements

= v0.2.4
* Minor performance improvements

= v0.2.3
* Rebranding text update

= v0.2.2
* Rebranding

= v0.2.1
* Fix for turning the cache OFF

= v0.2
* Domain identification fixed

== Changelog ==
= v0.1.29
* Added .pdf in the list of extensions allowed for CDN

= v0.1.28
* Fixed auto deactivation on package downgrade
* Enabled CDN for uploads folder

= v0.1.27
* Fixed CDN and CORS conflict

= v0.1.26
* Minor performance improvements

= v0.1.25
* Admin notice fixed for non-one.com domain
* Added logs
* Disabled CDN for .css files until Varnish rules are updated
* Fixed undefined variable warning

= v0.1.24
* CDN added
* CDN + Performance cache auto enabled by default
* Name changed from Performance cache to Performance Tools

= v0.1.23
* Fixed console errors due to validator

= v0.1.22
* Minor improvement

= v0.1.21
* Premium features added

= v0.1.20
* Fixed admin notice for non one.com domains

= v0.1.19
* Fixed PHP warnings while checking plugin info.

= v0.1.18
* Plugin link fixed
* update script updated

= v0.1.17
* Removed potential issues with WordPress Site Health feature
* Caching improved by periodically validation
* update.php fixes
* en_US as default english fallback

= v0.1.16
* Caching improved to recover itself

= v0.1.15
* Multiple PURGE requests combined into one

= v0.1.14
* Changelog popup content fixed

= v0.1.13
* Minor fixes

= v0.1.12
* Fixed PHP warning on PHP v7.3

= v0.1.11
* Fixed the guide link for Swedish locale.

= v0.1.10
* Cookie name fixed

= v0.1.9
* Compatibility information updated

= v0.1.8
* Minor css fixes

= v0.1.7
* Renamed the plugin

= v0.1.6
* Renamed log handles

= v0.1.5
* Minor fixes

= v0.1.4
* Help-desk urls are not working for different languages from WP dashboard
* Minor fixes

= v0.1.3
* Permalink specific messages
* Missing translations
* Minor fixes

= v0.1.2
* Console error on non WP packages

= v0.1.1
* Truncate long purge messages
* Other minor fixes

= v0.1.0
* Initial release.