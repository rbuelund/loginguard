<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\LoginGuard\Webauthn\Helper;

use Akeeba\LoginGuard\Webauthn\CredentialRepository;
use CBOR\Decoder;
use CBOR\OtherObject\OtherObjectManager;
use CBOR\Tag\TagObjectManager;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA;
use Cose\Algorithm\Signature\EdDSA;
use Cose\Algorithm\Signature\RSA;
use Cose\Algorithms;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Crypt\Crypt;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use RuntimeException;
use Throwable;
use Webauthn\AttestationStatement\AndroidKeyAttestationStatementSupport;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\FidoU2FAttestationStatementSupport;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AttestationStatement\PackedAttestationStatementSupport;
use Webauthn\AttestationStatement\TPMAttestationStatementSupport;
use Webauthn\AttestedCredentialData;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TokenBinding\TokenBindingNotSupportedHandler;
use Zend\Diactoros\ServerRequestFactory;

/**
 * Helper class to aid in credentials creation (link an authenticator to a user account)
 */
abstract class Credentials
{
	/**
	 * Create a public key for credentials creation. The result is a JSON string which can be used in Javascript code
	 * with navigator.credentials.create().
	 *
	 * @param   User  $user  The Joomla user to create the public key for
	 *
	 * @return  string
	 */
	public static function createPublicKey(User $user): string
	{
		/** @var CMSApplication $app */
		try
		{
			$app      = Factory::getApplication();
			$siteName = $app->get('sitename');
		}
		catch (Exception $e)
		{
			$siteName = 'Joomla! Site';
		}

		// Credentials repository
		$repository = new CredentialRepository($user->id);

		// Relaying Party -- Our site
		$rpEntity = new PublicKeyCredentialRpEntity(
			$siteName,
			Uri::getInstance()->toString(['host']),
			self::getSiteIcon()
		);

		// User Entity
		$userEntity = new PublicKeyCredentialUserEntity(
			$user->username,
			$user->id,
			$user->name,
			self::getAvatar($user, 64)
		);

		// Challenge
		try
		{
			$challenge = random_bytes(32);
		}
		catch (Exception $e)
		{
			$challenge = Crypt::genRandomBytes(32);
		}

		// Public Key Credential Parameters
		$publicKeyCredentialParametersList = [
			new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_ES256),
		];

		// Timeout: 60 seconds (given in milliseconds)
		$timeout = 60000;

		// Devices to exclude (already set up authenticators)
		$excludedPublicKeyDescriptors = [];
		$records                      = $repository->findAllForUserEntity($userEntity);

		foreach ($records as $record)
		{
			$excludedPublicKeyDescriptors[] = new PublicKeyCredentialDescriptor($record->getType(), $record->getCredentialPublicKey());
		}

		// Authenticator Selection Criteria (we used default values)
		$authenticatorSelectionCriteria = new AuthenticatorSelectionCriteria();

		// Extensions (not yet supported by the library)
		$extensions = new AuthenticationExtensionsClientInputs;

		// Attestation preference
		$attestationPreference = PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE;

		// Public key credential creation options
		$publicKeyCredentialCreationOptions = new PublicKeyCredentialCreationOptions(
			$rpEntity,
			$userEntity,
			$challenge,
			$publicKeyCredentialParametersList,
			$timeout,
			$excludedPublicKeyDescriptors,
			$authenticatorSelectionCriteria,
			$attestationPreference,
			$extensions
		);

		// Save data in the session
		Factory::getSession()->set('publicKeyCredentialCreationOptions', base64_encode(serialize($publicKeyCredentialCreationOptions)), 'plg_loginguard_webauthn');
		Factory::getSession()->set('registration_user_id', $user->id, 'plg_loginguard_webauthn');

