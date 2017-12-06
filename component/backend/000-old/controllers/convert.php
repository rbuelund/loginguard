<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

class LoginGuardControllerConvert extends JControllerLegacy
{
	public function __construct(array $config = array())
	{
		parent::__construct($config);

		$this->registerDefaultTask('convert');
	}

	public function convert($cachable = false, $urlparams = false)
	{
		// Set up the Model and View
		$model = $this->getConvertModel();
		$view  = $this->getConvertView();
		$view->setModel($model, true);

		// Perform the conversion
		$result = $model->convert();

		// Set the correct layout depending on what happened to the conversion
		$view->setLayout('default');

		if (!$result)
		{
			$view->setLayout('done');
			$model->disableTFA();
		}

		// Render the view
		$view->display();

		return $this;
	}

	/**
	 * Get a reference to the model which performs the conversion from TFA to TSV
	 *
	 * @return  LoginGuardModelConvert
	 */
	protected function getConvertModel()
	{
		/** @var LoginGuardModelConvert $model */
		$model = $this->getModel('Convert');

		return $model;
	}

	/**
	 * Get a reference to the view object for this view
	 *
	 * @return LoginGuardViewConvert
	 */
	protected function getConvertView()
	{
		// Get the view object
		$document   = JFactory::getDocument();
		$viewLayout = $this->input->get('layout', 'default', 'string');
		/** @var LoginGuardViewConvert $view */
		$view       = $this->getView('convert', 'html', '', array(
			'base_path' => $this->basePath,
			'layout'    => $viewLayout
		));

		$view->document = $document;

		return $view;
	}

	/**
	 * Assert that the user is a Super User
	 *
	 * @param   JUser   $user  The user to assert. Null to use the currently logged in user
	 *
	 * @return  void
	 */
	protected function assertSuperUser(JUser $user = null)
	{
		if (empty($user))
		{
			$user = JFactory::getUser();
		}

		if ($user->authorise('core.admin'))
		{
			return;
		}

		throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
	}
}