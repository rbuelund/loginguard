<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

/** @var \Akeeba\LoginGuard\Site\View\Captive\Html $this */

if (!is_null($this->browserId) || !$this->container->session->get('browserIdCodeLoaded', false, 'com_loginguard'))
{
	die('Someone is being naughty.');
}

// We now load the FingerprintJS2 v.2.1.0 library from a local file with a stupid name because Firefox blocks anything with "fingerprint" in the name.
$this->addJavascriptFile('media://com_loginguard/js/magicthingie.min.js', null, 'text/javascript', true, false);
//$this->addJavascriptFile('https://cdnjs.cloudflare.com/ajax/libs/fingerprintjs2/2.1.0/fingerprint2.min.js', null, 'text/javascript', false, true);
$this->addJavascriptFile('https://cdnjs.cloudflare.com/ajax/libs/murmurhash3js/3.0.1/murmurHash3js.js', null, 'text/javascript', false, true);
// We call this script "security" instead of "fingerprint" to prevent Firefox refusing to load it.
$this->addJavascriptFile('media://com_loginguard/js/security.js', null, 'text/javascript', true, false);
$js = <<< JS
//;
window.jQuery(document).ready (function($) {
    var akeebaLoginGuardCaptiveCheckingCounter = 0;
    var akeebaLoginGuardCaptiveCheckingTimer = setInterval(function () {
        // Wait until we have a browser ID or we've been here for more than 4 seconds
        var notYet = 
        	(typeof akeeba === 'undefined') || 
        	(typeof akeeba.LoginGuard === 'undefined') || 
        	(typeof akeeba.LoginGuard.fingerprint === 'undefined') ||
        	(typeof akeeba.LoginGuard.fingerprint.browserId === 'undefined') ||
        	(akeeba.LoginGuard.fingerprint.browserId === null);
        
        if (++akeebaLoginGuardCaptiveCheckingCounter >= 16)
        {
            document.forms.akeebaLoginguardForm.submit();
        }
        
        if (notYet)
        {
            return;
        }
        
        // Unset this timer
        clearInterval(akeebaLoginGuardCaptiveCheckingTimer);
        
        // Set the browser ID in the form and submit the form.
        document.getElementById('akeebaLoginguardFormBrowserId').value = akeeba.LoginGuard.fingerprint.browserId;
        document.forms.akeebaLoginguardForm.submit();
    }, 250);
});

JS;


$this->addJavascriptInline($js);
?>
<div class="akeeba-panel--info">
	<header class="akeeba-block-header">
		<h2>
			<?= JText::_('COM_LOGINGUARD_HEAD_FINGERPRINTING'); ?>
		</h2>
	</header>
	<p>
		<?= JText::_('COM_LOGINGUARD_LBL_FINGERPRINTING_MESSAGE'); ?>
	</p>
</div>

<form id="akeebaLoginguardForm" method="post" action="<?= JRoute::_('index.php?option=com_loginguard&view=Captive') ?>">
	<input type="hidden" name="<?= $this->container->platform->getToken(true) ?>" value="1">
	<input type="hidden" id="akeebaLoginguardFormBrowserId" name="browserId" value="">
</form>