		return json_encode($publicKeyCredentialCreationOptions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	/**
	 * Validate the authentication data returned by the device and return the attested credential data on success.
	 *
	 * An exception will be returned on error. Also, under very rare conditions, you may receive NULL instead of
	 * attested credential data which means that something was off in the returned data from the browser.
	 *
	 * @param   string  $data  The JSON-encoded data returned by the browser during the authentication flow
	 *
	 * @return  AttestedCredentialData|null
	 */
	public static function validateAuthenticationData(string $data): ?PublicKeyCredentialSource
	{
		// Retrieve the PublicKeyCredentialCreationOptions object created earlier and perform sanity checks
		$encodedOptions = Factory::getSession()->get('publicKeyCredentialCreationOptions', null, 'plg_loginguard_webauthn');

		if (empty($encodedOptions))
		{
			throw new RuntimeException(Text::_('PLG_LOGINGUARD_WEBAUTHN_ERR_CREATE_NO_PK'));
		}

		try
		{
			$publicKeyCredentialCreationOptions = unserialize(base64_decode($encodedOptions));
		}
		catch (Exception $e)
		{
			$publicKeyCredentialCreationOptions = null;
		}

		if (!is_object($publicKeyCredentialCreationOptions) || !($publicKeyCredentialCreationOptions instanceof PublicKeyCredentialCreationOptions))
		{
			throw new RuntimeException(Text::_('PLG_LOGINGUARD_WEBAUTHN_ERR_CREATE_NO_PK'));
		}

		// Retrieve the stored user ID and make sure it's the same one in the request.
		$storedUserId = Factory::getSession()->get('registration_user_id', 0, 'plg_loginguard_webauthn');
		$myUser       = Factory::getUser();
		$myUserId     = $myUser->id;

		if (($myUser->guest) || ($myUserId != $storedUserId))
		{
			throw new RuntimeException(Text::_('PLG_LOGINGUARD_WEBAUTHN_ERR_CREATE_INVALID_USER'));
		}

		// Cose Algorithm Manager
		$coseAlgorithmManager = new Manager;
		$coseAlgorithmManager->add(new ECDSA\ES256);
		$coseAlgorithmManager->add(new ECDSA\ES512);
		$coseAlgorithmManager->add(new EdDSA\EdDSA);
		$coseAlgorithmManager->add(new RSA\RS1);
		$coseAlgorithmManager->add(new RSA\RS256);
		$coseAlgorithmManager->add(new RSA\RS512);

		// Create a CBOR Decoder object
		$otherObjectManager = new OtherObjectManager();
		$tagObjectManager   = new TagObjectManager();
		$decoder            = new Decoder($tagObjectManager, $otherObjectManager);

		// The token binding handler
		$tokenBindingHandler = new TokenBindingNotSupportedHandler();

		// Attestation Statement Support Manager
		$coseAlgorithmManager               = new Manager();
		$attestationStatementSupportManager = new AttestationStatementSupportManager();
		$attestationStatementSupportManager->add(new NoneAttestationStatementSupport());
		$attestationStatementSupportManager->add(new FidoU2FAttestationStatementSupport($decoder));
		$attestationStatementSupportManager->add(new AndroidKeyAttestationStatementSupport($decoder));
		$attestationStatementSupportManager->add(new TPMAttestationStatementSupport);
		$attestationStatementSupportManager->add(new PackedAttestationStatementSupport($decoder, $coseAlgorithmManager));

		// Attestation Object Loader
		$attestationObjectLoader = new AttestationObjectLoader($attestationStatementSupportManager, $decoder);

		// Public Key Credential Loader
		$publicKeyCredentialLoader = new PublicKeyCredentialLoader($attestationObjectLoader, $decoder);

		// Credential Repository
		$credentialRepository = new CredentialRepository($storedUserId);

		// Extension output checker handler
		$extensionOutputCheckerHandler = new ExtensionOutputCheckerHandler();

		// Authenticator Attestation Response Validator
		$authenticatorAttestationResponseValidator = new AuthenticatorAttestationResponseValidator(
			$attestationStatementSupportManager,
			$credentialRepository,
			$tokenBindingHandler,
			$extensionOutputCheckerHandler
		);

		// Any Throwable from this point will bubble up to the GUI

		// We init the PSR-7 request object using Diactoros
		$request = ServerRequestFactory::fromGlobals();

		// Load the data
		$publicKeyCredential = $publicKeyCredentialLoader->load(base64_decode($data));
		$response            = $publicKeyCredential->getResponse();

		// Check if the response is an Authenticator Attestation Response
		if (!$response instanceof AuthenticatorAttestationResponse)
		{
			throw new RuntimeException('Not an authenticator attestation response');
		}

		// Check the response against the request
		$authenticatorAttestationResponseValidator->check($response, $publicKeyCredentialCreationOptions, $request);

		/**
		 * Everything is OK here. You can get the Public Key Credential Source. This object should be persisted using
		 * the Public Key Credential Source repository.
		 */
		return $authenticatorAttestationResponseValidator->check($response, $publicKeyCredentialCreationOptions, $request);
	}

	/**
	 * Creates a WebAuthn Public Key Credential Request (challenge) used by the browser during key verification
	 *
	 * @param   int  $user_id
	 *
	 * @return  string
	 *
	 * @since   3.1.0
	 */
	public static function createChallenge(int $user_id): string
	{
		// Create a WebAuthn challenge and set it in the session
		$repository = new CredentialRepository($user_id);

		// Load the saved credentials into an array of PublicKeyCredentialDescriptor objects
		try
		{
			$userEntity  = new PublicKeyCredentialUserEntity(
				'', $user_id, ''
			);
			$credentials = $repository->findAllForUserEntity($userEntity);
		}
		catch (Exception $e)
		{
			return json_encode(false);
		}

		// No stored credentials?
		if (empty($credentials))
		{
			return json_encode(false);
		}

		$registeredPublicKeyCredentialDescriptors = [];

		/** @var PublicKeyCredentialSource $record */
		foreach ($credentials as $record)
		{
			try
			{
				$registeredPublicKeyCredentialDescriptors[] = $record->getPublicKeyCredentialDescriptor();
			}
			catch (Throwable $e)
			{
				continue;
			}
		}

		try
		{
			$challenge = random_bytes(32);
		}
		catch (Exception $e)
		{
			$challenge = Crypt::genRandomBytes(32);
		}

		// Extensions
		$extensions = new AuthenticationExtensionsClientInputs;

		// Public Key Credential Request Options
		$publicKeyCredentialRequestOptions = new PublicKeyCredentialRequestOptions(
			$challenge,
			60000,
			Uri::getInstance()->toString(['host']),
			$registeredPublicKeyCredentialDescriptors,
			PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
			$extensions
		);

		// Save in session. This is used during the verification stage to prevent replay attacks.
		$session = Factory::getSession();
		$session->set('publicKeyCredentialRequestOptions', base64_encode(serialize($publicKeyCredentialRequestOptions)), 'plg_loginguard_webauthn');
		$session->set('userHandle', $user_id, 'plg_loginguard_webauthn');
		$session->set('userId', $user_id, 'plg_loginguard_webauthn');

		// Return the JSON encoded data to the caller
		return json_encode($publicKeyCredentialRequestOptions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	/**
	 * Checks if the browser's response to our challenge is valid.
	 *
	 * @param   string  $response  Base64-encoded response
	 *
	 * @throws  Exception  When something does not check out.
	 * @since   3.1.0
	 *
	 */
	public static function validateChallenge(string $response): void
	{
		$session = Factory::getSession();

		$encodedPkOptions = $session->get('publicKeyCredentialRequestOptions', null, 'plg_loginguard_webauthn');
		$userHandle       = $session->get('userHandle', null, 'plg_loginguard_webauthn');
		$userId           = $session->get('userId', null, 'plg_loginguard_webauthn');

		$session->set('publicKeyCredentialRequestOptions', null, 'plg_loginguard_webauthn');
		$session->set('userHandle', null, 'plg_loginguard_webauthn');
		$session->set('userId', null, 'plg_loginguard_webauthn');

		if (empty($userId))
		{
			throw new RuntimeException(Text::_('PLG_LOGINGUARD_WEBAUTHN_ERR_CREATE_INVALID_LOGIN_REQUEST'));
		}

		// Make sure the user exists
		$user = Factory::getUser($userId);

		if ($user->id != $userId)
		{
			throw new RuntimeException(Text::_('PLG_LOGINGUARD_WEBAUTHN_ERR_CREATE_INVALID_LOGIN_REQUEST'));
		}

		// Make sure the user is ourselves (we cannot perform 2SV on behalf of another user!)
		if (Factory::getUser()->id != $userId)
		{
			throw new RuntimeException(Text::_('PLG_LOGINGUARD_WEBAUTHN_ERR_CREATE_INVALID_LOGIN_REQUEST'));
		}

		// Make sure the public key credential request options in the session are valid
		$serializedOptions                 = base64_decode($encodedPkOptions);
		$publicKeyCredentialRequestOptions = unserialize($serializedOptions);

		if (!is_object($publicKeyCredentialRequestOptions) || empty($publicKeyCredentialRequestOptions) || !($publicKeyCredentialRequestOptions instanceof PublicKeyCredentialRequestOptions))
		{
			throw new RuntimeException(Text::_('PLG_LOGINGUARD_WEBAUTHN_ERR_CREATE_INVALID_LOGIN_REQUEST'));
		}

		// Unserialize the browser response data
		$data = base64_decode($response);

		// Create a credentials repository
		$credentialRepository = new CredentialRepository($userId);

		// Cose Algorithm Manager
		$coseAlgorithmManager = new Manager;
		$coseAlgorithmManager->add(new ECDSA\ES256);
		$coseAlgorithmManager->add(new ECDSA\ES512);
		$coseAlgorithmManager->add(new EdDSA\EdDSA);
		$coseAlgorithmManager->add(new RSA\RS1);
		$coseAlgorithmManager->add(new RSA\RS256);
		$coseAlgorithmManager->add(new RSA\RS512);

		// Create a CBOR Decoder object
		$otherObjectManager = new OtherObjectManager();
		$tagObjectManager   = new TagObjectManager();
		$decoder            = new Decoder($tagObjectManager, $otherObjectManager);

		// Attestation Statement Support Manager
		$algorithmManager                   = new Manager();
		$attestationStatementSupportManager = new AttestationStatementSupportManager();
		$attestationStatementSupportManager->add(new NoneAttestationStatementSupport());
		$attestationStatementSupportManager->add(new FidoU2FAttestationStatementSupport($decoder));
		$attestationStatementSupportManager->add(new AndroidKeyAttestationStatementSupport($decoder));
		$attestationStatementSupportManager->add(new TPMAttestationStatementSupport);
		$attestationStatementSupportManager->add(new PackedAttestationStatementSupport($decoder, $coseAlgorithmManager));

		// Attestation Object Loader
		$attestationObjectLoader = new AttestationObjectLoader($attestationStatementSupportManager, $decoder);

		// Public Key Credential Loader
		$publicKeyCredentialLoader = new PublicKeyCredentialLoader($attestationObjectLoader, $decoder);

		// The token binding handler
		$tokenBindingHandler = new TokenBindingNotSupportedHandler();

		// Extension Output Checker Handler
		$extensionOutputCheckerHandler = new ExtensionOutputCheckerHandler;

		// Authenticator Assertion Response Validator
		$authenticatorAssertionResponseValidator = new AuthenticatorAssertionResponseValidator(
			$credentialRepository,
			$decoder,
			$tokenBindingHandler,
			$extensionOutputCheckerHandler,
			$coseAlgorithmManager
		);

		// We init the Symfony Request object
		$request = ServerRequestFactory::fromGlobals();

		// Load the data
		$publicKeyCredential = $publicKeyCredentialLoader->load($data);
		$response            = $publicKeyCredential->getResponse();

		// Check if the response is an Authenticator Assertion Response
		if (!$response instanceof AuthenticatorAssertionResponse)
		{
			throw new RuntimeException('Not an authenticator assertion response');
		}

		/** @var AuthenticatorAssertionResponse $authenticatorAssertionResponse */
		$authenticatorAssertionResponse = $publicKeyCredential->getResponse();
		$authenticatorAssertionResponseValidator->check(
			$publicKeyCredential->getRawId(),
			$authenticatorAssertionResponse,
			$publicKeyCredentialRequestOptions,
			$request,
			$userHandle
		);
	}

	/**
	 * Get the user's avatar (through Gravatar)
	 *
	 * @param   User  $user  The Joomla user object
	 * @param   int   $size  The dimensions of the image to fetch (default: 64 pixels)
	 *
	 * @return  string  The URL to the user's avatar
	 *
	 * @since   3.1.0
	 */
	public static function getAvatar(User $user, int $size = 64)
	{
		$scheme    = Uri::getInstance()->getScheme();
		$subdomain = ($scheme == 'https') ? 'secure' : 'www';

		return sprintf('%s://%s.gravatar.com/avatar/%s.jpg?s=%u&d=mm', $scheme, $subdomain, md5($user->email), $size);
	}

	/**
	 * Try to find the site's favicon in the site's root, images, media, templates or current template directory.
	 *
	 * @return  string|null
	 *
	 * @since   3.1.0
	 */
	protected static function getSiteIcon(): ?string
	{
		$filenames = [
			'apple-touch-icon.png',
			'apple_touch_icon.png',
			'favicon.ico',
			'favicon.png',
			'favicon.gif',
			'favicon.bmp',
			'favicon.jpg',
			'favicon.svg',
		];

		try
		{
			$paths = [
				'/',
				'/images/',
				'/media/',
				'/templates/',
				'/templates/' . Factory::getApplication()->getTemplate(),
			];
		}
		catch (Exception $e)
		{
			return null;
		}

		foreach ($paths as $path)
		{
			foreach ($filenames as $filename)
			{
				$relFile  = $path . $filename;
				$filePath = JPATH_BASE . $relFile;

				if (is_file($filePath))
				{
					break 2;
				}

				$relFile = null;
			}
		}

		if (is_null($relFile))
		{
			return null;
		}

		return rtrim(Uri::base(), '/') . '/' . ltrim($relFile, '/');
	}
}