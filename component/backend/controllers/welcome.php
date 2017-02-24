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

	public function welcome($cachable = false, $urlparams = false)
	{
		$this->assertSuperUser();

		// Get the view object
		$document   = JFactory::getDocument();
		$viewLayout = $this->input->get('layout', 'default', 'string');
		/** @var LoginGuardViewWelcome $view */
		$view       = $this->getView('welcome', 'html', '', array(
			'base_path' => $this->basePath,
			'layout'    => $viewLayout
		));

		$view->document = $document;

		/** @var LoginGuardModelWelcome $model */
		$model = $this->getModel('welcome');
		$view->setModel($model, true);

		// TODO Do Stuff

		// Do not go through $this->display() because it overrides the model, nullifying the whole concept of MVC.
		$view->display();

		return $this;
	}

	protected function assertSuperUser()
	{
		if (JFactory::getUser()->authorise('core.admin'))
		{
			return;
		}

		throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
	}
}