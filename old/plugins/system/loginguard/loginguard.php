<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\LoginGuard\Site\Helper\Tfa;
use FOF30\Container\Container;

// Prevent direct access
defined('_JEXEC') or die;

// Minimum PHP version check
if (!version_compare(PHP_VERSION, '5.4.0', '>='))
{
	return;
}

/**
 * Work around the very broken and completely defunct eAccelerator on PHP 5.4 (or, worse, later versions).
 */
if (function_exists('eaccelerator_info'))
{
	$isBrokenCachingEnabled = true;

	if (function_exists('ini_get') && !ini_get('eaccelerator.enable'))
	{
		$isBrokenCachingEnabled = false;
	}

	if ($isBrokenCachingEnabled)
	{
		/**
		 * I know that this define seems pointless since I am returning. This means that we are exiting the file and
		 * the plugin class isn't defined, so Joomla cannot possibly use it.
		 *
		 * Well, that is how PHP works. Unfortunately, eAccelerator has some "novel" ideas about how to go about it.
		 * For very broken values of "novel". What does it do? It ignores the return and parses the plugin class below.
		 *
		 * You read that right. It ignores ALL THE CODE between here and the class declaration and parses the
		 * class declaration. Therefore the only way to actually NOT load the  plugin when you are using it on a
		 * server where an irresponsible sysadmin has installed and enabled eAccelerator (IT'S END OF LIFE AND BROKEN
		 * PER ITS CREATORS FOR CRYING OUT LOUD) is to define a constant and use it to return from the constructor
		 * method, therefore forcing PHP to return null instead of an object. This prompts Joomla to not do anything
		 * with the plugin.
		 */
		if (!defined('AKEEBA_EACCELERATOR_IS_SO_BORKED_IT_DOES_NOT_EVEN_RETURN'))
		{
			define('AKEEBA_EACCELERATOR_IS_SO_BORKED_IT_DOES_NOT_EVEN_RETURN', 3245);
		}

		return;
	}
}

// Make sure Akeeba LoginGuard is installed
if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_loginguard'))
{
	return;
}

// Load FOF
if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
{
	return;
}

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
	 * The component's container
	 *
	 * @var   Container
	 * @since 2.0.0
	 */
	private $container = null;

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
		/**
		 * Required to work around eAccelerator on PHP 5.4 and later.
		 *
		 * PUBLIC SERVICE ANNOUNCEMENT: eAccelerator IS DEFUNCT AND INCOMPATIBLE WITH PHP 5.4 AND ANY LATER VERSION. If
		 * you have it enabled on your server go ahead and uninstall it NOW. It's officially dead since 2012. Thanks.
		 */
		if (defined('AKEEBA_EACCELERATOR_IS_SO_BORKED_IT_DOES_NOT_EVEN_RETURN'))
		{
			return;
		}

		parent::__construct($subject, $config);

		try
		{
			if (!JComponentHelper::isInstalled('com_loginguard') || !JComponentHelper::isEnabled('com_loginguard'))
			{
				$this->enabled = false;
			}
			else
			{
				$this->container = Container::getInstance('com_loginguard');
			}
		}
		catch (Exception $e)
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

			// Joomla! 3: make sure the user identity is loaded. This MUST NOT be called in Joomla! 4, though.
			if (version_compare(JVERSION, '3.99999.99999', 'lt'))
			{
				$app->loadIdentity();
			}

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
				$session->set('return_url', JUri::getInstance()->toString(array('scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment')), 'com_loginguard');
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
		$records = Tfa::getUserTfaRecords($user->id);

		// No TFA methods? Then we obviously don't need to display a captive login page.
		if (empty($records))
		{
			return false;
		}

		// Let's get a list of all currently active TFA methods
		$tfaMethods = Tfa::getTfaMethods();

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
