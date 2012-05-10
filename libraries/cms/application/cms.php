<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Application
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

/**
 * Joomla! Application class
 *
 * The JApplicationCms is a transitional class used to move the Joomla! CMS from using the
 * legacy JApplication class as the root for its application instance to enabling the CMS
 * to build using JApplicationWeb.
 *
 * @package     Joomla.Libraries
 * @subpackage  Application
 * @since       3.0
 */
class JApplicationCms extends JApplicationWeb
{
	/**
	 * The scope of the application.
	 *
	 * @var    string
	 * @since  3.0
	 */
	public $scope = null;

	/**
	 * The client identifier.
	 *
	 * @var    integer
	 * @since  3.0
	 */
	protected $_clientId = null;

	/**
	 * The application message queue.
	 *
	 * @var    array
	 * @since  3.0
	 */
	protected $_messageQueue = array();

	/**
	 * The name of the application.
	 *
	 * @var    array
	 * @since  3.0
	 */
	protected $_name = null;

	/**
	 * Enqueue a system message.
	 *
	 * @param   string  $msg   The message to enqueue.
	 * @param   string  $type  The message type. Default is message.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function enqueueMessage($msg, $type = 'message')
	{
		// For empty queue, if messages exists in the session, enqueue them first.
		if (!count($this->_messageQueue))
		{
			$session = JFactory::getSession();
			$sessionQueue = $session->get('application.queue');

			if (count($sessionQueue))
			{
				$this->_messageQueue = $sessionQueue;
				$session->set('application.queue', null);
			}
		}

		// Enqueue the message.
		$this->_messageQueue[] = array('message' => $msg, 'type' => strtolower($type));
	}

	/**
	 * Gets a configuration value.
	 *
	 * @param   string  $varname  The name of the value to get.
	 * @param   string  $default  Default value to return
	 *
	 * @return  mixed  The user state.
	 *
	 * @since   3.0
	 */
	public function getCfg($varname, $default = null)
	{
		$config = JFactory::getConfig();
		return $config->get('' . $varname, $default);
	}

	/**
	 * Gets the client id of the current running application.
	 *
	 * @return  integer  A client identifier.
	 *
	 * @since   3.0
	 */
	public function getClientId()
	{
		return $this->_clientId;
	}

	/**
	 * Returns the application JMenu object.
	 *
	 * @param   string  $name     The name of the application/client.
	 * @param   array   $options  An optional associative array of configuration settings.
	 *
	 * @return  JMenu
	 *
	 * @since   3.0
	 */
	public function getMenu($name = null, $options = array())
	{
		if (!isset($name))
		{
			$name = $this->_name;
		}

		$menu = JMenu::getInstance($name, $options);

		if ($menu instanceof Exception)
		{
			return null;
		}

		return $menu;
	}

	/**
	 * Get the system message queue.
	 *
	 * @return  array  The system message queue.
	 *
	 * @since   3.0
	 */
	public function getMessageQueue()
	{
		// For empty queue, if messages exists in the session, enqueue them.
		if (!count($this->_messageQueue))
		{
			$session = JFactory::getSession();
			$sessionQueue = $session->get('application.queue');

			if (count($sessionQueue))
			{
				$this->_messageQueue = $sessionQueue;
				$session->set('application.queue', null);
			}
		}

		return $this->_messageQueue;
	}

	/**
	 * Returns the application JPathway object.
	 *
	 * @param   string  $name     The name of the application.
	 * @param   array   $options  An optional associative array of configuration settings.
	 *
	 * @return  JPathway
	 *
	 * @since   3.0
	 */
	public function getPathway($name = null, $options = array())
	{
		if (!isset($name))
		{
			$name = $this->_name;
		}

		$pathway = JPathway::getInstance($name, $options);

		if ($pathway instanceof Exception)
		{
			return null;
		}

		return $pathway;
	}

