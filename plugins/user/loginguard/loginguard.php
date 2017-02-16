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
}
