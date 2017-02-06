<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2006-2017 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

// Get an instance of the LoginGuard controller
$controller = JControllerLegacy::getInstance('LoginGuard');

// Get and execute the requested task
$input = JFactory::getApplication()->input;
$controller->execute($input->getCmd('task'));

// Apply any redirection set in the Controller
$controller->redirect();