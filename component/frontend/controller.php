<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

class LoginGuardController extends JControllerLegacy
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached.
	 * @param   boolean  $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JControllerLegacy  This object to support chaining.
	 */
	public function display($cachable = false, $urlparams = false)
	{
		$cachable = false;

		// Set the default view name and format
		$id   = $this->input->getInt('user_id', 0);
		$view = $this->input->getCmd('view', 'captive');
		$this->input->set('view', $view);

		// Check for edit form.
		if ($view === 'form' && !$this->checkEditId('com_loginguard.edit.user', $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			throw new RuntimeException(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 403);
		}

		// Captive view
		if ($view == 'captive')
		{
			// If we're already logged in go to the site's home page
			if (JFactory::getSession()->get('tfa_checked', 0, 'com_loginguard') == 1)
			{
				$homeURL = JUri::base();
				JFactory::getApplication()->redirect($homeURL);
			}

			// kill all modules on the page
			/** @var LoginGuardModelCaptive $model */
			$model = $this->getModel('captive', 'LoginGuardModel');
			$model->killAllModules();

			// Pass the TFA record ID to the model
			$record_id = $this->input->getInt('record_id', null);
			$model->setState('record_id', $record_id);
		}

		parent::display($cachable, $urlparams);

		return $this;
	}

}