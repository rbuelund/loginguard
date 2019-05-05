<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\LoginGuard\Site\Controller;

use Akeeba\LoginGuard\Site\Model\BackupCodes;
use Akeeba\LoginGuard\Site\Model\Captive as CaptiveModel;
use Exception;
use FOF30\Container\Container;
use FOF30\Controller\Controller;
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
	}

	/**
	 * Display the captive login page
	 *
	 * @since   2.0.0
	 */
	public function captive()
	{
		// If we're already logged in go to the site's home page
		if ($this->container->platform->getSessionVar('tfa_checked', 0, 'com_loginguard') == 1)
		{
			$url       = JRoute::_('index.php?option=com_loginguard&task=methods.display', false);
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

		$this->display();
	}

	/**
	 * Validate the TFA code entered by the user
	 *
	 * @since   2.0.0
	 *
	 * @throws  Exception
	 */
	public function validate()
	{
		$this->csrfProtection();

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
				$record->title
			]);

			// The code is wrong. Display an error and go back.
			$captiveURL = JRoute::_('index.php?option=com_loginguard&view=captive&record_id=' . $record_id, false);
			$message    = JText::_('COM_LOGINGUARD_ERR_INVALID_CODE');
			$this->setRedirect($captiveURL, $message, 'error');

			return;
		}

		$this->container->platform->runPlugins('onComLoginguardCaptiveValidateSuccess', [
			$record->title
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
}
