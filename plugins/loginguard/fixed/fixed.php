<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

/**
 * Akeeba LoginGuard Plugin for Two Factor Authentication method "Fixed"
 *
 * Requires a static string (password), different for each user. It effectively works as a second password. This is NOT
 * to be used on production sites. It serves as a demonstration plugin and as a template for developers to create their
 * own custom two factor authentication plugins.
 */
class PlgLoginguardFixed extends JPlugin
{
	public function __construct($subject, array $config = array())
	{
		parent::__construct($subject, $config);

		$this->loadLanguage();
	}


	/**
	 * The TFA method name handled by this plugin
	 *
	 * @var   string
	 */
	private $tfaMethodName = 'fixed';

	/**
	 * Gets the identity of this TFA method
	 *
	 * @return  array
	 */
	public function onLoginGuardTfaGetMethod()
	{
		return array(
			// Internal code of this TFA method
			'name'          => $this->tfaMethodName,
			// User-facing name for this TFA method
			'display'       => JText::_('PLG_LOGINGUARD_FIXED_LBL_DISPLAYEDAS'),
			// URL to the logo image for this method
			'image'         => 'plugins/loginguard/fixed/images/fixed.svg',
			// Are we allowed to disable it?
			'canDisable'    => true,
			// Are we allowed to have multiple instances of it per user?
			'allowMultiple' => false
		);
	}

	/**
	 * Returns the information which allows LoginGuard to render the captive TFA page. This is the page which appears
	 * right after you log in and asks you to validate your login with TFA.
	 *
	 * @param   stdClass  $record  The #__loginguard_tfa record currently selected by the user.
	 *
	 * @return  array
	 */
	public function onLoginGuardTfaCaptive($record)
	{
		// Make sure we are actually meant to handle this method
		if ($record->method != $this->tfaMethodName)
		{
			return array();
		}

		return array(
			// Custom HTML to display above the TFA form
			'pre_message'  => JText::_('PLG_LOGINGUARD_FIXED_LBL_PREMESSAGE'),
			// How to render the TFA code field. "input" (HTML input element) or "custom" (custom HTML)
			'field_type'   => 'input',
			// The type attribute for the HTML input box. Typically "text" or "password". Use any HTML5 input type.
			'input_type'   => 'password',
			// Placeholder text for the HTML input box. Leave empty if you don't need it.
			'placeholder'  => JText::_('PLG_LOGINGUARD_FIXED_LBL_PLACEHOLDER'),
			// Label to show above the HTML input box. Leave empty if you don't need it.
			'label'        => JText::_('PLG_LOGINGUARD_FIXED_LBL_LABEL'),
			// Custom HTML. Only used when field_type = custom.
			'html'         => '',
			// Custom HTML to display below the TFA form
			'post_message' => JText::_('PLG_LOGINGUARD_FIXED_LBL_POSTMESSAGE')
		);
	}

	public function onLoginGuardTfaGetSetup($record)
	{
		// Make sure we are actually meant to handle this method
		if ($record->method != $this->tfaMethodName)
		{
			return array();
		}

		// Load the options from the record (if any)
		$options = $this->decodeRecordOptions($record);

		/**
		 * Return the parameters used to render the GUI.
		 *
		 * Some TFA methods need to display a different interface before and after the setup. For example, when setting
		 * up Google Authenticator or a hardware OTP dongle you need the user to enter a TFA code to verify they are in
		 * possession of a correctly configured device. After the setup is complete you don't want them to see that
		 * field again. In the first state you could use the tabular_data to display the setup values, pre_message to
		 * display the QR code and field_type=input to let the user enter the TFA code. In the second state do the same
		 * BUT set field_type=custom, set html='' and show_submit=false to effectively hide the setup form from the
		 * user.
		 */
		return array(
			// Is this TFA method already enabled? Used to determine when to display the "Disable" button in the GUI.
			'is_enabled'     => !empty($options->fixed_code),
			// Default title if you are setting up this TFA method for the first time
			'default_title'  => JText::_('PLG_LOGINGUARD_FIXED_LBL_DEFAULTTITLE'),
			// Custom HTML to display above the TFA setup form
			'pre_message'    => JText::_('PLG_LOGINGUARD_FIXED_LBL_SETUP_PREMESSAGE'),
			// Heading for displayed tabular data. Typically used to display a list of fixed TFA codes, TOTP setup parameters etc
			'table_heading'  => '',
			// Any tabular data to display (label => custom HTML). See above
			'tabular_data'   => array(),
			// Hidden fields to include in the form (name => value)
			'hidden_data'    => array(),
			// How to render the TFA setup code field. "input" (HTML input element) or "custom" (custom HTML)
			'field_type'     => 'input',
			// The type attribute for the HTML input box. Typically "text" or "password". Use any HTML5 input type.
			'input_type'     => 'password',
			// Pre-filled value for the HTML input box. Typically used for fixed codes, the fixed YubiKey ID etc.
			'input_value'    => $options->fixed_code,
			// Placeholder text for the HTML input box. Leave empty if you don't need it.
			'placeholder'    => JText::_('PLG_LOGINGUARD_FIXED_LBL_PLACEHOLDER'),
			// Label to show above the HTML input box. Leave empty if you don't need it.
			'label'          => JText::_('PLG_LOGINGUARD_FIXED_LBL_LABEL'),
			// Custom HTML. Only used when field_type = custom.
			'html'           => '',
			// Should I show the submit button (apply the TFA setup)? Only applies when is_enabled is true.
			'show_submit'    => true,
			// onclick handler for the submit button (apply the TFA setup)?
			'submit_onclick' => '',
			// Custom HTML to display below the TFA setup form
			'post_message'   => JText::_('PLG_LOGINGUARD_FIXED_LBL_SETUP_POSTMESSAGE'),
		);
	}

	public function onLoginGuardTfaSaveSetup($record)
	{
		// Make sure we are actually meant to handle this method
		if ($record->method != $this->tfaMethodName)
		{
			return array();
		}

		// Load the options from the record (if any)
		$options = $this->decodeRecordOptions($record);

		// Merge with the submitted form data
		$input = JFactory::getApplication()->input;
		$code = $input->get('code', $options->fixed_code, 'raw');

		// Make sure the code is not empty
		if (empty($code))
		{
			throw new Exception(JText::_('PLG_LOGINGUARD_FIXED_ERR_EMPTYCODE'));
		}

		// Return the configuration to be serialized
		return (object) array(
			'fixed_code' => $code
		);
	}

	public function onLoginGuardTfaValidate($record, JUser $user, $code)
	{
		// Make sure we are actually meant to handle this method
		if ($record->method != $this->tfaMethodName)
		{
			return array();
		}

		// Load the options from the record (if any)
		$options = $this->decodeRecordOptions($record);

		// Double check the TFA method is for the correct user
		if ($user->id != $record->user_id)
		{
			return false;
		}

		// Check the TFA code for validity
		return $options->fixed_code == $code;
	}

	/**
	 * Decodes the options from a #__loginguard_tfa record into an options object.
	 *
	 * @param   stdClass  $record
	 *
	 * @return  stdClass
	 */
	private function decodeRecordOptions($record)
	{
		$options = array(
			'fixed_code' => ''
		);

		if (!empty($record->options))
		{
			$recordOptions = $record->options;

			if (is_string($recordOptions))
			{
				$recordOptions = json_decode($recordOptions, true);
			}

			$options = array_merge($options, $recordOptions);
		}

		return (object) $options;
	}
}