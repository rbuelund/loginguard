<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

// Prevent direct access
defined('_JEXEC') or die;

$js = <<< JS
;; // Defense against broken scripts

window.jQuery(document).ready(function($){
    document.getElementById('loginguard-webauthn-missing').style.display = 'none';
    
    if (typeof(navigator.credentials) == 'undefined')
    {
        document.getElementById('loginguard-webauthn-missing').style.display = 'block';
        document.getElementById('loginguard-webauthn-controls').style.display = 'none';
    }
});

JS;

Factory::getDocument()->addScriptDeclaration($js);

?>
<div id="loginguard-webauthn-missing">
	<div class="alert alert-error">
		<h4>
			<?php echo Text::_('PLG_LOGINGUARD_WEBAUTHN_ERR_NOTAVAILABLE_HEAD'); ?>
		</h4>
		<p>
			<?php echo Text::_('PLG_LOGINGUARD_WEBAUTHN_ERR_NOTAVAILABLE_BODY'); ?>
		</p>
	</div>
</div>
