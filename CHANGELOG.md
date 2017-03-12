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