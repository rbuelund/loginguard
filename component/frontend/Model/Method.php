<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\LoginGuard\Site\Model;

use Akeeba\LoginGuard\Site\Helper\Tfa;
use Exception;
use FOF30\Model\Model;
use FOF30\Utils\Ip;
use JBrowser;
use JLoader;
use Joomla\CMS\User\User;
use JText;
use JUser;
use RuntimeException;
use stdClass;

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * Two Step Verification method management model
 *
 * @since       2.0.0
 */
class Method extends Model
{
	/**
	 * List of TFA methods
	 *
	 * @var   array
	 * @since 2.0.0
	 */
	protected $tfaMethods = null;

	/**
	 * Is the specified TFA method available?
	 *
	 * @param   string  $method      The method to check.
	 *
	 * @return  bool
	 * @since   2.0.0
	 */
	public function methodExists($method)
	{
		if (!is_array($this->tfaMethods))
		{
			$this->populateTfaMethods();
		}

		return isset($this->tfaMethods[$method]);
	}

	/**
	 * Get the specified TFA method's record
	 *
	 * @param   string  $method      The method to retrieve.
	 *
	 * @return  array
	 * @since   2.0.0
	 */
	public function getMethod($method)
	{
		if (!$this->methodExists($method))
		{
			return array(
				'name'          => $method,
				'display'       => '',
				'shortinfo'     => '',
				'image'         => '',
				'canDisable'    => true,
				'allowMultiple' => true
			);
		}

		return $this->tfaMethods[$method];
	}

	/**
	 * Get the specified TFA record. It will return a fake default record when no record ID is specified.
	 *
	 * @param   JUser|User  $user  The user record. Null to use the currently logged in user.
	 *
	 * @return  object
	 * @since   2.0.0
	 */
	public function getRecord($user = null)
	{
		if (is_null($user))
		{
			$user = $this->container->platform->getUser();
		}

		$defaultRecord = $this->getDefaultRecord($user);
		$id            = (int) $this->getState('id', 0);

		if ($id <= 0)
		{
			return $defaultRecord;
		}

		$db    = $this->container->db;
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__loginguard_tfa'))
			->where($db->qn('user_id') . ' = ' . $db->q($user->id))
			->where($db->qn('id') . ' = ' . $db->q($id));
		try
		{
			$record = $db->setQuery($query)->loadObject();
		}
		catch (Exception $e)
		{
			return $defaultRecord;
		}

		/**
		 * Call onLoginGuardAfterReadRecord(&$record)
		 *
		 * This event fires right after a record has been successfully read from the database. You have the chance to
		 * modify the record. At this point we have not yet checked whether the record's method refers to an existing,
		 * activated LoginGuard authentication method plugin.
		 */
		$this->container->platform->runPlugins('onLoginGuardAfterReadRecord', [&$record]);

		if (!$this->methodExists($record->method))
		{
			return $defaultRecord;
		}

		// Did a plugin set the flag telling us that we must save the record again?
		if (isset($record->must_save) && ($record->must_save === 1))
		{
			unset($record->must_save);

			// We save a clone of the original object since plugins may change the content of the record on save.
			$recordToSave = clone $record;

			$this->saveRecord($recordToSave);
		}

		return $record;
	}

