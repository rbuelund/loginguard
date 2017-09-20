<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

/** @var LoginGuardViewConvert $this */

?>
<h1><?php echo JText::_('COM_LOGINGUARD_HEAD_CONVERT'); ?></h1>

<div class="alert alert-info">
    <span class="icon icon-info glyphicon glyphicon-info-sign"></span>
	<?php echo JText::_('COM_LOGINGUARD_CONVERT_INFO'); ?>
</div>

<p>
	<?php echo JText::_('COM_LOGINGUARD_CONVERT_MOREINFO'); ?>
</p>

<form action="index.php" name="adminForm" id="adminForm" method="get">
    <input type="hidden" name="option" value="com_loginguard"/>
    <input type="hidden" name="task" value="convert.convert"/>
    <input type="hidden" name="<?php echo JFactory::getSession()->getToken() ?>" value="1"/>
    <input type="submit" class="btn btn-default" value="<?php echo $this->escape(JText::_('COM_LOGINGUARD_CONVERT_BUTTON'))?>">
</form>
