<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_newsfeeds
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Newsfeeds Component Route Helper
 *
 * @package     Joomla.Site
 * @subpackage  com_newsfeeds
 * @since       1.5
 */
abstract class NewsfeedsHelperRoute
{
	/**
	 * @param	int	The route of the newsfeed
	 */
	public static function getNewsfeedRoute($id, $catid)
	{
		//Create the link
		$link = array('option' => 'com_newsfeeds', 'view' => 'newsfeed', 'id' => $id);

		if ((int)$catid > 1) {
			$link['catid'] = $catid;
		}

		return $link;
	}

	public static function getCategoryRoute($catid)
	{
		//Create the link
		$link = array('option' => 'com_newsfeeds', 'view' => 'category', 'id' => $catid);

		return $link;
	}
}