	/**
	 * Save a TFA record to the database
	 *
	 * @param   stdClass  $record
	 *
	 * @return  void
	 *
	 * @throws  RuntimeException  When the database insert / update fails (thrown from JDatabaseDriver in recent Joomla! versions)
	 * @since   2.0.0
	 */
	public function saveRecord(&$record)
	{
		$db = $this->container->db;

		// Get existing records for this user EXCEPT the current record
		$records = Tfa::getUserTfaRecords($record->user_id);

		if ($record->id)
		{
			$allRecords = $records;
			$records    = array();

			foreach ($allRecords as $rec)
			{
				if ($rec->id == $record->id)
				{
					continue;
				}

				$records[] = $rec;
			}
		}

		// Let's handle the default record flag
		switch ($record->default)
		{
			case 1:
				// If this record is marked as default we have to make all the other user's records non-default
				if (count($records))
				{
					foreach ($records as $rec)
					{
						if (!$rec->default)
						{
							continue;
						}

						$rec->default = 0;

						try
						{
							$db->updateObject('#__loginguard_tfa', $rec, array('id'));
						}
						catch (Exception $e)
						{
							// No problem if that fails
						}
					}
				}

				break;

			case 0:
				// If this record is NOT marked default we have to make it default unless another record is the default
				$record->default = 1;

				if (!empty($records))
				{
					foreach ($records as $rec)
					{
						if ($rec->default)
						{
							$record->default = 0;

							break;
						}
					}
				}

				break;
		}

		$isNewRecord = empty($record->id);

		/**
		 * Call onLoginGuardBeforeSaveRecord(&$record, $input).
		 *
		 * This is your last chance to modify the record being saved to the database. At this point it is NOT guaranteed
		 * that the changes you see will be committed to the database. Use this event only to modify $record itself. Do
		 * not make decisions based on its contents.
		 */
		$this->container->platform->runPlugins('onLoginGuardBeforeSaveRecord', [&$record]);

		if ($isNewRecord)
		{
			// Update the Created On, UA and IP columns
			JLoader::import('joomla.environment.browser');
			$jNow    = $this->container->platform->getDate();
			$browser = JBrowser::getInstance();
			$ip      = $this->container->platform->getSessionVar('session.client.address');

			if (empty($ip))
			{
				$ip = Ip::getIp();
			}

			$record->created_on = $jNow->toSql();
			$record->ua         = $browser->getAgentString();
			$record->ip         = $ip;

			$result = $db->insertObject('#__loginguard_tfa', $record, 'id');
		}
		else
		{
			$result = $db->updateObject('#__loginguard_tfa', $record, array('id'));
		}

		// For old Joomla versions which do not throw DB exceptions
		if (!$result)
		{
			throw new RuntimeException($db->getErrorMsg());
		}

		// If that was the very first method we added for that user let's also create their backup codes
		if ($isNewRecord && !count($records))
		{
			/** @var BackupCodes $model */
			$model = $this->container->factory->model('BackupCodes')->tmpInstance();
			$user  = $this->container->platform->getUser($record->user_id);
			$model->regenerateBackupCodes($user);
		}
	}

	/**
	 * @param   JUser   $user        The user record. Null to use the currently logged in user.
	 *
	 * @return  array
	 * @since   2.0.0
	 */
	public function getRenderOptions(JUser $user = null)
	{
		if (is_null($user))
		{
			$user = $this->container->platform->getUser();
		}

		$renderOptions = [
			// Default title if you are setting up this TFA method for the first time
			'default_title'  => '',
			// Custom HTML to display above the TFA setup form
			'pre_message'    => '',
			// Heading for displayed tabular data. Typically used to display a list of fixed TFA codes, TOTP setup parameters etc
			'table_heading'  => '',
			// Any tabular data to display (label => custom HTML). See above
			'tabular_data'   => array(),
			// Hidden fields to include in the form (name => value)
			'hidden_data'    => array(),
			// How to render the TFA setup code field. "input" (HTML input element) or "custom" (custom HTML)
			'field_type'     => 'input',
			// The type attribute for the HTML input box. Typically "text" or "password". Use any HTML5 input type.
			'input_type'     => 'text',
			// Pre-filled value for the HTML input box. Typically used for fixed codes, the fixed YubiKey ID etc.
			'input_value'    => '',
			// Placeholder text for the HTML input box. Leave empty if you don't need it.
			'placeholder'    => '',
			// Label to show above the HTML input box. Leave empty if you don't need it.
			'label'          => '',
			// Custom HTML. Only used when field_type = custom.
			'html'           => '',
			// Should I show the submit button (apply the TFA setup)?
			'show_submit'    => true,
			// onclick handler for the submit button (apply the TFA setup)
			'submit_onclick' => '',
			// Custom HTML to display below the TFA setup form
			'post_message'   => '',
			// A URL with help content for this method to display to the user
			'help_url'       => '',
		];

		$record  = $this->getRecord($user);
		$results = $this->container->platform->runPlugins('onLoginGuardTfaGetSetup', [$record]);

		if (empty($results))
		{
			return $renderOptions;
		}

		foreach ($results as $result)
		{
			if (empty($result))
			{
				continue;
			}

			return array_merge($renderOptions, $result);
		}

		return $renderOptions;
	}

