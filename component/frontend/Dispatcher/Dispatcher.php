<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\LoginGuard\Site\Dispatcher;

// Protect from unauthorized access
use Akeeba\LoginGuard\Admin\Dispatcher\Dispatcher as AdminDispatcher;

defined('_JEXEC') or die();

class Dispatcher extends AdminDispatcher
{
	/**
	 * The name of the default view, in case none is specified
	 *
	 * @var   string
	 * @since 2.0.0
	 */
	public $defaultView = 'Captive';

	/**
	 * Get the default view and task which is appropriate for this user.
	 *
	 * @return  void
	 *
	 * @since   2.0.0
	 */
	protected function getDefaultView()
	{
		$view = $this->defaultView;
		$task = 'default';

		$this->input->set('view', $view);
		$this->input->set('task', $task);
	}

}
