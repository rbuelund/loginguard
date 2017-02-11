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

	/**
	 * Delete all Two Step Verification methods for the given user.
	 *
	 * @param   JUser  $user  The user object to reset TSV for. Null to use the current user.
	 *
	 * @return  void
	 *
	 * @throws  RuntimeException  When the user is invalid or a database error has occurred.
	 */
	public function deleteAll(JUser $user = null)
	{
		// Make sure we have a user object
		if (is_null($user))
		{
			$user = JFactory::getUser();
		}

		// If the user object is a guest (who can't have TSV) we abort with an error
		if ($user->guest)
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->delete($db->qn('#__loginguard_tfa'))
			->where($db->qn('user_id') . ' = ' . $db->q($user->id));
		$db->setQuery($query)->execute();
	}
}