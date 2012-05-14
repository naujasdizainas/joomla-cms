<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Application
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

/**
 * Utility class for the submenu.
 *
 * @package     Joomla.Administrator
 * @subpackage  Application
 * @since       3.0
 * @deprecated  4.0
 */
abstract class JSubMenuHelper
{
	/**
	 * Method to add a menu item to submenu.
	 *
	 * @param   string   $name    Name of the menu item.
	 * @param   string   $link    URL of the menu item.
	 * @param   boolean  $active  True if the item is active, false otherwise.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 * @deprecated  4.0  Use AdministratorApplicationHelper::addSubmenuItem() instead.
	 */
	public static function addEntry($name, $link = '', $active = false)
	{
		AdministratorApplicationHelper::addSubmenuItem($name, $link, $active);
	}
}
