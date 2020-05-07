<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\LoginGuard\Admin\Controller;

use Akeeba\LoginGuard\Admin\Model\Welcome as WelcomeModel;
use FOF30\Container\Container;
use FOF30\Controller\Controller;
use Joomla\CMS\Router\Route as JRoute;
use Joomla\CMS\Language\Text as JText;
use RuntimeException;

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * Controller for the Welcome page in the backend of the site
 *
 * @package     Akeeba\LoginGuard\Admin\Controller
 *
 * @since       2.0.0
 */
class Welcome extends Controller
{
	/**
	 * Welcome constructor.
	 *
	 * Sets up the default task.
	 *
	 * @param   Container  $container
	 * @param   array      $config
	 *
	 * @since   2.0.0
	 */
	public function __construct(Container $container, array $config = [])
	{
		if (!isset($config['default_task']))
		{
			$config['default_task'] = 'welcome';
		}

		parent::__construct($container, $config);

		$this->cacheableTasks = [];
		$this->userCaching = 2;
	}

	/**
	 * Triggers before executing any task. We use it to limit this view to Super Users only.
	 *
	 * @param   string  $task
	 *
	 * @since   2.0.0
	 */
	protected function onBeforeExecute(&$task)
	{
		$this->assertSuperUser();
	}

	/**
	 * Displays the welcome screen for the Super User
	 *
	 * @since   2.0.0
	 */
	public function welcome()
	{
		$this->getModel()->checkAndFixDatabase();

		$this->display();
	}

	/**
	 * Assert that the user is a Super User
	 *
	 * @return  void
	 *
	 * @since   2.0.0
	 */
	protected function assertSuperUser()
	{
		if ($this->container->platform->authorise('core.admin', null))
		{
			return;
		}

		throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
	}
}
