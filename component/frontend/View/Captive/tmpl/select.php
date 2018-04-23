<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

/** @var \Akeeba\LoginGuard\Site\View\Captive\Html $this */

$shownMethods = array();

?>
<div id="loginguard-select" class="akeeba-panel--info">
    <header class="akeeba-block-header">
        <h3 id="loginguard-select-heading">
		    <?php echo JText::_('COM_LOGINGUARD_HEAD_SELECT_PAGE'); ?>
        </h3>
    </header>

    <div id="loginguard-select-information" class="akeeba-block--info">
        <p>
	        <?php echo JText::_('COM_LOGINGUARD_LBL_SELECT_INSTRUCTIONS'); ?>
        </p>
    </div>

	<?php foreach ($this->records as $record):
    if (!array_key_exists($record->method, $this->tfaMethods) && ($record->method != 'backupcodes')) continue;
    $allowEntryBatching = isset($this->tfaMethods[$record->method]) ? $this->tfaMethods[$record->method]['allowEntryBatching'] : false;

    if ($this->allowEntryBatching)
    {
	    if ($allowEntryBatching && in_array($record->method, $shownMethods)) continue;
	    $shownMethods[] = $record->method;
    }

        /** @var \Akeeba\LoginGuard\Site\Model\Captive $model */
		$model      = $this->getModel();
		$methodName = $model->translateMethodName($record->method);
    ?>
    <a href="<?php echo JRoute::_('index.php?option=com_loginguard&view=captive&record_id=' . $record->id)?>" class="loginguard-method">
        <img src="<?php echo JUri::root() . $model->getMethodImage($record->method) ?>" class="loginguard-method-image" />
        <?php if (!$this->allowEntryBatching || !$allowEntryBatching): ?>
        <span class="loginguard-method-title">
            <?php echo $record->title; ?>
        </span>
        <span class="loginguard-method-name">
            <?php echo $methodName ?>
        </span>
        <?php else: ?>
            <span class="loginguard-method-title">
            <?php echo $methodName ?>
        </span>
            <span class="loginguard-method-name">
            <?php echo $methodName ?>
        </span>
        <?php endif; ?>
    </a>
	<?php endforeach; ?>
</div>
