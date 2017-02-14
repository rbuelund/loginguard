<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

JLoader::register('LoginGuardTOTPBase32', __DIR__ . '/base32.php');

/**
 * This class provides an RFC6238-compliant Time-based One Time Passwords,
 * compatible with Google Authenticator (with PassCodeLength = 6 and TimePeriod = 30).
 */
class LoginGuardTOTPAuthenticator
{
	/**
	 * Passcode length, in characters
	 *
	 * @var   int
	 */
	private $_passCodeLength = 6;

	/**
	 * The modulo (10 ^ _passCodeLength) for the TOTP
	 *
	 * @var   number
	 */
	private $_pinModulo;

	/**
	 * The length of the base32-encoded key
	 *
	 * @var   int
	 */
	private $_secretLength = 10;

	/**
	 * The time step, in seconds.
	 *
	 * @var int
	 */
	private $_timeStep = 30;

	/**
	 * The base32 helper class
	 *
	 * @var   LoginGuardTOTPBase32
	 */
	private $_base32 = null;

	/**
	 * Initialises an RFC6238-compatible TOTP generator. Please note that this class does not implement the constraint
	 * in the last paragraph of §5.2 of RFC6238. It's up to you to ensure that the same user/device does not retry
	 * validation within the same Time Step.
	 *
	 * @param   int                   $timeStep        The Time Step (in seconds). Use 30 to be compatible with Google
	 *                                                 Authenticator.
	 * @param   int                   $passCodeLength  The generated passcode length. Default: 6 digits.
	 * @param   int                   $secretLength    The length of the secret key. Default: 10 bytes (80 bits).
	 * @param   LoginGuardTOTPBase32  $base32          The base32 en/decrypter
	 */
	public function __construct($timeStep = 30, $passCodeLength = 6, $secretLength = 10, $base32=null)
	{
		$this->_timeStep       = $timeStep;
		$this->_passCodeLength = $passCodeLength;
		$this->_secretLength   = $secretLength;
		$this->_pinModulo      = pow(10, $this->_passCodeLength);
		$this->_base32         = $base32;

		if (is_null($base32))
		{
			$this->_base32 = new LoginGuardTOTPBase32();
		}
	}

	/**
	 * Get the time period based on the $time timestamp and the Time Step defined. If $time is skipped or set to null
	 * the current timestamp will be used.
	 *
	 * @param   int|null  $time  Timestamp
	 *
	 * @return  int  The time period since the UNIX Epoch
	 */
	public function getPeriod($time = null)
	{
		if (is_null($time))
		{
			$time = time();
		}

		$period = floor($time / $this->_timeStep);

		return $period;
	}

