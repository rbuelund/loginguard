<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\LoginGuard\Site\View\Methods\Html;

// Prevent direct access
defined('_JEXEC') or die;

/** @var Html $this */

?>
<div id="loginguard-methods-list" class="akeeba-panel--info">
    <header class="akeeba-block-header">
        <h3 id="loginguard-methods-list-head">
		    <?php echo JText::_('COM_LOGINGUARD_HEAD_FIRSTTIME_PAGE'); ?>
        </h3>
    </header>
	<div id="loginguard-methods-list-instructions">
		<p>
			<span class="icon icon-help glyphicon glyphicon-info-sign"></span>
			<?php echo JText::_('COM_LOGINGUARD_LBL_FIRSTTIME_INSTRUCTIONS'); ?>
		</p>
        <p>
            <a href="<?php echo JRoute::_('index.php?option=com_loginguard&task=methods.dontshowthisagain' . ($this->returnURL ? '&returnurl=' . $this->escape(urlencode($this->returnURL)) : '') . '&user_id=' . $this->user->id . '&' . $this->getContainer()->platform->getToken() . '=1')?>"
               class="akeeba-btn--red">
		        <?php echo JText::_('COM_LOGINGUARD_LBL_FIRSTTIME_NOTINTERESTED'); ?>
            </a>
        </p>
	</div>
</div>

<?php $this->setLayout('list'); echo $this->loadTemplate(); ?>
