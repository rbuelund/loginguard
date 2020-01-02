<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\LoginGuard\Admin\View\Convert;

// Protect from unauthorized access
use FOF30\View\DataView\Html as BaseView;

defined('_JEXEC') or die();

class Html extends BaseView
{
	protected function onBeforeConvert()
	{
		if ($this->getLayout() != 'done')
		{
			$js = <<< JS
;; // Defense against broken scripts
window.jQuery(document).ready(function (){
	document.forms.adminForm.submit();
});

JS;

			$this->addJavascriptInline($js);
		}
	}
}
