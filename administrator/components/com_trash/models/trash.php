<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_trash
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');

/**
 * Trash Manager Controller
 *
 * @package     Joomla.Administrator
 * @subpackage  com_trash
 * @since       3.0
 */
class TrashModelTrash extends JModelList
{
	/**
	 * @var    integer
	 * @since  3.0
	 */
	protected $total;

	/**
	 * Method to auto-populate the model state.  Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field. [optional]
	 * @param   string  $direction  An optional direction. [optional]
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// List state information.
		parent::populateState('table', 'asc');
	}

	/**
	 * Get total of tables
	 *
	 * @return  integer  The total number of items in the tables
	 *
	 * @since   3.0
	 */
	public function getTotal()
	{
		if (!isset($this->total))
		{
			$this->getItems();
		}

		return $this->total;
	}

	/**
	 * Get tables
	 *
	 * @return  array  Table names as keys and trashed item count as values
	 *
	 * @since   3.0
	 */
	public function getItems()
	{
		if (!isset($this->items))
		{
			$app = JFactory::getApplication();
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$tables = $db->getTableList();

			// Some tables don't fit into this context; skip them
			$skippedGenericTables = array('#__extensions', '#__finder_filters', '#__finder_links', '#__finder_taxonomy');

			// We need to put the table prefix on our skipped array
			$skippedPrefixTables = array();
			foreach ($skippedGenericTables as $skipped)
			{
				$table = $db->replacePrefix($skipped);
				$skippedPrefixTables[] = $table;
			}

			// This array will hold the table name as key and trashed item count as value
			$results = array();

			foreach ($tables as $i => $tn)
			{
				// Make sure we get the right tables based on prefix
				if (stripos($tn, $app->getCfg('dbprefix')) !== 0)
				{
					unset($tables[$i]);
					continue;
				}

				// If the user specified a filter, then ignore tables not matching the filter
				if ($this->getState('filter.search') && stripos($tn, $this->getState('filter.search')) === false)
				{
					unset($tables[$i]);
					continue;
				}

				$fields = $db->getTableColumns($tn);

				// If the table doesn't have a published or state field, ignore it
				if (!(isset($fields['published'])) && !(isset($fields['state'])))
				{
					unset($tables[$i]);
					continue;
				}
			}
			foreach ($tables as $tn)
			{
				// Check if the table is in our skipped array
				if (in_array($tn, $skippedPrefixTables))
				{
					continue;
				}

				// Get the table's fields
				$fields = $db->getTableColumns($tn);
				$query->clear();
				$query->select('COUNT(*)');
				$query->from($db->quoteName($tn));

				// Handle tables where 'published' is the status column
				if (isset($fields['published']))
				{
					$query->where($db->quoteName('published') . ' = -2');
				}
				// Handle tables where 'state' is the status column and the table name isn't #__contact_details due to having both columns
				elseif (isset($fields['state']) && $tn != $db->replacePrefix('#__contact_details'))
				{
					$query->where($db->quoteName('state') . ' = -2');
				}

				$db->setQuery($query);
				if ($db->query())
				{
					$results[$tn] = $db->loadResult();
				}
				else
				{
					continue;
				}
			}

			$this->total = count($results);

			if ($this->getState('list.ordering') == 'table')
			{
				if ($this->getState('list.direction') == 'asc')
				{
					ksort($results);
				}
				else
				{
					krsort($results);
				}
			}
			else
			{
				if ($this->getState('list.direction') == 'asc')
				{
					asort($results);
				}
				else
				{
					arsort($results);
				}
			}
			$results = array_slice($results, $this->getState('list.start'), $this->getState('list.limit') ? $this->getState('list.limit') : null);
			$this->items = $results;
		}

		return $this->items;
	}
}
