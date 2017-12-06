<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

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

		$user_id = $this->form->getData()->get('id', null);

		if (is_null($user_id))
		{
			return JText::_('PLG_USER_LOGINGUARD_ERR_NOUSER');
		}

		$user = JFactory::getUser($user_id);

		// Get a model
		/** @var LoginGuardModelMethods $model */
		$model = new LoginGuardModelMethods();

		// Get a view object
		$view = new LoginGuardViewMethods(array(
			'base_path' => JPATH_SITE . '/components/com_loginguard'
		));
		$view->setModel($model, true);
		$view->returnURL = base64_encode(JUri::getInstance()->toString());
		$view->user      = $user;

		return $view->display();
	}
}
