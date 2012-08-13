<?php
/**
 * @package    Joomla.Administrator
 *
 * @copyright  Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Utility class for the submenu.
 *
 * @package  Joomla.Administrator
 * @since    1.5
 */
abstract class JSubMenuHelper
{
	/**
	 * Method to add a menu item to submenu.
	 *
	 * @param	string	$name	Name of the menu item.
	 * @param	string	$link	URL of the menu item.
	 * @param	bool	True if the item is active, false otherwise.
	 *
	 * @since    1.5
	 */
	public static function addEntry($name, $link = '', $active = false)
	{
		$menu = JToolbar::getInstance('submenu');
		$menu->appendButton($name, $link, $active);
	}
}
