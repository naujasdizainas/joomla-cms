<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Application
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

if (file_exists(dirname(__DIR__) . '/defines.php'))
{
	require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(__DIR__));
	require_once JPATH_BASE . '/application/defines.php';
}

// Launch the application
require_once JPATH_BASE . '/application/framework.php';

// Require the deprecated helpers
require_once JPATH_BASE . '/application/submenu.php';
require_once JPATH_BASE . '/application/toolbar.php';

// Register the Administrator application
JLoader::registerPrefix('Administrator', JPATH_ADMINISTRATOR);
