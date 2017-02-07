<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

class LoginGuardViewCaptive extends JViewLegacy
{
	function display($tpl = null)
	{
		/** @var LoginGuardModelCaptive $model */
		$model = $this->getModel();

		// TODO Implement this view

		// Back-end: show a title
		if ($model->isAdminPage())
		{
			JToolbarHelper::title(JText::_('COM_LOGINGUARD_HEAD_TFA_PAGE'), 'lock');
		}

		// Display the view
		parent::display($tpl);
	}
}