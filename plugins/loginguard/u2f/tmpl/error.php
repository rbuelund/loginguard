<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

$js = <<< JS
;; // Defense against broken scripts

window.jQuery(document).ready(function($){
    document.getElementById('loginguard-u2f-missing').style.display = 'none';
    
    if (typeof(window.u2f) == 'undefined')
    {
        document.getElementById('loginguard-u2f-missing').style.display = 'block';
        document.getElementById('loginguard-u2f-controls').style.display = 'none';
    }
});

JS;

JFactory::getDocument()->addScriptDeclaration($js);

?>
<div id="loginguard-u2f-missing">
	<div class="alert alert-error">
		<h4>
			<?php echo JText::_('PLG_LOGINGUARD_U2F_ERR_NOTAVAILABLE_HEAD'); ?>
		</h4>
		<p>
			<?php echo JText::_('PLG_LOGINGUARD_U2F_ERR_NOTAVAILABLE_BODY'); ?>
		</p>
	</div>
</div>
