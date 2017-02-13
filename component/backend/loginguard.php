<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

// The captive page has to be rendered with frontend code and frontend language strings
$lang = JFactory::getLanguage();
$lang->load('com_loginguard', JPATH_SITE, null, true, true);

// Get an instance of the LoginGuard controller
$controller = JControllerLegacy::getInstance('LoginGuard');

// Get and execute the requested task
$input = JFactory::getApplication()->input;
$controller->execute($input->getCmd('task'));

// Apply any redirection set in the Controller
$controller->redirect();