<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\LoginGuard\Webauthn;

use Akeeba\LoginGuard\Admin\Model\Tfa;
use FOF30\Container\Container;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use RuntimeException;
use Webauthn\AttestedCredentialData;
use Webauthn\CredentialRepository as CredentialRepositoryInterface;

// Prevent direct access
defined('_JEXEC') or die;

/**
 * Implementation of the credentials repository for the WebAuthn library.
 *
 * Important assumption: interaction with Webauthn through the library is only performed for the currently logged in
 * user. Therefore all methods which take a credential ID work by checking the LoginGuard TFA records of the current
 * user only. This is a necessity. The records are stored encrypted, therefore we cannot do a partial search in the
 * table. We have to load the records, decrypt them and inspect them. We cannot do that for thousands of records but
 * we CAN do that for the few records each user has under their account.
 *
 * This behavior can be changed by passing a user ID in the constructor of the class.
 *
 * @package     Akeeba\LoginGuard\Webauthn
 *
 * @since       3.1.0
 */
class CredentialRepository implements CredentialRepositoryInterface
{
	/**
	 * The user ID we will operate with
	 *
	 * @var   int
	 * @since 3.1.0
	 */
	private $user_id = 0;

	/**
	 * CredentialRepository constructor.
	 *
	 * @param   int  $user_id  The user ID this repository will be working with.
	 */
	public function __construct(int $user_id = 0)
	{
		if (empty($user_id))
		{
			$user_id = Factory::getUser()->id;
		}

		$this->user_id = $user_id;
	}


	/**
	 * Do we have stored credentials under the specified Credential ID?
	 *
	 * @param   string  $credentialId
	 *
	 * @return  bool
	 */
	public function has(string $credentialId): bool
	{
		$credentials = $this->getAll($this->user_id);

		foreach ($credentials as $attestedCredentialData)
		{
			if ($attestedCredentialData->getCredentialId() == $credentialId)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Retrieve the attested credential data given a Credential ID
	 *
	 * @param   string  $credentialId
	 *
	 * @return  AttestedCredentialData
	 */
	public function get(string $credentialId): AttestedCredentialData
	{
		$credentials = $this->getAll($this->user_id);

		foreach ($credentials as $attestedCredentialData)
		{
			if ($attestedCredentialData->getCredentialId() == $credentialId)
			{
				return $attestedCredentialData;
			}
		}

		throw new RuntimeException(Text::_('PLG_LOGINGUARD_WEBAUTHN_ERR_NO_STORED_CREDENTIAL'));
	}

	/**
	 * Return the user handle for the stored credential given its ID.
	 *
	 * The user handle must not be personally identifiable. Per https://w3c.github.io/webauthn/#user-handle it is
	 * acceptable to have a salted hash with a salt private to our server, e.g. Joomla's secret. The only immutable
	 * information in Joomla is the user ID so that's what we will be using.
	 *
	 * @param   string  $credentialId
	 *
	 * @return  string
	 */
	public function getUserHandleFor(string $credentialId): string
	{
		if (!$this->has($credentialId))
		{
			throw new RuntimeException(Text::_('PLG_LOGINGUARD_WEBAUTHN_ERR_NO_STORED_CREDENTIAL'));
		}

		return $this->getHandleFromUserId($this->user_id);
	}

	/**
	 * Returns the last seen counter for this authenticator
	 *
	 * @param   string   $credentialId  The authenticator's credential ID
	 *
	 * @return  int
	 */
	public function getCounterFor(string $credentialId): int
	{
		$credentials = $this->getAll($this->user_id);
		$tfaId       = 0;

		foreach ($credentials as $id => $attestedCredentialData)
		{
			if ($attestedCredentialData->getCredentialId() == $credentialId)
			{
				$tfaId = $id;

				break;
			}
		}

		if (!$tfaId)
		{
			throw new RuntimeException(Text::_('PLG_LOGINGUARD_WEBAUTHN_ERR_NO_STORED_CREDENTIAL'));
		}

		$container = Container::getInstance('com_loginguard');
		/** @var Tfa $tfaModel */
		$tfaModel = $container->factory->model('Tfa')->tmpInstance();
		$options  = $tfaModel->findOrFail($tfaId)->options;

		if (!is_array($options) || empty($options) || !isset($options['counter']))
		{
			throw new RuntimeException(Text::_('PLG_LOGINGUARD_WEBAUTHN_ERR_NO_STORED_CREDENTIAL'));
		}

		return $options['counter'];
	}

	/**
	 * Update the stored counter for this authenticator
	 *
	 * @param   string  $credentialId  The authenticator's credential ID
	 * @param   int     $newCounter    The new value of the counter we should store in the database
	 */
	public function updateCounterFor(string $credentialId, int $newCounter): void
	{
		$credentials = $this->getAll($this->user_id);
		$tfaId       = 0;

		foreach ($credentials as $id => $attestedCredentialData)
		{
			if ($attestedCredentialData->getCredentialId() == $credentialId)
			{
				$tfaId = $id;

				break;
			}
		}

		if (!$tfaId)
		{
			throw new RuntimeException(Text::_('PLG_LOGINGUARD_WEBAUTHN_ERR_NO_STORED_CREDENTIAL'));
		}

		$container = Container::getInstance('com_loginguard');
		/** @var Tfa $tfaModel */
		$tfaModel = $container->factory->model('Tfa')->tmpInstance();
		$record   = $tfaModel->findOrFail($tfaId);

		$record->options['counter'] = $newCounter;
		$record->save();
	}

	/**
	 * Get all credentials for a given user ID
	 *
	 * @param   int  $user_id  The user ID
	 *
	 * @return  AttestedCredentialData[]   [TFA record id => AttestedCredentialData, ...]
	 */
	public function getAll(int $user_id = 0): array
	{
		if (empty($user_id))
		{
			$user_id = $this->user_id;
		}

		$return = [];

		$container = Container::getInstance('com_loginguard');
		/** @var Tfa $tfaModel */
		$tfaModel = $container->factory->model('Tfa')->tmpInstance();
		$results  = $tfaModel->user_id($user_id)->method('webauthn')->get(true);

		if ($results->count() < 1)
		{
			return $return;
		}

		/** @var Tfa $result */
		foreach ($results as $result)
		{
			$options = $result->options;

			if (!is_array($options) || empty($options))
			{
				continue;
			}

			if (!isset($options['attested']))
			{
				continue;
			}

			if (is_string($options['attested']))
			{
				$options['attested'] = json_decode($options['attested'], true);
			}

			$return[$result->getId()] = AttestedCredentialData::createFromJson($options['attested']);
		}

		return $return;
	}

	/**
	 * Return a user handle given an integer Joomla user ID
	 *
	 * @param   int  $id  The user ID to convert
	 *
	 * @return  string  The user handle (HMAC-SHA-512 of the user ID)
	 */
	public function getHandleFromUserId(int $id): string
	{
		$secret = Factory::getConfig()->get('secret', '');
		$data   = sprintf('%010u', $id);

		return hash_hmac('sha512', $data, $secret, true);
	}

}