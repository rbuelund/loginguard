<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

$baseURL     = JUri::base();
$backend     = 0;

if (substr($baseURL, -14) == 'administrator/')
{
	$baseURL = substr($baseURL, 0, -14);
	$backend = 1;
}

$redirectURL = urlencode($baseURL . 'index.php?option=com_loginguard&task=callback.callback&method=pushbullet');
$oauth2URL = "https://www.pushbullet.com/authorize?client_id={$this->clientId}&redirect_uri=$redirectURL&response_type=code&state=$backend"

?>
<div id="loginguard-pushbullet-controls">
    <div class="control-group">
        <div class="controls">
            <a class="btn btn-primary btn-large btn-lg" href="<?php echo $oauth2URL ?>">
                <span class="icon icon-link glyphicon glyphicon-lock"></span>
				<?php echo JText::_('PLG_LOGINGUARD_PUSHBULLET_LBL_OAUTH2BUTTON'); ?>
            </a>
        </div>
    </div>
</div>