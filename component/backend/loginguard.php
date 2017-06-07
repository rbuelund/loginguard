<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

// Load both frontend and backend languages for this component
JFactory::getLanguage()->load('com_loginguard', JPATH_SITE, null, true, true);
JFactory::getLanguage()->load('com_loginguard', JPATH_ADMINISTRATOR, null, true, true);

// I have to do some special handling to accommodate for the discrepancies between how Joomla creates menu items and how
// Joomla handles component controllers. Ugh!
$app = JFactory::getApplication();
$view = $app->input->getCmd('view');
$task = $app->input->getCmd('task');

if (!empty($view))
{
	if (strpos($task, '.') === false)
	{
		$task = $view . '.' . $task;
	}
	else
	{
		list($view, $task2) = explode('.', $task, 2);
	}

	$app->input->set('view', $view);
	$app->input->set('task', $task);
}

// Get the media version
JLoader::register('LoginGuardHelperVersion', JPATH_SITE . '/components/com_loginguard/helpers/version.php');
$mediaVersion = md5(LoginGuardHelperVersion::component('com_loginguard'));

// Include CSS
if (version_compare(JVERSION, '3.6.999', 'le'))
{
	JHtml::_('stylesheet', 'com_loginguard/backend.min.css', array(
		'version'     => $mediaVersion,
		'relative'    => true,
		'detectDebug' => true
	), true, false, false, true);
}
else
{
	JHtml::_('stylesheet', 'com_loginguard/backend.min.css', array(
		'version'       => $mediaVersion,
		'relative'      => true,
		'detectDebug'   => true,
		'pathOnly'      => false,
		'detectBrowser' => true,
	), array(
		'type' => 'text/css',
	));
}

// Get an instance of the LoginGuard controller
$controller = JControllerLegacy::getInstance('LoginGuard');

// Get and execute the requested task
$input = JFactory::getApplication()->input;
$controller->execute($input->getCmd('task'));

// Apply any redirection set in the Controller
$controller->redirect();