<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

if (class_exists('JFormFieldModulePositions'))
{
	return;
}

JHtml::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_modules/helpers/html');
require_once JPATH_ADMINISTRATOR . '/components/com_modules/helpers/modules.php';

JFormHelper::loadFieldClass('groupedlist');

/**
 * ModulePositions Field class for the Joomla Framework.
 */
class JFormFieldModulePositions extends JFormFieldGroupedList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 */
	protected $type = 'ModulePositions';

	/**
	 * Method to get the field option groups.
	 *
	 * @return  array  The field option objects as a nested array in groups.
	 *
	 * @since   11.1
	 * @throws  UnexpectedValueException
	 */
	public function getGroups()
	{
		$groups = array();

		$clientId = is_null($this->element->attributes()->client_id) ? 0 : (int) $this->element->attributes()->client_id;
		$state    = is_null($this->element->attributes()->state) ? 1 : (int) $this->element->attributes()->state;

		// Get all module positions for this client ID
		$positions = JHtml::_('modules.positions', $clientId, $state, $this->value);

		// There's a junk position added with no content. Remove it.
		if (isset($positions['']))
		{
			unset($positions['']);
		}

		foreach ($positions as $label => $position)
		{
			if (!isset($position['items']))
			{
				continue;
			}

			$groups[$label] = array();

			foreach ($position['items'] as $item)
			{
				$item             = (array) $item;
				$disable          = isset($item['disable']) ? $item['disable'] : false;
				$groups[$label][] = JHtml::_('select.option', $item['value'], $item['text'], 'value', 'text', $disable);
			}
		}

		return $groups;
	}
}
