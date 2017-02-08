<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

/** @var LoginGuardViewCaptive $this */

?>
<div id="loginguard-select">
	<?php foreach ($this->records as $record):?>
	<div class="loginguard-method">
		<a href="<?php echo JRoute::_('index.php?option=com_loginguard&view=captive&record_id=' . $record->id)?>">
			<img src="<?php echo JUri::base() . $this->getModel()->getMethodImage($record->method) ?>" class="loginguard-method-image" />
			<span class="loginguard-method-title">
				<?php echo $this->escape($record->title) ?>
			</span>
			<span class="loginguard-method-name">
				<?php echo $this->getModel()->translateMethodName($record->method) ?>
			</span>
		</a>
	</div>
	<?php endforeach; ?>
</div>