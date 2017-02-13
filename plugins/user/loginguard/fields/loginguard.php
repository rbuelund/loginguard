<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

JLoader::register('LoginGuardViewMethods', JPATH_SITE . '/components/com_loginguard/views/methods/view.html.php');
JLoader::register('LoginGuardModelMethods', JPATH_SITE . '/components/com_loginguard/models/methods.php');

class JFormFieldLoginguard extends JFormField
{
	/**
	 * Element name
	 *
	 * @var   string
	 */
	protected $_name = 'Loginguard';

	function getInput()
	{
		// Make sure we can load the classes we need
		if (!class_exists('LoginGuardViewMethods', true) || !class_exists('LoginGuardModelMethods', true))
		{
			return JText::_('PLG_USER_LOGINGUARD_ERR_NOCOMPONENT');
		}

		// Load the language files
		JFactory::getLanguage()->load('com_loginguard', JPATH_SITE, null, true, true);

		// Get a model
		/** @var LoginGuardModelMethods $model */
		$model = new LoginGuardModelMethods();

		// Get a view object
		$view = new LoginGuardViewMethods(array(
			'base_path' => JPATH_SITE . '/components/com_loginguard'
		));
		$view->setModel($model, true);
		$view->returnURL = base64_encode(JUri::current());

		return $view->display();
	}
}