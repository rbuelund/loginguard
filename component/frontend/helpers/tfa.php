<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

JLoader::import('joomla.plugin.helper');

/**
 * Helper functions for TFA handling
 */
abstract class LoginGuardHelperTfa
{
	/**
	 * Cache of TFA records per user
	 *
	 * @var   array
	 */
	protected static $recordsPerUser = array();

	/**
	 * Cache of all currently active TFAs
	 *
	 * @var   array|null
	 */
	protected static $allTFAs = null;

	/**
	 * Are we inside the administrator application
	 *
	 * @var   bool
	 */
	protected static $isAdmin = null;

	/**
	 * Execute plugins and fetch back an array with their return values.
	 *
	 * @param   string  $event       The event (trigger) name, e.g. onBeforeScratchMyEar
	 * @param   array   $data        A hash array of data sent to the plugins as part of the trigger
	 * @param   object  $dispatcher  An events dispatcher, typically descending from JEventDispatcher.
	 *
	 * @return  array  A simple array containing the results of the plugins triggered
	 */
	public static function runPlugins($event, $data, $dispatcher = null)
	{
		if (is_null($dispatcher))
		{
			$app = JFactory::getApplication();

			if (method_exists($app, 'triggerEvent'))
			{
				return $app->triggerEvent($event, $data);
			}

			if (class_exists('JEventDispatcher'))
			{
				$dispatcher = JEventDispatcher::getInstance();
			}
			else
			{
				$dispatcher = JDispatcher::getInstance();
			}
		}

		return $dispatcher->trigger($event, $data);
	}

	/**
	 * Get a list of all of the TFA methods
	 *
	 * @param   object  $dispatcher  An events dispatcher, typically descending from JEventDispatcher.
	 *
	 * @return  array
	 */
	public static function getTfaMethods($dispatcher = null)
	{
		JPluginHelper::importPlugin('loginguard');

		if (is_null(self::$allTFAs))
		{
			// Get all the plugin results
			$temp = self::runPlugins('onLoginGuardTfaGetMethod', array(), $dispatcher);
			// Normalize the results
			self::$allTFAs = array();

			foreach ($temp as $method)
			{
				if (!is_array($method))
				{
					continue;
				}

				$method = array_merge(array(
					// Internal code of this TFA method
					'name'               => '',
					// User-facing name for this TFA method
					'display'            => '',
					// Short description of this TFA method displayed to the user
					'shortinfo'          => '',
					// URL to the logo image for this method
					'image'              => '',
					// Are we allowed to disable it?
					'canDisable'         => true,
					// Are we allowed to have multiple instances of it per user?
					'allowMultiple'      => false,
					// URL for help content
					'help_url'           => '',
					// Allow authentication against all entries of this TFA method. Otherwise authentication takes place against a SPECIFIC entry at a time.
					'allowEntryBatching' => false,
				), $method);

				if (empty($method['name']))
				{
					continue;
				}

				self::$allTFAs[$method['name']] = $method;
			}
		}

		return self::$allTFAs;
	}

	/**
	 * Get the TFA records for a specific user
	 *
	 * @param   int  $user_id  The user's ID
	 *
	 * @return  stdClass[]
	 */
	public static function getUserTfaRecords($user_id)
	{
		if (!isset(self::$recordsPerUser[$user_id]))
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
			            ->select('*')
			            ->from($db->qn('#__loginguard_tfa'))
			            ->where($db->qn('user_id') . ' = ' . $db->q($user_id))
						->order($db->qn('method') . ' ASC');

			try
			{
				self::$recordsPerUser[$user_id] = $db->setQuery($query)->loadObjectList();
			}
			catch (Exception $e)
			{
				self::$recordsPerUser[$user_id] = array();
			}
		}

		return self::$recordsPerUser[$user_id];
	}

	/**
	 * Are we inside an administrator page?
	 *
	 * @param   JApplicationCms  $app  The current CMS application which tells us if we are inside an admin page
	 *
	 * @return  bool
	 */
	public static function isAdminPage(JApplicationCms $app = null)
	{
		if (is_null(self::$isAdmin))
		{
			if (is_null($app))
			{
				$app = JFactory::getApplication();
			}

			self::$isAdmin = version_compare(JVERSION, '3.7.0', 'ge') ? $app->isClient('administrator') : $app->isAdmin();
		}

		return self::$isAdmin;
	}

	/**
	 * Is the current user allowed to edit the TFA configuration of $user? To do so I must either be editing my own
	 * account OR I have to be a Super User editing a non-superuser's account. Important to note: nobody can edit the
	 * accounts of Super Users except themselves. Therefore make damn sure you keep those backup codes safe!
	 *
	 * @param   JUser  $user  The user you want to know if we're allowed to edit
	 *
	 * @return  bool
	 */
	public static function canEditUser(JUser $user = null)
	{
		// I can edit myself
		if (empty($user))
		{
			return true;
		}

		// Guests can't have TFA
		if ($user->guest)
		{
			return false;
		}

		// Get the currently logged in used
		$myUser = JFactory::getUser();

		// Same user? I can edit myself
		if ($myUser->id == $user->id)
		{
			return true;
		}

		// To edit a different user I must be a User User myself. If I'm not, I can't edit another user!
		if (!$myUser->authorise('core.admin'))
		{
			return false;
		}

		// Even if I am a Super User I must not be able to edit another Super User.
		if ($user->authorise('core.admin'))
		{
			return false;
		}

		// I am a Super User trying to edit a non-superuser. That's allowed.
		return true;
	}

}