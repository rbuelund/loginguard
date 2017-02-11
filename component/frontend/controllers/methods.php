<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

class LoginGuardControllerMethods extends JControllerLegacy
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 * Recognized key values include 'name', 'default_task', 'model_path', and
	 * 'view_path' (this list is not meant to be comprehensive).
	 */
	public function __construct(array $config = array())
	{
		// We have to tell Joomla what is the name of the view, otherwise it defaults to the name of the *component*.
		$config['default_view'] = 'Methods';

		parent::__construct($config);
	}

	/**
	 * List all available Two Step Validation methods available and guide the user to setting them up
	 *
	 * @param   bool   $cachable   Can this view be cached
	 * @param   array  $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  self   The current JControllerLegacy object to support chaining.
	 */
	public function display($cachable = false, $urlparams = array())
	{
		// Make sure the user is logged in
		if (JFactory::getUser()->guest)
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		parent::display($cachable, $urlparams);

		return $this;
	}

	/**
	 * Disable Two Step Verification for the current user
	 *
	 * @param   bool   $cachable   Can this view be cached
	 * @param   array  $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  self   The current JControllerLegacy object to support chaining.
	 */
	public function disable($cachable = false, $urlparams = array())
	{
		// Make sure the user is logged in
		if (JFactory::getUser()->guest)
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// CSRF prevention
		$token = JFactory::getSession()->getToken();

		if ($this->input->get->getInt($token, 0) != 1)
		{
			die (JText::_('JINVALID_TOKEN'));
		}

		// Delete all TSV methods for the user
		/** @var LoginGuardModelMethods $model */
		$model   = $this->getModel('Methods');
		$type    = null;
		$message = null;

		try
		{
			$model->deleteAll();
		}
		catch (Exception $e)
		{
			$message = $e->getMessage();
			$type    = 'error';
		}

		// Redirect
		$this->setRedirect(JRoute::_('index.php?option=com_loginguard&task=methods.display', false), $message, $type);

		return $this;
	}
}