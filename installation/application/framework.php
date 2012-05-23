<?php
/**
 * @package     Joomla.Installation
 * @subpackage  Application
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/*
 * Joomla system checks.
 */

error_reporting(E_ALL);
@ini_set('magic_quotes_runtime', 0);

/*
 * Check if a configuration file already exists.
 */

if (file_exists(JPATH_CONFIGURATION . '/configuration.php') && (filesize(JPATH_CONFIGURATION . '/configuration.php') > 10) && !file_exists(JPATH_INSTALLATION . '/index.php'))
{
	header('Location: ../index.php');
	exit();
}

/*
 * Joomla system startup.
 */

// Import the Joomla Platform with the legacy classes.
require_once JPATH_LIBRARIES . '/import.legacy.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';

// Necessary Platform imports not handled by the autoloader.
jimport('joomla.environment.uri');
jimport('joomla.utilities.arrayhelper');
