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
	JLoader::register('LoginGuardAuthenticator', JPATH_ADMINISTRATOR . '/components/com_loginguard/helpers/authenticator.php');
}

if (!class_exists('LoginGuardPushbulletApi', true))
{
	JLoader::register('LoginGuardPushbulletApi', __DIR__ . '/classes/pushbullet.php');
}

/**
 * Akeeba LoginGuard Plugin for Two Step Verification method "Authentication Code by PushBullet"
 *
 * Requires entering a 6-digit code sent to the user through PushBullet. These codes change automatically every 30 seconds.
 */
class PlgLoginguardPushbullet extends JPlugin
{
	/**
	 * The PushBullet access token for the PushBullet account which owns the PushBullet OAuth Client defined by the
	 * clientId and secret below.
	 *
	 * @var   string
	 */
	public $accessToken;

	/**
	 * PushBullet OAuth2 Client ID
	 *
	 * @var   string
	 */
	public $clientId;

	/**
	 * PushBullet OAuth2 Secret ID
	 *
	 * @var   string
	 */
	private $secret;

	/**
	 * The TFA method name handled by this plugin
	 *
	 * @var   string
	 */
	private $tfaMethodName = 'pushbullet';

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

		// Load the PushBullet API parameters
		/** @var \Joomla\Registry\Registry $params */
		$params = $this->params;

		$this->accessToken = $params->get('access_token', null);
		$this->clientId    = $params->get('client_id', null);
		$this->secret      = $params->get('secret', null);

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
		// This plugin is disabled if you haven't configured it yet
		if (empty($this->accessToken) || empty($this->clientId) || empty($this->secret))
		{
			return false;
		}

		$helpURL = $this->params->get('helpurl', 'https://github.com/akeeba/loginguard/wiki/Pushbullet');

