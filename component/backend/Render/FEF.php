<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\LoginGuard\Admin\Render;

use FOF30\Render\Joomla3;

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * Akeeba FOF Renderer class for Akeeba Frontend Framework
 *
 * @since       2.0
 */
class FEF extends Joomla3
{
	/**
	 * Echoes any HTML to show before the view template. We override it to load the CSS files required for FEF.
	 *
	 * @param   string    $view    The current view
	 * @param   string    $task    The current task
	 *
	 * @return  void
	 */
	public function preRender($view, $task)
	{
		$mediaVersion = $this->container->mediaVersion;

		$this->container->template->addCSS('media://com_akeeba/css/fef.min.css', $mediaVersion);

		parent::preRender($view, $task);
	}


	/**
	 * Opens the FEF styling wrapper element. Our component;s output will be inside this wrapper.
	 *
	 * @param   array  $classes  An array of additional CSS classes to add to the outer page wrapper element.
	 *
	 * @return  void
	 */
	protected function openPageWrapper($classes)
	{
		$customClasses = implode($classes, ' ');
		echo <<< HTML
<div id="akeeba-renderer-fef" class="akeeba-renderer-fef $customClasses">

HTML;
	}

	/**
	 * Close the FEF styling wrapper element.
	 *
	 * @return  void
	 */
	protected function closePageWrapper()
	{
		echo <<< HTML
</div>

HTML;

	}

}
