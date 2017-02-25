<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

?>

<?php if ($this->noMethods || $this->notInstalled): ?>
<div class="alert alert-error">
	<span class="label label-important"><?php echo JText::_('COM_LOGINGUARD_STATUS_NOTREADY'); ?></span>
	<?php echo JText::_('COM_LOGINGUARD_STATUS_NOTREADY_INFO'); ?>
</div>
<?php elseif ($this->noGeoIP): ?>
<div class="alert alert-warning">
	<span class="label label-warning"><?php echo JText::_('COM_LOGINGUARD_STATUS_ALMOSTREADY'); ?></span>
	<?php echo JText::_('COM_LOGINGUARD_STATUS_ALMOSTREADY_INFO'); ?>
</div>
<?php else: ?>
<div class="alert alert-success">
	<span class="label label-success"><?php echo JText::_('COM_LOGINGUARD_STATUS_READY'); ?></span>
	<?php echo JText::_('COM_LOGINGUARD_STATUS_READY_INFO'); ?>
</div>
<?php endif; ?>

<?php if ($this->notInstalled): ?>
<h2>
	<span class="icon icon-power-cord"></span>
	<span>
        <?php echo JText::_('COM_LOGINGUARD_ERR_NOPLUGINS_HEAD'); ?>
	</span>
</h2>
<p>
	<?php echo JText::_('COM_LOGINGUARD_ERR_PLUGINS_INFO_COMMON'); ?>
	<?php echo JText::_('COM_LOGINGUARD_ERR_NOPLUGINS_INFO'); ?>
</p>
<?php endif; ?>

<?php if ($this->noMethods): ?>
<h2>
	<span class="icon icon-warning-2"></span>
	<span>
		<?php echo JText::_('COM_LOGINGUARD_ERR_NOTINSTALLEDPLUGINS_HEAD'); ?>
	</span>
</h2>
<p>
	<?php echo JText::_('COM_LOGINGUARD_ERR_PLUGINS_INFO_COMMON'); ?>
	<?php echo JText::_('COM_LOGINGUARD_ERR_NOTINSTALLEDPLUGINS_INFO'); ?>
</p>
<?php endif; ?>

<?php if ($this->noGeoIP): ?>
<h2>
	<span class="icon icon-connection"></span>
	<?php echo JText::_('COM_LOGINGUARD_ERR_GEOIP_NOTINSTALLED_HEAD'); ?>
</h2>
<p>
	<?php echo JText::_('COM_LOGINGUARD_ERR_GEOIP_NOTINSTALLED_BODY'); ?>
	<br />
	<a href="https://www.akeebabackup.com/download/akgeoip.html" target="_blank"
	   class="btn btn-primary">
		<span class="icon icon-download"></span>
		<?php echo JText::_('COM_LOGINGUARD_ERR_GEOIP_NOTINSTALLED_BUTTON'); ?>
	</a>
</p>
<?php elseif ($this->geoIPNeedsUpdate): ?>
<h2>
	<span class="icon icon-refresh"></span>
	<?php echo JText::_('COM_LOGINGUARD_LBL_GEOIP_UPDATE_HEAD'); ?>
</h2>
<p>
	<?php echo JText::_('COM_LOGINGUARD_LBL_GEOIP_UPDATE_BODY'); ?>
	<br />
	<a href="<?php echo JRoute::_('index.php?option=com_loginguard&task=welcome.updategeoip&' . JFactory::getSession()->getToken() . '=1')?>"
	   class="btn btn-primary">
		<span class="icon icon-download"></span>
		<?php echo JText::_('COM_LOGINGUARD_LBL_GEOIP_UPDATE_BUTTON'); ?>
	</a>
</p>
<?php elseif ($this->geoIPNeedsUpgrade): ?>
	<h2>
		<span class="icon icon-refresh"></span>
		<?php echo JText::_('COM_LOGINGUARD_LBL_GEOIP_UPGRADE_HEAD'); ?>
	</h2>
	<p>
		<?php echo JText::_('COM_LOGINGUARD_LBL_GEOIP_UPGRADE_BODY'); ?>
		<br />
		<a href="<?php echo JRoute::_('index.php?option=com_loginguard&task=welcome.upgradegeoip&' . JFactory::getSession()->getToken() . '=1')?>"
		   class="btn btn-primary">
			<span class="icon icon-download"></span>
			<?php echo JText::_('COM_LOGINGUARD_LBL_GEOIP_UPGRADE_BUTTON'); ?>
		</a>
	</p>
<?php endif; ?>
