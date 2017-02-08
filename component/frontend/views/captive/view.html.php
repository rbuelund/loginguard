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
	/**
	 * The TFA method records for the current user which correspond to enabled plugins
	 *
	 * @var  array
	 */
	public $records = array();

	/**
	 * The currently selected TFA method record against which we'll be authenticating
	 *
	 * @var  null|stdClass
	 */
	public $record = null;

	/**
	 * The captive TFA page's rendering options
	 *
	 * @var   array|null
	 */
	public $renderOptions = null;

	/**
	 * The title to display at the top of the page
	 *
	 * @var   string
	 */
	public $title = '';

	/**
	 * Is this an administrator page?
	 *
	 * @var   bool
	 */
	public $isAdmin = false;

	function display($tpl = null)
	{
		/** @var LoginGuardModelCaptive $model */
		$model = $this->getModel();

		$this->isAdmin = $model->isAdminPage();

		// Load the list of TFA records and the currently selected record (if any)
		$this->records = $this->get('records');
		$this->record  = $this->get('record');

		// If we only have one record there's no point asking the user to select a TFA method
		if (count($this->records) == 1)
		{
			$this->record = $this->records[0];
		}

		// Set the correct layout based on the records
		$this->setLayout('select');

		if (!is_null($this->record))
		{
			$this->setLayout('default');

			// If we have a selected record load its rendering options
			$this->renderOptions = $model->loadCaptiveRenderOptions($this->record);
		}

		// Which title should I use for the page?
		$this->title = $this->get('PageTitle');

		// Back-end: always show a title in the 'title' module position, not in the page body
		if ($this->isAdmin)
		{
			JToolbarHelper::title(JText::_('COM_LOGINGUARD_HEAD_TFA_PAGE'), 'lock');
			$this->title = '';
		}

		// Display the view
		parent::display($tpl);
	}
}