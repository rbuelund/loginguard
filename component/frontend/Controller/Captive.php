<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\LoginGuard\Site\Controller;

use Akeeba\LoginGuard\Site\Model\BackupCodes;
use Akeeba\LoginGuard\Site\Model\Captive as CaptiveModel;
use Exception;
use FOF30\Container\Container;
use FOF30\Controller\Controller;
use FOF30\Controller\Mixin\PredefinedTaskList;
use JLoader;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Router\Route as JRoute;
use Joomla\CMS\Uri\Uri as JUri;
use RuntimeException;

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * Controller for the captive login view
 *
 * @since       2.0.0
 */
class Captive extends Controller
{
	use PredefinedTaskList;

	/**
	 * Constructor. Sets up the default task
	 *
	 * @param   Container  $container
	 * @param   array      $config
	 *
	 * @since   2.0.0
	 */
	public function __construct(Container $container, array $config = [])
	{
		if (!isset($config['default_task']))
		{
			$config['default_task'] = 'captive';
		}

		parent::__construct($container, $config);

		$this->setPredefinedTaskList(['captive', 'validate']);
	}

	/**
	 * Display the captive login page
	 *
	 * @since   2.0.0
	 */
	public function captive()
	{
		$user = $this->container->platform->getUser();

		// Only allow logged in users
		if ($user->guest)
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// If we're already logged in go to the site's home page
		if ($this->container->platform->getSessionVar('tfa_checked', 0, 'com_loginguard') == 1)
		{
			$url = JRoute::_('index.php?option=com_loginguard&task=methods.display', false);
			$this->setRedirect($url);

			return;
		}

		// kill all modules on the page
		try
		{
			/** @var CaptiveModel $model */
			$model = $this->getModel();
			$model->killAllModules();
		}
		catch (Exception $e)
		{
			// If we can't kill the modules we can still survive.
		}

		// Pass the TFA record ID to the model
		$record_id = $this->input->getInt('record_id', null);
		$model->setState('record_id', $record_id);

		// Validate by Browser ID
		$browserId = $this->getBrowserId();

		if (!is_null($browserId) && is_string($browserId) && $model->hasBrowserId($user->id, $browserId))
		{
			// Reaffirm the validity of the browser ID
			$model->hitBrowserId($user->id, $browserId);

			// Tell the plugins that we successfully applied 2SV – used by our User Actions Log plugin.
			$this->container->platform->runPlugins('onComLoginguardCaptiveValidateSuccess', [
				JText::_('COM_LOGINGUARD_LBL_METHOD_BROWSERID'),
			]);

			// Flag the user as fully logged in
			$this->container->platform->setSessionVar('tfa_checked', 1, 'com_loginguard');

			// Get the return URL stored by the plugin in the session
			$return_url = $this->container->platform->getSessionVar('return_url', '', 'com_loginguard');

			// If the return URL is not set or not inside this site redirect to the site's front page
			if (empty($return_url) || !JUri::isInternal($return_url))
			{
				$return_url = JUri::base();
			}

			$this->setRedirect($return_url);

			return;
		}

		$this->display();
	}

