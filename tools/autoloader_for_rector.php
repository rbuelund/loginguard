<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Define ourselves as a parent file
use FOF30\Autoloader\Autoloader;

// Try to get the path to the Joomla! installation
$joomlaPath = $_SERVER['HOME'] . '/Sites/dev3';

if (isset($_SERVER['JOOMLA_SITE']) && is_dir($_SERVER['JOOMLA_SITE']))
{
	$joomlaPath = $_SERVER['JOOMLA_SITE'];
}

if (!is_dir($joomlaPath))
{
	echo <<< TEXT


CONFIGURATION ERROR

Your configured path to the Joomla site does not exist. Rector requires loading
core Joomla classes to operate properly.

Please set the JOOMLA_SITE environment variable before running Rector.

Example:

JOOMLA_SITE=/var/www/joomla rector process $(pwd) --config rector.yaml \
  --dry-run

I will now error out. Bye-bye!

TEXT;

	throw new InvalidArgumentException("Invalid Joomla site root folder.");
}

// Required to run the boilerplate FOF CLI code
$originalDirectory = getcwd();
chdir($joomlaPath . '/cli');

// Setup and import the base CLI script
$minphp = '7.1.0';

// Boilerplate -- START
define('_JEXEC', 1);

foreach ([__DIR__, getcwd()] as $curdir)
{
	if (file_exists($curdir . '/defines.php'))
	{
		define('JPATH_BASE', realpath($curdir . '/..'));
		require_once $curdir . '/defines.php';

		break;
	}

	if (file_exists($curdir . '/../includes/defines.php'))
	{
		define('JPATH_BASE', realpath($curdir . '/..'));
		require_once $curdir . '/../includes/defines.php';

		break;
	}
}

defined('JPATH_LIBRARIES') || die ('This script must be placed in or run from the cli folder of your site.');

require_once JPATH_LIBRARIES . '/fof30/Cli/Application.php';
// Boilerplate -- END

// Undo the temporary change for the FOF CLI boilerplate code
chdir($originalDirectory);

// Load FOF 3
if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
{
	throw new RuntimeException('FOF 3.0 is not installed', 500);
}

// Load Akeeba LoginGuard's autoloader through FOF 3
$container = FOF30\Container\Container::getInstance('com_loginguard');

// Other classes
/** @var Composer\Autoload\ClassLoader $autoloader */
$autoloader = include(__DIR__ . '/../vendor/autoload.php');
$autoloader->addClassMap([
	# Form fields
	'JFormFieldModulePositions'              => __DIR__ . '/../component/backend/Field/Joomla/modulepositions.php',
	# Post-installation scripts, package and component
	'Pkg_LoginguardInstallerScript'          => __DIR__ . '/../component/script.akeeba.php',
	'Com_LoginguardInstallerScript'          => __DIR__ . '/../component/script.com_loginguard.php',
	# Plugins
	'plgActionlogLoginguard'                 => __DIR__ . '/../plugins/actionlog/loginguard/loginguard.php',
	'plgActionlogLoginguardInstallerScript'  => __DIR__ . '/../plugins/actionlog/loginguard/script.php',
	'plgLoginguardEmail'                     => __DIR__ . '/../plugins/loginguard/email/email.php',
	'plgLoginguardEmailInstallerScript'      => __DIR__ . '/../plugins/loginguard/email/script.php',
	'plgLoginguardFixed'                     => __DIR__ . '/../plugins/loginguard/fixed/fixed.php',
	'plgLoginguardFixedInstallerScript'      => __DIR__ . '/../plugins/loginguard/fixed/script.php',
	'plgLoginguardPushbullet'                => __DIR__ . '/../plugins/loginguard/pushbullet/pushbullet.php',
	'plgLoginguardPushbulletInstallerScript' => __DIR__ . '/../plugins/loginguard/pushbullet/script.php',
	'plgLoginguardSmsapi'                    => __DIR__ . '/../plugins/loginguard/smsapi/smsapi.php',
	'plgLoginguardSmsapiInstallerScript'     => __DIR__ . '/../plugins/loginguard/smsapi/script.php',
	'plgLoginguardTotp'                      => __DIR__ . '/../plugins/loginguard/totp/totp.php',
	'plgLoginguardTotpInstallerScript'       => __DIR__ . '/../plugins/loginguard/totp/script.php',
	'plgLoginguardU2f'                       => __DIR__ . '/../plugins/loginguard/u2f/u2f.php',
	'plgLoginguardU2fInstallerScript'        => __DIR__ . '/../plugins/loginguard/u2f/script.php',
	'plgLoginguardWebauthn'                  => __DIR__ . '/../plugins/loginguard/webauthn/webauthn.php',
	'plgLoginguardWebauthnInstallerScript'   => __DIR__ . '/../plugins/loginguard/webauthn/script.php',
	'plgLoginguardYubikey'                   => __DIR__ . '/../plugins/loginguard/yubikey/yubikey.php',
	'plgLoginguardYubikeyInstallerScript'    => __DIR__ . '/../plugins/loginguard/yubikey/script.php',
	'plgSystemLoginguard'                    => __DIR__ . '/../plugins/system/loginguard/loginguard.php',
	'plgSystemLoginguardInstallerScript'     => __DIR__ . '/../plugins/system/loginguard/script.php',
	'plgUserLoginguard'                      => __DIR__ . '/../plugins/user/loginguard/loginguard.php',
	'plgUserLoginguardInstallerScript'       => __DIR__ . '/../plugins/user/loginguard/script.php',

	# Akeeba Usage Stats
	'AkeebaUsagestats'                       => __DIR__ . '/../../usagestats/lib/usagestats.php',
]);
