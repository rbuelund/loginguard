<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

/** @var  LoginGuardViewMethod  $this */

JHtml::_('bootstrap.tooltip');

?>
<form action="index.php" method="post" id="loginguard-method-edit">
	<input type="hidden" name="option" value="com_loginguard">
	<input type="hidden" name="task" value="method.save">
	<input type="hidden" name="id" value="<?php echo $this->record->id ?>">
	<input type="hidden" name="method" value="<?php echo $this->record->method ?>">
	<input type="hidden" name="<?php echo JSession::getFormToken() ?>" value="1">

	<?php if (!empty($this->renderOptions['hidden_data'])): ?>
	<?php foreach ($this->renderOptions['hidden_data'] as $key => $value): ?>
	<input type="hidden" name="<?php echo $this->escape($key) ?>" value="<?php echo $this->escape($value) ?>">
	<?php endforeach; ?>
	<?php endif; ?>

	<?php if (!empty($this->title)): ?>
	<h3 id="loginguard-method-edit-head">
		<?php echo JText::_($this->title) ?>
	</h3>
	<?php endif; ?>

	<div class="control-group form-group">
		<label class="control-label hasTooltip" for="loginguard-method-edit-title"
			title="<?php echo $this->escape(JText::_('COM_LOGINGUARD_LBL_EDIT_FIELD_TITLE_DESC')) ?>">
			<?php echo JText::_('COM_LOGINGUARD_LBL_EDIT_FIELD_TITLE'); ?>
		</label>
		<div class="controls">
			<input type="text" class="form-control" id="loginguard-method-edit-title"
			       name="title"
			       value="<?php echo $this->escape($this->record->title) ?>"
			       placeholder="<?php echo JText::_('COM_LOGINGUARD_LBL_EDIT_FIELD_TITLE_DESC') ?>">
		</div>
	</div>

    <div class="control-group form-group">
        <div class="controls">
            <label class="control-label hasTooltip"
            title="<?php echo $this->escape(JText::_('COM_LOGINGUARD_LBL_EDIT_FIELD_DEFAULT_DESC')); ?>">
                <input type="checkbox" <?php echo $this->record->default ? 'checked="checked"' : ''; ?> name="default">
				<?php echo JText::_('COM_LOGINGUARD_LBL_EDIT_FIELD_DEFAULT'); ?>
            </label>
        </div>
    </div>

	<?php if (!empty($this->renderOptions['pre_message'])): ?>
	<div class="loginguard-method-edit-pre-message">
		<?php echo $this->renderOptions['pre_message'] ?>
	</div>
	<?php endif; ?>

	<?php if (!empty($this->renderOptions['tabular_data'])): ?>
	<div class="loginguard-method-edit-tabular-container">
		<?php if (!empty($this->renderOptions['table_heading'])): ?>
		<h4>
			<?php echo $this->renderOptions['table_heading'] ?>
		</h4>
		<?php endif; ?>
		<table class="table table-striped">
			<tbody>
			<?php foreach ($this->renderOptions['tabular_data'] as $cell1 => $cell2): ?>
			<tr>
				<td>
					<?php echo $cell1 ?>
				</td>
				<td>
					<?php echo $cell2 ?>
				</td>
			</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

	<?php if ($this->renderOptions['field_type'] == 'html'): ?>
	<?php echo $this->renderOptions['html']; ?>
	<?php else: ?>
	<div class="control-group form-group">
		<?php if ($this->renderOptions['label']): ?>
		<label class="control-label hasTooltip" for="loginguard-method-edit-code">
			<?php echo $this->renderOptions['label']; ?>
		</label>
		<?php endif; ?>
		<div class="controls">
			<input type="<?php echo $this->renderOptions['input_type']; ?>"
			       class="form-control" id="loginguard-method-edit-title"
			       name="code"
			       value="<?php echo $this->escape($this->renderOptions['input_value']) ?>"
			       placeholder="<?php echo $this->escape($this->renderOptions['placeholder']) ?>">
		</div>
	</div>
	<?php endif; ?>

	<div class="control-group">
		<div class="controls">
			<?php if ($this->renderOptions['show_submit'] || empty($this->record->id)): ?>
			<button type="submit" class="btn btn-primary"
				<?php echo $this->renderOptions['submit_onclick'] ? "onclick=\"{$this->renderOptions['submit_onclick']}\"" : '' ?>>
				<span class="icon icon-ok-circle glyphicon glyphicon-ok-circle"></span>
				<?php echo JText::_('COM_LOGINGUARD_LBL_EDIT_SUBMIT'); ?>
			</button>
			<?php endif; ?>

			<a href="<?php echo JRoute::_('index.php?option=com_loginguard&view=methods.display') ?>"
			   class="btn btn-small btn-sm btn-default">
				<span class="icon icon-cancel-2 glyphicon glyphicon-cancel-2"></span>
				<?php echo JText::_('COM_LOGINGUARD_LBL_EDIT_CANCEL'); ?>
			</a>
		</div>
	</div>

	<?php if (!empty($this->renderOptions['post_message'])): ?>
		<div class="loginguard-method-edit-post-message">
			<?php echo $this->renderOptions['post_message'] ?>
		</div>
	<?php endif; ?>
</form>