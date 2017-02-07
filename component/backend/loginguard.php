<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2006-2017 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

// The captive page has to be rendered with frontend code and frontend language strings
$app    = JFactory::getApplication();
$view   = $app->input->getCmd('view', 'captive');
$task   = $app->input->getCmd('task', 'default');
$config = array(
	'base_path' => JPATH_COMPONENT_ADMINISTRATOR
);

if (($view == 'captive') || (substr($task, 0, 8) == '.captive'))
{
	$lang = JFactory::getLanguage();
	$lang->load('com_loginguard', JPATH_SITE, null, true, true);

	$config['base_path'] = JPATH_SITE . '/components/com_loginguard';
}

// Get an instance of the LoginGuard controller
$controller = JControllerLegacy::getInstance('LoginGuard', $config);

// Get and execute the requested task
$input = JFactory::getApplication()->input;
$controller->execute($input->getCmd('task'));

// Apply any redirection set in the Controller
$controller->redirect();