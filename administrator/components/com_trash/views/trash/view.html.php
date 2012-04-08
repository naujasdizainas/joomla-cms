<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_trash
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * HTML View class for the Trash component
 *
 * @package     Joomla.Administrator
 * @subpackage  com_trash
 * @since       3.0
 */
class TrashViewTrash extends JView
{
	protected $tables;

	/**
	 * Method to display the view.
	 *
	 * @param   string  $tpl  A template file to load. [optional]
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @since   3.0
	 */
	public function display($tpl = null)
	{
		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');
		$this->state		= $this->get('State');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode("\n", $errors));
			return false;
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	protected function addToolbar()
	{
		JToolBarHelper::title(JText::_('COM_TRASH_GLOBAL_TRASH'), 'trash.png');
		if (JFactory::getUser()->authorise('core.admin', 'com_trash')) {
			JToolBarHelper::custom('trash', 'trash.png', 'trash_f2.png', 'JTOOLBAR_DELETE', true);
			JToolBarHelper::divider();
			JToolBarHelper::preferences('com_trash');
			JToolBarHelper::divider();
		}
		JToolBarHelper::help('JHELP_SITE_MAINTENANCE_GLOBAL_CHECK-IN');
	}
}
