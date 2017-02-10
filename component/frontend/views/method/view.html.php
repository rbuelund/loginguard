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
		$this->setLayout('edit');
		$this->renderOptions = $this->get('RenderOptions');
		$this->record        = $this->get('record');
		$this->title         = $this->get('PageTitle');

		// Back-end: always show a title in the 'title' module position, not in the page body
		if ($this->isAdmin)
		{
			JToolbarHelper::title(JText::_($this->title), 'lock');
			$this->title = '';
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
}