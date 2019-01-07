## Release highlights

**Disable U2F on unsupported browsers**. FIDO U2F (Security Keys) were made available for all browsers, even those which do not allow Security Keys to be used at all or reasonably. This version disables U2F completely on these browsers. Security implication: you should _ALWAYS_ set up a fallback login method other than Security Key and Backup Codes. For the reasoning behind this change please see issue #66 on this repository.
 
## Joomla and PHP Compatibility

Akeeba LoginGuard is compatible with Joomla! 3.4, 3.5, 3.6, 3.7, 3.8 and 3.9.

Akeeba LoginGuard requires PHP 5.4 or later. It's also compatible with PHP 5.5, 5.6, 7.0, 7.1, 7.2 and 7.3. The next version will drop support for PHP 5.4 and 5.5.

We strongly recommend using the latest published Joomla! version and PHP 7.2 or later _for optimal security of your site_.

**IMPORTANT!** Starting Summer 2019 we will drop support for all versions of PHP which are [officially considered End Of Life (EOL) by the PHP project](http://php.net/eol.php). EOL  versions of PHP _no longer receive security updates_ and MUST NOT be used on production sites. 

## Changelog

**Other changes**

* Disable U2F on unsupported browsers (gh-66). 

**Bug fixes**

* Backup Codes displayed twice in the "Select a second step method" page (gh-60).
