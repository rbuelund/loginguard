<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2006-2017 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

/**
 * Akeeba LoginGuard System Plugin
 *
 * Implements the captive Two Factor Authentication page
 */
class PlgSystemLoginguard extends JPlugin
{
	public function onAfterRoute()
	{
		// We only kick in if the session flag is not set
		try
		{
			$session = JFactory::getSession();

			if ($session->get('tfa_checked', 0, 'com_loginguard'))
			{
				return;
			}
		}
		catch (Exception $e)
		{
			// Can't get access to the session? Must be under CLI which is not supported.
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

		// 2FA is not applicable under CLI
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

			if ($view == 'captive')
			{
				return;
			}
		}

		// We only kick in when the user has actually set up TFA.
		if ($this->needsTFA($user))
		{
			$url = JRoute::_('index.php?option=com_loginguard&view=captive', false);
			$app->redirect($url, 307);

			return;
		}

		// If we're here someone just logged in but does not have TFA set up. Just flag him as logged in and continue.
		$session->set('tfa_checked', 0, 'com_loginguard');
	}

	/**
	 * Does the current user need to complete 2FA authentication before allowed to access the site?
	 *
	 * @return  bool
	 */
	private function needsTFA(JUser $user)
	{
		// TODO Implement me

		return true;
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