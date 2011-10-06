<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_weblinks
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Weblinks Component Route Helper
 *
 * @static
 * @package     Joomla.Site
 * @subpackage  com_weblinks
 * @since       1.5
 */
abstract class WeblinksHelperRoute
{
	/**
	 * @param	int	The route of the weblink
	 */
	public static function getWeblinkRoute($id, $catid)
	{
		//Create the link
		$link = array('option' => 'com_weblinks', 'view' => 'weblink', 'id' => $id);
		if ($catid > 1) {
			$link['catid'] = $catid;
		}

		return $link;
	}

	/**
	 * @param	int		$id		The id of the weblink.
	 * @param	string	$return	The return page variable.
	 */
	public static function getFormRoute($id, $return = null)
	{
		// Create the link.
		if ($id) {
			$link = 'index.php?option=com_weblinks&task=weblink.edit&w_id='. $id;
		}
		else {
			$link = 'index.php?option=com_weblinks&task=weblink.add&w_id=0';
		}

		if ($return) {
			$link .= '&return='.$return;
		}

		return $link;
	}

	public static function getCategoryRoute($catid)
	{
		//Create the link
		$link = array('option' => 'com_weblinks', 'view' => 'category', 'id' => $catid);

		return $link;
	}
}
