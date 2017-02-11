<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

/** @var LoginGuardViewMethods $this */

JHtml::_('bootstrap.tooltip');

?>
<div id="loginguard-methods-list">
	<h3 id="loginguard-methods-list-head">
		<?php echo JText::_('COM_LOGINGUARD_HEAD_LIST_PAGE'); ?>
	</h3>
	<div id="loginguard-methods-list-instructions" class="alert alert-info">
		<span class="icon icon-info glyphicon glyphicon-info"></span>
		<?php echo JText::_('COM_LOGINGUARD_LBL_LIST_INSTRUCTIONS'); ?>
	</div>

	<div id="loginguard-methods-list-container">
		<?php foreach($this->methods as $method): ?>
		<div class="loginguard-methods-list-method loginguard-methods-list-method-name-<?php echo htmlentities($method['name'])?>">
			<img
				class="loginguard-methods-list-method-image" src="<?php echo JUri::root() . $method['image'] ?>"
			>
			<h4 class="loginguard-methods-list-method-title">
				<?php echo $method['display'] ?>
				<span class="hasTooltip icon icon-info-sign glyphicon glyphicon-info-sign"
				      title="<?php echo $this->escape($method['shortinfo']) ?>"></span>
			</h4>

			<div class="loginguard-methods-list-method-records-container">
				<?php if (count($method['active'])): ?>
					<div class="loginguard-methods-list-method-records">
						<?php foreach($method['active'] as $record): ?>
							<div class="loginguard-methods-list-method-record">
							<span class="loginguard-methods-list-method-record-title">
								<?php if ($record->default): ?>
                                    <span class="icon icon-star glyphicon glyphicon-star"></span>
								<?php endif; ?>
                                <?php echo $this->escape($record->title); ?>
							</span>
								<a href="<?php echo JRoute::_('index.php?option=com_loginguard&task=method.edit&id=' . (int) $record->id)?>"
								   class="btn btn-mini btn-xs btn-default loginguard-methods-list-method-record-edit"
								>
									<span class="icon icon-pencil glyphicon glyphicon-pencil"></span>
								</a>
								<?php if ($method['canDisable']): ?>
									<a href="<?php echo JRoute::_('index.php?option=com_loginguard&task=method.delete&method=' . (int) $record->id)?>"
									   class="btn btn-mini btn-xs btn-danger loginguard-methods-list-method-record-delete"
									>
										<span class="icon icon-trash glyphicon glyphicon-trash"></span>
									</a>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if (empty($method['active']) || $method['allowMultiple']): ?>
					<div class="loginguard-methods-list-method-addnew-container">
						<a href="<?php echo JRoute::_('index.php?option=com_loginguard&task=method.add&method=' . urlencode($method['name']))?>"
						   class="loginguard-methods-list-method-addnew"
						>
							<?php echo JText::sprintf('COM_LOGINGUARD_LBL_LIST_ADD_A', $method['display']) ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php endforeach; ?>
	</div>

</div>
