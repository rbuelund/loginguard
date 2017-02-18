<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

JLoader::register('LoginGuardHelperTfa', JPATH_SITE . '/components/com_loginguard/helpers/tfa.php');

/**
 * Two Step Verification method management model
 */
class LoginGuardModelMethod extends JModelLegacy
{
	/**
	 * List of TFA methods
	 *
	 * @var  array
	 */
	protected $tfaMethods = null;

	/**
	 * Is the specified TFA method available?
	 *
	 * @param   string  $method      The method to check.
	 * @param   object  $dispatcher  The Joomla event dispatcher. Leave null to use the current application's dispatcher.
	 *
	 * @return  bool
	 */
	public function methodExists($method, $dispatcher = null)
	{
		if (!is_array($this->tfaMethods))
		{
			$this->populateTfaMethods($dispatcher);
		}

		return isset($this->tfaMethods[$method]);
	}

	/**
	 * Get the specified TFA method's record
	 *
	 * @param   string  $method      The method to retrieve.
	 * @param   object  $dispatcher  The Joomla event dispatcher. Leave null to use the current application's dispatcher.
	 *
	 * @return  array
	 */
	public function getMethod($method, $dispatcher = null)
	{
		if (!$this->methodExists($method, $dispatcher))
		{
			return array(
				'name'          => $method,
				'display'       => '',
				'shortinfo'     => '',
				'image'         => '',
				'canDisable'    => true,
				'allowMultiple' => true
			);
		}

		return $this->tfaMethods[$method];
	}

	/**
	 * Get the specified TFA record. It will return a fake default record when no record ID is specified.
	 *
	 * @param   JUser  $user  The user record. Null to use the currently logged in user.
	 *
	 * @return  object
	 */
	public function getRecord(JUser $user = null)
	{
		if (is_null($user))
		{
			$user = JFactory::getUser();
		}

		$defaultRecord = $this->getDefaultRecord($user);

		$id = (int) $this->getState('id', 0);

		if ($id <= 0)
		{
			return $defaultRecord;
		}

		$db    = $this->getDbo();
		$query = $db->getQuery(true)
		            ->select('*')
		            ->from($db->qn('#__loginguard_tfa'))
		            ->where($db->qn('user_id') . ' = ' . $db->q($user->id))
		            ->where($db->qn('id') . ' = ' . $db->q($id));
		try
		{
			$record = $db->setQuery($query)->loadObject();
		}
		catch (Exception $e)
		{
			return $defaultRecord;
		}

		if (!$this->methodExists($record->method))
		{
			return $defaultRecord;
		}

		return $record;
	}

	/**
	 * Save a TFA record to the database
	 *
	 * @param   stdClass  $record
	 *
	 * @return  void
	 *
	 * @throws  RuntimeException  When the database insert / update fails (thrown from JDatabaseDriver in recent Joomla! versions)
	 */
	public function saveRecord(&$record)
	{
		$db = $this->getDbo();

		// Get existing records for this user EXCEPT the current record
		$records = LoginGuardHelperTfa::getUserTfaRecords($record->user_id);

		if ($record->id)
		{
			$allRecords = $records;
			$records    = array();

			foreach ($allRecords as $rec)
			{
				if ($rec->id == $record->id)
				{
					continue;
				}

				$records[] = $rec;
			}
		}

		// Let's handle the default record flag
		switch ($record->default)
		{
			case 1:
				// If this record is marked as default we have to make all the other user's records non-default
				if (count($records))
				{
					foreach ($records as $rec)
					{
						if (!$rec->default)
						{
							continue;
						}

						$rec->default = 0;

						try
						{
							$db->updateObject('#__loginguard_tfa', $rec, array('id'));
						}
						catch (Exception $e)
						{
							// No problem if that fails
						}
					}
				}

				break;

			case 0:
				// If this record is NOT marked default we have to make it default unless another record is the default
				$record->default = 1;

				if (!empty($records))
				{
					foreach ($records as $rec)
					{
						if ($rec->default)
						{
							$record->default = 0;

							break;
						}
					}
				}

				break;
		}

		if (!$record->id)
		{
			// Update the Created On, UA and IP columns
			JLoader::import('joomla.environment.browser');
			$jNow    = JDate::getInstance();
			$browser = JBrowser::getInstance();
			$session = JFactory::getSession();
			$ip      = $session->get('session.client.address');

			if (empty($ip))
			{
				$ip = $_SERVER['REMOTE_ADDR'];
			}

			$record->created_on = $jNow->toSql();
			$record->ua         = $browser->getAgentString();
			$record->ip         = $ip;

			$result = $db->insertObject('#__loginguard_tfa', $record, 'id');
		}
		else
		{
			$result = $db->updateObject('#__loginguard_tfa', $record, array('id'));
		}

		// For old Joomla versions which do not throw DB exceptions
		if (!$result)
		{
			throw new RuntimeException($db->getErrorMsg());
		}

		// If that was the very first method we added for that user let's also create their backup codes
		/** @var LoginGuardModelBackupcodes $model */
		$model = JModelLegacy::getInstance('Backupcodes', 'LoginGuardModel');
		$user = JFactory::getUser($record->user_id);
		$model->regenerateBackupCodes($user);
	}

