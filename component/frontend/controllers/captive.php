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
	/**
	 * Validate the TFA code entered by the user
	 *
	 * @param   bool   $cachable       Can this view be cached
	 * @param   array  $urlparameters  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 */
	public function validate($cachable = false, $urlparameters = array())
	{
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
		$results = LoginGuardHelperTfa::runPlugins('onLoginGuardTfaValidate', array($record, $user, $code));
		$isValidCode = false;

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
			$captiveURL = JRoute::_('index.php?option=com_loginguard&view=captive', false);
			$message    = JText::_('COM_LOGINGUARD_ERR_INVALID_CODE');
			$this->setRedirect($captiveURL, $message, 'error');

			return;
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
	}
}