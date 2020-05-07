<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\LoginGuard\Site\Controller;

use Akeeba\LoginGuard\Site\Helper\Tfa;
use Akeeba\LoginGuard\Site\Model\Methods as MethodsModel;
use Exception;
use FOF30\Container\Container;
use FOF30\Controller\Controller;
use FOF30\Controller\Mixin\PredefinedTaskList;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Router\Route as JRoute;
use Joomla\CMS\Uri\Uri as JUri;
use RuntimeException;

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * Controller for the methods management page
 *
 * @since       2.0.0
 */
class Methods extends Controller
{
	use PredefinedTaskList;

	public function __construct(Container $container, array $config = [])
	{
		parent::__construct($container, $config);

		$this->setPredefinedTaskList(['main', 'display', 'dontshowthisagain', 'disable']);
	}

	/**
	 * List all available Two Step Validation methods available and guide the user to setting them up
	 *
	 * @return  void
	 * @since   2.0.0
	 */
	public function main()
	{
		$this->assertLoggedInUser();

		// Make sure I am allowed to edit the specified user
		$user_id = $this->input->getInt('user_id', null);
		$user    = $this->container->platform->getUser($user_id);

		if (!Tfa::canEditUser($user))
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$returnURL       = $this->input->getBase64('returnurl');
		$view            = $this->getView();
		$view->returnURL = $returnURL;
		$view->user      = $user;

		parent::display();
	}

	/**
	 * Disable Two Step Verification for the current user
	 *
	 * @return  void
	 * @since   2.0.0
	 *
	 * @throws  Exception
	 */
	public function disable()
	{
		$this->assertLoggedInUser();

		// CSRF prevention
		$this->csrfProtection();

		// Make sure I am allowed to edit the specified user
		$user_id = $this->input->getInt('user_id', null);
		$user    = $this->container->platform->getUser($user_id);

		if (!Tfa::canEditUser($user))
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// Delete all TSV methods for the user
		/** @var MethodsModel $model */
		$model   = $this->getModel('Methods');
		$type    = null;
		$message = null;

		try
		{
			$model->deleteAll($user);
		}
		catch (Exception $e)
		{
			$message = $e->getMessage();
			$type    = 'error';
		}

		// Redirect
		$url       = JRoute::_('index.php?option=com_loginguard&view=Methods&task=display&user_id=' . $user_id, false);
		$returnURL = $this->input->getBase64('returnurl');

		if (!empty($returnURL))
		{
			$url = base64_decode($returnURL);
		}

		$this->setRedirect($url, $message, $type);
	}

	/**
	 * Disable Two Step Verification for the current user
	 *
	 * @return  void
	 * @since   2.0.0
	 *
	 * @throws  Exception
	 */
	public function dontshowthisagain($cachable = false, $urlparams = [])
	{
		$this->assertLoggedInUser();

		// CSRF prevention
		$this->csrfProtection();

		// Make sure I am allowed to edit the specified user
		$user_id = $this->input->getInt('user_id', null);
		$user    = $this->container->platform->getUser($user_id);

		if (!Tfa::canEditUser($user))
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		/** @var MethodsModel $model */
		$model = $this->getModel('Methods');
		$model->setFlag($user, true);

		// Redirect
		$url       = JUri::base();
		$returnURL = $this->input->getBase64('returnurl');

		if (!empty($returnURL))
		{
			$url = base64_decode($returnURL);
		}

		$this->setRedirect($url);
	}

	private function assertLoggedInUser()
	{
		if ($this->container->platform->getUser()->guest)
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}
}
