<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

class LoginGuardModelWelcome extends JModelLegacy
{
	/**
	 * Are there any published LoginGuard plugins in the specified folder?
	 *
	 * @return  bool
	 */
	public function isLoginGuardPluginPublished($folder)
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true)
		            ->select('COUNT(*)')
		            ->from($db->qn('#__extensions'))
		            ->where($db->qn('type') . ' = ' . $db->q('plugin'))
		            ->where($db->qn('element') . ' = ' . $db->q('loginguard'))
		            ->where($db->qn('folder') . ' = ' . $db->q($folder))
		            ->where($db->qn('enabled') . ' = ' . $db->q(1));

		try
		{
			return !empty($db->setQuery($query)->loadResult());
		}
		catch (Exception $e)
		{
			return false;
		}
	}


	/**
	 * Are there any published LoginGuard plugins?
	 *
	 * @return  bool
	 */
	public function hasPublishedPlugins()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true)
		            ->select('COUNT(*)')
		            ->from($db->qn('#__extensions'))
		            ->where($db->qn('type') . ' = ' . $db->q('plugin'))
		            ->where($db->qn('folder') . ' = ' . $db->q('loginguard'))
		            ->where($db->qn('enabled') . ' = ' . $db->q(1));

		try
		{
			return !empty($db->setQuery($query)->loadResult());
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Are there any installed LoginGuard plugins?
	 *
	 * @return  bool
	 */
	public function hasInstalledPlugins()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . ' = ' . $db->q('plugin'))
			->where($db->qn('folder') . ' = ' . $db->q('loginguard'));

		try
		{
			return !empty($db->setQuery($query)->loadResult());
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Is the Akeeba GeoIP Provider plugin installed?
	 *
	 * @return  bool
	 */
	public function hasGeoIPPlugin()
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
		            ->select('COUNT(*)')
		            ->from($db->qn('#__extensions'))
		            ->where($db->qn('type') . ' = ' . $db->q('plugin'))
		            ->where($db->qn('folder') . ' = ' . $db->q('system'))
		            ->where($db->qn('element') . ' = ' . $db->q('akgeoip'));

		try
		{
			$result = $db->setQuery($query)->loadResult();
		}
		catch (Exception $e)
		{
			return false;
		}

		return !empty($result);
	}

	/**
	 * Does the GeoIP database need update?
	 *
	 * @param   integer  $maxAge  The maximum age of the db in days (default: 30)
	 *
	 * @return  bool
	 */
	public function needsGeoIPUpdate($maxAge = 30)
	{
		// Find the correct database file
		$filePath = JPATH_ROOT . '/plugins/system/akgeoip/db/GeoLite2-City.mmdb';

		if (!is_file($filePath))
		{
			$filePath = JPATH_ROOT . '/plugins/system/akgeoip/db/GeoLite2-Country.mmdb';
		}

		if (!is_file($filePath))
		{
			// No database file found
			return false;
		}

		// Get the modification time of the database file
		$modTime = @filemtime($filePath);

		// This is now
		$now = time();

		// Minimum time difference we want (15 days) in seconds
		if ($maxAge <= 0)
		{
			$maxAge = 30;
		}

		$threshold = $maxAge * 24 * 3600;

		// Do we need an update?
		$needsUpdate = ($now - $modTime) > $threshold;

		return $needsUpdate;
	}

	/**
	 * Do I need to upgrade the GeoIP database to city level?
	 *
	 * @return  bool
	 */
	public function needsGeoIPUpgrade()
	{
		if (!class_exists('AkeebaGeoipProvider'))
		{
			return false;
		}

		if (!method_exists('AkeebaGeoipProvider', 'getCity'))
		{
			return false;
		}

		// Find the correct database file
		$cityPath = JPATH_ROOT . '/plugins/system/akgeoip/db/GeoLite2-City.mmdb';

		return !is_file($cityPath);
	}

	/**
	 * Update the MaxMind GeoLite database
	 *
	 * @param   bool  $forceCity  Should I forcibly upgrade to a city-level database?
	 *
	 * @return  bool
	 */
	public function updateGeoIPDb($forceCity = false)
	{
		// Load the GeoIP library if it's not already loaded
		if (!class_exists('AkeebaGeoipProvider'))
		{
			return false;
		}

		if (!method_exists('AkeebaGeoipProvider', 'getCity'))
		{
			$forceCity = false;
		}

		$geoip  = new AkeebaGeoipProvider();

		if ($forceCity)
		{
			return $geoip->updateDatabase(true);
		}

		return $geoip->updateDatabase();
	}

	/**
	 * Do I need to migrate Joomla Two Factor Authentication information into Akeeba LoginGuard?
	 *
	 * @return  bool
	 */
	public function needsMigration()
	{
		// There is no TFA in Joomla < 3.2
		if (version_compare(JVERSION, '3.2.0', 'lt'))
		{
			return false;
		}

		// Get the users with Joomla! TFA records
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
		            ->select('COUNT(*)')
		            ->from($db->qn('#__users'))
		            ->where($db->qn('otpKey') . ' != ' . $db->q(''))
		            ->where($db->qn('otep') . ' != ' . $db->q(''));
		$result = $db->setQuery($query)->loadResult();

		return !empty($result);
	}
}