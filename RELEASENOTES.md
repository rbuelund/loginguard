## Release highlights
 
**Improved U2F**. You don't have to press a button on the screen to start the U2F authentication process, just like GitHub implements it.

**Show the TFA status in the user profile page**. Yuu can now see the TFA status and quickly turn it off from the user profile page.

**Bug fixes**. A number of bugs and oversights have been corrected in this version.

For more information and documentation for administrators, users and developers please [consult the documentation Wiki](https://github.com/akeeba/loginguard/wiki).
 
## Joomla and PHP Compatibility

Akeeba LoginGuard is compatible with Joomla! 3.4, 3.5, 3.6 and 3.7. It requires PHP 5.3.10 or later, the same minimum PHP version as Joomla! itself. It's also compatible with PHP 5.4, 5.5, 5.6, 7.0 and 7.1.

We strongly recommend using the latest published Joomla! version and PHP 7.0 or 7.1 later _for optimal security of your site_. It makes no sense adding two step login verification to a site that's running vulnerable software. It's like locking your door and leaving your windows wide open. It will not keep the bad guys out.

## Changelog

**Other changes**

* Improved static media versioning.
* Security Key (U2F) plugin: start the U2F validation request immediately, without having to press the button on the screen.
* Security Key (U2F) plugin: do not show the confusing Validate button.
* Show TFA status in the Profile status page (before editing).

**Bug fixes**

* Missing file.
* PHP warnings on Joomla! 3.7.0 because Joomla! broke backwards compatibility, again.
* Disabling method batching doesn't display each authentication method separately in the captive page. 
* Backup Codes not shown in the authentication method selection page.
* Workaround for Joomla! Bug 16147 (https://github.com/joomla/joomla-cms/issues/16147) - Cannot access component after installation when cache is enabled
