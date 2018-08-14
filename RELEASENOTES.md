## Release highlights

**Rewritten interface using the FOF framework**. This version of Akeeba LoginGuard uses our FOF framework instead of Joomla's core MVC framework. This allows us to support a wider variety of Joomla! versions more easily and with much less pain for you.
 
**Rewritten interface using our own CSS framework**. We have created our own CSS framework which works across different Joomla! versions and even with templates which don't use Bootstrap (the official CSS framework of Joomla! itself). This makes our software better looking in even more sites without customization right out of the box.

**Preliminary Joomla! 4 compatibility (tested against 4.0.0 Alpha 2)**. Our software includes preliminary compatibility for Joomla! 4. We have tested our software against Joomla! 4.0.0 Alpha 2. We cannot promise that this will ensure compatibility with Joomla! 4 stable. Depending on how Joomla! 4 development proceeds we _may_ have to postpone or temporarily suspend Joomla! 4 compatibility in the future.

**Minimum requirements increased to PHP 5.4 or later. Tested up to and including PHP 7.2.**. This version of our software no longer supports PHP 5.3. Please note that PHP 5.3 has been [end of life since August 14th, 2014](http://php.net/eol.php).

For more information and documentation for administrators, users and developers please [consult the documentation Wiki](https://github.com/akeeba/loginguard/wiki).
 
## Joomla and PHP Compatibility

Akeeba LoginGuard is compatible with Joomla! 3.4, 3.5, 3.6, 3.7 and 3.8. Preliminary support for Joomla! 4 is present but does not guarantee compatibility with a future release of Joomla! 4 stable and be postponed or temporarily suspended depending on Joomla 4's development.

Akeeba LoginGuard requires PHP 5.4 or later. It's also compatible with PHP 5.5, 5.6, 7.0, 7.1 and 7.2.

We strongly recommend using the latest published Joomla! version and PHP 7.0 or later _for optimal security of your site_. It makes no sense adding two step login verification to a site that's running vulnerable software. It's like locking your door and leaving your windows wide open. It will not keep the bad guys out.

## Changelog

**Other changes**

* Rewritten interface using the FOF framework
* Rewritten interface using our own CSS framework
* Preliminary Joomla! 4 compatibility (tested against 4.0.0 Alpha 2)
* Minimum requirements increased to PHP 5.4 or later. Tested up to and including PHP 7.2.

**Bug fixes**

* PHP Notice when the user does not have any backup codes (it can only happen if you tamper with the database).
