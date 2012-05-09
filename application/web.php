<?php
/**
 * @package     Joomla.Site
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
 * @package     Joomla.Site
 * @subpackage  Application
 * @since       3.0
 */
final class SiteApplicationWeb extends JApplicationWeb
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
	 * Currently active template
	 *
	 * @var    object
	 * @since  3.0
	 */
	private $template = null;

	/**
	 * Option to filter by language
	 *
	 * @var    boolean
	 * @since  3.0
	 */
	private $_language_filter = false;

	/**
	 * Option to detect language by the browser
	 *
	 * @var    boolean
	 * @since  3.0
	 */
	private $_detect_browser = false;

	/**
	 * Check if the user can access the application
	 *
	 * @param   integer  $itemid  The item ID to check authorisation for
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	protected function authorise($itemid)
	{
		$menus = $this->getMenu();
		$user = JFactory::getUser();

		if (!$menus->authorise($itemid))
		{
			if ($user->get('id') == 0)
			{
				// Redirect to login
				$uri = JURI::getInstance();
				$return = (string) $uri;

				// Set the data
				$this->setUserState('users.login.form.data', array('return' => $return ));

				$url = JRoute::_('index.php?option=com_users&view=login', false);

				$this->redirect($url, JText::_('JGLOBAL_YOU_MUST_LOGIN_FIRST'));
			}
			else
			{
				JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
			}
		}
	}

	/**
	 * Dispatch the application
	 *
	 * @param	string  $component  The component which is being rendered.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function dispatch($component = null)
	{
		try
		{
			// Get the component if not set.
			if (!$component)
			{
				$component = $this->input->getCmd('option', null);
			}

			// Set the class document object
			$this->document	= JFactory::getDocument();

			// Set up the params
			$document	= $this->document;
			$user		= JFactory::getUser();
			$router		= self::getRouter();
			$params		= $this->getParams();

			switch ($document->getType())
			{
				case 'html':
					// Get language
					$lang_code = JFactory::getLanguage()->getTag();
					$languages = JLanguageHelper::getLanguages('lang_code');

					// Set metadata
					if (isset($languages[$lang_code]) && $languages[$lang_code]->metakey)
					{
						$document->setMetaData('keywords', $languages[$lang_code]->metakey);
					}
					else
					{
						$document->setMetaData('keywords', $this->getCfg('MetaKeys'));
					}

					$document->setMetaData('rights', $this->getCfg('MetaRights'));

					if ($router->getMode() == JROUTER_MODE_SEF)
					{
						$document->setBase(htmlspecialchars(JURI::current()));
					}

					break;

				case 'feed':
					$document->setBase(htmlspecialchars(JURI::current()));
					break;
			}

			$document->setTitle($params->get('page_title'));
			$document->setDescription($params->get('page_description'));

			// Add version number or not based on global configuration
			if ($this->config->get('MetaVersion', 0))
			{
				$document->setGenerator('Joomla! - Open Source Content Management  - Version ' . JVERSION);
			}
			else
			{
				$document->setGenerator('Joomla! - Open Source Content Management');
			}

			$contents = JComponentHelper::renderComponent($component);
			$document->setBuffer($contents, 'component');

			// Trigger the onAfterDispatch event.
			JPluginHelper::importPlugin('system');
			$this->triggerEvent('onAfterDispatch');
		}

		// Mop up any uncaught exceptions.
		catch (Exception $e)
		{
			$code = $e->getCode();
			JError::raiseError($code ? $code : 500, $e->getMessage());
		}
	}

	/**
	 * Method to run the Web application routines.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	protected function doExecute()
	{
		// Register the application to JFactory
		JFactory::$application = $this;

		// Register the client ID
		$this->_clientId = 0;

		// Initialise the application
		$this->initialiseApp();

		// Mark afterIntialise in the profiler.
		JDEBUG ? $_PROFILER->mark('afterInitialise') : null;

		// Route the application
		$this->route();

		// Mark afterRoute in the profiler.
		JDEBUG ? $_PROFILER->mark('afterRoute') : null;

		// Dispatch the application
		$this->dispatch();

		// Mark afterDispatch in the profiler.
		JDEBUG ? $_PROFILER->mark('afterDispatch') : null;
	}

	/**
	 * Gets a configuration value.
	 *
	 * @param   string  $varname  The name of the value to get.
	 * @param   string  $default  Default value to return
	 *
	 * @return  mixed  The user state.
	 *
	 * @note    Present to maintain CMS B/C
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
	 * @note    Present to maintain CMS B/C
	 * @since   3.0
	 */
	public function getClientId()
	{
		return $this->_clientId;
	}

	/**
	 * Returns the application JMenu object.
	 *
	 * @return  JMenu
	 *
	 * @since   3.0
	 */
	public function getMenu()
	{
		$menu = JMenu::getInstance('site', array());

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
	 * Get the application parameters
	 *
	 * @param   string  $option  The component option
	 *
	 * @return  object  The parameters object
	 *
	 * @since   3.0
	 */
	public function getParams($option = null)
	{
		static $params = array();

		$hash = '__default';
		if (!empty($option))
		{
			$hash = $option;
		}

		if (!isset($params[$hash]))
		{
			// Get component parameters
			if (!$option)
			{
				$option = $this->input->getCmd('option', null);
			}

			// Get new instance of component global parameters
			$params[$hash] = clone JComponentHelper::getParams($option);

			// Get menu parameters
			$menus = $this->getMenu();
			$menu = $menus->getActive();

			// Get language
			$lang_code = JFactory::getLanguage()->getTag();
			$languages = JLanguageHelper::getLanguages('lang_code');

			$title = $this->config->get('sitename');
			if (isset($languages[$lang_code]) && $languages[$lang_code]->metadesc)
			{
				$description = $languages[$lang_code]->metadesc;
			}
			else
			{
				$description = $this->config->get('MetaDesc');
			}
			$rights = $this->config->get('MetaRights');
			$robots = $this->config->get('robots');
			// Lets cascade the parameters if we have menu item parameters
			if (is_object($menu))
			{
				$temp = new JRegistry;
				$temp->loadString($menu->params);
				$params[$hash]->merge($temp);
				$title = $menu->title;
			}
			else
			{
				// get com_menu global settings
				$temp = clone JComponentHelper::getParams('com_menus');
				$params[$hash]->merge($temp);
				// if supplied, use page title
				$title = $temp->get('page_title', $title);
			}

			$params[$hash]->def('page_title', $title);
			$params[$hash]->def('page_description', $description);
			$params[$hash]->def('page_rights', $rights);
			$params[$hash]->def('robots', $robots);
		}

		return $params[$hash];
	}

	/**
	 * Return a reference to the JPathway object.
	 *
	 * @param   array  $options  An optional associative array of configuration settings.
	 *
	 * @return  JPathway
	 *
	 * @since   3.0
	 */
	public function getPathway(array $options = array())
	{
		$options = array();
		$pathway = JPathway::getInstance('site', $options);

		if ($pathway instanceof Exception)
		{
			return null;
		}

		return $pathway;
	}

	/**
	 * Return a reference to the JRouter object.
	 *
	 * @param   array  $options  An optional associative array of configuration settings.
	 *
	 * @return	JRouter
	 * @since	3.0
	 */
	public static function getRouter(array $options = array())
	{
		jimport('joomla.application.router');

		$config = JFactory::getConfig();
		$options['mode'] = $config->get('sef');
		$router = JRouter::getInstance('site', $options);

		if ($router instanceof Exception)
		{
			return null;
		}

		return $router;
	}

	/**
	 * Gets the name of the current template.
	 *
	 * @param   array  $params  An optional associative array of configuration settings
	 *
	 * @return  string  The name of the template.
	 *
	 * @since   3.0
	 */
	public function getTemplate($params = array())
	{
		if (is_object($this->template))
		{
			if ($params)
			{
				return $this->template;
			}
			return $this->template->template;
		}

		// Get the id of the active menu item
		$menu = $this->getMenu();
		$item = $menu->getActive();
		if (!$item)
		{
			$item = $menu->getItem($this->input->getInt('Itemid', null));
		}

		$id = 0;
		if (is_object($item))
		{
			// Valid item retrieved
			$id = $item->template_style_id;
		}

		$tid = $this->input->getCmd('templateStyle', 0);
		if (is_numeric($tid) && (int) $tid > 0)
		{
			$id = (int) $tid;
		}

		$cache = JFactory::getCache('com_templates', '');
		if ($this->_language_filter)
		{
			$tag = JFactory::getLanguage()->getTag();
		}
		else
		{
			$tag = '';
		}
		if (!$templates = $cache->get('templates0' . $tag))
		{
			// Load styles
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('id', 'home', 'template', 's.params')));
			$query->from($db->quoteName('#__template_styles', 's'));
			$query->leftJoin($db->quoteName('#__extensions', 'e') . ' ON e.element=s.template AND e.type=' . $db->quote('template') . ' AND e.client_id=s.client_id');
			$query->where($db->quoteName('s.client_id') . ' = 0');
			$query->where($db->quoteName('e.enabled') . ' = 1');

			$db->setQuery($query);
			$templates = $db->loadObjectList('id');
			foreach ($templates as &$template)
			{
				$registry = new JRegistry;
				$registry->loadString($template->params);
				$template->params = $registry;

				// Create home element
				if ($template->home == 1 && !isset($templates[0]) || $this->_language_filter && $template->home == $tag)
				{
					$templates[0] = clone $template;
				}
			}
			$cache->store($templates, 'templates0' . $tag);
		}

		if (isset($templates[$id]))
		{
			$template = $templates[$id];
		}
		else
		{
			$template = $templates[0];
		}

		// Allows for overriding the active template from the request
		$template->template = $this->input->getCmd('template', $template->template);
		$template->template = JFilterInput::getInstance()->clean($template->template, 'cmd'); // need to filter the default value as well

		// Fallback template
		if (!file_exists(JPATH_THEMES . '/' . $template->template . '/index.php'))
		{
			JError::raiseWarning(0, JText::_('JERROR_ALERTNOTEMPLATE'));
			$template->template = 'beez_20';
			if (!file_exists(JPATH_THEMES . '/beez_20/index.php'))
			{
				$template->template = '';
			}
		}

		// Cache the result
		$this->template = $template;
		if ($params)
		{
			return $template;
		}
		return $template->template;
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
	 * @return  The request user state.
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
	 * @return  void
	 *
	 * @since   3.0
	 */
	protected function initialiseApp()
	{
		// If a language was specified it has priority, otherwise use user or default language settings
		JPluginHelper::importPlugin('system', 'languagefilter');

		if (empty($options['language']))
		{
			// Detect the specified language
			$lang = $this->input->getString('language', null);

			// Make sure that the user's language exists
			if ($lang && JLanguage::exists($lang))
			{
				$options['language'] = $lang;
			}
		}

		if ($this->_language_filter && empty($options['language']))
		{
			// Detect cookie language
			$lang = $this->input->cookie->get(JApplication::getHash('language'), null, 'string');

			// Make sure that the user's language exists
			if ($lang && JLanguage::exists($lang))
			{
				$options['language'] = $lang;
			}
		}

		if (empty($options['language']))
		{
			// Detect user language
			$lang = JFactory::getUser()->getParam('language');

			// Make sure that the user's language exists
			if ($lang && JLanguage::exists($lang))
			{
				$options['language'] = $lang;
			}
		}

		if ($this->_detect_browser && empty($options['language']))
		{
			// Detect browser language
			$lang = JLanguageHelper::detectLanguage();

			// Make sure that the user's language exists
			if ($lang && JLanguage::exists($lang))
			{
				$options['language'] = $lang;
			}
		}

		if (empty($options['language']))
		{
			// Detect default language
			$params = JComponentHelper::getParams('com_languages');
			$options['language'] = $params->get('site', $this->config->get('language', 'en-GB'));
		}

		// One last check to make sure we have something
		if (!JLanguage::exists($options['language']))
		{
			$lang = $this->config->get('language', 'en-GB');
			if (JLanguage::exists($lang))
			{
				$options['language'] = $lang;
			}
			else
			{
				$options['language'] = 'en-GB'; // as a last ditch fail to english
			}
		}

		// Set the language to the config
		$this->config->set('language', $options['language']);

		// Set user specific editor.
		$user = JFactory::getUser();
		$editor = $user->getParam('editor', $this->config->get('editor'));
		if (!JPluginHelper::isEnabled('editors', $editor))
		{
			$editor = $this->config->get('editor');
			if (!JPluginHelper::isEnabled('editors', $editor))
			{
				$editor = 'none';
			}
		}

		$this->config->set('editor', $editor);

		// Load Library language
		$lang = JFactory::getLanguage();

		/*
		 * Try the lib_joomla file in the current language (without allowing the loading of the file in the default language)
		 * Fallback to the default language if necessary
		 */
		$lang->load('lib_joomla', JPATH_SITE, null, false, false)
		|| $lang->load('lib_joomla', JPATH_ADMINISTRATOR, null, false, false)
		|| $lang->load('lib_joomla', JPATH_SITE, null, true)
		|| $lang->load('lib_joomla', JPATH_ADMINISTRATOR, null, true);

		// Trigger the onAfterInitialise event.
		JPluginHelper::importPlugin('system');
		$this->triggerEvent('onAfterInitialise');
	}

	/**
	 * Is admin interface?
	 *
	 * @return  boolean  True if this application is administrator.
	 *
	 * @since   11.1
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
	 * @since   11.1
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
	 * @since   11.1
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

		$Itemid = $this->input->getInt('Itemid', null);
		$this->authorise($Itemid);
	}

	/**
	 * Rendering is the process of pushing the document buffers into the template
	 * placeholders, retrieving data from the document and pushing it into
	 * the application response buffer.
	 *
	 * @return  void
	 *
	 * @since   11.3
	 */
	protected function render()
	{
		$document	= JFactory::getDocument();
		$user		= JFactory::getUser();

		// Get the format to render
		$format = $document->getType();

		switch ($format)
		{
			case 'feed':
				$params = array();
				break;

			case 'html':
			default:
				$template	= $this->getTemplate(true);
				$file		= $this->input->getCmd('tmpl', 'index');

				if (!$this->getCfg('offline') && ($file == 'offline'))
				{
					$file = 'index';
				}

				if ($this->getCfg('offline') && !$user->authorise('core.login.offline'))
				{
					$uri		= JFactory::getURI();
					$return		= (string)$uri;
					$this->setUserState('users.login.form.data', array( 'return' => $return ) );
					$file = 'offline';
					JResponse::setHeader('Status', '503 Service Temporarily Unavailable', 'true');
				}
				if (!is_dir(JPATH_THEMES . '/' . $template->template) && !$this->getCfg('offline')) {
					$file = 'component';
				}
				$params = array(
					'template'	=> $template->template,
					'file'		=> $file.'.php',
					'directory'	=> JPATH_THEMES,
					'params'	=> $template->params
				);
				break;
		}

		// Parse the document.
		$document->parse($params);

		$caching = false;
		if ($this->getCfg('caching') && $this->getCfg('caching', 2) == 2 && !$user->get('id'))
		{
			$caching = true;
		}

		// Render the document.
		$this->setBody($document->render($caching, $params));
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

/**
 * Methods below need review still
 */

	/**
	 * Login authentication function
	 *
	 * @param	array	Array('username' => string, 'password' => string)
	 * @param	array	Array('remember' => boolean)
	 *
	 * @see JApplication::login
	 */
	public function login($credentials, $options = array())
	{
		 // Set the application login entry point
		if (!array_key_exists('entry_url', $options)) {
			$options['entry_url'] = JURI::base().'index.php?option=com_users&task=user.login';
		}

		// Set the access control action to check.
		$options['action'] = 'core.login.site';

		return parent::login($credentials, $options);
	}

	/**
	 * @deprecated 1.6	Use the authorise method instead.
	 */
	public function authorize($itemid)
	{
		JLog::add('JSite::authorize() is deprecated. Use JSite::authorise() instead.', JLog::WARNING, 'deprecated');
		return $this->authorise($itemid);
	}

	/**
	 * Get the application parameters
	 *
	 * @param	string	The component option
	 *
	 * @return	object	The parameters object
	 * @since	1.5
	 */
	public function getPageParameters($option = null)
	{
		return $this->getParams($option);
	}

	/**
	 * Overrides the default template that would be used
	 *
	 * @param string	The template name
	 * @param mixed		The template style parameters
	 */
	public function setTemplate($template, $styleParams=null)
 	{
 		if (is_dir(JPATH_THEMES . '/' . $template)) {
 			$this->template = new stdClass();
 			$this->template->template = $template;
			if ($styleParams instanceof JRegistry) {
				$this->template->params = $styleParams;
			}
			else {
				$this->template->params = new JRegistry($styleParams);
			}
 		}
 	}

	/**
	 * Return the current state of the language filter.
	 *
	 * @return	boolean
	 * @since	1.6
	 */
	public function getLanguageFilter()
	{
		return $this->_language_filter;
	}

	/**
	 * Set the current state of the language filter.
	 *
	 * @return	boolean	The old state
	 * @since	1.6
	 */
	public function setLanguageFilter($state=false)
	{
		$old = $this->_language_filter;
		$this->_language_filter=$state;
		return $old;
	}
	/**
	 * Return the current state of the detect browser option.
	 *
	 * @return	boolean
	 * @since	1.6
	 */
	public function getDetectBrowser()
	{
		return $this->_detect_browser;
	}

	/**
	 * Set the current state of the detect browser option.
	 *
	 * @return	boolean	The old state
	 * @since	1.6
	 */
	public function setDetectBrowser($state=false)
	{
		$old = $this->_detect_browser;
		$this->_detect_browser=$state;
		return $old;
	}

	/**
	 * Redirect to another URL.
	 *
	 * Optionally enqueues a message in the system message queue (which will be displayed
	 * the next time a page is loaded) using the enqueueMessage method. If the headers have
	 * not been sent the redirect will be accomplished using a "301 Moved Permanently"
	 * code in the header pointing to the new location. If the headers have already been
	 * sent this will be accomplished using a JavaScript statement.
	 *
	 * @param	string	The URL to redirect to. Can only be http/https URL
	 * @param	string	An optional message to display on redirect.
	 * @param	string  An optional message type.
	 * @param	boolean	True if the page is 301 Permanently Moved, otherwise 303 See Other is assumed.
	 * @param	boolean	True if the enqueued messages are passed to the redirection, false else.
	 * @return	none; calls exit().
	 * @since	1.5
	 * @see		JApplication::enqueueMessage()
	 */
	public function redirect($url, $msg='', $msgType='message', $moved = false, $persistMsg = true)
	{
		if (!$persistMsg) {
			$this->_messageQueue = array();
		}
		parent::redirect($url, $msg, $msgType, $moved);
	}
}
