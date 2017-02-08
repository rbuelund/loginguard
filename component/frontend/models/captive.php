<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

JLoader::register('LoginGuardHelperTfa', JPATH_SITE . '/components/com_loginguard/helpers/tfa.php');

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
	 */
	public function killAllModules()
	{
		$allowedPositions = $this->getAllowedModulePositions();

		$app = JFactory::getApplication();
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
	 * Are we inside an administrator page?
	 *
	 * @return  bool
	 */
	public function isAdminPage()
	{
		$app = JFactory::getApplication();
		return version_compare(JVERSION, '3.7.0', 'ge') ? $app->isClient('administrator') : $app->isAdmin();
	}

	/**
	 * Get the TFA records for the current user which correspond to active plugins
	 *
	 * @return  array
	 */
	public function getRecords()
	{
		// Get the user's TFA records
		$user = JFactory::getUser();
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
	 *
	 * @return mixed|null
	 */
	public function getRecord()
	{
		$id = (int) $this->getState('record_id', null);

		if ($id <= 0)
		{
			return null;
		}

		$user  = JFactory::getUser();
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

		if (!in_array($record->method, $methodNames))
		{
			return null;
		}

		return $record;
	}

	/**
	 * Load the captive login page render options for a specific TFA record
	 *
	 * @param   stdClass  $record  The TFA record to process
	 *
	 * @return  array  The rendering options
	 */
	public function loadCaptiveRenderOptions($record)
	{
		$results = LoginGuardHelperTfa::runPlugins('onLoginGuardTfaCaptive', array($record));

		$renderOptions = array(
			'pre_message'  => '',
			'field_type'   => 'input',
			'input_type'   => 'text',
			'placeholder'  => '',
			'label'        => '',
			'html'         => '',
			'post_message' => ''
		);

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
	 * Get a list of module positions we are allowed to display
	 *
	 * @return  array
	 */
	private function getAllowedModulePositions()
	{
		$isAdmin = $this->isAdminPage();

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