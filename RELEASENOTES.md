## Release highlights

**Upgraded to WebAuthn library version 2**. This is required to work with Joomla 4. It also means that embedded authentication methods (e.g. Chrome using a MacBook Pro's TouchID sensor or a Windows Hello face camera) will now work properly. Plus, it adds support for Bluetooth-based authenticators. 

**Joomla 4 compatibility**. Well, at least at an experimental level since J4 is currently in beta.

**Automatic database schema fix**. If your LoginGuard tables break just visit the component in the backend and it will fix its tables.

**Skip browser fingerprinting if Remember Me is disabled at the component level**. In this case there is no need for the browser fingerprinting and the code shouldn't run.

**HTML5 number field for OTPs**. That will make your mobile users' lives easier, typing in those 6-digit codes with an on-screen numeric keypad instead of full alphanumeric keyboard.

## Joomla and PHP Compatibility

Akeeba LoginGuard is compatible with Joomla! 3.9 and 4.0. Joomla 4 compatibility is considered experimental since Joomla 4 is still in beta.

Akeeba LoginGuard requires at least PHP 7.1. It's also compatible with PHP 7.2, 7.3 and 7.4.

We strongly recommend using the latest published Joomla! version and PHP 7.3 or later _for optimal security of your site_.

**IMPORTANT!** We are dropping support for all versions of PHP which are [officially considered End Of Life (EOL) by the PHP project](http://php.net/eol.php) a few months after they go EOL. These versions of PHP _no longer receive bug fixes or security updates_ and MUST NOT be used on production sites.

## Changelog

**New**

* LoginGuard will fix and update its database if necessary when you visit its backend page as a Super User
* Joomla 4 compatible

**Other Changes**

* Minimum requirement: Joomla 3.9
* Internal changes to use proper-cased views everywhere instead of legacy task=viewname.taskname when building public URLs
* Do not go through browser fingerprinting if the Remember Me feature is disabled at the component level.
* HTML5 number field for 6 digit codes (Email, PushBullet, SMS, Time-based One Time Password)
* Now using WebAuthn library version 2, required for operating inside Joomla 4

**Bug fixes**

* Unable to change 2SV method on Chrome when caching is enabled either at the Joomla or web server level.
* Joomla 4 throws an exception when mail is disabled and you try to send an email
* Unhandled exception page was incompatible with Joomla 4
