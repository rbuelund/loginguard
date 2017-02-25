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
		You have not yet enabled any Akeeba LoginGuard plugins.
	</span>
</h2>
<p>
	Akeeba LoginGuard plugins implement the Second Step Verification methods. Without any available plugins Akeeba LoginGuard will not work at all.

	Please go to Extensions, Plugins and activate some plugins in the <code>loginguard</code> folder.
</p>
<?php endif; ?>

<?php if ($this->noMethods): ?>
<h2>
	<span class="icon icon-warning-2"></span>
	<span>
		You have not installed any Akeeba LoginGuard plugins.
	</span>
</h2>
<p>
	Akeeba LoginGuard plugins implement the Second Step Verification methods. Without any available plugins Akeeba LoginGuard will not work at all.

	Please install some plugins or try installing Akeeba LoginGuard's ZIP package again, twice in a row, without uninstalling it before or in between.
</p>
<?php endif; ?>

<?php if ($this->noGeoIP): ?>
<h2>
	<span class="icon icon-connection"></span>
	You have not installed the GeoIP plugin
</h2>
<p>
	Akeeba LoginGuard can display the country and/or city where each authentication method was last used. For that to work you need to install and enable our free of charge System - Akeeba GeoIP provider plugin.
	<br />
	<a href="https://www.akeebabackup.com/download/akgeoip.html" target="_blank"
	   class="btn btn-primary">
		<span class="icon icon-download"></span>
		Download the plugin
	</a>
</p>
<?php elseif ($this->geoIPNeedsUpdate): ?>
<h2>
	<span class="icon icon-refresh"></span>
	Update the GeoIP database
</h2>
<p>
	Akeeba LoginGuard can display the country and/or city where each authentication method was last used. That's done using the MaxMind GeoLite2 Country database. You are advised to update it at least once per month. On most servers you can perform the update by clicking the button below. If that doesn't work on your server, please consult our documentation.
	<br />
	<a href="<?php echo JRoute::_('index.php?option=com_loginguard&task=welcome.updategeoip&' . JFactory::getSession()->getToken() . '=1')?>"
	   class="btn btn-primary">
		<span class="icon icon-download"></span>
		Update the GeoIP database
	</a>
</p>
<?php elseif ($this->geoIPNeedsUpgrade): ?>
	<h2>
		<span class="icon icon-refresh"></span>
		Update the GeoIP database
	</h2>
	<p>
		Akeeba LoginGuard can currently only display the country where each authentication method was last used. You need to upgrade to the bigger, more detailed, GeoIP database to display city information. Click the button below to upgrade the database. If that doesn't work on your server please consult our documentation.
		<br />
		<a href="<?php echo JRoute::_('index.php?option=com_loginguard&task=welcome.upgradegeoip&' . JFactory::getSession()->getToken() . '=1')?>"
		   class="btn btn-primary">
			<span class="icon icon-download"></span>
			Upgrade the GeoIP database
		</a>
	</p>
<?php endif; ?>
