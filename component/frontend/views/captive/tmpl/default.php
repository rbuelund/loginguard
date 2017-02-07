<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2017 Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Prevent direct access
defined('_JEXEC') or die;

// TODO Implement this view template
?>

<h1>Temporary Proof Of Concept</h1>

<form action="index.php" method="POST">
    <input type="hidden" name="option" value="com_loginguard">
    <input type="hidden" name="task" value="captive.validate">
    <input type="hidden" name="method" value="poc">

    <div class="form-group">
        <label for="loginGuardCode" class="hidden" aria-hidden="true">Secret Code</label>
        <input type="text" name="code" value="" placeholder="Secret Code" id="loginGuardCode" class="form-control input-large">
    </div>

    <input type="submit" value="Validate" class="btn btn-large btn-lg btn-primary">
</form>
