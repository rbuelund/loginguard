<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
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
		    <?php echo JText::_('COM_LOGINGUARD_HEAD_LIST_PAGE'); ?>
        </h3>
    </header>

    <div id="loginguard-methods-list-instructions">
        <p>
            <span class="icon icon-info glyphicon glyphicon-info-sign"></span>
			<?php echo JText::_('COM_LOGINGUARD_LBL_LIST_INSTRUCTIONS'); ?>
        </p>
    </div>

	<div id="loginguard-methods-reset-container" class="akeeba-panel--<?php echo $this->tfaActive ? 'success' : 'danger' ?>">
        <div class="akeeba-container--75-25">
            <div>
                <span id="loginguard-methods-reset-message">
                    <?php echo JText::sprintf('COM_LOGINGUARD_LBL_LIST_STATUS', JText::_('COM_LOGINGUARD_LBL_LIST_STATUS_' . ($this->tfaActive ? 'ON' : 'OFF'))) ?>
                </span>
            </div>
            <div class="loginguard-methods-reset-container-removeall-container">
	            <?php if ($this->tfaActive): ?>
                    <a href="<?php echo JRoute::_('index.php?option=com_loginguard&task=methods.disable&' . JFactory::getSession()->getToken() . '=1' . ($this->returnURL ? '&returnurl=' . $this->escape(urlencode($this->returnURL)) : '') . '&user_id=' . $this->user->id) ?>"
                       class="akeeba-btn--red">
			            <?php echo JText::_('COM_LOGINGUARD_LBL_LIST_REMOVEALL'); ?>
                    </a>
	            <?php endif; ?>
            </div>
        </div>
	</div>

</div>
<?php $this->setLayout('list'); echo $this->loadTemplate(); ?>
