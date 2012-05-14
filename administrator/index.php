<?php
/**
 * @package    Joomla.Administrator
 *
 * @copyright  Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set flag that this is a parent file.
define('_JEXEC', 1);

// Bootstrap the application
require_once __DIR__ . '/application/bootstrap.php';

// Mark afterLoad in the profiler.
JDEBUG ? $_PROFILER->mark('afterLoad') : null;

// Get the site application
$app = JApplicationWeb::getInstance('AdministratorApplicationWeb');

// Execute the application
$app->execute();