	/**
	 * Delete a TFA method record
	 *
	 * @param   int         $id
	 * @param   JUser|User  $user
	 *
	 * @since   2.0.0
	 */
	public function deleteRecord($id, $user = null)
	{
		// Make sure we have a valid user
		if (is_null($user))
		{
			$user = $this->container->platform->getUser();
		}

		// The user must be a registered user, not a guest
		if ($user->guest)
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// The record ID must be a positive integer
		if ($id <= 0)
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// The record must exist and belong to the specified user
		$this->setState('id', $id);
		$record = $this->getRecord($user);

		if (($record->user_id != $user->id) || ($record->id != $id))
		{
			throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// Try to delete the record
		$db    = $this->container->db;
		$query = $db->getQuery(true)
			->delete($db->qn('#__loginguard_tfa'))
			->where($db->qn('id') . ' = ' . $db->q($id))
			->where($db->qn('user_id') . ' = ' . $db->q($user->id));
		$db->setQuery($query)->execute();

		// We will need the list of records later on
		$allRecords = Tfa::getUserTfaRecords($record->user_id);

		// If the record was the default set a new default
		if ($record->default && !empty($records))
		{
			$records = $allRecords;

			do
			{
				$otherRecord = array_shift($records);
				$otherRecord->default = 1;

				if ($otherRecord->method != 'backupcodes')
				{
					break;
				}

				$otherRecord = null;

			} while (!empty($records));


			try
			{
				if (!empty($otherRecord))
				{
					$this->saveRecord($record);
				}
			}
			catch (Exception $e)
			{
				// If we can't set a new default record it's OK, we'll survive.
			}
		}

		// If the last remaining record is backup codes we need to remove it
		if (!empty($allRecords) && (count($allRecords) == 1))
		{
			$backupCodesRecord = array_shift($allRecords);

			$query = $db->getQuery(true)
				->delete($db->qn('#__loginguard_tfa'))
				->where($db->qn('id') . ' = ' . $db->q($backupCodesRecord->id));

			try
			{
				$db->setQuery($query)->execute();
			}
			catch (Exception $e)
			{
				// If we can't delete the backup codes it's OK, we'll survive.
			}
		}
	}

	/**
	 * Return the title to use for the page
	 *
	 * @return  string
	 * @since   2.0.0
	 */
	public function getPageTitle()
	{
		$task    = $this->getState('task', 'edit');
		$langKey = "COM_LOGINGUARD_HEAD_{$task}_PAGE";

		return JText::_($langKey);
	}

	/**
	 * Populate the list of TFA methods
	 *
	 * @since   2.0.0
	 */
	private function populateTfaMethods()
	{
		$this->tfaMethods = array();
		$tfaMethods       = Tfa::getTfaMethods();

		if (empty($tfaMethods))
		{
			return;
		}

		foreach ($tfaMethods as $method)
		{
			$this->tfaMethods[$method['name']] = $method;
		}

		// We also need to add the backup codes method
		$this->tfaMethods['backupcodes'] = array(
			'name' => 'backupcodes',
			'display' => JText::_('COM_LOGINGUARD_LBL_BACKUPCODES'),
			'shortinfo' => JText::_('COM_LOGINGUARD_LBL_BACKUPCODES_DESCRIPTION'),
			'image' => 'media/com_loginguard/images/emergency.svg',
			'canDisable' => false,
			'allowMultiple' => false,
		);
	}

	/**
	 * Get the default TFA method for the user
	 *
	 * @param   JUser|User  $user  The user record. Null to use the current user.
	 *
	 * @return  object
	 * @since   2.0.0
	 */
	protected function getDefaultRecord($user = null)
	{
		if (is_null($user))
		{
			$user = $this->container->platform->getUser();
		}

		$method = $this->getState('method');
		$title  = '';

		if (is_null($this->tfaMethods))
		{
			$this->populateTfaMethods();
		}

		if ($method && isset($this->tfaMethods[$method]))
		{
			$title = $this->tfaMethods[$method]['display'];
		}

		$record = (object) array(
			'id'      => null,
			'user_id' => $user->id,
			'title'   => $title,
			'method'  => $method,
			'default' => 0,
			'options' => '{}'
		);

		return $record;
	}

}
