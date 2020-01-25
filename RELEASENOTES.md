## Release highlights

**Browser fingerprinting to reduce 2SV prompts**. On popular request, we added an optional feature to disable 2SV prompts for a period of time as long as the user is logging in from a device and browser previously marked as secure. 

## Joomla and PHP Compatibility

Akeeba LoginGuard is compatible with Joomla! 3.8 and 3.9.

Akeeba LoginGuard requires at least PHP 7.1. It's also compatible with PHP 7.2, 7.3 and 7.4.

We strongly recommend using the latest published Joomla! version and PHP 7.3 or later _for optimal security of your site_.

**IMPORTANT!** We are dropping support for all versions of PHP which are [officially considered End Of Life (EOL) by the PHP project](http://php.net/eol.php) a few months after they go EOL. These versions of PHP _no longer receive bug fixes or security updates_ and MUST NOT be used on production sites.

## Changelog

**New**

* Browser fingerprinting to reduce 2SV prompts

**Bug fixes**

* Dark Mode “Auto” setting ended up being the same as “Always”