	/**
	 * @param   JUser   $user        The user record. Null to use the currently logged in user.
	 * @param   object  $dispatcher  The Joomla events dispatcher for plugins. Null to use the system default.
	 *
	 * @return  array
	 */
	public function getRenderOptions(JUser $user = null, $dispatcher = null)
	{
		if (is_null($user))
		{
			$user = JFactory::getUser();
		}

		$renderOptions = array(
			// Default title if you are setting up this TFA method for the first time
			'default_title'  => '',
			// Custom HTML to display above the TFA setup form
			'pre_message'    => '',
			// Heading for displayed tabular data. Typically used to display a list of fixed TFA codes, TOTP setup parameters etc
			'table_heading'  => '',
			// Any tabular data to display (label => custom HTML). See above
			'tabular_data'   => array(),
			// Hidden fields to include in the form (name => value)
			'hidden_data'    => array(),
			// How to render the TFA setup code field. "input" (HTML input element) or "custom" (custom HTML)
			'field_type'     => 'input',
			// The type attribute for the HTML input box. Typically "text" or "password". Use any HTML5 input type.
			'input_type'     => 'text',
			// Pre-filled value for the HTML input box. Typically used for fixed codes, the fixed YubiKey ID etc.
			'input_value'    => '',
			// Placeholder text for the HTML input box. Leave empty if you don't need it.
			'placeholder'    => '',
			// Label to show above the HTML input box. Leave empty if you don't need it.
			'label'          => '',
			// Custom HTML. Only used when field_type = custom.
			'html'           => '',
			// Should I show the submit button (apply the TFA setup)?
			'show_submit'    => true,
			// onclick handler for the submit button (apply the TFA setup)
			'submit_onclick' => '',
			// Custom HTML to display below the TFA setup form
			'post_message'   => '',
		);

		$record  = $this->getRecord($user);
		$results = LoginGuardHelperTfa::runPlugins('onLoginGuardTfaGetSetup', array($record), $dispatcher);

		if (empty($results))
		{
			return $renderOptions;
		}

		foreach ($results as $result)
		{
			if (empty($result))
			{
				continue;
			}

			return array_merge($renderOptions, $result);
		}

		return $renderOptions;
	}

	public function deleteRecord($id, JUser $user = null)
	{
		// Make sure we have a valid user
		if (is_null($user))
		{
			$user = JFactory::getUser();
		}

		// The user must be a registered user, not a guest
		if ($user->guest)
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// The record ID must be a positive integer
		if ($id <= 0)
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// The record must exist and belong to the specified user
		$this->setState('id', $id);
		$record = $this->getRecord($user);

		if (($record->user_id != $user->id) || ($record->id != $id))
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// Try to delete the record
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
		            ->delete($db->qn('#__loginguard_tfa'))
		            ->where($db->qn('id') . ' = ' . $db->q($id))
		            ->where($db->qn('user_id') . ' = ' . $db->q($user->id));
		$db->setQuery($query)->execute();

		// If the record was the default set a new default
		if ($record->default)
		{
			$records = LoginGuardHelperTfa::getUserTfaRecords($record->user_id);

			if (empty($records))
			{
				return;
			}

			$record          = array_shift($records);
			$record->default = 1;

			try
			{
				$this->saveRecord($record);
			}
			catch (Exception $e)
			{
				// If we can't set a new default record it's OK, we'll survive.
			}
		}
	}

	/**
	 * Return the title to use for the page
	 *
	 * @return  string
	 */
	public function getPageTitle()
	{
		$task    = $this->getState('task', 'edit');
		$langKey = "COM_LOGINGUARD_HEAD_{$task}_PAGE";

		return JText::_($langKey);
	}

	/**
	 * Populate the list of TFA methods
	 *
	 * @param   object  $dispatcher
	 */
	private function populateTfaMethods($dispatcher)
	{
		$this->tfaMethods = array();
		$tfaMethods       = LoginGuardHelperTfa::getTfaMethods($dispatcher);

		if (empty($tfaMethods))
		{
			return;
		}

		foreach ($tfaMethods as $method)
		{
			$this->tfaMethods[$method['name']] = $method;
		}
	}

	/**
	 * @param   JUser   $user        The user record. Null to use the current user.
	 * @param   object  $dispatcher  The Joomla events dispatcher for plugins. Null to use the system default.
	 *
	 * @return  object
	 */
	protected function getDefaultRecord(JUser $user = null, $dispatcher = null)
	{
		if (is_null($user))
		{
			$user = JFactory::getUser();
		}

		$method = $this->getState('method');
		$title  = '';

		if (is_null($this->tfaMethods))
		{
			$this->populateTfaMethods($dispatcher);
		}

		if ($method && isset($this->tfaMethods[$method]))
		{
			$title = $this->tfaMethods[$method]['display'];
		}

		$record = (object) array(
			'id'      => null,
			'user_id' => $user->id,
			'title'   => $title,
			'method'  => $method,
			'default' => 0,
			'options' => '{}'
		);

		return $record;
	}
}