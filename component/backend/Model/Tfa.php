<?php
/**
 * @package   AkeebaLoginGuard
 * @copyright Copyright (c)2016-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\LoginGuard\Admin\Model;

// Protect from unauthorized access
defined('_JEXEC') or die();

use FOF30\Container\Container;
use FOF30\Model\DataModel;

/**
 * @package     Akeeba\LoginGuard\Admin\Model
 *
 * @since       3.0.0
 *
 * @property int    $user_id
 * @property string $title
 * @property string $method
 * @property int    $default
 * @property string $created_on
 * @property string $last_user
 * @property array  $options
 *
 * @method   user_id($v)
 * @method   title($v)
 * @method   method($v)
 * @method   default($v)
 * @method   created_on($v)
 * @method   last_used($v)
 * @method   options($v)
 */
class Tfa extends DataModel
{
	public function __construct(Container $container, array $config = array())
	{
		$config['idFieldName'] = 'id';

		parent::__construct($container, $config);
	}


}