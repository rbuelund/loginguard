<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
use FOF30\Input\Input;

defined('_JEXEC') or die;

// Minimum PHP version check
if (!version_compare(PHP_VERSION, '5.4.0', '>='))
{
	return;
}

/**
 * Work around the very broken and completely defunct eAccelerator on PHP 5.4 (or, worse, later versions).
 */
if (function_exists('eaccelerator_info'))
{
	$isBrokenCachingEnabled = true;

	if (function_exists('ini_get') && !ini_get('eaccelerator.enable'))
	{
		$isBrokenCachingEnabled = false;
	}

	if ($isBrokenCachingEnabled)
	{
		/**
		 * I know that this define seems pointless since I am returning. This means that we are exiting the file and
		 * the plugin class isn't defined, so Joomla cannot possibly use it.
		 *
		 * Well, that is how PHP works. Unfortunately, eAccelerator has some "novel" ideas about how to go about it.
		 * For very broken values of "novel". What does it do? It ignores the return and parses the plugin class below.
		 *
		 * You read that right. It ignores ALL THE CODE between here and the class declaration and parses the
		 * class declaration. Therefore the only way to actually NOT load the  plugin when you are using it on a
		 * server where an irresponsible sysadmin has installed and enabled eAccelerator (IT'S END OF LIFE AND BROKEN
		 * PER ITS CREATORS FOR CRYING OUT LOUD) is to define a constant and use it to return from the constructor
		 * method, therefore forcing PHP to return null instead of an object. This prompts Joomla to not do anything
		 * with the plugin.
		 */
		if (!defined('AKEEBA_EACCELERATOR_IS_SO_BORKED_IT_DOES_NOT_EVEN_RETURN'))
		{
			define('AKEEBA_EACCELERATOR_IS_SO_BORKED_IT_DOES_NOT_EVEN_RETURN', 3245);
		}

		return;
	}
}

// Make sure Akeeba LoginGuard is installed
if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_loginguard'))
{
	return;
}

// Load FOF
if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
{
	return;
}

/**
 * Akeeba LoginGuard Plugin for encrypting the data at rest.
 *
 * This plugin is not necessary on most sites. Enabling it has non-obvious downsides. READ THE DOCUMENTATION FIRST.
 */
class PlgLoginguardEncrypt extends JPlugin
{
	/**
	 * Caches the password used by this plugin to encrypt the LoginGuard information.
	 *
	 * @var  string
	 */
	private $password = '';

	public function __construct($subject, array $config = array())
	{
		parent::__construct($subject, $config);

		$this->password = $this->getSecretKey();
	}


	/**
	 * Encrypt the LoginGuard configuration before saving it to the database.
	 *
	 * @param   object $record The record being saved
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @since   2.0.2
	 */
	public function onLoginGuardBeforeSaveRecord(&$record)
	{
		if (empty($this->password))
		{
			JFactory::getApplication()->enqueueMessage(JText::_('PLG_LOGINGUARD_ENCRYPT_ERR_CANTSAVEPASSWORD'), 'error');

			return;
		}

		$aes = new FOF30\Encrypt\Aes($this->password, 128, 'cbc');
		$record->options = '###AES128###' . $aes->encryptString($record->options);
	}

	/**
	 * Decrypt the LoginGuard configuration after reading it from the database.
	 *
	 * @param   object  $record  The LoginGuard record we read from the database
	 *
	 * @return  void
	 *
	 * @since   2.0.2
	 */
	public function onLoginGuardAfterReadRecord(&$record)
	{
		if (empty($this->password))
		{
			return;
		}

		if (substr($record->options, 0, 12) != '###AES128###')
		{
			// The settings are not encrypted yet. Flag them as in need to be saved again.
			$record->must_save = 1;

			return;
		}

		$aes = new FOF30\Encrypt\Aes($this->password, 128, 'cbc');
		$encrypted = substr($record->options, 12);
		$record->options = rtrim($aes->decryptString($encrypted));
	}

	/**
	 * Gets the secret key for settings encryption. If none exists yet, it will be generated for you.
	 *
	 * @return  string
	 *
	 * @return  void
	 *
	 * @since   2.0.2
	 */
	private function getSecretKey()
	{
		$keyFile = __DIR__ . '/secretkey.php';

		if (file_exists($keyFile))
		{
			@include_once $keyFile;
		}

		if (!defined('AKEEBA_LOGINGUARD_ENCRYPT_KEY'))
		{
			$this->generateKey($keyFile);

			if (file_exists($keyFile))
			{
				@include_once $keyFile;
			}
		}

		if (!defined('AKEEBA_LOGINGUARD_ENCRYPT_KEY'))
		{
			return '';
		}

		return AKEEBA_LOGINGUARD_ENCRYPT_KEY;
	}

	/**
	 * Generates a secret key file with a new, random key.
	 *
	 * @param   string  $keyFile  The path to the file where the key will be saved
	 *
	 * @return  void
	 *
	 * @since   2.0.2
	 */
	private function generateKey($keyFile)
	{
		$key = JUserHelper::genRandomPassword(32);

		$fileData = '<?' . "php defined('_JEXEC') or die;\n";
		$fileData .= "define('AKEEBA_LOGINGUARD_ENCRYPT_KEY', '$key');\n";

		if (@file_put_contents($keyFile, $fileData) !== false)
		{
			return;
		}

		JFile::write($keyFile, $fileData);
	}
}
