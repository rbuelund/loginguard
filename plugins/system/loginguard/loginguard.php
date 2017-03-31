<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

/**
 * Akeeba LoginGuard System Plugin
 *
 * Implements the captive Two Step Verification page
 */
class PlgSystemLoginguard extends JPlugin
{
	/**
	 * Are we enabled, all requirements met etc?
	 *
	 * @var   bool
	 */
	public $enabled = true;

	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An optional associative array of configuration settings.
	 *                             Recognized key values include 'name', 'group', 'params', 'language'
	 *                             (this list is not meant to be comprehensive).
	 */
	public function __construct($subject, array $config = array())
	{
		parent::__construct($subject, $config);

		JLoader::register('LoginGuardHelperTfa', JPATH_SITE . '/components/com_loginguard/helpers/tfa.php');

		if (!class_exists('LoginGuardHelperTfa'))
		{
			$this->enabled = false;
		}
	}

	/**
	 * Gets triggered right after Joomla has finished with the SEF routing and before it has the chance to dispatch the
	 * application (load any components).
	 *
	 * @return  void
	 */
	public function onAfterRoute()
	{
		// If the requirements are not met do not proceed
		if (!$this->enabled)
		{
			return;
		}

		// Get the session objects
		try
		{
			$session = JFactory::getSession();
		}
		catch (Exception $e)
		{
			// Can't get access to the session? Must be under CLI which is not supported.
			return;
		}

		// We only kick in if the session flag is not set
		if ($session->get('tfa_checked', 0, 'com_loginguard'))
		{
			return;
		}

		// Make sure we are logged in
		try
		{
			$app = JFactory::getApplication();
			$app->loadIdentity();
			$user = $app->getIdentity();
		}
		catch (\Exception $e)
		{
			// This would happen if we are in CLI or under an old Joomla! version. Either case is not supported.
			return;
		}

		// The plugin only needs to kick in when you have logged in
		if ($user->get('guest'))
		{
			return;
		}

		list($isCLI, $isAdmin) = $this->isCliAdmin();

		// TFA is not applicable under CLI
		if ($isCLI)
		{
			return;
		}

		// If we are in the administrator section we only kick in when the user has backend access privileges
		if ($isAdmin && !$user->authorise('core.login.admin'))
		{
			return;
		}

		// We only kick in if the option and task are not the ones of the captive page
		$option = strtolower($app->input->getCmd('option'));
		$task = strtolower($app->input->getCmd('task'));
		$view = strtolower($app->input->getCmd('view'));

		if ($option == 'com_loginguard')
		{
			// In case someone gets any funny ideas...
			$app->input->set('tmpl', 'index');
			$app->input->set('format', 'html');
			$app->input->set('layout', null);

			if (in_array($view, array('ajax', 'captive')))
			{
				return;
			}
		}

		// Allow the frontend user to log out (in case they forgot their TFA code or something)
		if (!$isAdmin && ($option == 'com_users') && ($task == 'user.logout'))
		{
			return;
		}

		// Allow the backend user to log out (in case they forgot their TFA code or something)
		if ($isAdmin && ($option == 'com_login') && ($task == 'logout'))
		{
			return;
		}

		// We only kick in when the user has actually set up TFA.
		$needsTFA = $this->needsTFA($user);

		if ($needsTFA)
		{
			// Save the current URL, but only if we haven't saved a URL or if the saved URL is NOT internal to the site.
			$return_url = $session->get('return_url', '', 'com_loginguard');

			if (empty($return_url) || !JUri::isInternal($return_url))
			{
				$session->set('return_url', JUri::current(), 'com_loginguard');
			}

			// Redirect
			$url = JRoute::_('index.php?option=com_loginguard&view=captive', false);
			$app->redirect($url, 307);

			return;
		}

		// If we're here someone just logged in but does not have TFA set up. Just flag him as logged in and continue.
		$session->set('tfa_checked', 1, 'com_loginguard');

		// If we don't have TFA set up yet AND the user plugin had set up a redirection we will honour it
		$redirectionUrl = $session->get('postloginredirect', null, 'com_loginguard');

		if (!$needsTFA && $redirectionUrl)
		{
			$session->set('postloginredirect', null, 'com_loginguard');

			JFactory::getApplication()->redirect($redirectionUrl);
		}
	}

	/**
	 * Does the current user need to complete TFA authentication before allowed to access the site?
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
	 * Checks if we are running under a CLI script or inside an administrator session
	 *
	 * @return  array
	 */
	protected function isCliAdmin()
	{
		$isCLI = false;
		$isAdmin = false;

		try
		{
			if (is_null(\JFactory::$application))
			{
				$isCLI = true;
			}
			else
			{
				$app = \JFactory::getApplication();
				$isCLI = $app instanceof \Exception || $app instanceof \JApplicationCli;
			}
		}
		catch (\Exception $e)
		{
			$isCLI = true;
		}

		if (!$isCLI && \JFactory::$application)
		{
			if (version_compare(JVERSION, '3.7.0', 'ge'))
			{
				$isAdmin = JFactory::getApplication()->isClient('administrator');
			}
			else
			{
				$isAdmin = JFactory::getApplication()->isAdmin();
			}
		}

		return array($isCLI, $isAdmin);
	}

}