	/**
	 * Check is the given passcode $code is a valid TOTP generated using secret key $secret.
	 *
	 * @param   string  $secret  The Base32-encoded secret key
	 * @param   string  $code    The passcode to check
	 *
	 * @return  boolean True if the code is valid
	 */
	public function checkCode($secret, $code)
	{
		$time = $this->getPeriod();

		for ($i = -1; $i <= 1; $i++)
		{
			if ($this->getCode($secret, ($time + $i) * $this->_timeStep) == $code)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets the TOTP passcode for a given secret key $secret and a given UNIX timestamp $time
	 *
	 * @param   string  $secret  The Base32-encoded secret key
	 * @param   int     $time    UNIX timestamp
	 *
	 * @return  string
	 */
	public function getCode($secret, $time = null)
	{
		$period = $this->getPeriod($time);
		$secret = $this->_base32->decode($secret);

		$time = pack("N", $period);
		$time = str_pad($time, 8, chr(0), STR_PAD_LEFT);

		$hash = hash_hmac('sha1', $time, $secret, true);
		$offset = ord(substr($hash, -1));
		$offset = $offset & 0xF;

		$truncatedHash = $this->hashToInt($hash, $offset) & 0x7FFFFFFF;
		$pinValue = str_pad($truncatedHash % $this->_pinModulo, $this->_passCodeLength, "0", STR_PAD_LEFT);

		return $pinValue;
	}

	/**
	 * Extracts a part of a hash as an integer
	 *
	 * @param   string  $bytes  The hash
	 * @param   string  $start  The char to start from (0 = first char)
	 *
	 * @return  string
	 */
	protected function hashToInt($bytes, $start)
	{
		$input = substr($bytes, $start, strlen($bytes) - $start);
		$val2 = unpack("N", substr($input, 0, 4));

		return $val2[1];
	}

	/**
	 * Returns a QR code URL for easy setup of TOTP apps like Google Authenticator
	 *
	 * @param   string  $user      User
	 * @param   string  $hostname  Hostname
	 * @param   string  $secret    Secret string
	 *
	 * @return  string
	 */
	public function getUrl($user, $hostname, $secret)
	{
		$url = sprintf("otpauth://totp/%s@%s?secret=%s", $user, $hostname, $secret);
		$encoder = "https://chart.googleapis.com/chart?chs=200x200&chld=Q|2&cht=qr&chl=";
		$encoderURL = $encoder . urlencode($url);

		return $encoderURL;
	}

	/**
	 * Generates a (semi-)random Secret Key for TOTP generation
	 *
	 * @return  string
	 */
	public function generateSecret()
	{
		$secret = $this->generateRandomSecret($this->_secretLength);

		return $this->_base32->encode($secret);
	}

	/**
	 *
	 * Returns a cryptographically secure random value.
	 *
	 * @param   integer  $bytes  How many bytes to return
	 *
	 * @return  string
	 */
	private function generateRandomSecret($bytes = 32)
	{
		if (extension_loaded('openssl') && (version_compare(PHP_VERSION, '5.3.4') >= 0 || IS_WIN))
		{
			$strong = false;
			$randBytes = openssl_random_pseudo_bytes($bytes, $strong);

			if ($strong)
			{
				return $randBytes;
			}
		}

		if (extension_loaded('mcrypt'))
		{
			return mcrypt_create_iv($bytes, MCRYPT_DEV_URANDOM);
		}

		return $this->generateRandomBytesNative($bytes);
	}

	/**
	 * Generate random bytes. Adapted from Joomla! 3.2.
	 *
	 * @param   integer  $length  Length of the random data to generate
	 *
	 * @return  string  Random binary data
	 */
	private function generateRandomBytesNative($length = 32)
	{
		$length = (int) $length;
		$sslStr = '';

		/*
		 * Collect any entropy available in the system along with a number
		 * of time measurements of operating system randomness.
		 */
		$bitsPerRound = 2;
		$maxTimeMicro = 400;
		$shaHashLength = 20;
		$randomStr = '';
		$total = $length;

		// Check if we can use /dev/urandom.
		$urandom = false;
		$handle = null;

		// This is PHP 5.3.3 and up
		if (function_exists('stream_set_read_buffer') && @is_readable('/dev/urandom'))
		{
			$handle = @fopen('/dev/urandom', 'rb');

			if ($handle)
			{
				$urandom = true;
			}
		}

		while ($length > strlen($randomStr))
		{
			$bytes = ($total > $shaHashLength)? $shaHashLength : $total;
			$total -= $bytes;

			/*
			 * Collect any entropy available from the PHP system and filesystem.
			 * If we have ssl data that isn't strong, we use it once.
			 */
			$entropy = rand() . uniqid(mt_rand(), true) . $sslStr;
			$entropy .= implode('', @fstat(fopen(__FILE__, 'r')));
			$entropy .= memory_get_usage();
			$sslStr = '';

			if ($urandom)
			{
				stream_set_read_buffer($handle, 0);
				$entropy .= @fread($handle, $bytes);
			}
			else
			{
				/*
				 * There is no external source of entropy so we repeat calls
				 * to mt_rand until we are assured there's real randomness in
				 * the result.
				 *
				 * Measure the time that the operations will take on average.
				 */
				$samples = 3;
				$duration = 0;

				for ($pass = 0; $pass < $samples; ++$pass)
				{
					$microStart = microtime(true) * 1000000;
					$hash = sha1(mt_rand(), true);

					for ($count = 0; $count < 50; ++$count)
					{
						$hash = sha1($hash, true);
					}

					$microEnd = microtime(true) * 1000000;
					$entropy .= $microStart . $microEnd;

					if ($microStart >= $microEnd)
					{
						$microEnd += 1000000;
					}

					$duration += $microEnd - $microStart;
				}

				$duration = $duration / $samples;

				/*
				 * Based on the average time, determine the total rounds so that
				 * the total running time is bounded to a reasonable number.
				 */
				$rounds = (int) (($maxTimeMicro / $duration) * 50);

				/*
				 * Take additional measurements. On average we can expect
				 * at least $bitsPerRound bits of entropy from each measurement.
				 */
				$iter = $bytes * (int) ceil(8 / $bitsPerRound);

				for ($pass = 0; $pass < $iter; ++$pass)
				{
					$microStart = microtime(true);
					$hash = sha1(mt_rand(), true);

					for ($count = 0; $count < $rounds; ++$count)
					{
						$hash = sha1($hash, true);
					}

					$entropy .= $microStart . microtime(true);
				}
			}

			$randomStr .= sha1($entropy, true);
		}

		if ($urandom)
		{
			@fclose($handle);
		}

		return substr($randomStr, 0, $length);
	}

}
