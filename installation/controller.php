<?php
/**
 * @package    Joomla.Installation
 *
 * @copyright  Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Base controller class for the Joomla Core Installer.
 *
 * @package  Joomla.Installation
 * @since    3.0
 */
class InstallationController extends JControllerLegacy
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController  This object to support chaining.
	 *
	 * @since   3.0
	 */
	public function display($cachable = false, $urlparams = array())
	{
		// Get the application
		$app = JFactory::getApplication();

		// Get the current URI to redirect to.
		$uri		= JURI::getInstance();
		$redirect	= base64_encode($uri);

		// Get the document object.
		$document	= $app->getDocument();

		// Set the default view name and format from the Request.
		if (file_exists(JPATH_CONFIGURATION . '/configuration.php') && (filesize(JPATH_CONFIGURATION . '/configuration.php') > 10)
			&& file_exists(JPATH_INSTALLATION . '/index.php'))
		{
			$default_view	= 'remove';
		}
		else
		{
			$default_view	= 'language';
		}

		$vName		= $app->input->getWord('view', $default_view);
		$vFormat	= $document->getType();
		$lName		= $app->input->getWord('layout', 'default');

		if (strcmp($vName, $default_view) == 0)
		{
			$app->input->set('view', $default_view);
		}

		if ($view = $this->getView($vName, $vFormat))
		{
			switch ($vName)
			{
				default:
					$model = $this->getModel('Setup', 'InstallationModel', array('dbo' => null));
					break;
			}

			// Push the model into the view (as default).
			$view->setModel($model, true);
			$view->setLayout($lName);

			// Push document object into the view.
			$view->document = $document;

			$view->display();
		}

		return $this;
	}
}
