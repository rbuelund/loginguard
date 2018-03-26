<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

/** @var \Akeeba\LoginGuard\Site\View\Captive\Html $this */

?>
<div class="loginguard-captive akeeba-panel--info">
    <header class="akeeba-block-header">
        <h3 id="loginguard-title">
            <span>
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
            </span>
            <span class="loginguard-captive-head-help">
                <?php if (!empty($this->renderOptions['help_url'])): ?>
                <a href="<?php echo $this->renderOptions['help_url'] ?>"
                   class="akeeba-btn--dark--mini" target="_blank"
                >
                    <span class="akion-ios-information"></span>
                </a>
                <?php endif;?>
            </span>
        </h3>
    </header>

	<?php if ($this->renderOptions['pre_message']): ?>
        <div class="loginguard-captive-pre-message akeeba-block--info">
			<?php echo $this->renderOptions['pre_message'] ?>
        </div>
	<?php endif; ?>

    <form action="<?php echo JUri::base() ?>index.php" method="POST" id="loginguard-captive-form" class="akeeba-form--horizontal">
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
                <div class="akeeba-form-group">
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

        <div id="loginguard-captive-form-standard-buttons" class="akeeba-form-group--pull-right">
            <div class="akeeba-form-group--actions">
                <button type="submit" class="akeeba-btn--large--primary" id="loginguard-captive-button-submit"
                        style="<?php echo $this->renderOptions['hide_submit'] ? 'display: none' : '' ?>">
                    <span class="akion-chevron-right"></span>
		            <?php echo JText::_('COM_LOGINGUARD_LBL_VALIDATE'); ?>
                </button>

	            <?php if ($this->isAdmin): ?>
                <a href="<?php echo JRoute::_('index.php?option=com_login&task=logout&' . $this->getContainer()->platform->getToken() . '=1') ?>"
                   class="akeeba-btn--red" id="loginguard-captive-button-logout">
                    <span class="akion-power"></span>
                    <?php echo JText::_('COM_LOGINGUARD_LBL_LOGOUT'); ?>
                </a>
	            <?php else: ?>
                <a href="<?php echo JRoute::_('index.php?option=com_users&task=user.logout&' . $this->getContainer()->platform->getToken() . '=1') ?>"
                   class="akeeba-btn--red" id="loginguard-captive-button-logout">
                    <span class="akion-ios-locked"></span>
                    <?php echo JText::_('COM_LOGINGUARD_LBL_LOGOUT'); ?>
                </a>
	            <?php endif; ?>
            </div>
        </div>

	    <?php if (count($this->records) > 1): ?>
            <div id="loginguard-captive-form-choose-another" class="akeeba-form-group--pull-right">
                <a href="<?php echo JRoute::_('index.php?option=com_loginguard&view=captive&task=select') ?>">
				    <?php echo JText::_('COM_LOGINGUARD_LBL_USEDIFFERENTMETHOD'); ?>
                </a>
            </div>
	    <?php endif; ?>


        <?php if (!empty($this->renderOptions['post_message'])): ?>
            <div class="loginguard-method-edit-post-message akeeba-panel--info">
			    <?php echo $this->renderOptions['post_message'] ?>
            </div>
	    <?php endif; ?>

        <div>
            <input type="hidden" name="option" value="com_loginguard">
            <input type="hidden" name="task" value="captive.validate">
            <input type="hidden" name="record_id" value="<?php echo $this->record->id ?>">
            <input type="hidden" name="<?php echo $this->getContainer()->platform->getToken() ?>" value="1">
        </div>
    </form>

</div>
