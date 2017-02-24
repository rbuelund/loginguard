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
		/** @var LoginGuardModelCaptive $model */
		$model = $this->getModel();

		// Show a title and the component's Options button
		JToolbarHelper::title(JText::_('COM_LOGINGUARD'), 'lock');
		JToolbarHelper::preferences('com_loginguard');

		// Display the view
		return parent::display($tpl);
	}
}