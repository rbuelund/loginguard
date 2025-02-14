<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\LoginGuard\Webauthn\PluginTraits\ComposerDependencies;
use Akeeba\LoginGuard\Webauthn\PluginTraits\TfaCaptive;
use Akeeba\LoginGuard\Webauthn\PluginTraits\TfaGetMethod;
use Akeeba\LoginGuard\Webauthn\PluginTraits\TfaGetSetup;
use Akeeba\LoginGuard\Webauthn\PluginTraits\TfaSaveSetup;
use Akeeba\LoginGuard\Webauthn\PluginTraits\TfaValidate;
use FOF40\Autoloader\Autoloader;
use Joomla\CMS\Plugin\CMSPlugin;

// Prevent direct access
defined('_JEXEC') || die;

// Add ourselves to the autoloader
if (!class_exists('FOF40\Autoloader\Autoloader'))
{
	return;
}

Autoloader::getInstance()->addMap('Akeeba\\LoginGuard\\Webauthn\\', [realpath(__DIR__ . '/Webauthn')]);


/**
 * Akeeba LoginGuard Plugin for Two Step Verification method "Webauthn Security Key"
 *
 * Uses W3C Web Authentication for Two Step Verification. Due to browser and library limitations it does not support
 * credentials stored in a TPM. Therefore it's essentially a newer, more widely adopted version of U2F.
 *
 * @since   3.1.0
 */
class PlgLoginguardWebauthn extends CMSPlugin
{
	// Load the Traits which implement the LoginGuard methods
	use ComposerDependencies, TfaGetMethod, TfaGetSetup, TfaSaveSetup, TfaCaptive, TfaValidate;

	/**
	 * The TFA method name handled by this plugin
	 *
	 * @var   string
	 * @since 3.1.0
	 */
	private $tfaMethodName = 'webauthn';

	/**
	 * Should I report myself as enabled?
	 *
	 * @var   bool
	 * @since 3.1.0
	 */
	private $enabled = true;

	/**
	 * Constructor. Loads the language files as well.
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An optional associative array of configuration settings.
	 *                             Recognized key values include 'name', 'group', 'params', 'language'
	 *                             (this list is not meant to be comprehensive).
	 *
	 * @since   3.1.0
	 */
	public function __construct($subject, array $config = array())
	{
		parent::__construct($subject, $config);

		// Load the language file
		$this->loadLanguage();
	}
}
