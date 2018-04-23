<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

/** @var  \Akeeba\LoginGuard\Site\View\Method\Html  $this */

JHtml::_('bootstrap.tooltip');

$cancelURL = JRoute::_('index.php?option=com_loginguard&task=methods.display&user_id=' . $this->user->id);

if (!empty($this->returnURL))
{
	$cancelURL = $this->escape(base64_decode($this->returnURL));
}

$token = $this->getContainer()->platform->getToken();

?>
<form action="<?php echo JUri::base() ?>index.php" method="post" id="loginguard-method-edit" class="akeeba-form--horizontal akeeba-panel--info">
    <header class="akeeba-block-header">
        <h3 id="loginguard-method-edit-head">
		<span>
            <?php echo JText::_($this->title) ?>
        </span>
        <?php if (!empty($this->renderOptions['help_url'])): ?>
            <span class="loginguard-method-edit-head-help">
                <a href="<?php echo $this->renderOptions['help_url'] ?>"
               class="akeeba-btn--dark--mini" target="_blank"
            >
                    <span class="akion-ios-information"></span>
                </a>
            </span>
        <?php endif;?>
        </h3>
    </header>

	<div class="akeeba-form-group">
		<label class="hasTooltip" for="loginguard-method-edit-title"
			title="<?php echo $this->escape(JText::_('COM_LOGINGUARD_LBL_EDIT_FIELD_TITLE_DESC')) ?>">
			<?php echo JText::_('COM_LOGINGUARD_LBL_EDIT_FIELD_TITLE'); ?>
		</label>
        <input type="text" id="loginguard-method-edit-title"
               name="title"
               value="<?php echo $this->escape($this->record->title) ?>"
               placeholder="<?php echo JText::_('COM_LOGINGUARD_LBL_EDIT_FIELD_TITLE_DESC') ?>">
	</div>

    <div class="akeeba-form-group--checkbox--pull-right">
        <label class="hasTooltip"
               title="<?php echo $this->escape(JText::_('COM_LOGINGUARD_LBL_EDIT_FIELD_DEFAULT_DESC')); ?>">
            <input type="checkbox" <?php echo $this->record->default ? 'checked="checked"' : ''; ?> name="default">
		    <?php echo JText::_('COM_LOGINGUARD_LBL_EDIT_FIELD_DEFAULT'); ?>
        </label>
    </div>

	<?php if (!empty($this->renderOptions['pre_message'])): ?>
	<div class="loginguard-method-edit-pre-message akeeba-block--info">
		<?php echo $this->renderOptions['pre_message'] ?>
	</div>
	<?php endif; ?>

	<?php if (!empty($this->renderOptions['tabular_data'])): ?>
	<div class="loginguard-method-edit-tabular-container">
		<?php if (!empty($this->renderOptions['table_heading'])): ?>
		<h5>
			<?php echo $this->renderOptions['table_heading'] ?>
		</h5>
		<?php endif; ?>
		<table class="akeeba-table--striped">
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

	<?php if ($this->renderOptions['field_type'] == 'custom'): ?>
	<?php echo $this->renderOptions['html']; ?>
	<?php else: ?>
	<div class="akeeba-form-group">
		<?php if ($this->renderOptions['label']): ?>
		<label class="hasTooltip" for="loginguard-method-edit-code">
			<?php echo $this->renderOptions['label']; ?>
		</label>
		<?php endif; ?>
        <input type="<?php echo $this->renderOptions['input_type']; ?>"
               id="loginguard-method-code"
               name="code"
               value="<?php echo $this->escape($this->renderOptions['input_value']) ?>"
               placeholder="<?php echo $this->escape($this->renderOptions['placeholder']) ?>">
	</div>
	<?php endif; ?>

	<div class="akeeba-form-group--pull-right">
		<div class="akeeba-form-group--actions">
			<?php if ($this->renderOptions['show_submit'] || $this->isEditExisting): ?>
			<button type="submit" class="akeeba-btn--primary"
				<?php echo $this->renderOptions['submit_onclick'] ? "onclick=\"{$this->renderOptions['submit_onclick']}\"" : '' ?>>
				<span class="akion-checkmark-circled"></span>
				<?php echo JText::_('COM_LOGINGUARD_LBL_EDIT_SUBMIT'); ?>
			</button>
			<?php endif; ?>

			<a href="<?php echo $cancelURL ?>"
			   class="akeeba-btn--small--red">
				<span class="akion-android-cancel"></span>
				<?php echo JText::_('COM_LOGINGUARD_LBL_EDIT_CANCEL'); ?>
			</a>
		</div>
	</div>

	<?php if (!empty($this->renderOptions['post_message'])): ?>
		<div class="loginguard-method-edit-post-message akeeba-panel--info">
			<?php echo $this->renderOptions['post_message'] ?>
		</div>
	<?php endif; ?>

    <div>
        <input type="hidden" name="option" value="com_loginguard">
        <input type="hidden" name="task" value="method.save">
        <input type="hidden" name="id" value="<?php echo (int) $this->record->id ?>">
        <input type="hidden" name="method" value="<?php echo $this->record->method ?>">
        <input type="hidden" name="user_id" value="<?php echo $this->user->id ?>">
        <input type="hidden" name="<?php echo $token ?>" value="1">
		<?php if (!empty($this->returnURL)): ?>
            <input type="hidden" name="returnurl" value="<?php echo $this->escape($this->returnURL) ?>">
		<?php endif; ?>

		<?php if (!empty($this->renderOptions['hidden_data'])): ?>
			<?php foreach ($this->renderOptions['hidden_data'] as $key => $value): ?>
                <input type="hidden" name="<?php echo $this->escape($key) ?>" value="<?php echo $this->escape($value) ?>">
			<?php endforeach; ?>
		<?php endif; ?>
    </div>
</form>