	/**
	 * Validate the TFA code entered by the user
	 *
	 * @throws  Exception
	 * @since   2.0.0
	 *
	 */
	public function validate()
	{
		$this->csrfProtection();

		// Only allow logged in users
		if ($this->container->platform->getUser()->guest)
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// Get the TFA parameters from the request
		$record_id = $this->input->getInt('record_id', null);
		$code      = $this->input->get('code', null, 'raw');
		/** @var CaptiveModel $model */
		$model = $this->getModel();

		// Validate the TFA record
		$model->setState('record_id', $record_id);
		$record = $model->getRecord();

		if (empty($record))
		{
			$this->container->platform->runPlugins('onComLoginguardCaptiveValidateInvalidMethod', []);

			throw new RuntimeException(JText::_('COM_LOGINGUARD_ERR_INVALID_METHOD'), 500);
		}

		// Validate the code
		$user = $this->container->platform->getUser();

		$results     = $this->container->platform->runPlugins('onLoginGuardTfaValidate', [$record, $user, $code]);
		$isValidCode = false;

		if ($record->method == 'backupcodes')
		{
			/** @var BackupCodes $codesModel */
			$codesModel = $this->getModel('BackupCodes');
			$results    = [$codesModel->isBackupCode($code, $user)];

			/**
			 * This is required! Do not remove!
			 *
			 * There is a saveRecord() call below. It saves the in-memory TFA record to the database. That includes the options
			 * key which contains the configuration of the method. For backup codes, these are the actual codes you can use.
			 * When we check for a backup code validity we also "burn" it, i.e. we remove it from the options table and save
			 * that to the database. However, this DOES NOT update the $record here. Therefore the call to saveRecord() would
			 * overwrite the database contents with a record that _includes_ the backup code we had just burned. As a result the
			 * single use backup codes end up being multiple use.
			 *
			 * By doing a getRecord() here, right after we have "burned" any correct backup codes, we resolve this issue. The
			 * loaded record will reflect the database contents where the options DO NOT include the code we just used.
			 * Therefore the call to saveRecord() will result in the correct database state, i.e. the used backup code being
			 * removed.
			 */
			$record = $model->getRecord();
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
			$this->container->platform->runPlugins('onComLoginguardCaptiveValidateFailed', [
				$record->title,
			]);

			// The code is wrong. Display an error and go back.
			$captiveURL = JRoute::_('index.php?option=com_loginguard&view=captive&record_id=' . $record_id, false);
			$message    = JText::_('COM_LOGINGUARD_ERR_INVALID_CODE');
			$this->setRedirect($captiveURL, $message, 'error');

			return;
		}

		$this->container->platform->runPlugins('onComLoginguardCaptiveValidateSuccess', [
			$record->title,
		]);

		// Update the Last Used, UA and IP columns
		JLoader::import('joomla.environment.browser');
		$jNow = $this->container->platform->getDate();

		$record->last_used = $jNow->toSql();

		try
		{
			$record->save();
		}
		catch (Exception $e)
		{
			// We don't really care if the timestamp can't be saved to the database
		}

		// Flag the user as fully logged in
		$this->container->platform->setSessionVar('tfa_checked', 1, 'com_loginguard');

		// Get the return URL stored by the plugin in the session
		$return_url = $this->container->platform->getSessionVar('return_url', '', 'com_loginguard');

		// If the return URL is not set or not inside this site redirect to the site's front page
		if (empty($return_url) || !JUri::isInternal($return_url))
		{
			$return_url = JUri::base();
		}

		$this->setRedirect($return_url);
	}

	/**
	 * Get the Browser ID from the session or the request
	 *
	 * Checks if there is a valid request trying to set the browser ID. If so, it's saved in the session and returned.
	 * Otherwise we return whatever browser ID we currently have in the session.
	 *
	 * @return string|null
	 */
	private function getBrowserId(): ?string
	{
		/**
		 * I will only accept a browser ID if it's POSTed with a valid anti-CSRF token and the session flag is set. This
		 * gives me adequate assurances that there's no monkey business going on.
		 */
		try
		{
			$allowedFlag = $this->csrfProtection() &&
				($this->input->getMethod() == 'POST') &&
				$this->container->session->get('browserIdCodeLoaded', false, 'com_loginguard');
		}
		catch (Exception $e)
		{
			$allowedFlag = false;
		}

		// Get the browser ID recorded in the session and in the request
		$browserIdSession = $this->container->session->get('browserId', null, 'com_loginguard');
		$browserIdRequest = $this->input->post->getString('browserId', null);

		// Nobody is trying to set a browser ID in the request. Return the browser ID we stored in the session.
		if (!$allowedFlag && is_null($browserIdRequest))
		{
			return $browserIdSession;
		}

		// Attempt to pass a browser ID from a page other than layout=fingerprint. Pretend fingerprinting failed.
		if (!$allowedFlag)
		{
			$browserIdRequest = '';
		}

		// We already have a browser ID in the session and we're given a different one. That's... strange.
		if (!is_null($browserIdSession) && !empty($browserIdRequest) && ($browserIdRequest != $browserIdSession))
		{
			$browserIdRequest = '';
		}

		// Normalize zero, null and empty string to an empty string that means "fingerprinting failed"
		if (empty($browserIdRequest))
		{
			$browserIdRequest = '';
		}

		// Reset the flag to prevent opportunities to override our browser fingerprinting
		$this->container->session->set('browserIdCodeLoaded', false, 'com_loginguard');
		// Save the browser ID in the session
		$this->container->session->set('browserId', $browserIdRequest, 'com_loginguard');

		// Finally, return the browser ID as requested
		return $browserIdRequest;
	}
}
