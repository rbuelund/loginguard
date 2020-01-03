## Release highlights

**Removed unused GeoIP feature**. We have not been collecting IP addresses for well over a year. There is no point having the GeoIP plugin integration in LoginGuard anymore.

**Support for Dark Mode**. A new dark theme has been added. You can choose if you want to always enable it, have it follow the browser's preferences or disable it.

**Common PHP version warning scripts**. We have normalized the wording of warnings about old, End of Life and dangerously old PHP versions. You will get a reminder to update PHP if it has entered its final year of support, a warning to update PHP if it has recently become End of Life, a much more urgent warning if it's been End of Life for over 6 months and an error if it's no longer supported by our software.

## Joomla and PHP Compatibility

Akeeba LoginGuard is compatible with Joomla! 3.8 and 3.9.

Akeeba LoginGuard requires at least PHP 7.1. It's also compatible with PHP 7.2, 7.3 and 7.4.

We strongly recommend using the latest published Joomla! version and PHP 7.3 or later _for optimal security of your site_.

**IMPORTANT!** We are dropping support for all versions of PHP which are [officially considered End Of Life (EOL) by the PHP project](http://php.net/eol.php) a few months after they go EOL. These versions of PHP _no longer receive bug fixes or security updates_ and MUST NOT be used on production sites.

## Changelog

**New**

* Support for Dark Mode
* Common PHP version warning scripts

**Removed features**

* We do not need the GeoIP plugin integration since 3.0.0; related functionality has been removed

**Bug fixes**

* You could see an inactive (therefore confusing) 2SV method registration page while not logged in.
* Joomla's forced password reset makes LoginGuard go into an infinite redirection loop (gh-76)