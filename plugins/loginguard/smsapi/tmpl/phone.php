<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

// Load media
JHtml::_('stylesheet', 'plg_loginguard_smsapi/telinput.min.css', array(
	'version'     => 'auto',
	'relative'    => true,
	'detectDebug' => true
), true, false, false, true);
JHtml::_('jquery.framework');
JHtml::_('bootstrap.framework');
JHtml::_('script', 'plg_loginguard_smsapi/telinput.min.js', true, true, false, false, true);
JHtml::_('script', 'plg_loginguard_smsapi/utils.min.js', true, true, false, false, true);

$token     = JFactory::getApplication()->getSession()->getFormToken();
$actionURL = JUri::base() . 'index.php?option=com_loginguard&task=callback.callback&method=smsapi&' . $token . '=1';
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

JFactory::getApplication()->getDocument()->addScriptDeclaration($js);
?>
<div class="form form-horizontal" id="loginGuardSMSAPIForm">
    <div id="loginguard-smsapi-controls">
        <div class="control-group">
            <label class="control-label" for="loginGuardSMSAPIPhone">
                <?php echo JText::_('PLG_LOGINGUARD_SMSAPI_LBL_PHONE') ?>
            </label>
            <div class="controls">
                <input type="text" name="phone-entry-field" id="loginGuardSMSAPIPhone" value="" class="input-large" />
            </div>
        </div>
        <div class="control-group">
            <div class="controls">
                <button type="button" class="btn btn-primary" onclick="loginGuardSMSAPISendCode()">
                    <span class="icon icon-phone glyphicon glyphicon-phone"></span>
	                <?php echo JText::_('PLG_LOGINGUARD_SMSAPI_LBL_SENDCODEBUTTON'); ?>
                </button>
            </div>
        </div>
    </div>
</div>