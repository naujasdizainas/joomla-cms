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

// Define the base path and require the other defines
define('JPATH_BASE', dirname(__DIR__));
require_once JPATH_BASE . '/application/defines.php';

// Launch the application
require_once JPATH_BASE . '/application/framework.php';

// Register the Site application
JLoader::registerPrefix('Installation', JPATH_INSTALLATION);
