<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

class LoginGuardViewWelcome extends JViewLegacy
{
	/**
	 * Is the user plugin missing / disabled?
	 *
	 * @var   bool
	 */
	public $noUserPlugin = false;

	/**
	 * Is the system plugin missing / disabled?
	 *
	 * @var   bool
	 */
	public $noSystemPlugin = false;

	/**
	 * Are no published methods detected?
	 *
	 * @var   bool
	 */
	public $noMethods = false;

	/**
	 * Are no loginguard plugins installed?
	 *
	 * @var   bool
	 */
	public $notInstalled = false;

	/**
	 * Is the GeoIP plugin not installed?
	 *
	 * @var   bool
	 */
	public $noGeoIP = false;

	/**
	 * Does the GeoIP database require an update?
	 *
	 * @var   bool
	 */
	public $geoIPNeedsUpdate = false;

	/**
	 * Does the GeoIP database require an upgrade from country-only data to city-level data? Only available when you use
	 * the GeoIP provider plugin v.2.0 or later.
	 *
	 * @var   bool
	 */
	public $geoIPNeedsUpgrade = false;

	/**
	 * Do we have to migrate from Joomla's Two Factor Authentication?
	 *
	 * @var   bool
	 */
	public $needsMigration = false;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 *
	 * @see     JViewLegacy::loadTemplate()
	 */
	function display($tpl = null)
	{
		/** @var LoginGuardModelWelcome $model */
		$model = $this->getModel();

		$this->noMethods         = !$model->hasPublishedPlugins();
		$this->notInstalled      = !$model->hasInstalledPlugins();
		$this->noGeoIP           = !$model->hasGeoIPPlugin();
		$this->geoIPNeedsUpdate  = $model->needsGeoIPUpdate();
		$this->geoIPNeedsUpgrade = $model->needsGeoIPUpgrade();
		$this->noUserPlugin      = !$model->isLoginGuardPluginPublished('user');
		$this->noSystemPlugin    = !$model->isLoginGuardPluginPublished('system');
		$this->needsMigration    = $model->needsMigration();

		// Show a title and the component's Options button
		JToolbarHelper::title(JText::_('COM_LOGINGUARD') . ': <small>' . JText::_('COM_LOGINGUARD_HEAD_WELCOME') . '</small>', 'lock');
		JToolbarHelper::help('', false, 'https://github.com/akeeba/loginguard/wiki');
		JToolbarHelper::preferences('com_loginguard');

		// Display the view
		return parent::display($tpl);
	}
}