		return array(
			// Internal code of this TFA method
			'name'          => $this->tfaMethodName,
			// User-facing name for this TFA method
			'display'       => JText::_('PLG_LOGINGUARD_PUSHBULLET_LBL_DISPLAYEDAS'),
			// Short description of this TFA method displayed to the user
			'shortinfo'     => JText::_('PLG_LOGINGUARD_PUSHBULLET_LBL_SHORTINFO'),
			// URL to the logo image for this method
			'image'         => 'media/plg_loginguard_pushbullet/images/pushbullet.png',
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
		$helpURL  = $this->params->get('helpurl', 'https://github.com/akeeba/loginguard/wiki/Authenticator-App');

		// Make sure we are actually meant to handle this method
		if ($record->method != $this->tfaMethodName)
		{
			return array();
		}

		// Load the options from the record (if any)
		$options = $this->_decodeRecordOptions($record);
		$key     = isset($options['key']) ? $options['key'] : '';
		$token   = isset($options['token']) ? $options['token'] : '';

		// If there's a key or toekn in the session use that instead.
		$session = JFactory::getSession();
		$key     = $session->get('pushbullet.key', $key, 'com_loginguard');
		$token   = $session->get('pushbullet.token', $token, 'com_loginguard');

		// Initialize objects
		$totp = new LoginGuardAuthenticator();

		// If there's still no key in the options, generate one and save it in the session
		if (empty($key))
		{
			$key = $totp->generateSecret();
			$session->set('pushbullet.key', $key, 'com_loginguard');
		}

		$session->set('pushbullet.user_id', $record->user_id, 'com_loginguard');

		// If there is no token we need to show the OAuth2 button
		if (empty($token))
		{
			$layoutPath = JPluginHelper::getLayoutPath('loginguard', 'pushbullet', 'oauth2');
			ob_start();
			include $layoutPath;
			$html = ob_get_clean();

			return array(
				// Default title if you are setting up this TFA method for the first time
				'default_title'  => JText::_('PLG_LOGINGUARD_PUSHBULLET_LBL_DISPLAYEDAS'),
				// Custom HTML to display above the TFA setup form
				'pre_message'    => JText::_('PLG_LOGINGUARD_PUSHBULLET_LBL_SETUP_INSTRUCTIONS'),
				// Heading for displayed tabular data. Typically used to display a list of fixed TFA codes, TOTP setup parameters etc
				'table_heading'  => '',
				// Any tabular data to display (label => custom HTML). See above
				'tabular_data'   => array(),
				// Hidden fields to include in the form (name => value)
				'hidden_data'    => array(),
				// How to render the TFA setup code field. "input" (HTML input element) or "custom" (custom HTML)
				'field_type'     => 'custom',
				// The type attribute for the HTML input box. Typically "text" or "password". Use any HTML5 input type.
				'input_type'     => '',
				// Pre-filled value for the HTML input box. Typically used for fixed codes, the fixed YubiKey ID etc.
				'input_value'    => '',
				// Placeholder text for the HTML input box. Leave empty if you don't need it.
				'placeholder'    => '',
				// Label to show above the HTML input box. Leave empty if you don't need it.
				'label'          => '',
				// Custom HTML. Only used when field_type = custom.
				'html'           => $html,
				// Should I show the submit button (apply the TFA setup)? Only applies in the Add page.
				'show_submit'    => false,
				// onclick handler for the submit button (apply the TFA setup)?
				'submit_onclick' => '',
				// Custom HTML to display below the TFA setup form
				'post_message'   => '',
				// URL for help content
				'help_url' => $helpURL,
			);

		}

		// We have a token and a key. Send a push message with a new code and ask the user to enter it.
		$this->sendCode($key, $token);

		return array(
			// Default title if you are setting up this TFA method for the first time
			'default_title'  => JText::_('PLG_LOGINGUARD_PUSHBULLET_LBL_DISPLAYEDAS'),
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
			'placeholder'    => JText::_('PLG_LOGINGUARD_PUSHBULLET_LBL_SETUP_PLACEHOLDER'),
			// Label to show above the HTML input box. Leave empty if you don't need it.
			'label'          => JText::_('PLG_LOGINGUARD_PUSHBULLET_LBL_SETUP_LABEL'),
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
		$token   = isset($options['toke']) ? $options['tokens'] : '';

		// If there is no key in the options fetch one from the session
		if (empty($key))
		{
			$key = $session->get('pushbullet.key', null, 'com_loginguard');
		}

		// If there is no key in the options fetch one from the session
		if (empty($token))
		{
			$token = $session->get('pushbullet.token', null, 'com_loginguard');
		}

		// If there is still no key in the options throw an error
		if (empty($key))
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// If there is still no token in the options throw an error
		if (empty($token))
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
			throw new RuntimeException(JText::_('PLG_LOGINGUARD_PUSHBULLET_ERR_INVALID_CODE'), 500);
		}

		// The code is valid. Unset the key from the session.
		$session->set('totp.key', null, 'com_loginguard');

		// Return the configuration to be serialized
		return array(
			'key'   => $key,
			'token' => $token
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
		$token   = isset($options['token']) ? $options['token'] : '';
		$helpURL = $this->params->get('helpurl', 'https://github.com/akeeba/loginguard/wiki/Pushbullet');

		// Send a push message with a new code and ask the user to enter it.
		$this->sendCode($key, $token);

		return array(
			// Custom HTML to display above the TFA form
			'pre_message'  => '',
			// How to render the TFA code field. "input" (HTML input element) or "custom" (custom HTML)
			'field_type'   => 'input',
			// The type attribute for the HTML input box. Typically "text" or "password". Use any HTML5 input type.
			'input_type'   => 'text',
			// Placeholder text for the HTML input box. Leave empty if you don't need it.
			'placeholder'  => JText::_('PLG_LOGINGUARD_PUSHBULLET_LBL_SETUP_PLACEHOLDER'),
			// Label to show above the HTML input box. Leave empty if you don't need it.
			'label'        => JText::_('PLG_LOGINGUARD_PUSHBULLET_LBL_SETUP_LABEL'),
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
			'token' => ''
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
	 * Creates a new TOTP code based on secret key $key and sends it to the user via PushBullet using the access token
	 * $token.
	 *
	 * @param   string  $key    The TOTP secret key
	 * @param   string  $token  The PushBullet access token
	 * @param   JUser   $user   The Joomla! user to use
	 *
	 * @return  void
	 *
	 * @throws  LoginGuardPushbulletApiException  If something goes wrong
	 */
	public function sendCode($key, $token, JUser $user = null)
	{
		// Make sure we have a user
		if (!is_object($user) || !($user instanceof JUser))
		{
			$user = JFactory::getUser();
		}

		// Get the API objects
		$totp = new LoginGuardAuthenticator();
		$pushBullet = new LoginGuardPushbulletApi($token);

		// Create the list of variable replacements
		$code = $totp->getCode($key);

		$replacements = array(
			'[CODE]'     => $code,
			'[SITENAME]' => '',
			'[SITEURL]'  => JUri::base(),
			'[USERNAME]' => $user->username,
			'[EMAIL]'    => $user->email,
			'[FULLNAME]' => $user->name,
		);

		// Get the title and body of the push message
		$subject = JText::_('PLG_LOGINGUARD_PUSHBULLET_PUSH_TITLE');
		$subject = str_ireplace(array_keys($replacements), array_values($replacements), $subject);
		$message = JText::_('PLG_LOGINGUARD_PUSHBULLET_PUSH_MESSAGE');
		$message = str_ireplace(array_keys($replacements), array_values($replacements), $message);

		// Push the message to all of the user's devices
		$pushBullet->pushNote('', $subject, $message);
	}

	/**
	 * Handle the OAuth2 callback
	 *
	 * The user is redirected to the callback URL by PushBullet itself. A code is sent back as a query string parameter.
	 * The code is sent back to PushBullet and we are given back a token. What happens next depends on the state URL
	 * parameter.
	 *
	 * If state=0 the 2SV setup was initiated by the frontend of the site. Therefore we just need to save the token in
	 * the session and redirect the user back to the 2SV method setup page. This will be picked up by the
	 * onLoginGuardTfaGetSetup method and a code will be sent to the user which he has to enter to finalize the setup.
	 *
	 * If state=1 the 2SV setup was initiated by the backend of the site. The callback is always in the frontend of
	 * the site since PushBullet checks the path of the URL versus what has been configured. However, since I'm in the
	 * frontend of the site I cannot set a session variable and read it from the backend. In this case I redirect the
	 * browser to the backend callback URL passing the token as a query string parameter. When this is detected the
	 * token is read from the q.s.p. and the rest of the process described above (save to session and redirect to setup
	 * page) takes place.
	 *
	 * @param   string  $method  The 2SV method used during the callback.
	 *
	 * @return  bool  Only returns false when this plugin is not supposed to handle the request. Redirects the
	 *                application otherwise (no return value).
	 */
	public function onLoginGuardCallback($method)
	{
		if ($method != $this->tfaMethodName)
		{
			return false;
		}

		$app   = JFactory::getApplication();
		$input = $app->input;

		// Should I redirect to the back-end?
		$backend = $input->getInt('state', 0);

		// Do I have a token access variable?
		$token = $input->getString('token', null);

		// If I have no token and it's the front-end I have received a token in the URL fragment from PushBullet
		JLoader::register('LoginGuardHelperTfa', JPATH_SITE . '/components/com_loginguard/helpers/tfa.php');

		if (empty($token) && !LoginGuardHelperTfa::isAdminPage())
		{
			// The returned URL has a code query string parameter I need to use to retrieve a token
			$code  = $input->getString('code', null);
			$api   = new LoginGuardPushbulletApi($this->accessToken);
			$token = $api->getToken($code, $this->clientId, $this->secret);
		}

		// Do I have to redirect to the backend?
		if ($backend == 1)
		{
			$redirectURL = JUri::base() . 'administrator/index.php?option=com_loginguard&task=callback.callback&method=pushbullet&token=' . $token;
			$app->redirect($redirectURL);

			// Just to make IDEs happy. The application is closed above during the redirection.
			return false;
		}

		// Set the token to the session
		$session = JFactory::getSession();
		$session->set('pushbullet.token', $token, 'com_loginguard');

		// Get the User ID for the editor page
		$user_id = $session->get('pushbullet.user_id', null, 'com_loginguard');
		$session->set('pushbullet.user_id', null, 'com_loginguard');

		// Redirect to the editor page
		$userPart = empty($user_id) ? '' : ('&user_id='. $user_id);
		$redirectURL = 'index.php?option=com_loginguard&task=method.add&method=pushbullet' . $userPart;

		$app->redirect($redirectURL);

		// Just to make IDEs happy. The application is closed above during the redirection.
		return false;
	}
}