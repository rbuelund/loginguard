<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\LoginGuard\Site\Controller;

use FOF30\Container\Container;
use FOF30\Controller\Controller;
use Joomla\CMS\Language\Text as JText;
use RuntimeException;

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * AJAX controller. Handles requests originating from an asynchronous request issued by the user's browser.
 *
 * @since       2.0.0
 */
class Ajax extends Controller
{
	/**
	 * Ajax constructor.
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
			$config['default_task'] = 'json';
		}
		parent::__construct($container, $config);
	}

	/**
	 * Implement an AJAX feature. Results are returned as JSON. In case of no response the JSON string literal "null"
	 * is returned.
	 *
	 * @return  void
	 * @since   2.0.0
	 */
	public function json()
	{
		// Only allow logged in users
		if ($this->container->platform->getUser()->guest)
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$result = $this->getResult();

		echo json_encode($result);

		// Immediately close the application
		$this->container->platform->closeApplication();
	}

	/**
	 * Implement an AJAX feature. Results are returned as JSON surrounded by triple hashes. In case of no response the
	 * JSON string literal "null" surrounded by triple hashes is returned, i.e.:
	 * ###null###
	 *
	 * The triple-hash-surrounded-JSON format has proven to be the best way to work around brain-dead plugins and hosts
	 * which forcibly inject HTML or other crap to the output _even when the format query string parameter is explicitly
	 * set to raw_. This solution has proven itself since Joomla! 1.0, all the way back in 2005.
	 *
	 * @return  void
	 * @since   2.0.0
	 */
	public function hashjson()
	{
		// Only allow logged in users
		if ($this->container->platform->getUser()->guest)
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$result = $this->getResult();

		echo '###' . json_encode($result) . '###';

		// Immediately close the application
		$this->container->platform->closeApplication();
	}

	/**
	 * Implement an AJAX feature. The first plugin handling the request is responsible of returning the results in
	 * whatever format is best for the application. If no plugin handles the request the application closes without
	 * returning a response.
	 *
	 * @return  void
	 * @since   2.0.0
	 */
	public function raw()
	{
		// Only allow logged in users
		if ($this->container->platform->getUser()->guest)
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// Note: we return no result. The first plugin which handles the request is supposed to do that.
		$result = $this->getResult();

		// Immediately close the application
		$this->container->platform->closeApplication();
	}

	/**
	 * Common part of request handling across all tasks. Makes sure the request is a valid AJAX requests, triggers the
	 * plugin event and returns the first non-empty result.
	 *
	 * @return  mixed  Null if no plugin handled the event. Otherwise the first non-false plugin result.
	 * @since   2.0.0
	 */
	private function getResult()
	{
		// Make sure format=raw
		$format = $this->input->getCmd('format', 'html');

		if ($format != 'raw')
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// Get the method and make sure it's non-empty
		$method = $this->input->getCmd('method', '');
		$action = $this->input->getCmd('action', '');

		if (empty($method) || empty($action))
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// Trigger the onLoginGuardAjax plugin event
		$this->container->platform->importPlugin('loginguard');
		$results = $this->container->platform->runPlugins('onLoginGuardAjax', [$method, $action]);
		$result  = null;

		foreach ($results as $aResult)
		{
			if ($aResult !== false)
			{
				$result = $aResult;

				break;
			}
		}

		return $result;
	}
}
