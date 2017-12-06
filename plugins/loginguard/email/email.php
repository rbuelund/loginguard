<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

if (!class_exists('LoginGuardAuthenticator', true))
{
	require_once JPATH_ADMINISTRATOR . '/components/com_loginguard/helpers/authenticator.php';
}

/**
 * Akeeba LoginGuard Plugin for Two Step Verification method "Authentication Code by PushBullet"
 *
 * Requires entering a 6-digit code sent to the user through PushBullet. These codes change automatically every 30 seconds.
 */
class PlgLoginguardEmail extends JPlugin
{
	/**
	 * The TFA method name handled by this plugin
	 *
	 * @var   string
	 */
	private $tfaMethodName = 'email';

	/**
	 * Constructor. Loads the language files as well.
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An optional associative array of configuration settings.
	 *                             Recognized key values include 'name', 'group', 'params', 'language'
	 *                             (this list is not meant to be comprehensive).
	 */
	public function __construct($subject, array $config = array())
	{
		parent::__construct($subject, $config);

		// Load the language files
		$this->loadLanguage();
	}

	/**
	 * Gets the identity of this TFA method
	 *
	 * @return  array|false
	 */
	public function onLoginGuardTfaGetMethod()
	{
		$helpURL = $this->params->get('helpurl', 'https://github.com/akeeba/loginguard/wiki/Email');

		return array(
			// Internal code of this TFA method
			'name'          => $this->tfaMethodName,
			// User-facing name for this TFA method
			'display'       => JText::_('PLG_LOGINGUARD_EMAIL_LBL_DISPLAYEDAS'),
			// Short description of this TFA method displayed to the user
			'shortinfo'     => JText::_('PLG_LOGINGUARD_EMAIL_LBL_SHORTINFO'),
			// URL to the logo image for this method
			'image'         => 'media/plg_loginguard_email/images/email.svg',
			// Are we allowed to disable it?
			'canDisable'    => true,
			// Are we allowed to have multiple instances of it per user?
			'allowMultiple' => false,
			// URL for help content
			'help_url' => $helpURL,
		);
	}

	/**
	 * Returns the information which allows LoginGuard to render the TFA setup page. This is the page which allows the
	 * user to add or modify a TFA method for their user account. If the record does not correspond to your plugin
	 * return an empty array.
	 *
	 * @param   stdClass  $record  The #__loginguard_tfa record currently selected by the user.
	 *
	 * @return  array
	 */
	public function onLoginGuardTfaGetSetup($record)
	{
		$helpURL  = $this->params->get('helpurl', 'https://github.com/akeeba/loginguard/wiki/Email');

		// Make sure we are actually meant to handle this method
		if ($record->method != $this->tfaMethodName)
		{
			return array();
		}

		// Load the options from the record (if any)
		$options = $this->_decodeRecordOptions($record);
		$key     = isset($options['key']) ? $options['key'] : '';

		// If there's a key in the session use that instead.
		$session = JFactory::getSession();
		$key     = $session->get('emailcode.key', $key, 'com_loginguard');

		// Initialize objects
		$totp = new LoginGuardAuthenticator();

		// If there's still no key in the options, generate one and save it in the session
		if (empty($key))
		{
			$key = $totp->generateSecret();
			$session->set('emailcode.key', $key, 'com_loginguard');
		}

		$session->set('emailcode.user_id', $record->user_id, 'com_loginguard');

		// Send an email message with a new code and ask the user to enter it.
		$user = JFactory::getUser($record->user_id);
		$this->sendCode($key, $user);

		return array(
			// Default title if you are setting up this TFA method for the first time
			'default_title'  => JText::_('PLG_LOGINGUARD_EMAIL_LBL_DISPLAYEDAS'),
			// Custom HTML to display above the TFA setup form
			'pre_message'    => '',
			// Heading for displayed tabular data. Typically used to display a list of fixed TFA codes, TOTP setup parameters etc
			'table_heading'  => '',
			// Any tabular data to display (label => custom HTML). See above
			'tabular_data'   => array(),
			// Hidden fields to include in the form (name => value)
			'hidden_data'    => array(
				'key' => $key,
			),
			// How to render the TFA setup code field. "input" (HTML input element) or "custom" (custom HTML)
			'field_type'     => 'input',
			// The type attribute for the HTML input box. Typically "text" or "password". Use any HTML5 input type.
			'input_type'     => 'text',
			// Pre-filled value for the HTML input box. Typically used for fixed codes, the fixed YubiKey ID etc.
			'input_value'    => '',
			// Placeholder text for the HTML input box. Leave empty if you don't need it.
			'placeholder'    => JText::_('PLG_LOGINGUARD_EMAIL_LBL_SETUP_PLACEHOLDER'),
			// Label to show above the HTML input box. Leave empty if you don't need it.
			'label'          => JText::_('PLG_LOGINGUARD_EMAIL_LBL_SETUP_LABEL'),
			// Custom HTML. Only used when field_type = custom.
			'html'           => '',
			// Should I show the submit button (apply the TFA setup)? Only applies in the Add page.
			'show_submit'    => true,
			// onclick handler for the submit button (apply the TFA setup)?
			'submit_onclick' => '',
			// Custom HTML to display below the TFA setup form
			'post_message'   => '',
			// URL for help content
			'help_url' => $helpURL,
		);
	}

