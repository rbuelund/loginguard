<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

/** @var \Akeeba\LoginGuard\Admin\View\Convert\Html $this */

?>
<div class="akeeba-panel--success">
    <header class="akeeba-block-header">
        <h2>
			<?php echo JText::_('COM_LOGINGUARD_CONVERT_DONE_HEAD'); ?>
        </h2>
    </header>
    <p>
		<?php echo JText::_('COM_LOGINGUARD_CONVERT_DONE_INFO'); ?>
    </p>
</div>
