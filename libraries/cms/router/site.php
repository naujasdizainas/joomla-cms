<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Router
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

jimport('joomla.application.router');

/**
 * Class to create and parse routes for the site application
 *
 * @package     Joomla.Libraries
 * @subpackage  Router
 * @since       1.5
 */
class JRouterSite extends JRouter
{
	protected $componentRouters = array();

	protected $routerMethod;

	protected $routerClass;

	public function getComponentRouter($component, $functionName = 'build')
	{
		if (isset($this->componentRouters[$component]))
		{
			if (is_object($this->componentRouters[$component]))
			{
				return array($this->componentRouters[$component], $functionName);
			}
			elseif (is_string($this->componentRouters[$component]))
			{
				return $this->componentRouters[$component] . $functionName . 'Route';
			}
			else
			{
				return 'JRouterDummyRouter';
			}
		}
		$compname = ucfirst(substr($component, 4));
		if (!class_exists($compname . 'Router'))
		{
			// Use the component routing handler if it exists
			$path = JPATH_SITE . '/components/' . $component . '/router.php';

			// Use the custom routing handler if it exists
			if (file_exists($path))
			{
				require_once $path;
				if (!class_exists($compname . 'Router'))
				{
					$this->componentRouters[$component] = $compname;
				}
			}
			else
			{
				$this->componentRouters[$component] = false;
			}
		}
		if (class_exists($compname . 'Router'))
		{
			$name = $compname . 'Router';
			$this->componentRouters[$component] = new $name();
		}
		if (is_object($this->componentRouters[$component]))
		{
			return array($this->componentRouters[$component], $functionName);
		}
		elseif (is_string($this->componentRouters[$component]))
		{
			return $this->componentRouters[$component] . $functionName . 'Route';
		}
		else
		{
			return 'JRouterDummyRouter';
		}
	}

	public function setComponentRouter($component, $router)
	{
		$this->componentRouters[$component] = $router;
	}
}

function JRouterDummyRouter(&$query)
{
	return array();
}
