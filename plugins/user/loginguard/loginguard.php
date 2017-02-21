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

		if (!LoginGuardHelperTfa::isAdminPage() && (JFactory::getApplication()->input->getCmd('layout', 'default') != 'edit'))
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
		$form->loadFile('loginguard', false);

		return true;
	}

	public function onUserAfterSave($data, $isNew, $result, $error)
	{
		$userId	= JArrayHelper::getValue($data, 'id', 0, 'int');

		if ($userId && $result && isset($data['ats']) && (count($data['ats'])))
		{
            $db = JFactory::getDbo();

            $query = $db->getQuery(true)
                        ->delete($db->qn('#__user_profiles'))
                        ->where($db->qn('user_id').' = '.$db->q($userId))
                        ->where($db->qn('profile_key').' LIKE '.$db->q('ats.%', false));

            $db->setQuery($query)->execute();

            $order	= 1;

            $query = $db->getQuery(true)
                        ->insert($db->qn('#__user_profiles'))
                        ->columns(array($db->qn('user_id'), $db->qn('profile_key'), $db->qn('profile_value'), $db->qn('ordering')));

            foreach ($data['ats'] as $k => $v)
            {
                $query->values($userId.', '.$db->quote('ats.'.$k).', '.$db->quote($v).', '.$order++);
            }

            $db->setQuery($query)->execute();
		}

		return true;
	}

    /**
     * Remove all user profile information for the given user ID
     *
     * Method is called after user data is deleted from the database
     *
     * @param    array $user Holds the user data
     * @param    boolean $success True if user was succesfully stored in the database
     * @param    string $msg Message
     *
     * @return bool
     *
     * @throws Exception
     */
	public function onUserAfterDelete($user, $success, $msg)
	{
		if (!$success)
        {
			return false;
		}

		$userId	= JArrayHelper::getValue($user, 'id', 0, 'int');

		if ($userId)
		{
            $db = JFactory::getDbo();

            $query = $db->getQuery(true)
                        ->delete($db->qn('#__user_profiles'))
                        ->where($db->qn('user_id').' = '.$db->q($userId))
                        ->where($db->qn('profile_key').' LIKE '.$db->q('ats.%', false));

            $db->setQuery($query)->execute();
		}

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
		if ($this->needsTFA($user))
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
	 * @return  bool
	 */
	private function needsTFA(JUser $user)
	{
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
