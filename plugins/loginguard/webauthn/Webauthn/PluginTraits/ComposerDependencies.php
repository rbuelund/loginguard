<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */


namespace Akeeba\LoginGuard\Webauthn\PluginTraits;


// Prevent direct access
defined('_JEXEC') or die;

trait ComposerDependencies
{
	protected function loadComposerDependencies()
	{
		// Is the library already loaded?
		if (class_exists('Webauthn\CredentialRepository'))
		{
			return;
		}

		require_once JPATH_ADMINISTRATOR . '/components/com_loginguard/vendor/autoload.php';
	}
}