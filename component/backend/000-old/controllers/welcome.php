<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

class LoginGuardControllerWelcome extends JControllerLegacy
{
	public function __construct(array $config = array())
	{
		parent::__construct($config);

		$this->registerDefaultTask('welcome');
	}

	public function welcome($cachable = false, $urlparams = false)
	{
		$this->assertSuperUser();

		return $this->display(false, false);
	}

	/**
	 * Handle the update of the GeoIP database
	 *
	 * @return  $this
	 */
	public function updategeoip()
	{
		$message = JText::_('COM_LOGINGUARD_MSG_GEOIP_UPDATE_SUCCESS');
		$type = 'info';

		if (!$this->handleGeoIPUpdate(false))
		{
			$message = JText::_('COM_LOGINGUARD_MSG_GEOIP_UPDATE_FAIL');
			$type = 'error';
		}

		$this->setRedirect(JRoute::_('index.php?option=com_loginguard&view=welcome'), $message, $type);

		return $this;
	}

	/**
	 * Handle the upgrade of the GeoIP database to the city-level version
	 *
	 * @return  $this
	 */
	public function upgradeageoip()
	{
		$message = JText::_('COM_LOGINGUARD_MSG_GEOIP_UPGRADE_SUCCESS');
		$type = 'info';

		if (!$this->handleGeoIPUpdate(true))
		{
			$message = JText::_('COM_LOGINGUARD_MSG_GEOIP_UPGRADE_FAIL');
			$type = 'error';
		}

		$this->setRedirect(JRoute::_('index.php?option=com_loginguard&view=welcome'), $message, $type);

		return $this;
	}

	/**
	 * Handles the update of the GeoIP database
	 *
	 * @param   bool  $forceCity  Should I force an upgrade to the city-level database?
	 *
	 * @return  bool  True on success
	 */
	protected function handleGeoIPUpdate($forceCity = false)
	{
		$token = JFactory::getSession()->getToken();

		if ($this->input->getInt($token, 0) !== 1)
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		/** @var LoginGuardModelWelcome $moodel */
		$moodel = $this->getModel('welcome');
		return $moodel->updateGeoIPDb($forceCity);
	}

	/**
	 * Assert that the user is a Super User
	 *
	 * @param   JUser   $user  The user to assert. Null to use the currently logged in user
	 *
	 * @return  void
	 */
	protected function assertSuperUser(JUser $user = null)
	{
		if (empty($user))
		{
			$user = JFactory::getUser();
		}

		if ($user->authorise('core.admin'))
		{
			return;
		}

		throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
	}
}