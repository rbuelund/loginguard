<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

class LoginGuardViewMethod extends JViewLegacy
{
	/**
	 * Is this an administrator page?
	 *
	 * @var   bool
	 */
	public $isAdmin = false;

	/**
	 * The editor page render options
	 *
	 * @var   array
	 */
	public $renderOptions = array();

	/**
	 * The TFA method record being edited
	 *
	 * @var   object
	 */
	public $record = null;

	/**
	 * The title text for this page
	 *
	 * @var  string
	 */
	public $title = '';

	/**
	 * The return URL to use for all links and forms
	 *
	 * @var   string
	 */
	public $returnURL = null;

	/**
	 * The user object used to display this page
	 *
	 * @var   JUser
	 */
	public $user = null;

	/**
	 * The backup codes for the current user. Only applies when the backup codes record is being "edited"
	 *
	 * @var   array
	 */
	public $backupCodes = array();

	/**
	 * Am I editing an existing method? If it's false then I'm adding a new method.
	 *
	 * @var   bool
	 */
	public $isEditExisting = false;

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

		/** @var LoginGuardModelMethod $model */
		$model = $this->getModel();
		$this->setLayout('edit');
		$this->renderOptions = $model->getRenderOptions($this->user);
		$this->record        = $model->getRecord($this->user);
		$this->title         = $model->getPageTitle();
		$this->isAdmin       = LoginGuardHelperTfa::isAdminPage();

		// Backup codes are a special case, rendered with a special layout
		if ($this->record->method == 'backupcodes')
		{
			$this->setLayout('backupcodes');

			$backupCodes = json_decode($this->record->options);

			if (!is_array($backupCodes))
			{
				$backupCodes = array();
			}

			$backupCodes = array_filter($backupCodes, function ($x) {
				return !empty($x);
			});

			if (count($backupCodes) % 2 != 0)
			{
				$backupCodes[] = '';
			}

			/**
			 * The call to array_merge resets the array indices. This is necessary since array_filter kept the indices,
			 * meaning our elements are completely out of order.
			 */
			$this->backupCodes = array_merge($backupCodes);
		}

		// Set up the isEditExisting property.
		$this->isEditExisting = !empty($this->record->id);

		// Back-end: always show a title in the 'title' module position, not in the page body
		if ($this->isAdmin)
		{
			JToolbarHelper::title(JText::_('COM_LOGINGUARD') . " <small>" . $this->title . "</small>", 'lock');

			$helpUrl     = $this->renderOptions['help_url'];

			if (!empty($helpUrl))
			{
				JToolbarHelper::help('', false, $helpUrl);
			}

			$this->title = '';
		}

		// Get the media version
		JLoader::register('LoginGuardHelperVersion', JPATH_SITE . '/components/com_loginguard/helpers/version.php');
		$mediaVersion = md5(LoginGuardHelperVersion::component('com_loginguard'));

		// Include CSS
		if (version_compare(JVERSION, '3.6.999', 'le'))
		{
			JHtml::_('stylesheet', 'com_loginguard/methods.min.css', array(
				'version'     => $mediaVersion,
				'relative'    => true,
				'detectDebug' => true
			), true, false, false, true);
		}
		else
		{
			JHtml::_('stylesheet', 'com_loginguard/methods.min.css', array(
				'version'       => $mediaVersion,
				'relative'      => true,
				'detectDebug'   => true,
				'pathOnly'      => false,
				'detectBrowser' => true,
			), array(
				'type' => 'text/css',
			));
		}

		// Display the view
		return parent::display($tpl);
	}
}