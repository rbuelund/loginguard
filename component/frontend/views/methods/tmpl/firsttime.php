<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

/** @var LoginGuardViewMethods $this */

?>
<div id="loginguard-methods-list">
	<?php if (!$this->isAdmin): ?>
	<h3 id="loginguard-methods-list-head">
		<?php echo JText::_('COM_LOGINGUARD_HEAD_FIRSTTIME_PAGE'); ?>
	</h3>
	<?php endif; ?>
	<div id="loginguard-methods-list-instructions">
		<p>
			<span class="icon icon-help glyphicon glyphicon-info-sign"></span>
			<?php echo JText::_('COM_LOGINGUARD_LBL_FIRSTTIME_INSTRUCTIONS'); ?>
		</p>
		<a href="<?php echo JRoute::_('index.php?option=com_loginguard&task=methods.dontshowthisagain' . ($this->returnURL ? '&returnurl=' . $this->escape(urlencode($this->returnURL)) : '') . '&user_id=' . $this->user->id . '&' . JFactory::getSession()->getToken() . '=1')?>"
		   class="btn btn-danger">
			<?php echo JText::_('COM_LOGINGUARD_LBL_FIRSTTIME_NOTINTERESTED'); ?>
		</a>
	</div>

	<?php $this->setLayout('list'); echo $this->loadTemplate(); ?>

</div>
