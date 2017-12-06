<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

class LoginGuardViewConvert extends JViewLegacy
{
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
		// Show a title and the component's Options button
		JToolbarHelper::title(JText::_('COM_LOGINGUARD') . ': <small>' . JText::_('COM_LOGINGUARD_HEAD_CONVERT') . '</small>', 'loginguard');

		if ($this->getLayout() != 'done')
		{
			$js = <<< JS
window.jQuery(document).ready(function (){
	document.forms.adminForm.submit();
});

JS;

			JFactory::getDocument()->addScriptDeclaration($js);
		}
		else
		{
			JToolbarHelper::back('JTOOLBAR_BACK', JRoute::_('index.php?option=com_loginguard'));
		}

		// Display the view
		return parent::display($tpl);
	}
}