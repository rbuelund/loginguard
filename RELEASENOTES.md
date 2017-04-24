## Release highlights
 
**More Second Step Verification methods**. You can now send authentication codes to users using e-mail, push messages or texts (SMS).

**Compatible with Joomla's Remember Me feature**. When you are logged back into the site through Joomla!'s Remember Me feature's cookies you will not be asked to re-authenticate. 
 
For more information and documentation for administrators, users and developers please [consult the documentation Wiki](https://github.com/akeeba/loginguard/wiki).
 
## Joomla and PHP Compatibility

Akeeba LoginGuard is compatible with Joomla! 3.4, 3.5, 3.6 and 3.7. It requires PHP 5.3.10 or later, the same minimum PHP version as Joomla! itself. It's also compatible with PHP 5.4, 5.5, 5.6, 7.0 and 7.1.

We strongly recommend using the latest published Joomla! version and PHP 7.0 or 7.1 later _for optimal security of your site_. It makes no sense adding two step login verification to a site that's running vulnerable software. It's like locking your door and leaving your windows wide open. It will not keep the bad guys out.  

## Changelog

**New features**

* Send authentication code by email
* Send authentication code by push message (using PushBullet)
* Send authentication code by mobile text message (using SMSAPI.com)
* Don't ask for 2SV when the Remember Me plugin logs you back in

**Bug fixes**

* The query disappears from the URL after authenticating the second factor
* You can see the first time setup page after logging out
* Some browser and server combinations end up with the browser sending double requests to the captive login page making U2F authentication all but impossible.
* Missing file
