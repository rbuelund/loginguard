<?php
/**
 * @package    solo
 * @copyright  Copyright (c)2014-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU GPL version 3 or later
 */

use FOF30\Container\Container;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die();

// PHP version check
if (!version_compare(PHP_VERSION, '5.6.0', '>='))
{
	return;
}

class plgActionlogLoginguard extends CMSPlugin
{
	/** @var Container */
	private $container;

	/**
	 * Constructor
	 *
	 * @param       object $subject The object to observe
	 * @param       array  $config  An array that holds the plugin configuration
	 *
	 * @since       6.4.0
	 */
	public function __construct(& $subject, $config)
	{
		// Make sure LoginGuard is installed
		if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_loginguard'))
		{
			return;
		}

		// Make sure LoginGuard is enabled
		if ( !ComponentHelper::isEnabled('com_loginguard'))
		{
			return;
		}

		// Load FOF
		if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
		{
			return;
		}

		$this->container = Container::getInstance('com_loginguard');

		// No point in logging guest actions
		if ($this->container->platform->getUser()->guest)
		{
			return;
		}

		// If any of the above statement returned, our plugin is not attached to the subject, so it's basically disabled
		parent::__construct($subject, $config);
	}

	/**
	 * Logs converting from Joomla's TFA
	 *
	 * @return  void
	 */
	public function onComLoginguardControllerConvertAfterConvert()
	{
		$this->container->platform->logUserAction('', 'PLG_ACTIONLOG_LOGINGUARD_ACTION_CONVERT', 'com_loginguard');
	}

	/**
	 * Logs updating the GeoIP database
	 *
	 * @return  void
	 */
	public function onComLoginguardControllerWelcomeAfterUpdategeoip()
	{
		$this->container->platform->logUserAction('', 'PLG_ACTIONLOG_LOGINGUARD_ACTION_WELCOME_UPDATEGEOIP', 'com_loginguard');
	}

	/**
	 * Logs showing the TSV selection method
	 *
	 * @return  void
	 */
	public function onComLoginguardCaptiveShowSelect()
	{
		$this->container->platform->logUserAction('', 'PLG_ACTIONLOG_LOGINGUARD_ACTION_CAPTIVE_SELECT', 'com_loginguard');
	}

	/**
	 * Logs showing the captive login page
	 *
	 * @param   string  $methodTitleEscaped
	 *
	 * @return  void
	 */
	public function onComLoginguardCaptiveShowCaptive($methodTitleEscaped)
	{
		$this->container->platform->logUserAction($methodTitleEscaped, 'PLG_ACTIONLOG_LOGINGUARD_ACTION_CAPTIVE_CAPTIVE', 'com_loginguard');
	}
}
