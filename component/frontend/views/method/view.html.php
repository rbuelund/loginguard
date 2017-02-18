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

		// Backend: display a save button, unless we're showing Backup Codes
		if ($this->isAdmin && ($this->record->method != 'backupcodes'))
		{
			$bar = JToolbar::getInstance('toolbar');

			if ($this->renderOptions['show_submit'] || empty($this->record->id))
			{
				$this->renderSubmitToolbarButton($bar);
			}
		}

		// Back-end: always show a title in the 'title' module position, not in the page body
		if ($this->isAdmin)
		{
			JToolbarHelper::title(JText::_('COM_LOGINGUARD') . " <small>" . $this->title . "</small>", 'lock');

			$this->title = '';

			$bar = JToolbar::getInstance('toolbar');
			$url = JRoute::_('index.php?option=com_loginguard&task=methods.display&user_id=' . $this->user->id);

			if (!empty($this->returnURL))
			{
				$url = base64_decode($this->returnURL);
			}

			$bar->appendButton('Link', 'cancel', 'JTOOLBAR_CANCEL', $url);
		}

		// Include CSS
		JHtml::_('stylesheet', 'com_loginguard/methods.min.css', array(
			'version'     => 'auto',
			'relative'    => true,
			'detectDebug' => true
		), true, false, false, true);

		// Display the view
		return parent::display($tpl);
	}

	/**
	 * Renders the form's Submit button in the toolbar
	 *
	 * The only way to construct a custom button is The Super Hard Way. We have to use JLayout to convert the icon name
	 * to a usable class name first. Then we have to use yet another JLayout to render the custom button. Despite your
	 * expectations the correct JLayout template is NOT joomla.toolbar.custom since it's just an echo of HTML. It's not
	 * even joomla.toolbar.standard since it expects the PHP code to know the button class we need to use (therefore
	 * completely nullifying the reason for having a JLayout, which is what had lead me to call JLayout a stupid idea
	 * ever since it was imlemented). Instead we have to use joomla.toolbar.confirm(!!!) because that is the ONLY layout
	 * which allows us to just give it a label, an icon class and some JavaScript to execute.
	 *
	 * @param   JToolbar  $bar  The backend toolbar object
	 *
	 * @return  void
	 */
	private function renderSubmitToolbarButton($bar)
	{
		$iconOptions = array(
			'icon' => 'save'
		);

		$iconLayout  = new JLayoutFile('joomla.toolbar.iconclass');
		$iconClass   = $iconLayout->render($iconOptions);

		$buttonOptions = array(
			'doTask' => 'document.forms[\'loginguard-method-edit\'].submit();',
			'class'  => $iconClass,
			'text'   => JText::_('COM_LOGINGUARD_LBL_EDIT_SUBMIT')
		);

		if ($this->renderOptions['submit_onclick'])
		{
			$buttonOptions['doTask'] = $this->renderOptions['submit_onclick'];
		}

		$buttonLayout  = new JLayoutFile('joomla.toolbar.confirm');
		$buttonHtml    = $buttonLayout->render($buttonOptions);

		$bar->appendButton('Custom', $buttonHtml, 'something');
	}
}