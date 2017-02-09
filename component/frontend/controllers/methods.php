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
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 404);
		}

		parent::display($cachable, $urlparams);

		return $this;
	}
}