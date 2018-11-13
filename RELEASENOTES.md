## SECURITY UPDATE

We fixed an authenticated security bypass which could be used to disable two step verification. This issue was discovered by Ivaylo V. on October 31st, 2018. We fixed this and released a new version within a few minutes of disclosure.

We recommend that all users update immediately. All previous versions of the software are affected.

## Release highlights

* **Security release**

For more documentation for administrators, users and developers please [consult the documentation Wiki](https://github.com/akeeba/loginguard/wiki).
 
## Joomla and PHP Compatibility

Akeeba LoginGuard is compatible with Joomla! 3.4, 3.5, 3.6, 3.7, 3.8 and 3.9.

Akeeba LoginGuard requires PHP 5.4 or later. It's also compatible with PHP 5.5, 5.6, 7.0, 7.1, 7.2 and 7.3. The next version will drop support for PHP 5.4 and 5.5.

We strongly recommend using the latest published Joomla! version and PHP 7.2 or later _for optimal security of your site_.

## Changelog

**New**

* Working around Joomla! 3.9's Privacy Consent breaking captive login.
