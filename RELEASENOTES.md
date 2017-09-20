## Release highlights
 
**Backup codes are more obvious**. We changed the way the Backup Codes header renders to make their existence and intent more obvious to your site's users.

**Auto-focus the two step verification field**. So you can just type your code / press the button on your YubiKey without needing to move your hands off the keyboard or hit TAB a million times.

**Bug fixes**. A number of bugs and oversights have been corrected in this version.

For more information and documentation for administrators, users and developers please [consult the documentation Wiki](https://github.com/akeeba/loginguard/wiki).
 
## Joomla and PHP Compatibility

Akeeba LoginGuard is compatible with Joomla! 3.4, 3.5, 3.6, 3.7 and 3.8. It requires PHP 5.3.10 or later, the same minimum PHP version as Joomla! itself. It's also compatible with PHP 5.4, 5.5, 5.6, 7.0, 7.1 and 7.2.

We strongly recommend using the latest published Joomla! version and PHP 7.0 or 7.1 later _for optimal security of your site_. It makes no sense adding two step login verification to a site that's running vulnerable software. It's like locking your door and leaving your windows wide open. It will not keep the bad guys out.

## Changelog

**Other changes**

* Make the intent of Backup Codes more obvious
* Auto-focus the two step verification field
* Do not escape the LoginGuard method title (allows for title formatting, e.g. with the backup codes method) 

**Bug fixes**

* The emergency backup codes could be reused
