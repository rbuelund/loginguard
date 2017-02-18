<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

class LoginGuardControllerCaptive extends JControllerLegacy
{
	public function __construct(array $config = array())
	{
		parent::__construct($config);

		$this->registerDefaultTask('captive');
	}

	public function captive($cachable = false, $urlparams = false)
	{
		// Set the default view name and format
		$id       = $this->input->getInt('user_id', 0);

		// Get the view object
		$document   = JFactory::getDocument();
		$viewLayout = $this->input->get('layout', 'default', 'string');
		$view       = $this->getView('captive', 'html', '', array(
			'base_path' => $this->basePath,
			'layout'    => $viewLayout
		));

		$view->document = $document;

		// If we're already logged in go to the site's home page
		if (JFactory::getSession()->get('tfa_checked', 0, 'com_loginguard') == 1)
		{
			$url       = JRoute::_('index.php?option=com_loginguard&task=methods.display', false);
			JFactory::getApplication()->redirect($url);
		}

		// Pass the model to the view
		/** @var LoginGuardModelCaptive $model */
		$model = $this->getModel('captive');
		$view->setModel($model, true);

		// kill all modules on the page
		$model->killAllModules();

		// Pass the TFA record ID to the model
		$record_id = $this->input->getInt('record_id', null);
		$model->setState('record_id', $record_id);

		// Do not go through $this->display() because it overrides the model, nullifying the whole concept of MVC.
		$view->display();

		return $this;
	}


	/**
	 * Validate the TFA code entered by the user
	 *
	 * @param   bool   $cachable       Can this view be cached
	 * @param   array  $urlparameters  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  self   The current JControllerLegacy object to support chaining.
	 */
	public function validate($cachable = false, $urlparameters = array())
	{
		// CSRF Check
		$token = JSession::getFormToken();
		$value = $this->input->post->getInt($token, 0);

		if ($value != 1)
		{
			die(JText::_('JINVALID_TOKEN'));
		}

		// Get the TFA parameters from the request
		$record_id = $this->input->getInt('record_id', null);
		$code      = $this->input->get('code', null, 'raw');
		/** @var LoginGuardModelCaptive $model */
		$model = $this->getModel('Captive', 'LoginGuardModel');

		// Validate the TFA record
		$model->setState('record_id', $record_id);
		$record = $model->getRecord();

		if (empty($record))
		{
			throw new RuntimeException(JText::_('COM_LOGINGUARD_ERR_INVALID_METHOD'), 500);
		}

		// Validate the code
		$user = JFactory::getUser();

		JLoader::register('LoginGuardHelperTfa', JPATH_SITE . '/components/com_loginguard/helpers/tfa.php');
		$results     = LoginGuardHelperTfa::runPlugins('onLoginGuardTfaValidate', array($record, $user, $code));
		$isValidCode = false;

		if ($record->method == 'backupcodes')
		{
			/** @var LoginGuardModelBackupcodes $codesModel */
			$codesModel = JModelLegacy::getInstance('Backupcodes', 'LoginGuardModel');
			$results = array($codesModel->isBackupCode($code, $user));
		}

		if (is_array($results) && !empty($results))
		{
			foreach ($results as $result)
			{
				if ($result)
				{
					$isValidCode = true;

					break;
				}
			}
		}

		if (!$isValidCode)
		{
			// The code is wrong. Display an error and go back.
			$captiveURL = JRoute::_('index.php?option=com_loginguard&view=captive&record_id=' . $record_id, false);
			$message    = JText::_('COM_LOGINGUARD_ERR_INVALID_CODE');
			$this->setRedirect($captiveURL, $message, 'error');

			return $this;
		}

		// Update the Last Used, UA and IP columns
		JLoader::import('joomla.environment.browser');
		$jNow    = JDate::getInstance();
		$browser = JBrowser::getInstance();
		$session = JFactory::getSession();
		$ip      = $session->get('session.client.address');

		if (empty($ip))
		{
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		$record->last_used = $jNow->toSql();
		$record->ua        = $browser->getAgentString();
		$record->ip        = $ip;

		if (!class_exists('LoginGuardModelMethod'))
		{
			JLoader::register('LoginGuardModelMethod', __DIR__ . '/../models/method.php');
		}

		$methodModel = new LoginGuardModelMethod();

		try
		{
			$methodModel->saveRecord($record);
		}
		catch (Exception $e)
		{
			// We don't really care if the timestamp can't be saved to the database
		}

		// Flag the user as fully logged in
		$session = JFactory::getSession();
		$session->set('tfa_checked', 1, 'com_loginguard');

		// Get the return URL stored by the plugin in the session
		$return_url = $session->get('return_url', '', 'com_loginguard');

		// If the return URL is not set or not inside this site redirect to the site's front page
		if (empty($return_url) || !JUri::isInternal($return_url))
		{
			$return_url = JUri::base();
		}

		$this->setRedirect($return_url);

		return $this;
	}
}