<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

/** @var \Akeeba\LoginGuard\Site\View\Captive\Html $this */

?>
<div class="loginguard-captive">
	<?php if (!empty($this->renderOptions['help_url'])): ?>
        <span class="pull-right">
        <a href="<?php echo $this->renderOptions['help_url'] ?>"
           class="btn btn-sm btn-small btn-default btn-inverse"
           target="_blank"
        >
            <span class="icon icon-question-sign glyphicon glyphicon-question-sign"></span>
        </a>
        </span>
	<?php endif;?>
    <h3 id="loginguard-title">
	    <?php if (!empty($this->title)): ?>
	    <?php echo $this->title ?> <small> &ndash;
	    <?php endif; ?>
        <?php if (!$this->allowEntryBatching): ?>
        <?php echo $this->escape($this->record->title) ?>
        <?php else: ?>
        <?php echo $this->escape($this->getModel()->translateMethodName($this->record->method)) ?>
        <?php endif; ?>
	    <?php if (!empty($this->title)): ?>
        </small>
        <?php endif; ?>
    </h3>


	<?php if ($this->renderOptions['pre_message']): ?>
        <div class="loginguard-captive-pre-message">
			<?php echo $this->renderOptions['pre_message'] ?>
        </div>
	<?php endif; ?>

    <form action="<?php echo JUri::base() ?>index.php" method="POST" id="loginguard-captive-form">
        <input type="hidden" name="option" value="com_loginguard">
        <input type="hidden" name="task" value="captive.validate">
        <input type="hidden" name="record_id" value="<?php echo $this->record->id ?>">
        <input type="hidden" name="<?php echo JSession::getFormToken() ?>" value="1">

	    <div id="loginguard-captive-form-method-fields">
		    <?php if ($this->renderOptions['field_type'] == 'custom'): ?>
			    <?php echo $this->renderOptions['html']; ?>
		    <?php else:
                $js = <<< JS
; // Fix broken third party Javascript...
window.addEventListener("DOMContentLoaded", function() {
    document.getElementById('loginGuardCode').focus();
});

JS;
		        $this->addJavascriptInline($js);

            ?>
                <div class="form-group">
                    <label for="loginGuardCode" <?php echo $this->renderOptions['label'] ? '' : 'class="hidden" aria-hidden="true"'?>>
					    <?php echo $this->renderOptions['label'] ?>
                    </label>
                    <input type="<?php echo $this->renderOptions['input_type'] ?>"
                           name="code"
                           value=""
					    <?php if (!empty($this->renderOptions['placeholder'])): ?>
                            placeholder="<?php echo $this->renderOptions['placeholder'] ?>"
					    <?php endif; ?>
                           id="loginGuardCode"
                           class="form-control input-large"
                    >
                </div>
		    <?php endif;?>
        </div>

        <div id="loginguard-captive-form-standard-buttons">
            <button type="submit" class="btn btn-large btn-lg btn-primary" id="loginguard-captive-button-submit"
                    style="<?php echo $this->renderOptions['hide_submit'] ? 'display: none' : '' ?>">
                <span class="icon icon-rightarrow"></span>
                <span class="glyphicon glyphicon-ok"></span>
		        <?php echo JText::_('COM_LOGINGUARD_LBL_VALIDATE'); ?>
            </button>

	        <?php if ($this->isAdmin): ?>
                <a href="<?php echo JRoute::_('index.php?option=com_login&task=logout&' . JSession::getFormToken() . '=1') ?>"
                   class="btn btn-danger" id="loginguard-captive-button-logout">
                    <span class="icon icon-lock"></span>
                    <span class="glyphicon glyphicon-off"></span>
			        <?php echo JText::_('COM_LOGINGUARD_LBL_LOGOUT'); ?>
                </a>
	        <?php else: ?>
                <a href="<?php echo JRoute::_('index.php?option=com_users&task=user.logout&' . JSession::getFormToken() . '=1') ?>"
                   class="btn btn-danger" id="loginguard-captive-button-logout">
                    <span class="icon icon-lock"></span>
                    <span class="glyphicon glyphicon-off"></span>
			        <?php echo JText::_('COM_LOGINGUARD_LBL_LOGOUT'); ?>
                </a>
	        <?php endif; ?>
        </div>

        <?php if (count($this->records) > 1): ?>
        <div id="loginguard-captive-form-choose-another">
            <a href="<?php echo JRoute::_('index.php?option=com_loginguard&view=captive&task=select') ?>">
                <?php echo JText::_('COM_LOGINGUARD_LBL_USEDIFFERENTMETHOD'); ?>
            </a>
        </div>
        <?php endif; ?>
    </form>

	<?php if ($this->renderOptions['post_message']): ?>
        <div class="loginguard-captive-post-message">
			<?php echo $this->renderOptions['post_message'] ?>
        </div>
	<?php endif; ?>

</div>