	/**
	 * Parse the input from the TFA setup page and return the configuration information to be saved to the database. If
	 * the information is invalid throw a RuntimeException to signal the need to display the editor page again. The
	 * message of the exception will be displayed to the user. If the record does not correspond to your plugin return
	 * an empty array.
	 *
	 * @param   stdClass  $record  The #__loginguard_tfa record currently selected by the user.
	 * @param   JInput    $input   The user input you are going to take into account.
	 *
	 * @return  array  The configuration data to save to the database
	 *
	 * @throws  RuntimeException  In case the validation fails
	 */
	public function onLoginGuardTfaSaveSetup($record, JInput $input)
	{
		// Make sure we are actually meant to handle this method
		if ($record->method != $this->tfaMethodName)
		{
			return array();
		}

		$session = JFactory::getSession();

		// Load the options from the record (if any)
		$options = $this->_decodeRecordOptions($record);
		$key     = isset($options['key']) ? $options['key'] : '';

		// If there is no key in the options fetch one from the session
		if (empty($key))
		{
			$key = $session->get('emailcode.key', null, 'com_loginguard');
		}

		// If there is still no key in the options throw an error
		if (empty($key))
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		/**
		 * If the code is empty but the key already existed in $options someone is simply changing the title / default
		 * method status. We can allow this and stop checking anything else now.
		 */
		$code = $input->getInt('code');

		if (empty($code) && !empty($optionsKey))
		{
			return $options;
		}

		// In any other case validate the submitted code
		$totp = new LoginGuardAuthenticator();
		$isValid = $totp->checkCode($key, $code);

		if (!$isValid)
		{
			throw new RuntimeException(JText::_('PLG_LOGINGUARD_EMAIL_ERR_INVALID_CODE'), 500);
		}

		// The code is valid. Unset the key from the session.
		$session->set('totp.key', null, 'com_loginguard');

		// Return the configuration to be serialized
		return array(
			'key'   => $key,
		);
	}

	/**
	 * Returns the information which allows LoginGuard to render the captive TFA page. This is the page which appears
	 * right after you log in and asks you to validate your login with TFA.
	 *
	 * @param   stdClass  $record  The #__loginguard_tfa record currently selected by the user.
	 *
	 * @return  array
	 */
	public function onLoginGuardTfaCaptive($record)
	{
		// Make sure we are actually meant to handle this method
		if ($record->method != $this->tfaMethodName)
		{
			return array();
		}

		// Load the options from the record (if any)
		$options = $this->_decodeRecordOptions($record);
		$key     = isset($options['key']) ? $options['key'] : '';
		$helpURL = $this->params->get('helpurl', 'https://github.com/akeeba/loginguard/wiki/Email');

		// Send an email message with a new code and ask the user to enter it.
		$user = JFactory::getUser($record->user_id);
		$this->sendCode($key, $user);

		return array(
			// Custom HTML to display above the TFA form
			'pre_message'  => '',
			// How to render the TFA code field. "input" (HTML input element) or "custom" (custom HTML)
			'field_type'   => 'input',
			// The type attribute for the HTML input box. Typically "text" or "password". Use any HTML5 input type.
			'input_type'   => 'text',
			// Placeholder text for the HTML input box. Leave empty if you don't need it.
			'placeholder'  => JText::_('PLG_LOGINGUARD_EMAIL_LBL_SETUP_PLACEHOLDER'),
			// Label to show above the HTML input box. Leave empty if you don't need it.
			'label'        => JText::_('PLG_LOGINGUARD_EMAIL_LBL_SETUP_LABEL'),
			// Custom HTML. Only used when field_type = custom.
			'html'         => '',
			// Custom HTML to display below the TFA form
			'post_message' => '',
			// URL for help content
			'help_url'     => $helpURL,
		);
	}

