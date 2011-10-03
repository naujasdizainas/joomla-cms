<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_contact
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Contact Component Route Helper
 *
 * @static
 * @package     Joomla.Site
 * @subpackage  com_contact
 * @since       1.5
 */
abstract class ContactHelperRoute
{
	/**
	 * @param	int	The route of the newsfeed
	 */
	public static function getContactRoute($id, $catid)
	{
		// Create the link
		$link = 'index.php?option=com_contact&view=contact&id='. $id;
		if ($catid > 1)
		{
			$link .= '&catid='.$catid;
		}

		return $link;
	}

	public static function getCategoryRoute($catid)
	{
		// Create the link
		$link = 'index.php?option=com_contact&view=category&id='.$catid;

		return $link;
	}
}
