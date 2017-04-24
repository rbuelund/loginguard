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
 * Captive Two Step Verification page's model
 */
class LoginGuardModelCaptive extends JModelLegacy
{
	/**
	 * Cache of the names of the currently active TFA methods
	 *
	 * @var  null
	 */
	protected $activeTFAMethodNames = null;

	/**
	 * Prevents Joomla from displaying any modules.
	 *
	 * This is implemented with a trick. If you use jdoc tags to load modules the JDocumentRendererHtmlModules
	 * uses JModuleHelper::getModules() to load the list of modules to render. This goes through JModuleHelper::load()
	 * which triggers the onAfterModuleList event after cleaning up the module list from duplicates. By resetting
	 * the list to an empty array we force Joomla to not display any modules.
	 *
	 * Similar code paths are followed by any canonical code which tries to load modules. So even if your template does
	 * not use jdoc tags this code will still work as expected.
	 *
	 * @param   JApplicationCms  $app  The CMS application to manipulate
	 *
	 * @return  void
	 */
	public function killAllModules(JApplicationCms $app = null)
	{
		if (is_null($app))
		{
			$app = JFactory::getApplication();
		}

		$allowedPositions = $this->getAllowedModulePositions();

		$app->registerEvent('onAfterModuleList', function (&$modules) use ($allowedPositions) {
			if (empty($modules))
			{
				return;
			}

			if (empty($allowedPositions))
			{
				$modules = array();

				return;
			}

			$filtered = array();

			foreach ($modules as $module)
			{
				if (in_array($module->position, $allowedPositions))
				{
					$filtered[] = $module;
				}
			}

			$modules = $filtered;
		});
	}

	/**
	 * Get the TFA records for the user which correspond to active plugins
	 *
	 * @param   JUser  $user   The user for which to fetch records. Skip to use the current user.
	 *
	 * @return  array
	 */
	public function getRecords(JUser $user = null)
	{
		if (is_null($user))
		{
			$user = JFactory::getUser();
		}

		// Get the user's TFA records
		$records = LoginGuardHelperTfa::getUserTfaRecords($user->id);

		// No TFA methods? Then we obviously don't need to display a captive login page.
		if (empty($records))
		{
			return array();
		}

		// Get the enabled TFA methods' names
		$methodNames = $this->getActiveMethodNames();

		// Filter the records based on currently active TFA methods
		$ret = array();

		foreach($records as $record)
		{
			if (in_array($record->method, $methodNames))
			{
				$ret[] = $record;
			}
		}

		return $ret;
	}

	/**
	 * Get the currently selected TFA record for the current user. If the record ID is empty, it does not correspond to
	 * the currently logged in user or does not correspond to an active plugin null is returned instead.
	 *
	 * @param   JUser  $user  The user for which to fetch records. Skip to use the current user.
	 *
	 * @return mixed|null
	 */
	public function getRecord(JUser $user = null)
	{
		$id = (int) $this->getState('record_id', null);

		if ($id <= 0)
		{
			return null;
		}

		if (is_null($user))
		{
			$user = JFactory::getUser();
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
			return null;
		}

		$methodNames = $this->getActiveMethodNames();

		if (!in_array($record->method, $methodNames) && ($record->method != 'backupcodes'))
		{
			return null;
		}

		return $record;
	}

