## Release highlights

**See which users have 2SV enabled or not**. A new page in the backend of the component gives you an overview of the 2SV status of your users.
 
**Ability to force-disable TSV for specific user groups**. Highly discouraged. In some cases you may want to prevent certain user groups from applying two step verification. This leads to decreased security of their accounts. Use wisely or, better yet, do not use it at all.

**Forced 2SV for specific user groups (gh-49)**. Require certain user groups to enable two step verification for their accounts. They won't be allowed access to the site until they enable 2SV on their accounts. This is strongly recommended for anyone who can publish information directly or make changes to your site (not just Super Users but also Administrators, Publishers, Editors etc).

For more information and documentation for administrators, users and developers please [consult the documentation Wiki](https://github.com/akeeba/loginguard/wiki).
 
## Joomla and PHP Compatibility

Akeeba LoginGuard is compatible with Joomla! 3.4, 3.5, 3.6, 3.7 and 3.8.

Akeeba LoginGuard requires PHP 5.4 or later. It's also compatible with PHP 5.5, 5.6, 7.0, 7.1 and 7.2.

We strongly recommend using the latest published Joomla! version and PHP 7.2 or later _for optimal security of your site_. It makes no sense adding two step login verification to a site that's running vulnerable software. It's like locking your door and leaving your windows wide open. It will not keep the bad guys out.

## Changelog

**New**

* Users page to see which users have 2SV enabled or not
* Ability to force-disable TSV for specific user groups
* gh-49 Forced 2SV for specific user groups

**Other changes**

* Joomla! 3.9 backend Components menu item compatibility
* Allow com_ajax in the captive page (used by cookie banners and similar)

**Bug fixes**

* U2F might fail on Firefox due to a missing semicolon
