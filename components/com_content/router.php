<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Content Router
 *
 *
 *
 */
class ContentRouter extends JComponentRouter
{
	function __construct()
	{
		$this->register('categories', 'categories');
		$this->register('category', 'category', 'id', 'categories', '', true, array('default', 'blog'));
		$this->register('article', 'article', 'id', 'category', 'catid');
		$this->register('archive', 'archive');
		$this->register('featured', 'featured');
		parent::__construct();
	}
}
