<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_config
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

JFormHelper::loadFieldClass('checkboxes');

/**
 * Form Field class for the Joomla Framework.
 *
 * @package		Joomla.Framework
 * @subpackage	Form
 * @since		1.6
 */
class JFormFieldComponentSEFRules extends JFormFieldCheckboxes
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'SEF Rules';

	/**
	 * Method to get the field options.
	 *
	 * @return	array	The field option objects.
	 * @since	1.6
	 */
	protected function getOptions()
	{
		// Initialize variables.
		$options = array();

		$rules = array();
		$app = JFactory::getApplication();
		$event = $app->triggerEvent('onComponentRouterRules');

		foreach ($event as $ruleset)
		{
			$rules = array_merge($rules, (array) $ruleset);
		}

		foreach ($rules as $rule)
		{
			$options[] = JHtml::_('select.option', $rule, 'COM_CONFIG_FIELD_SEF_COMPONENT_ROUTER_' . strtoupper($rule) . '_LABEL', 'value', 'text');
		}

		reset($options);

		return $options;
	}
}
