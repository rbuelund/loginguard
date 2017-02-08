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
<div class="loginguard-captive">
    <?php if (!empty($this->title)): ?>
    <h3 id="loginguard-title">
        <?php echo $this->title ?>
    </h3>
    <?php endif; ?>

	<?php if ($this->renderOptions['pre_message']): ?>
        <div class="loginguard-captive-pre-message">
			<?php echo $this->renderOptions['pre_message'] ?>
        </div>
	<?php endif; ?>

    <form action="index.php" method="POST" id="loginguard-captive-form">
        <input type="hidden" name="option" value="com_loginguard">
        <input type="hidden" name="task" value="captive.validate">
        <input type="hidden" name="record_id" value="<?php echo $this->record->id ?>">

	    <?php if ($this->renderOptions['field_type'] == 'custom'): ?>
            <?php echo $this->renderOptions['html']; ?>
	    <?php else: ?>
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

        <button type="submit" class="btn btn-large btn-lg btn-primary">
            <span class="icon icon-rightarrow"></span>
            <span class="glyphicon glyphicon-ok"></span>
	        <?php echo JText::_('COM_LOGINGUARD_LBL_VALIDATE'); ?>
        </button>

        <?php if ($this->isAdmin): ?>
            <a href="<?php echo JRoute::_('index.php?option=com_login&task=logout&' . JSession::getFormToken() . '=1') ?>"
               class="btn btn-danger">
                <span class="icon icon-lock"></span>
                <span class="glyphicon glyphicon-off"></span>
		        <?php echo JText::_('COM_LOGINGUARD_LBL_LOGOUT'); ?>
            </a>
        <?php else: ?>
            <a href="<?php echo JRoute::_('index.php?option=com_users&task=user.logout&' . JSession::getFormToken() . '=1') ?>"
               class="btn btn-danger">
                <span class="icon icon-lock"></span>
                <span class="glyphicon glyphicon-off"></span>
		        <?php echo JText::_('COM_LOGINGUARD_LBL_LOGOUT'); ?>
            </a>
        <?php endif; ?>

        <?php if (count($this->records) > 1): ?>
        <br/>
        <a href="<?php echo JRoute::_('index.php?option=com_loginguard&view=captive') ?>">
            <?php echo JText::_('COM_LOGINGUARD_LBL_USEDIFFERENTMETHOD'); ?>
        </a>
        <?php endif; ?>
    </form>

	<?php if ($this->renderOptions['post_message']): ?>
        <div class="loginguard-captive-post-message">
			<?php echo $this->renderOptions['post_message'] ?>
        </div>
	<?php endif; ?>

</div>