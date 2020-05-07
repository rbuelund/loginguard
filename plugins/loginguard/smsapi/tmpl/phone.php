<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

// Prevent direct access
defined('_JEXEC') or die;

// Load media
HTMLHelper::_('stylesheet', 'plg_loginguard_smsapi/telinput.min.css', array(
	'version'     => 'auto',
	'relative'    => true,
	'detectDebug' => true
), true, false, false, true);
HTMLHelper::_('jquery.framework');
HTMLHelper::_('bootstrap.framework');
HTMLHelper::_('script', 'plg_loginguard_smsapi/telinput.min.js', true, true, false, false, true);
HTMLHelper::_('script', 'plg_loginguard_smsapi/utils.min.js', true, true, false, false, true);

$token     = Factory::getApplication()->getSession()->getFormToken();
$actionURL = Uri::base() . 'index.php?option=com_loginguard&view=Callback&task=callback&method=smsapi&' . $token . '=1';
$js        = /** @lang JavaScript */
	<<< JS
;; // Defense against broken scripts
window.jQuery(document).ready(function ($){
    $("#loginGuardSMSAPIPhone").intlTelInput({
        preferredCountries: []
    });
});

function loginGuardSMSAPISendCode()
{
	var phone = window.jQuery('#loginGuardSMSAPIPhone').intlTelInput("getNumber");
	window.location = '$actionURL' + '&phone=' + encodeURIComponent(phone);
}

JS;

Factory::getApplication()->getDocument()->addScriptDeclaration($js);
?>
<div class="akeeba-form--horizontal" id="loginGuardSMSAPIForm">
    <div class="akeeba-form-group">
        <label for="loginGuardSMSAPIPhone">
			<?php echo JText::_('PLG_LOGINGUARD_SMSAPI_LBL_PHONE') ?>
        </label>
        <input type="text" name="phone-entry-field" id="loginGuardSMSAPIPhone" value="" class="input-large" />
    </div>
    <div class="akeeba-form-group--pull-right">
        <div class="akeeba-form-group--actions">
            <button type="button" class="akeeba-btn--primary" onclick="loginGuardSMSAPISendCode()">
                <span class="icon icon-phone glyphicon glyphicon-phone"></span>
				<?php echo JText::_('PLG_LOGINGUARD_SMSAPI_LBL_SENDCODEBUTTON'); ?>
            </button>
        </div>
    </div>
</div>
