<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2006-2017 Nicholas K. Dionysopoulos / Akeeba Ltd
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

		// Captive view: kill all modules on the page
		if ($view == 'captive')
		{
			/** @var LoginGuardModelCaptive $model */
			$model = $this->getModel('captive', 'LoginGuardModel');
			$model->killAllModules();
		}

		parent::display($cachable, $urlparams);

		return $this;
	}

}