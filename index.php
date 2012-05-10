<?php
/**
 * @package    Joomla.Site
 *
 * @copyright  Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// Set flag that this is a parent file.
define('_JEXEC', 1);

// Bootstrap the application
require_once __DIR__ . '/application/bootstrap.php';

// Mark afterLoad in the profiler.
JDEBUG ? $_PROFILER->mark('afterLoad') : null;

// Get the site application
$app = JApplicationWeb::getInstance('SiteApplicationWeb');

// Register the application's dispatcher
$app->set('dispatcher', $app->loadDispatcher());

// Execute the application
$app->execute();
