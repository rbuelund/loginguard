<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

define('AKEEBA_COMMON_WRONGPHP', 1);
$minPHPVersion         = '7.1.0';
$recommendedPHPVersion = '7.3';
$softwareName          = 'Akeeba LoginGuard';
$silentResults         = true;

if (!require_once(JPATH_COMPONENT_ADMINISTRATOR . '/View/wrongphp.php'))
{
	echo 'Your PHP version is too old for this component.';

	return;
}

// HHVM made sense in 2013, now PHP 7 is a way better solution than an hybrid PHP interpreter
if (defined('HHVM_VERSION'))
{
	(include_once JPATH_COMPONENT_ADMINISTRATOR . '/View/hhvm.php') or die('We have detected that you are running HHVM instead of PHP. This software WILL NOT WORK properly on HHVM. Please switch to PHP 7 instead.');

	return;
}

// PHP 7.0 or later; we can catch PHP Fatal Errors as well
try
{
	if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
	{
		throw new RuntimeException('FOF 3.0 is not installed', 500);
	}

	FOF30\Container\Container::getInstance('com_loginguard')->dispatcher->dispatch();
}
catch (Throwable $e)
{
	// DO NOT REMOVE -- They are used by errorhandler.php below.
	$title = 'Akeeba LoginGuard';
	$isPro = false;

	if (!(include_once __DIR__ . '/View/errorhandler.php'))
	{
		throw $e;
	}
}