	/**
	 * Returns the application JRouter object.
	 *
	 * @param   string  $name     The name of the application.
	 * @param   array   $options  An optional associative array of configuration settings.
	 *
	 * @return  JRouter
	 *
	 * @since   3.0
	 */
	public static function getRouter($name = null, array $options = array())
	{
		if (!isset($name))
		{
			$app = JFactory::getApplication();
			$name = $app->getName();
		}

		jimport('joomla.application.router');
		$router = JRouter::getInstance($name, $options);

		if ($router instanceof Exception)
		{
			return null;
		}

		return $router;
	}

	/**
	 * Gets a user state.
	 *
	 * @param   string  $key      The path of the state.
	 * @param   mixed   $default  Optional default value, returned if the internal value is null.
	 *
	 * @return  mixed  The user state or null.
	 *
	 * @since   3.0
	 */
	public function getUserState($key, $default = null)
	{
		$session = JFactory::getSession();
		$registry = $session->get('registry');

		if (!is_null($registry))
		{
			return $registry->get($key, $default);
		}

		return $default;
	}

	/**
	 * Gets the value of a user state variable.
	 *
	 * @param   string  $key      The key of the user state variable.
	 * @param   string  $request  The name of the variable passed in a request.
	 * @param   string  $default  The default value for the variable if not found. Optional.
	 * @param   string  $type     Filter for the variable, for valid values see {@link JFilterInput::clean()}. Optional.
	 *
	 * @return  object  The request user state.
	 *
	 * @since   3.0
	 */
	public function getUserStateFromRequest($key, $request, $default = null, $type = 'none')
	{
		$cur_state = $this->getUserState($key, $default);
		$new_state = $this->input->get($request, null, $type);

		// Save the new value only if it was set in this request.
		if ($new_state !== null)
		{
			$this->setUserState($key, $new_state);
		}
		else
		{
			$new_state = $cur_state;
		}

		return $new_state;
	}

	/**
	 * Initialise the application.
	 *
	 * @param   array  $options  An optional associative array of configuration settings.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	protected function initialiseApp($options = array())
	{
		// Set the language in the class.
		$config = JFactory::getConfig();

		// Check that we were given a language in the array (since by default may be blank).
		if (isset($options['language']))
		{
			$config->set('language', $options['language']);
		}

		// Set user specific editor.
		$user = JFactory::getUser();
		$editor = $user->getParam('editor', $this->getCfg('editor'));
		if (!JPluginHelper::isEnabled('editors', $editor))
		{
			$editor = $this->getCfg('editor');
			if (!JPluginHelper::isEnabled('editors', $editor))
			{
				$editor = 'none';
			}
		}

		$config->set('editor', $editor);

		// Trigger the onAfterInitialise event.
		JPluginHelper::importPlugin('system');
		$this->triggerEvent('onAfterInitialise');
	}

	/**
	 * Is admin interface?
	 *
	 * @return  boolean  True if this application is administrator.
	 *
	 * @since   3.0
	 */
	public function isAdmin()
	{
		return ($this->_clientId == 1);
	}

	/**
	 * Is site interface?
	 *
	 * @return  boolean  True if this application is site.
	 *
	 * @since   3.0
	 */
	public function isSite()
	{
		return ($this->_clientId == 0);
	}

	/**
	 * Route the application.
	 *
	 * Routing is the process of examining the request environment to determine which
	 * component should receive the request. The component optional parameters
	 * are then set in the request object to be processed when the application is being
	 * dispatched.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	protected function route()
	{
		// Get the full request URI.
		$uri = clone JURI::getInstance();

		$router = $this->getRouter();
		$result = $router->parse($uri);

		foreach ($result as $key => $value)
		{
			$this->input->def($key, $value);
		}

		// Trigger the onAfterRoute event.
		JPluginHelper::importPlugin('system');
		$this->triggerEvent('onAfterRoute');
	}

	/**
	 * Sets the value of a user state variable.
	 *
	 * @param   string  $key    The path of the state.
	 * @param   string  $value  The value of the variable.
	 *
	 * @return  mixed  The previous state, if one existed.
	 *
	 * @since   3.0
	 */
	public function setUserState($key, $value)
	{
		$session = JFactory::getSession();
		$registry = $session->get('registry');

		if (!is_null($registry))
		{
			return $registry->set($key, $value);
		}

		return null;
	}
}
