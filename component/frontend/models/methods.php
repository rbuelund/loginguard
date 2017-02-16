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

	/**
	 * Format a relative timestamp. It deals with timestamps today and yesterday in a special manner. Example returns:
	 * Yesterday, 13:12
	 * Today, 08:33
	 * January 1, 2015
	 *
	 * @param   string  $dateTimeText  The database time string to use, e.g. "2017-01-13 13:25:36"
	 *
	 * @return  string  The formatted, human-readable date
	 */
	public function formatRelative($dateTimeText)
	{
		// The timestamp is given in UTC. Make sure Joomla! parses it as such.
		$utcTimeZone = new DateTimeZone('UTC');
		$jDate       = JDate::getInstance($dateTimeText, $utcTimeZone);
		$unixStamp   = $jDate->toUnix();

		// I'm pretty sure we didn't have TFA in Joomla back in 1970 ;)
		if ($unixStamp < 0)
		{
			return '&ndash;';
		}

		// I need to display the date in the user's local timezone. That's how you do it.
		$user   = JFactory::getUser();
		$userTZ = $user->getParam('timezone', 'UTC');
		$tz     = new DateTimeZone($userTZ);
		$jDate->setTimezone($tz);

		// Default format string: way in the past, the time of the day is not important
		$formatString    = JText::_('COM_LOGINGUARD_LBL_DATE_FORMAT_PAST');
		$containerString = JText::_('COM_LOGINGUARD_LBL_PAST');

		// If the timestamp is within the last 72 hours we may need a special format
		if ($unixStamp > (time() - (72 * 3600)))
		{
			// Is this timestamp today?
			$jNow = JDate::getInstance();
			$jNow->setTimezone($tz);
			$checkNow  = $jNow->format('Ymd', true);
			$checkDate = $jDate->format('Ymd', true);

			if ($checkDate == $checkNow)
			{
				$formatString    = JText::_('COM_LOGINGUARD_LBL_DATE_FORMAT_TODAY');
				$containerString = JText::_('COM_LOGINGUARD_LBL_TODAY');
			}
			else
			{
				// Is this timestamp yesterday?
				$jYesterday = clone $jNow;
				$jYesterday->setTime(0, 0, 0);
				$oneSecond = new DateInterval('PT1S');
				$jYesterday->sub($oneSecond);
				$checkYesterday = $jYesterday->format('Ymd', true);

				if ($checkDate == $checkYesterday)
				{
					$formatString    = JText::_('COM_LOGINGUARD_LBL_DATE_FORMAT_YESTERDAY');
					$containerString = JText::_('COM_LOGINGUARD_LBL_YESTERDAY');
				}
			}
		}

		return sprintf($containerString, $jDate->format($formatString, true));
	}

	/**
	 * Extract the the browser and platform from a User Agent string and format them in a human-readable manner.
	 *
	 * @param   string  $ua  A User-Agent string
	 *
	 * @return  string  Human readable format, e.g. "Chrome on Windows"
	 */
	public function formatBrowser($ua)
	{
		if (empty($ua))
		{
			return '';
		}

		JLoader::import('joomla.environment.browser');
		$jBrowser = JBrowser::getInstance($ua);
		$platform = $jBrowser->getPlatform();
		$browser = $jBrowser->getBrowser();

		// Let's make sure we have the correct platform
		if (strpos($ua, '; Android') !== false)
		{
			$platform = 'android';
		}
		elseif ((strpos($ua, 'iPhone;')) !== false || (strpos($ua, 'iPad;') !== false) || (strpos($ua, 'iPod;') !== false) || (strpos($ua, 'iPad touch;') !== false))
		{
			$platform = 'ios';
		}
		elseif (strpos($ua, 'Linux') !== false)
		{
			$platform = 'linux';
		}

		// Let's make sure we have the correct browser
		if (strpos($ua, 'Edge/'))
		{
			$browser = 'edge';
		}
		if (strpos($ua, 'Chromium/'))
		{
			$browser = 'chromium';
		}
		elseif (strpos($ua, 'Opera Mini/'))
		{
			$browser = 'operamini';
		}
		elseif (strpos($ua, 'Maxthon;'))
		{
			$browser = 'maxthon';
		}
		elseif (strpos($ua, 'YaBrowser/'))
		{
			$browser = 'yandex';
		}
		elseif (strpos($ua, 'Avant Browser'))
		{
			$browser = 'avant';
		}
		elseif (strpos($ua, 'Camino/'))
		{
			$browser = 'camino';
		}
		elseif (strpos($ua, 'Epiphany/'))
		{
			$browser = 'epiphany';
		}
		elseif (strpos($ua, 'Galeon/'))
		{
			$browser = 'galeon';
		}
		elseif (strpos($ua, 'Iceweasel/'))
		{
			$browser = 'iceweasel';
		}
		elseif (strpos($ua, 'K-Meleon/'))
		{
			$browser = 'kmeleon';
		}
		elseif (strpos($ua, 'Midori/'))
		{
			$browser = 'midori';
		}
		elseif (strpos($ua, 'rekonq/'))
		{
			$browser = 'rekonq';
		}
		elseif (strpos($ua, 'SamsungBrowser/'))
		{
			$browser = 'samsung';
		}
		elseif (strpos($ua, 'SeaMonkey/'))
		{
			$browser = 'seamonkey';
		}
		elseif (strpos($ua, 'Iron/'))
		{
			$browser = 'iron';
		}
		elseif (strpos($ua, 'Dalvik/'))
		{
			$browser = 'android';
		}
		elseif (strpos($ua, 'presto/'))
		{
			$browser = 'android';
		}
		elseif (strpos($ua, 'Vivaldi/'))
		{
			$browser = 'vivaldi';
		}

		// Translate the information
		$platformText  = JText::_('COM_LOGINGUARD_LBL_BROWSER_PLATFORM_' . $platform);
		$browserString = JText::_('COM_LOGINGUARD_LBL_BROWSER_' . $browser);

		return JText::sprintf('COM_LOGINGUARD_LBL_BROWSER', $browserString, $platformText);
	}

	/**
	 * Format an IP address in a human readable format, either as an IP or - if the Akeeba GeoIP plugin is installed and
	 * enabled - as a country or as a city and country (depending on the GeoIP database you have installed).
	 *
	 * @param   string  $ip  The IPv4/IPv6 address of the visitor.
	 *
	 * @return  string  Human readable format e.g. "on 123.123.123.123", "from Germany", or "from Tokyo, Japan"
	 */
	public function formatIp($ip)
	{
		if (empty($ip))
		{
			return '';
		}

		$string = JText::sprintf('COM_LOGINGUARD_LBL_FROMIP', $ip);

		if (class_exists('AkeebaGeoipProvider'))
		{
			$geoip     = new AkeebaGeoipProvider();
			$country   = $geoip->getCountryName($ip);

			if (!empty($country))
			{
				$string = JText::sprintf('COM_LOGINGUARD_LBL_FROMCOUNTRY', $country);
			}

			if (method_exists($geoip, 'getCity'))
			{
				$city = $geoip->getCity($ip);

				if (!empty($city) && !empty($country))
				{
					$string = JText::sprintf('COM_LOGINGUARD_LBL_FROMCITYCOUNTRY', $city, $country);
				}
			}
		}

		return $string;
	}
}