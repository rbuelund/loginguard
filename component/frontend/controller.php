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
		// Set the default view name and format
		$id       = $this->input->getInt('user_id', 0);
		$viewName = $this->input->getCmd('view', 'captive');
		$this->input->set('view', $viewName);

		// Get the view object
		$document   = JFactory::getDocument();
		$viewType   = $document->getType();
		$viewLayout = $this->input->get('layout', 'default', 'string');
		$view       = $this->getView($viewName, $viewType, '', array(
			'base_path' => $this->basePath,
			'layout'    => $viewLayout
		));

		$view->document = $document;

		// Check for edit form.
		if ($viewName === 'form' && !$this->checkEditId('com_loginguard.edit.user', $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			throw new RuntimeException(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id), 403);
		}

		// Captive view
		if ($viewName == 'captive')
		{
			// If we're already logged in go to the site's home page
			if (JFactory::getSession()->get('tfa_checked', 0, 'com_loginguard') == 1)
			{
				$nonSefUrl = 'index.php?option=com_loginguard&';

				if (LoginGuardHelperTfa::isAdminPage())
				{
					$nonSefUrl .= 'task=users.default';
				}
				else
				{
					$nonSefUrl .= 'task=methods.display';
				}

				$url       = JRoute::_($nonSefUrl, false);
				JFactory::getApplication()->redirect($url);
			}

			// Pass the model to the view
			/** @var LoginGuardModelCaptive $model */
			$model = $this->getModel($viewName);
			$view->setModel($model, true);

			// kill all modules on the page
			$model->killAllModules();

			// Pass the TFA record ID to the model
			$record_id = $this->input->getInt('record_id', null);
			$model->setState('record_id', $record_id);
		}

		// Do not go through $this->display() because it overrides the model, nullifying the whole concept of MVC.
		$view->display();

		return $this;
	}

}