	/**
	 * Validates the Two Factor Authentication code submitted by the user in the captive Two Step Verification page. If
	 * the record does not correspond to your plugin return FALSE.
	 *
	 * @param   stdClass  $record  The TFA method's record you're validatng against
	 * @param   JUser     $user    The user record
	 * @param   string    $code    The submitted code
	 *
	 * @return  bool
	 */
	public function onLoginGuardTfaValidate($record, JUser $user, $code)
	{
		// Make sure we are actually meant to handle this method
		if ($record->method != $this->tfaMethodName)
		{
			return false;
		}

		// Double check the TFA method is for the correct user
		if ($user->id != $record->user_id)
		{
			return false;
		}

		// Load the options from the record (if any)
		$options = $this->_decodeRecordOptions($record);
		$key = isset($options['key']) ? $options['key'] : '';

		// If there is no key in the options throw an error
		if (empty($key))
		{
			return false;
		}

		// Check the TFA code for validity
		$totp = new LoginGuardAuthenticator();
		return $totp->checkCode($key, $code);
	}

	/**
	 * Decodes the options from a #__loginguard_tfa record into an options object.
	 *
	 * @param   stdClass  $record
	 *
	 * @return  array
	 */
	private function _decodeRecordOptions($record)
	{
		$options = array(
			'key'   => '',
		);

		if (!empty($record->options))
		{
			$recordOptions = $record->options;

			if (is_string($recordOptions))
			{
				$recordOptions = json_decode($recordOptions, true);
			}

			$options = array_merge($options, $recordOptions);
		}

		return $options;
	}

	/**
	 * Creates a new TOTP code based on secret key $key and sends it to the user via email.
	 *
	 * @param   string  $key    The TOTP secret key
	 * @param   JUser   $user   The Joomla! user to use
	 *
	 * @return  void
	 *
	 * @throws  LoginGuardPushbulletApiException  If something goes wrong
	 */
	public function sendCode($key, JUser $user = null)
	{
		// Make sure we have a user
		if (!is_object($user) || !($user instanceof JUser))
		{
			$user = JFactory::getUser();
		}

		// Get the API objects
		$totp = new LoginGuardAuthenticator();

		// Create the list of variable replacements
		$code = $totp->getCode($key);

		$replacements = array(
			'[CODE]'     => $code,
			'[SITENAME]' => JFactory::getConfig()->get('sitename'),
			'[SITEURL]'  => JUri::base(),
			'[USERNAME]' => $user->username,
			'[EMAIL]'    => $user->email,
			'[FULLNAME]' => $user->name,
		);

		// Get the title and body of the e-mail message
		$subject = JText::_('PLG_LOGINGUARD_EMAIL_MESSAGE_SUBJECT');
		$subject = str_ireplace(array_keys($replacements), array_values($replacements), $subject);
		$body = JText::_('PLG_LOGINGUARD_EMAIL_MESSAGE_BODY');
		$body = str_ireplace(array_keys($replacements), array_values($replacements), $body);

		// Send email
		$mailer = JFactory::getMailer();
		$mailer->setSubject($subject);
		$mailer->setBody($body);
		$mailer->addRecipient($user->email, $user->name);
		$mailer->Send();
	}
}
