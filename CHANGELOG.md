# 3.3.1

**New**

* LoginGuard will fix and update its database if necessary when you visit its backend page as a Super User
* Joomla 4 compatible

**Other Changes**

* Internal changes to use proper-cased views everywhere instead of legacy task=viewname.taskname when building public URLs
* Do not go through browser fingerprinting if the Remember Me feature is disabled at the component level.

**Bug fixes**

* Unable to change 2SV method on Chrome when caching is enabled either at the Joomla or web server level.

# 3.3.0

**New**

* Browser fingerprinting to reduce 2SV prompts

**Bug fixes**

* Dark Mode “Auto” setting ended up being the same as “Always”
* U2F and WebAuthn do not show a verification button if your browser / hardware cancels the verification [gh-80]
* Missing file css/dark.min.css from the media folder
* Cannot access backend if you have TFA enabled and you're not a Super User (or have the core.manage privilege for LoginGuard)

# 3.2.1

**New**

* Support for Dark Mode
* Common PHP version warning scripts

**Removed features**

* We do not need the GeoIP plugin integration since 3.0.0; related functionality has been removed

**Bug fixes**

* You could see an inactive (therefore confusing) 2SV method registration page while not logged in.
* Joomla's forced password reset makes LoginGuard go into an infinite redirection loop (gh-76)

# 3.2.0

**New**

* Joomla User Actions Log integration

**Bug fixes**

* Security: Guest users can view a list of Two Step Verification method name for all users (but NOT their settings; 2SV security was NOT compromised).

# 3.1.1

**Bug fixes**

* User Profile fields not displayed correctly when using an Edit Profile menu item

# 3.1.0

**New**

* Option to disable Two Step Verification on silent login (e.g. when Remember Me is used)

**Other changes**

* Dropped support for Joomla! 3.4, 3.5, 3.6, 3.7 as well as PHP 5.4 and 5.5
* Protection of all component and plugin folders against direct web access
* Always allow entry batching for Security Key, WebAuthn and YubiKey

**Bug fixes**

* Backup Codes were not selectable during verification

# 3.0.4

**Other changes**

* Disable U2F on unsupported browsers (gh-66). 

**Bug fixes**

* Backup Codes displayed twice in the "Select a second step method" page (gh-60).

# 3.0.3

**New**

* Working around Joomla! 3.9's Privacy Consent breaking captive login.

# 3.0.2

**Security update**

* Fixed an authenticated security bypass which could be used to disable two step verification, discovered by Ivaylo V. 

# 3.0.1

**New**

* Users page to see which users have 2SV enabled or not
* Ability to force-disable TSV for specific user groups
* gh-49 Forced 2SV for specific user groups

**Other changes**

* Joomla! 3.9 backend Components menu item compatibility
* Allow com_ajax in the captive page (used by cookie banners and similar)

**Bug fixes**

* U2F might fail on Firefox due to a missing semicolon

# 3.0.0

**Other changes**

* Fully refactored to make better use of FOF 3 features
* Automatic encryption of TFA settings for GDPR compliance (only affects TFA methods which are newly created or used after installing this version)

**Removed features**

* IP addresses and User Agents are no longer collected for GDPR compliance.

**Bug fixes**

* The Log Out button results in an invalid token error (h/t Ivaylo V. for the heads up)

# 2.0.1

**Removed features**

* Removed unused GeoIP feature

**Other changes**

* Using the built-in FOF page renderer instead of a custom one

**Bug fixes**

* TOTP (Google Authenticator) plugin does not display the QR code due to missing JS file
* Leftover records after the user is deleted from Joomla

# 2.0.0

**New**

* Rewritten interface using the FOF framework
* Rewritten interface using our own CSS framework
* Preliminary Joomla! 4 compatibility (tested against 4.0.0 Alpha 2)
* Minimum requirements increased to PHP 5.4 or later. Tested up to and including PHP 7.2.
* Warn the user if either FOF or FEF is not installed.

**Bug fixes**

* PHP Notice when the user does not have any backup codes (it can only happen if you tamper with the database).
* Google's QR code API was deprecated, breaking the TOTP authentication plugin (gh-38)

# 1.2.1

**Other changes**

* Make the intent of Backup Codes more obvious
* Auto-focus the two step verification field
* Do not escape the LoginGuard method title (allows for title formatting, e.g. with the backup codes method) 

**Bug fixes**

* The emergency backup codes could be reused

# 1.2.0

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

# 1.1.1

**Bug fixes**

* Missing file

# 1.1.0

**New features**

* Send authentication code by email
* Send authentication code by push message (using PushBullet)
* Send authentication code by mobile text message (using SMSAPI.com)
* Don't ask for 2SV when the Remember Me plugin logs you back in

**Bug fixes**

* The query disappears from the URL after authenticating the second factor
* You can see the first time setup page after logging out
* Some browser and server combinations end up with the browser sending double requests to the captive login page making U2F authentication all but impossible.

# 1.0.0

**New features**
* Two Step Verification for the front- and backend of your Joomla! site.
* Verification with Google Authenticator and compatible applications.
* Verification with YubiKey in OTP mode using the Yubico or custom validation servers.
* Verification with U2F hardware keys on Google Chrome (Linux, Windows, macOS, Android), Firefox (Linux, Windows, macOS) and Opera (Linux, Windows, macOS).
* Migrate settings from Joomla's Two Factor Authentication and our legacy Akeeba YubiKey Plugins for Joomla! Two Factor Authentication.
* Optional. Let your users manage their Two Step Verification settings from their user profile edit page.
* Optional. Automatically show a page where your users can set up Two Step Verification if they haven't done already (displays either the default page or a custom article).

**Other changes**
* Use the new U2F API 1.1
* Consistency of Confirm button appearing below the form, even in the backend.
* The plugins now put their data in the media folder, following Joomla's best practices. 

**Bug fixes**
* The submit button wasn't shown on the edit method page when using U2F.
* Try to notify when U2F is not supported by the browser instead of silently failing.
