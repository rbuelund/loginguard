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

		if (!$record->id)
		{
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
			'options' => '{}'
		);

		return $record;
	}
}