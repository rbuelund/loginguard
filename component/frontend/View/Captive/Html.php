<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\LoginGuard\Site\View\Captive;

// Protect from unauthorized access
use Akeeba\LoginGuard\Site\Helper\Tfa;
use Akeeba\LoginGuard\Site\Model\BackupCodes;
use Akeeba\LoginGuard\Site\Model\Captive;
use FOF30\View\DataView\Html as BaseView;
use Joomla\CMS\Language\Text as JText;

defined('_JEXEC') or die();

class Html extends BaseView
{
	/**
	 * The TFA method records for the current user which correspond to enabled plugins
	 *
	 * @var   \Akeeba\LoginGuard\Site\Model\Tfa[]
	 * @since 2.0.0
	 */
	public $records = [];

	/**
	 * The currently selected TFA method record against which we'll be authenticating
	 *
	 * @var   \Akeeba\LoginGuard\Site\Model\Tfa
	 * @since 2.0.0
	 */
	public $record = null;

	/**
	 * The captive TFA page's rendering options
	 *
	 * @var   array|null
	 * @since 2.0.0
	 */
	public $renderOptions = null;

	/**
	 * The title to display at the top of the page
	 *
	 * @var   string
	 * @since 2.0.0
	 */
	public $title = '';

	/**
	 * Is this an administrator page?
	 *
	 * @var   bool
	 * @since 2.0.0
	 */
	public $isAdmin = false;

	/**
	 * Does the currently selected method allow authenticating against all of its records?
	 *
	 * @var   bool
	 * @since 2.0.0
	 */
	public $allowEntryBatching = false;

	/**
	 * All enabled TFA methods (plugins)
	 *
	 * @var   array
	 * @since 2.0.0
	 */
	public $tfaMethods;

	/**
	 * Executes before displaying the captive login page
	 *
	 * @return  void
	 * @since   2.0.0
	 */
	function onBeforeCaptive()
	{
		/** @var Captive $model */
		$model = $this->getModel();

		// Load data from the model
		$this->isAdmin         = $this->container->platform->isBackend();
		$this->records         = $this->get('records');
		$this->record          = $this->get('record');
		$this->tfaMethods      = Tfa::getTfaMethods();

		if (!empty($this->records))
		{
			/** @var BackupCodes $codesModel */
			$codesModel = $this->container->factory->model('BackupCodes');
			$backupCodesRecord = $codesModel->getBackupCodesRecord();

			if (!is_null($backupCodesRecord))
			{
				$backupCodesRecord->title = JText::_('COM_LOGINGUARD_LBL_BACKUPCODES');
				$this->records[] = $backupCodesRecord;
			}
		}

		// If we only have one record there's no point asking the user to select a TFA method
		if (empty($this->record))
		{
			// Default to the first record
			$this->record = reset($this->records);

			// If we have multiple records try to make this record the default
			if (count($this->records) > 1)
			{
				foreach ($this->records as $record)
				{
					if ($record->default)
					{
						$this->record = $record;

						break;
					}
				}
			}
		}

		$this->renderOptions      = $model->loadCaptiveRenderOptions($this->record);
		$this->allowEntryBatching = isset($this->renderOptions['allowEntryBatching']) ? $this->renderOptions['allowEntryBatching'] : 0;

		// Set the correct layout based on the availability of a TFA record
		$this->setLayout('default');

		if (is_null($this->record) || ($model->getState('task') == 'select'))
		{
			$this->setLayout('select');
		}

		// Which title should I use for the page?
		$this->title = $this->get('PageTitle');

		// Include CSS
		$this->addCssFile('media://com_loginguard/css/captive.min.css', null, 'text/css', null, [
			'relative'    => true,
			'detectDebug' => true
		]);
	}

}