	/**
	 * Load the captive login page render options for a specific TFA record
	 *
	 * @param   stdClass  $record      The TFA record to process
	 * @param   object    $dispatcher  The Joomla events dispatcher for plugins. Null to use the system default.
	 *
	 * @return  array  The rendering options
	 */
	public function loadCaptiveRenderOptions($record, $dispatcher = null)
	{
		$renderOptions = array(
			'pre_message'        => '',
			'field_type'         => 'input',
			'input_type'         => 'text',
			'placeholder'        => '',
			'label'              => '',
			'html'               => '',
			'post_message'       => '',
			'help_url'           => '',
			'allowEntryBatching' => false,
		);

		if (empty($record))
		{
			return $renderOptions;
		}

		$results = LoginGuardHelperTfa::runPlugins('onLoginGuardTfaCaptive', array($record), $dispatcher);

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
	 * Returns the title to display in the captive login page, or an empty string if no title is to be displayed.
	 *
	 * @return  string
	 */
	public function getPageTitle()
	{
		// In the frontend we can choose if we will display a title
		jimport('joomla.component.helper');

		$params = JComponentHelper::getParams('com_loginguard');
		$showTitle = (bool) $params->get('frontend_show_title', 1);

		if (!$showTitle)
		{
			return '';
		}

		return JText::_('COM_LOGINGUARD_HEAD_TFA_PAGE');
	}

	/**
	 * Translate a TFA method's name into its human-readable, display name
	 *
	 * @param   string  $name  The internal TFA method name
	 *
	 * @return  string
	 */
	public function translateMethodName($name)
	{
		static $map = null;

		if (!is_array($map))
		{
			$map = array();
			$tfaMethods = LoginGuardHelperTfa::getTfaMethods();

			if (!empty($tfaMethods))
			{
				foreach ($tfaMethods as $tfaMethod)
				{
					$map[$tfaMethod['name']] = $tfaMethod['display'];
				}
			}
		}

		if ($name == 'backupcodes')
		{
			return JText::_('COM_LOGINGUARD_LBL_BACKUPCODES_METHOD_NAME');
		}

		return isset($map[$name]) ? $map[$name] : $name;
	}

	/**
	 * Translate a TFA method's name into the relative URL if its logo image
	 *
	 * @param   string  $name  The internal TFA method name
	 *
	 * @return  string
	 */
	public function getMethodImage($name)
	{
		static $map = null;

		if (!is_array($map))
		{
			$map = array();
			$tfaMethods = LoginGuardHelperTfa::getTfaMethods();

			if (!empty($tfaMethods))
			{
				foreach ($tfaMethods as $tfaMethod)
				{
					$map[$tfaMethod['name']] = $tfaMethod['image'];
				}
			}
		}

		if ($name == 'backupcodes')
		{
			return 'media/com_loginguard/images/emergency.svg';
		}

		return isset($map[$name]) ? $map[$name] : $name;
	}

	/**
	 * Get a list of module positions we are allowed to display
	 *
	 * @param   JApplicationCms  $app  The CMS application to manipulate
	 *
	 * @return  array
	 */
	private function getAllowedModulePositions(JApplicationCms $app = null)
	{
		$isAdmin = LoginGuardHelperTfa::isAdminPage($app);

		// Load the list of allowed module positions from the component's settings. May be different for front- and back-end
		$params = JComponentHelper::getParams('com_loginguard');
		$configKey = 'allowed_positions_' . ($isAdmin ? 'backend' : 'frontend');
		$res = $params->get($configKey, array());

		// In the backend we must always add the 'title' module position
		if ($isAdmin)
		{
			$res[] = 'title';
		}

		return $res;
	}

	/**
	 * Return all the active TFA methods' names
	 *
	 * @return  array
	 */
	private function getActiveMethodNames()
	{
		if (is_null($this->activeTFAMethodNames))
		{
			// Let's get a list of all currently active TFA methods
			$tfaMethods = LoginGuardHelperTfa::getTfaMethods();

			// If not TFA method is active we can't really display a captive login page.
			if (empty($tfaMethods))
			{
				$this->activeTFAMethodNames = array();
				return $this->activeTFAMethodNames;
			}

			// Get a list of just the method names
			$this->activeTFAMethodNames = array();

			foreach ($tfaMethods as $tfaMethod)
			{
				$this->activeTFAMethodNames[] = $tfaMethod['name'];
			}
		}

		return $this->activeTFAMethodNames;
	}
}