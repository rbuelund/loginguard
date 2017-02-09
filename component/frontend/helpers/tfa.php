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
			self::$allTFAs = self::runPlugins('onLoginGuardTfaGetMethod', array(), $dispatcher);
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
			            ->where($db->qn('user_id') . ' = ' . $db->q($user_id));

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
}