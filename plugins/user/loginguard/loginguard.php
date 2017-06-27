<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

/**
 * LoginGuard User Plugin
 *
 * Renders a button linking to the Two Step Verification setup page
 */
class plgUserLoginguard extends JPlugin
{
	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An optional associative array of configuration settings.
	 *                             Recognized key values include 'name', 'group', 'params', 'language'
	 *                             (this list is not meant to be comprehensive).
	 */
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);

		$this->loadLanguage();
	}

	/**
	 * Adds additional fields to the user editing form
	 *
	 * @param   JForm  $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return  boolean
	 */
	public function onContentPrepareForm($form, $data)
	{
		if (!($form instanceof JForm))
		{
			throw new InvalidArgumentException('JERROR_NOT_A_FORM');
		}

		// Check we are manipulating a valid form.
		$name = $form->getName();

		if (!in_array($name, array('com_admin.profile', 'com_users.user', 'com_users.profile', 'com_users.registration')))
		{
			return true;
		}

		$layout = JFactory::getApplication()->input->getCmd('layout', 'default');

		if (!LoginGuardHelperTfa::isAdminPage() && !in_array($layout, array('edit', 'default')))
		{
			return true;
		}

		// Get the user ID
		$id = null;

		if (is_array($data))
		{
			$id = isset($data['id']) ? $data['id'] : null;
		}
		elseif (is_object($data) && is_null($data) && ($data instanceof JRegistry))
		{
			$id = $data->get('id');
		}
		elseif (is_object($data) && !is_null($data))
		{
			$id = isset($data->id) ? $data->id : null;
		}

		$user = JFactory::getUser($id);

		// Make sure the loaded user is the correct one
		if ($user->id != $id)
		{
			return true;
		}

		// Make sure I am either editing myself OR I am a Super User AND I'm not editing another Super User
		if (!LoginGuardHelperTfa::canEditUser($user))
		{
			return true;
		}

		// Add the fields to the form.
		JForm::addFormPath(dirname(__FILE__) . '/loginguard');

		// Special handling for profile overview page
		if ($layout == 'default')
		{
			$tfaMethods = LoginGuardHelperTfa::getUserTfaRecords($id);

			/**
			 * We cannot pass a boolean or integer; if it's false/0 Joomla! will display "No information entered". We
			 * cannot use a list field to display it in a human readable format, Joomla! just dumps the raw value if you
			 * use such a field. So all I can do is pass raw text. Um, whatever.
			 */
			$data->loginguard = array(
				'hastfa' => (count($tfaMethods) > 0) ? JText::_('PLG_USER_LOGINGUARD_FIELD_HASTFA_ENABLED') : JText::_('PLG_USER_LOGINGUARD_FIELD_HASTFA_DISABLED')
			);

			$form->loadFile('list', false);

			return true;
		}

		// Profile edit page
		$form->loadFile('loginguard', false);

		return true;
	}

	/**
	 * Runs after successful login of the user. Used to redirect the user to a page where they can set up their Two Step
	 * Verification after logging in.
	 *
	 * @param   array  $options  Passed by Joomla. user: a JUser object; responseType: string, authentication response
	 *                           type.
	 */
	public function onUserAfterLogin($options)
	{
		// Make sure the option to redirect is set
		if (!$this->params->get('redirectonlogin', 1))
		{
			return;
		}

		// Make sure I have a valid user
		/** @var JUser $user */
		$user = $options['user'];

		if (!is_object($user) || !($user instanceof JUser))
		{
			return;
		}

		// Make sure this user does not already have 2SV enabled
		if ($this->needsTFA($user, $options['responseType']))
		{
			return;
		}

		// Make sure the user does not have a flag to not bother him again with the 2SV setup page
		if ($this->hasFlag($user))
		{
			return;
		}

		// Get the redirection URL
		$url           = JRoute::_('index.php?option=com_loginguard&task=methods.display&layout=firsttime', false);
		$configuredUrl = $this->params->get('redirecturl', null);

		if ($configuredUrl)
		{
			$url = $configuredUrl;
		}

		// Prepare to redirect
		JFactory::getSession()->set('postloginredirect', $url, 'com_loginguard');
	}

	/**
	 * Does the current user need to complete 2FA authentication before allowed to access the site?
	 *
	 * @param   JUser   $user          The user object we are checking
	 * @param   string  $responseType  The login response type (optional)
	 *
	 * @return  bool
	 */
	private function needsTFA(JUser $user, $responseType = null)
	{
		/**
		 * If the login type is silent (cookie, social login / single sign-on, gmail, ldap) we will not ask for 2SV. The
		 * login risk has already been managed by the external authentication method. For your reference, the
		 * authentication response types are as follows:
		 *
		 * - Joomla: username and password login
		 * - Cookie: "Remember Me" cookie with a secure, single use token and other safeguards for the user session
		 * - GMail: login with GMail credentials (probably no longer works)
		 * - LDAP: Joomla's LDAP plugin
		 * - SocialLogin: Akeeba Social Login (login with Facebook etc)
		 */
		$silentResponses = array('cookie', 'gmail', 'ldap', 'sociallogin');

		if (is_string($responseType) && !empty($responseType) && in_array(strtolower($responseType), $silentResponses))
		{
			return false;
		}

		// Get the user's TFA records
		$records = LoginGuardHelperTfa::getUserTfaRecords($user->id);

		// No TFA methods? Then we obviously don't need to display a captive login page.
		if (empty($records))
		{
			return false;
		}

		// Let's get a list of all currently active TFA methods
		$tfaMethods = LoginGuardHelperTfa::getTfaMethods();

		// If not TFA method is active we can't really display a captive login page.
		if (empty($tfaMethods))
		{
			return false;
		}

		// Get a list of just the method names
		$methodNames = array();

		foreach ($tfaMethods as $tfaMethod)
		{
			$methodNames[] = $tfaMethod['name'];
		}

		// Filter the records based on currently active TFA methods
		foreach($records as $record)
		{
			if (in_array($record->method, $methodNames))
			{
				// We found an active method. Show the captive page.
				return true;
			}
		}

		// No viable TFA method found. We won't show the captive page.
		return false;
	}

	/**
	 * Does the user have a "don't show this again" flag?
	 *
	 * @param   JUser  $user  The user to check
	 *
	 * @return  bool
	 */
	private function hasFlag(JUser $user)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('profile_value'))
			->from($db->qn('#__user_profiles'))
			->where($db->qn('user_id') . ' = ' . $db->q($user->id))
			->where($db->qn('profile_key') . ' = ' . $db->q('loginguard.dontshow'));

		try
		{
			$result = $db->setQuery($query)->loadResult();
		}
		catch (Exception $e)
		{
			$result = 1;
		}

		return is_null($result) ? false : ($result == 1);
	}
}
