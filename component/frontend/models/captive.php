<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2006-2017 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

class LoginGuardModelCaptive extends JModelLegacy
{
	/**
	 * Prevents Joomla from displaying any modules.
	 *
	 * This is implemented with a trick. If you use jdoc tags to load modules the JDocumentRendererHtmlModules
	 * uses JModuleHelper::getModules() to load the list of modules to render. This goes through JModuleHelper::load()
	 * which triggers the onAfterCleanModuleList event after cleaning up the module list from duplicates. By resetting
	 * the list to an empty array we force Joomla to not display any modules.
	 *
	 * Similar code paths are followed by any canonical code which tries to load modules. So even if your template does
	 * not use jdoc tags this code will still work as expected.
	 */
	public function killAllModules()
	{
		$app = JFactory::getApplication();
		$app->registerEvent('onAfterCleanModuleList', function (&$modules) {
			$modules = array();
		});
	}
}