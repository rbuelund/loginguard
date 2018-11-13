<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\LoginGuard\Site\Helper\Tfa;
use FOF30\Container\Container;
use Joomla\CMS\User\User;

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
	 * User groups for which Two Step Verification is never applied
	 *
	 * @var   array
	 * @since 3.0.1
	 */
	private $neverTSVUserGroups = [];

	/**
	 * User groups for which Two Step Verification is mandatory
	 *
	 * @var   array
	 * @since 3.0.1
	 */
	private $forceTSVUserGroups = [];

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

		$this->neverTSVUserGroups = $this->container->params->get('neverTSVUserGroups', []);

		if (!is_array($this->neverTSVUserGroups))
		{
			$this->neverTSVUserGroups = [];
		}

		$this->forceTSVUserGroups = $this->container->params->get('forceTSVUserGroups', []);

		if (!is_array($this->forceTSVUserGroups))
		{
			$this->forceTSVUserGroups = [];
		}
	}

	/**
	 * MAGIC TRICK. If you have enabled Joomla's Privacy Consent you'd end up with an infinite redirection loop. That's
	 * because Joomla! did a partial, naive copy of my original research code on captive Joomla! logins. They did not
	 * implement configurable exceptions since they do not use the CMS in the real world and do not understand the use
	 * cases.
	 *
	 * Since fixing Joomla's code is not an option, as we have found from bitter experience, we'll do what we have
	 * always been doing best: work around it based on our knowledge of real world Joomla usage and how the beast truly
	 * works under the hood.
	 *
	 * In this episode of Crazy Stuff Nicholas Has To Do To Get Basic Functionlaity Working we will explore how to use
	 * PHP Reflection to detect the offending Joomla! Privacy Consent system plugin and snuff it out before it can issue
	 * its redirections. Yo, Joomla!, I invented this UI pattern. Do you think your bad aping of it would stop me? HAH!
	 *
	 * @since  3.0.3
	 * @throws Exception
	 */
	public function onAfterInitialise()
	{
		$app    = JFactory::getApplication();
		$option = $app->input->getCmd('option', null);

		/**
		 * If we're going to need to perform a redirection and Joomla's privacy consent is also enabled we will snuff it
		 * so it doesn't cause an infinite redirection loop. The correct solution would be Joomla! allowing users to
		 * specify exceptions to the captive login but having its developers think of that requires them to use the CMS
		 * in the real world which, as we know, is not the case. No problem. I've made a career working around the
		 * Joomla! core, haven't I?
		 */
		if (
			($this->willNeedRedirect() || ($option == 'com_loginguard'))
			&& version_compare(JVERSION, '3.8.999', 'gt'))
		{
			$this->snuffJoomlaPrivacyConsent();
		}
	}

	/**
	 * Gets triggered right after Joomla has finished with the SEF routing and before it has the chance to dispatch the
	 * application (load any components).
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 */
	public function onAfterRoute()
	{
		if (!$this->willNeedRedirect())
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

		// We only kick in when the user has actually set up TFA or must definitely enable TFA.
		$needsTFA     = $this->needsTFA($user);
		$disabledTSV  = $this->disabledTSV($user);
		$mandatoryTSV = $this->mandatoryTSV($user);

		if ($needsTFA && !$disabledTSV)
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

		// If the user is in a group that requires TFA we will redirect them to the setup page
		if (!$needsTFA && $mandatoryTSV)
		{
			// First unset the flag to make sure the redirection will apply until they conform to the mandatory TFA
			$session->set('tfa_checked', 0, 'com_loginguard');

			// Now set a flag which forces rechecking TSV for this user
			$session->set('recheck_mandatory_tsv', 1, 'com_loginguard');

			// Then redirect them to the setup page
			$this->redirectToTSVSetup();
		}

		if (!$needsTFA && $redirectionUrl && !$disabledTSV)
		{
			$session->set('postloginredirect', null, 'com_loginguard');

			JFactory::getApplication()->redirect($redirectionUrl);
		}
	}

	/**
	 * Does the current user need to complete TFA authentication before being allowed to access the site?
	 *
	 * @return  bool
	 */
	private function needsTFA(JUser $user)
	{
		/** @var \Akeeba\LoginGuard\Site\Model\Tfa $tfaModel */
		$tfaModel = $this->container->factory->model('Tfa')->tmpInstance();

		// Get the user's TFA records
		$records = $tfaModel->user_id($user->id)->get(true);

		// No TFA methods? Then we obviously don't need to display a captive login page.
		if ($records->count() < 1)
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
		$methodNames = [];

		foreach ($tfaMethods as $tfaMethod)
		{
			$methodNames[] = $tfaMethod['name'];
		}

		// Filter the records based on currently active TFA methods
		foreach ($records as $record)
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
				$app   = \JFactory::getApplication();
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

	/**
	 * Does the user belong in a group indicating TSV should be disabled for them?
	 *
	 * @param   JUser|User $user
	 *
	 * @return  bool
	 */
	private function disabledTSV($user)
	{
		// If the user belongs to a "never check for TSV" user group they are exempt from TSV
		$userGroups             = $user->getAuthorisedGroups();
		$belongsToTSVUserGroups = array_intersect($this->neverTSVUserGroups, $userGroups);

		return !empty($belongsToTSVUserGroups);
	}

	/**
	 * Does the user belong in a group indicating TSV is required for them?
	 *
	 * @param   JUser|User $user
	 *
	 * @return  bool
	 */
	private function mandatoryTSV($user)
	{
		// If the user belongs to a "never check for TSV" user group they are exempt from TSV
		$userGroups             = $user->getAuthorisedGroups();
		$belongsToTSVUserGroups = array_intersect($this->forceTSVUserGroups, $userGroups);

		return !empty($belongsToTSVUserGroups);
	}

	/**
	 * Redirect the user to the Two Step Verification method setup page.
	 *
	 * @return  void
	 *
	 * @since   3.0.1
	 */
	private function redirectToTSVSetup()
	{
		try
		{
			$app = JFactory::getApplication();
		}
		catch (\Exception $e)
		{
			// This would happen if we are in CLI or under an old Joomla! version. Either case is not supported.
			return;
		}

		// If we are in a LoginGuard page do not redirect
		$option = strtolower($app->input->getCmd('option'));

		if ($option == 'com_loginguard')
		{
			return;
		}

		// Otherwise redirect to the LoginGuard TSV setup page after enqueueing a message
		$url = 'index.php?option=com_loginguard&view=Methods';
		$app->redirect($url, 307);
	}

	private function willNeedRedirect()
	{
		// If the requirements are not met do not proceed
		if (!$this->enabled)
		{
			return false;
		}

		// Get the session objects
		try
		{
			$session = JFactory::getSession();
		}
		catch (Exception $e)
		{
			// Can't get access to the session? Must be under CLI which is not supported.
			return false;
		}

		/**
		 * We only kick in if the session flag is not set AND the user is not flagged for monitoring of their TSV status
		 *
		 * In case a user belongs to a group which requires TSV to be always enabled and they logged in without having
		 * TSV enabled we have the recheck flag. This prevents the user from enabling and immediately disabling TSV,
		 * circumventing the requirement for TSV.
		 */
		$tfaChecked = $session->get('tfa_checked', 0, 'com_loginguard');
		$tfaRecheck = $session->get('recheck_mandatory_tsv', 0, 'com_loginguard');

		if ($tfaChecked && !$tfaRecheck)
		{
			return false;
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
			return false;
		}

		// The plugin only needs to kick in when you have logged in
		if ($user->get('guest'))
		{
			return false;
		}

		list($isCLI, $isAdmin) = $this->isCliAdmin();

		// TFA is not applicable under CLI
		if ($isCLI)
		{
			return false;
		}

		// If we are in the administrator section we only kick in when the user has backend access privileges
		if ($isAdmin && !$user->authorise('core.login.admin'))
		{
			return false;
		}

		$needsTFA = $this->needsTFA($user);

		if ($tfaChecked && $tfaRecheck && $needsTFA)
		{
			return false;
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

			if (empty($view) && (strpos($task, '.') !== false))
			{
				list($view, $task) = explode('.', $task, 2);
			}

			// The captive login page is always allowed
			if ($view === 'captive')
			{
				return false;
			}

			// These views are only allowed if you do not have 2SV enabled *or* if you have already logged in.
			if (!$needsTFA && in_array($view, array('ajax', 'method', 'methods')))
			{
				return false;
			}
		}

		// Allow the frontend user to log out (in case they forgot their TFA code or something)
		if (!$isAdmin && ($option == 'com_users') && ($task == 'user.logout'))
		{
			return false;
		}

		// Allow the backend user to log out (in case they forgot their TFA code or something)
		if ($isAdmin && ($option == 'com_login') && ($task == 'logout'))
		{
			return false;
		}

		/**
		 * Allow com_ajax. This is required for cookie acceptance in the following scenario. Your session has expired,
		 * therefore you need to re-apply TFA. Moreover, your cookie acceptance cookie has also expired and you need to
		 * accept the site's cookies again.
		 */
		if ($option == 'com_ajax')
		{
			return false;
		}

		return true;
	}

	private function snuffJoomlaPrivacyConsent()
	{
		/**
		 * The privacy suite is not ported to Joomla! 4 yet.
		 */
		if (version_compare(JVERSION, '3.9999.9999', 'ge'))
		{
			return;
		}

		// The broken Joomla! consent plugin is not activated
		if (!class_exists('PlgSystemPrivacyconsent'))
		{
			return;
		}

		// Get the events dispatcher and find which observer is the offending plugin
		$dispatcher     = JEventDispatcher::getInstance();
		$refDispatcher  = new ReflectionObject($dispatcher);
		$refObservers   = $refDispatcher->getProperty('_observers');
		$refObservers->setAccessible(true);
		$observers = $refObservers->getValue($dispatcher);

		$jConsentObserverId = 0;

		foreach ($observers as $id => $o)
		{
			if (!is_object($o))
			{
				continue;
			}

			if ($o instanceof \PlgSystemPrivacyconsent)
			{
				$jConsentObserverId = $id;

				break;
			}
		}

		// Nope. Cannot find the offending plugin.
		if ($jConsentObserverId == 0)
		{
			return;
		}

		// Now we need to remove the offending plugin from the onAfterRoute event.
		$refMethods = $refDispatcher->getProperty('_methods');
		$refMethods->setAccessible(true);
		$methods = $refMethods->getValue($dispatcher);

		$methods['onafterroute'] = array_filter($methods['onafterroute'], function($id) use ($jConsentObserverId) {
			return $id != $jConsentObserverId;
		});
		$refMethods->setValue($dispatcher, $methods);
	}
}
