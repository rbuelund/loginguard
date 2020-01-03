<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

// Prevent direct access
defined('_JEXEC') or die;

/** @var  \Akeeba\LoginGuard\Site\View\Method\Html $this */

HTMLHelper::_('bootstrap.tooltip');

$cancelURL = Route::_('index.php?option=com_loginguard&task=methods.display&user_id=' . $this->user->id);

if (!empty($this->returnURL))
{
	$cancelURL = $this->escape(base64_decode($this->returnURL));
}

if ($this->record->method != 'backupcodes')
{
	throw new RuntimeException(JText::_('JERROR_ALERTNOAUTHOR'), 403);
}

$token = $this->getContainer()->platform->getToken();

?>
<h3>
    <?php echo JText::_('COM_LOGINGUARD_LBL_BACKUPCODES') ?>
</h3>

<div class="akeeba-block--info">
	<?php echo JText::_('COM_LOGINGUARD_LBL_BACKUPCODES_INSTRUCTIONS') ?>
</div>

<table class="akeeba-table--striped">
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

<div class="akeeba-panel--info">
    <p>
		<?php echo JText::_('COM_LOGINGUARD_LBL_BACKUPCODES_RESET_INFO'); ?>
    </p>

    <p>
        <a class="akeeba-btn--red" href="<?php echo Route::_('index.php?option=com_loginguard&task=method.regenbackupcodes&user_id=' . $this->user->id . (empty($this->returnURL) ? '' : '&returnurl=' . $this->returnURL . '&' . $token . '=1')) ?>">
            <span class="akion-refresh"></span>
		    <?php echo JText::_('COM_LOGINGUARD_LBL_BACKUPCODES_RESET'); ?>
        </a>

        <a href="<?php echo $cancelURL ?>"
           class="akeeba-btn--default">
            <span class="akion-android-cancel"></span>
		    <?php echo JText::_('COM_LOGINGUARD_LBL_EDIT_CANCEL'); ?>
        </a>

    </p>
</div>
