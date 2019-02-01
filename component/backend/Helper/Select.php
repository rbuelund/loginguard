<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\LoginGuard\Admin\Helper;

use Joomla\CMS\Helper\UserGroupsHelper;

// Prevent direct access
defined('_JEXEC') or die;

class Select
{
	/**
	 * Cache of user groups
	 *
	 * @var   array
	 * @since 3.0.1
	 */
	static $groups = [];

	/**
	 * Get a nested list of Joomla User Group options
	 *
	 * @return  array
	 *
	 * @since   3.0.1
	 */
	public static function getGroupOptions()
	{
		if (empty(static::$groups))
		{
			static::$groups = [];
			$groups         = UserGroupsHelper::getInstance()->getAll();
			$options        = array();

			foreach ($groups as $group)
			{
				$options[] = (object) array(
					'text'  => str_repeat('- ', $group->level) . $group->title,
					'value' => $group->id,
					'level' => $group->level
				);
			}

			static::$groups = $options;
		}

		return static::$groups;
	}
}