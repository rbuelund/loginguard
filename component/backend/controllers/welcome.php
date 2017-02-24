<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

class LoginGuardControllerWelcome extends JControllerLegacy
{
	public function __construct(array $config = array())
	{
		parent::__construct($config);

		$this->registerDefaultTask('welcome');
	}

	public function captive($cachable = false, $urlparams = false)
	{
		// Get the view object
		$document   = JFactory::getDocument();
		$viewLayout = $this->input->get('layout', 'default', 'string');
		$view       = $this->getView('captive', 'html', '', array(
			'base_path' => $this->basePath,
			'layout'    => $viewLayout
		));

		$view->document = $document;

		/** @var LoginGuardModelCaptive $model */
		$model = $this->getModel('captive');
		$view->setModel($model, true);

		// TODO Do Stuff

		// Do not go through $this->display() because it overrides the model, nullifying the whole concept of MVC.
		$view->display();

		return $this;
	}
}