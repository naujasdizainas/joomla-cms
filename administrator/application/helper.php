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
 * Joomla! Administrator Application helper class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Application
 * @since       3.0
 */
class AdministratorApplicationHelper
{
	/**
	 * Return the application option string [main component].
	 *
	 * @return  string  The option string
	 *
	 * @since   3.0
	 */
	public static function findOption()
	{
		// Retrieve the JInput object
		$input = JFactory::getApplication()->input;

		$option = strtolower($input->getCmd('option', null));

		$user = JFactory::getUser();
		if (($user->get('guest')) || !$user->authorise('core.login.admin'))
		{
			$option = 'com_login';
		}

		if (empty($option))
		{
			$option = 'com_cpanel';
		}

		$input->set('option', $option);
		return $option;
	}

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
	 */
	public static function addSubmenuItem($name, $link = '', $active = false)
	{
		$menu = JToolBar::getInstance('submenu');
		$menu->appendButton($name, $link, $active);
	}
}
