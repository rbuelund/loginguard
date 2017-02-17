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
 * Model for managing backup codes
 */
class LoginGuardModelBackupcodes extends JModelLegacy
{
	/**
	 * Caches the backup codes per user ID
	 *
	 * @var  array
	 */
	protected $cache = array();

	/**
	 * Returns the backup codes for the specified user. Cached values will be preferentially returned, therefore you
	 * MUST go through this model's methods ONLY when dealing with backup codes.
	 *
	 * @param   JUser  $user  The user for which you want the backup codes
	 *
	 * @return  array|null  The backup codes, or null if they do not exist
	 */
	public function getBackupCodes(JUser $user = null)
	{
		// Make sure I have a user
		if (empty($user))
		{
			$user = JFactory::getUser();
		}

		// If there is no cached record load it from the database
		if (!isset($this->cache[$user->id]))
		{
			// Intiialize (null = no record exists)
			$this->cache[$user->id] = null;
			$json = null;

			// Try to load the record
			$db = $this->getDbo();
			$query = $db->getQuery(true)
				->select($db->qn('options'))
				->from($db->qn('#__loginguard_tfa'))
				->where($db->qn('user_id') . ' = ' . $db->q($user->id))
				->where($db->qn('method') . ' = ' . $db->q('backupcodes'));

			try
			{
				$json = $db->setQuery($query)->loadResult();
			}
			catch (Exception $e)
			{
				// Any db issue is equivalent to "no such record exists"
			}

			if (!empty($json))
			{
				$this->cache[$user->id] = json_decode($json, true);
			}
		}

		return $this->cache[$user->id];
	}

	/**
	 * Generate a new set of backup codes for the specified user. The generated codes are immediately saved to the
	 * database and the internal cache is updated.
	 *
	 * @param   JUser  $user  Which user to generate codes for?
	 */
	public function regenerateBackupCodes(JUser $user = null)
	{
		// Make sure I have a user
		if (empty($user))
		{
			$user = JFactory::getUser();
		}

		// Generate backup codes
		$backupCodes = array();

		for ($i = 0; $i < 10; $i++)
		{
			
		}

		$this->saveBackupCodes($backupCodes, $user);
	}

	public function isBackupCode($code, JUser $user = null)
	{
		// TODO Load the backup codes

		// TODO If the backup codes is not an array (no backup codes) return false

		// TODO If the backup codes are an empty array (you burned them all) return false

		// TODO If the code is not in the array return false

		// TODO Remove the code from the array

		// TODO Save the backup codes
	}

	public function saveBackupCodes(array $codes, JUser $user = null)
	{
		// TODO Try to load existing backup codes

		// TODO If the backup codes is null insert a new record

		// TODO Otherwise update the existing db record

		// TODO Finally update the cache
	}
}