<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

class LoginGuardViewMethods extends JViewLegacy
{
	/**
	 * Is this an administrator page?
	 *
	 * @var   bool
	 */
	public $isAdmin = false;

	/**
	 * The TFA methods available for this user
	 *
	 * @var   array
	 */
	public $methods = array();

	/**
	 * The return URL to use for all links and forms
	 *
	 * @var   string
	 */
	public $returnURL = null;

	/**
	 * Are there any active TFA methods at all?
	 *
	 * @var   bool
	 */
	public $tfaActive = false;

	/**
	 * Which method has the default record?
	 *
	 * @var   string
	 */
	public $defaultMethod = '';

	/**
	 * The user object used to display this page
	 *
	 * @var   JUser
	 */
	public $user = null;

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
		if (empty($this->user))
		{
			$this->user = JFactory::getUser();
		}

		/** @var LoginGuardModelMethods $model */
		$model = $this->getModel();

		$this->setLayout('list');
		$this->methods = $model->getMethods($this->user);
		$this->isAdmin = LoginGuardHelperTfa::isAdminPage();

		$activeRecords = 0;

		if (count($this->methods))
		{
			foreach ($this->methods as $methodName => $method)
			{
				$methodActiveRecords = count($method['active']);

				if (!$methodActiveRecords)
				{
					continue;
				}

				$activeRecords += $methodActiveRecords;
				$this->tfaActive = true;

				foreach ($method['active'] as $record)
				{
					if ($record->default)
					{
						$this->defaultMethod = $methodName;

						break;
					}
				}
			}
		}

		// If there are no backup codes yet we should create new ones
		/** @var LoginGuardModelBackupcodes $model */
		$model = JModelLegacy::getInstance('Backupcodes', 'LoginGuardModel');

		if ($activeRecords && empty($model->getBackupCodes($this->user)))
		{
			$model->regenerateBackupCodes($this->user);
		}

		$backupCodesRecord = $model->getBackupCodesRecord($this->user);

		if (!is_null($backupCodesRecord))
		{
			$this->methods['backupcodes'] = array(
				'name' => 'backupcodes',
				'display' => JText::_('COM_LOGINGUARD_LBL_BACKUPCODES'),
				'shortinfo' => JText::_('COM_LOGINGUARD_LBL_BACKUPCODES_DESCRIPTION'),
				'image' => 'media/com_loginguard/images/emergency.svg',
				'canDisable' => false,
				'allowMultiple' => false,
				'active' => array($backupCodesRecord)
			);
		}

		// Include CSS
		JHtml::_('stylesheet', 'com_loginguard/methods.min.css', array(
			'version'     => 'auto',
			'relative'    => true,
			'detectDebug' => true
		), true, false, false, true);

		// Back-end: always show a title in the 'title' module position, not in the page body
		if ($this->isAdmin)
		{
			JToolbarHelper::title(JText::_('COM_LOGINGUARD') . " <small>" . JText::_('COM_LOGINGUARD_HEAD_LIST_PAGE') . "</small>", 'lock');
			$this->title = '';
		}

		// Display the view
		return parent::display($tpl);
	}
}