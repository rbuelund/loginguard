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
 * Two Step Verification methods list page's model
 */
class LoginGuardModelMethods extends JModelLegacy
{
	/**
	 * Returns a list of all available and their currently active records for given user.
	 *
	 * @param   JUser  $user  The user object. Skip to use the current user.
	 *
	 * @return  array
	 */
	public function getMethods($user = null)
	{
		if (!is_object($user) || !($user instanceof JUser))
		{
			$user = JFactory::getUser();
		}


		if ($user->guest)
		{
			return array();
		}

		// Get an associative array of TFA methods
		$rawMethods = LoginGuardHelperTfa::getTfaMethods();
		$methods    = array();

		foreach ($rawMethods as $method)
		{
			$method['active'] = array();
			$methods[$method['name']] = $method;
		}

		// Put the user TFA records into the methods array
		$userTfaRecords = LoginGuardHelperTfa::getUserTfaRecords($user->id);

		if (!empty($userTfaRecords))
		{
			foreach ($userTfaRecords as $record)
			{
				if (!isset($methods[$record->method]))
				{
					continue;
				}

				$methods[$record->method]['active'][$record->id] = $record;
			}
		}

		return $methods;
	}
}