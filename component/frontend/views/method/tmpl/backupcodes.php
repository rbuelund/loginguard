<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

/** @var  LoginGuardViewMethod $this */

JHtml::_('bootstrap.tooltip');

$cancelURL = JRoute::_('index.php?option=com_loginguard&task=methods.display&user_id=' . $this->user->id);

if (!empty($this->returnURL))
{
	$cancelURL = $this->escape(base64_decode($this->returnURL));
}

if ($this->record->method != 'backupcodes')
{
	throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
}

?>
<h3>
    <?php echo JText::_('COM_LOGINGUARD_LBL_BACKUPCODES') ?>
</h3>

<div class="alert alert-info">
	<?php echo JText::_('COM_LOGINGUARD_LBL_BACKUPCODES_INSTRUCTIONS') ?>
</div>

<table class="table table-striped">
	<?php for ($i = 0; $i < (count($this->backupCodes) / 2); $i++): ?>
        <tr>
            <td>
	            <?php if (!empty($this->backupCodes[2 * $i])): ?>
                &#128273;
                <?php echo $this->backupCodes[2 * $i] ?>
                <?php endif; ?>
            </td>
            <td>
                <?php if (!empty($this->backupCodes[1 + 2 * $i])): ?>
                &#128273;
                <?php echo $this->backupCodes[1 + 2 * $i] ?>
                <?php endif ;?>
            </td>
        </tr>
	<?php endfor; ?>
</table>

<p>
    <?php echo JText::_('COM_LOGINGUARD_LBL_BACKUPCODES_RESET_INFO'); ?>
</p>

<a class="btn btn-danger" href="<?php echo JRoute::_('index.php?option=com_loginguard&task=method.regenbackupcodes&user_id=' . $this->user->id . (empty($this->returnURL ? '' : '&returnurl=' . $this->returnURL))) ?>">
	<?php echo JText::_('COM_LOGINGUARD_LBL_BACKUPCODES_RESET'); ?>
</a>

<a href="<?php echo $cancelURL ?>"
   class="btn btn-default">
    <span class="icon icon-cancel-2 glyphicon glyphicon-cancel-2"></span>
	<?php echo JText::_('COM_LOGINGUARD_LBL_EDIT_CANCEL'); ?>
</a>