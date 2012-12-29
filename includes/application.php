<?php
/**
 * @package    Joomla.Site
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Joomla! Application class
 *
 * Provide many supporting API functions
 *
 * @package     Joomla.Site
 * @subpackage  Application
 * @since       1.5
 */
final class JSite extends JApplication
{
	/**
	 * Currently active template
	 *
	 * @var    object
	 * @since  1.5
	 */
	private $_template = null;

	/**
	 * Option to filter by language
	 *
	 * @var    boolean
	 * @since  1.6
	 */
	private $_language_filter = false;

	/**
	 * Option to detect language by the browser
	 *
	 * @var    boolean
	 * @since  1.6
	 */
	private $_detect_browser = false;

	/**
	 * Class constructor.
	 *
	 * @param   array  $config  A configuration array including optional elements such as session
	 * session_name, clientId and others. This is not exhaustive.
	 *
	 * @since   1.5
	 */
	public function __construct($config = array())
	{
		$config['clientId'] = 0;
		parent::__construct($config);
	}

	/**
	 * Initialise the application.
	 *
	 * @param   array  $options  An optional associative array of configuration settings.
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public function initialise($options = array())
	{
		$config = JFactory::getConfig();
		$user   = JFactory::getUser();

		// If the user is a guest we populate it with the guest user group.
		if ($user->guest)
		{
			$guestUsergroup = JComponentHelper::getParams('com_users')->get('guest_usergroup', 1);
			$user->groups = array($guestUsergroup);
		}

		/*
		 * If a language was specified it has priority
		 * Otherwise use user or default language settings
		 */
		JPluginHelper::importPlugin('system', 'languagefilter');

		if (empty($options['language']))
		{
			$lang = $this->input->getString('language', null);

			if ($lang && JLanguage::exists($lang))
			{
				$options['language'] = $lang;
			}
		}

		if ($this->_language_filter && empty($options['language']))
		{
			// Detect cookie language
			$lang = $this->input->getString(self::getHash('language'), null, 'cookie');

			// Make sure that the user's language exists
			if ($lang && JLanguage::exists($lang))
			{
				$options['language'] = $lang;
			}
		}

		if (empty($options['language']))
		{
			// Detect user language
			$lang = $user->getParam('language');

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
			$client = JApplicationHelper::getClientInfo($this->getClientId());
			$options['language'] = $params->get($client->name, $config->get('language', 'en-GB'));
		}

		// One last check to make sure we have something
		if (!JLanguage::exists($options['language']))
		{
			$lang = $config->get('language', 'en-GB');

			if (JLanguage::exists($lang))
			{
				$options['language'] = $lang;
			}
			else
			{
				// As a last ditch fail to english
				$options['language'] = 'en-GB';
			}
		}

		// Execute the parent initialise method.
		parent::initialise($options);

		// Load Library language
		$lang = JFactory::getLanguage();

		/*
		 * Try the lib_joomla file in the current language (without allowing the loading of the file in the default language)
		 * Fallback to the lib_joomla file in the default language if the current language isn't found
		 */
		$lang->load('lib_joomla', JPATH_SITE, null, false, false)
		|| $lang->load('lib_joomla', JPATH_ADMINISTRATOR, null, false, false)
		|| $lang->load('lib_joomla', JPATH_SITE, null, true)
		|| $lang->load('lib_joomla', JPATH_ADMINISTRATOR, null, true);
	}

	/**
	 * Route the application.
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public function route()
	{
		parent::route();

		$Itemid = $this->input->getInt('Itemid');

		$this->authorise($Itemid);
	}

	/**
	 * Dispatch the application.
	 *
	 * @param   string  $component  The component to dispatch.
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public function dispatch($component = null)
	{
		// Get the component if not set.
		if (!$component)
		{
			$component = $this->input->get('option');
		}

		$document = JFactory::getDocument();
		$router   = $this->getRouter();
		$params   = $this->getParams();

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
		if ($this->getCfg('MetaVersion', 0))
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

	/**
	 * Render the application.
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public function render()
	{
		$document = JFactory::getDocument();
		$user     = JFactory::getUser();

		// Get the format to render
		$format = $document->getType();

		switch ($format)
		{
			case 'feed':
				$params = array();
				break;

			case 'html':
			default:
				$template = $this->getTemplate(true);
				$file = $this->input->get('tmpl', 'index');

				if (!$this->getCfg('offline') && ($file == 'offline'))
				{
					$file = 'index';
				}

				if ($this->getCfg('offline') && !$user->authorise('core.login.offline'))
				{
					$uri = JUri::getInstance();
					$return = (string) $uri;
					$this->setUserState('users.login.form.data', array('return' => $return));
					$file = 'offline';
					JResponse::setHeader('Status', '503 Service Temporarily Unavailable', 'true');
				}

				if (!is_dir(JPATH_THEMES . '/' . $template->template) && !$this->getCfg('offline'))
				{
					$file = 'component';
				}

				$params = array(
					'template'  => $template->template,
					'file'      => $file . '.php',
					'directory' => JPATH_THEMES,
					'params'    => $template->params
				);

				break;
		}

		// Parse the document.
		$document = JFactory::getDocument();
		$document->parse($params);

		// Trigger the onBeforeRender event.
		JPluginHelper::importPlugin('system');
		$this->triggerEvent('onBeforeRender');

		$caching = false;

		if ($this->getCfg('caching') && $this->getCfg('caching', 2) == 2 && !$user->get('id'))
		{
			$caching = true;
		}

		// Render the document.
		JResponse::setBody($document->render($caching, $params));

		// Trigger the onAfterRender event.
		$this->triggerEvent('onAfterRender');
	}

	/**
	 * Login authentication function
	 *
	 * @param   array  $credentials  Array('username' => string, 'password' => string)
	 * @param   array  $options      Array('remember' => boolean)
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.5
	 */
	public function login($credentials, $options = array())
	{
		// Set the application login entry point
		if (!array_key_exists('entry_url', $options))
		{
			$options['entry_url'] = JUri::base() . 'index.php?option=com_users&task=user.login';
		}

		// Set the access control action to check.
		$options['action'] = 'core.login.site';

		return parent::login($credentials, $options);
	}

	/**
	 * Check if the user can access the application
	 *
	 * @param   string  $itemid  The itemid of the menu item to check
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function authorise($itemid)
	{
		$menus = $this->getMenu();
		$user  = JFactory::getUser();

		if (!$menus->authorise($itemid))
		{
			if ($user->get('id') == 0)
			{
				// Redirect to login
				$uri = JUri::getInstance();
				$return = (string) $uri;

				$this->setUserState('users.login.form.data', array('return' => $return));

				$url = 'index.php?option=com_users&view=login';
				$url = JRoute::_($url, false);

				$this->redirect($url, JText::_('JGLOBAL_YOU_MUST_LOGIN_FIRST'));
			}
			else
			{
				JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
			}
		}
	}

	/**
	 * Get the appliaction parameters
	 *
	 * @param   string  $option  The component option
	 *
	 * @return  object  The parameters object
	 *
	 * @since   1.5
	 */
	public function getParams($option = null)
	{
		static $params = array();

		$hash = '__default';

		if (!empty($option))
		{
			$hash = $option;
		}

		// Only fetch the params if not cached
		if (!isset($params[$hash]))
		{
			// Get component parameters
			if (!$option)
			{
				$option = $this->input->get('option');
			}
			// Get new instance of component global parameters
			$params[$hash] = clone JComponentHelper::getParams($option);

			// Get menu parameters
			$menus = $this->getMenu();
			$menu  = $menus->getActive();

			// Get language
			$lang_code = JFactory::getLanguage()->getTag();
			$languages = JLanguageHelper::getLanguages('lang_code');

			$title = $this->getCfg('sitename');

			if (isset($languages[$lang_code]) && $languages[$lang_code]->metadesc)
			{
				$description = $languages[$lang_code]->metadesc;
			}
			else
			{
				$description = $this->getCfg('MetaDesc');
			}
			$rights = $this->getCfg('MetaRights');
			$robots = $this->getCfg('robots');

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
				// Get com_menu global settings
				$temp = clone JComponentHelper::getParams('com_menus');
				$params[$hash]->merge($temp);

				// If supplied, use page title
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
	 * Get the application parameters
	 *
	 * @param   string  $option  The component option
	 *
	 * @return  object  The parameters object
	 *
	 * @since   1.5
	 */
	public function getPageParameters($option = null)
	{
		return $this->getParams($option);
	}

	/**
	 * Get the template data
	 *
	 * @param   boolean  $params  True to return the template with params, false for only the template name
	 *
	 * @return  mixed  The template name or the full template data object
	 *
	 * @since   1.5
	 */
	public function getTemplate($params = false)
	{
		// If the template data is already cached, then return it.
		if (is_object($this->_template))
		{
			if ($params)
			{
				return $this->_template;
			}

			return $this->_template->template;
		}

		// Get the id of the active menu item
		$menu = $this->getMenu();
		$item = $menu->getActive();

		if (!$item)
		{
			$item = $menu->getItem($this->input->getInt('Itemid'));
		}

		$id = 0;

		if (is_object($item))
		{
			// Valid item retrieved
			$id = $item->template_style_id;
		}

		$tid = $this->input->get('templateStyle', 0, 'uint');

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
			$query->select('id, home, template, s.params');
			$query->from('#__template_styles as s');
			$query->where('s.client_id = 0');
			$query->where('e.enabled = 1');
			$query->leftJoin('#__extensions as e ON e.element=s.template AND e.type=' . $db->quote('template') . ' AND e.client_id=s.client_id');

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
		$template->template = $this->input->get('template', $template->template);

		// Need to filter the default value as well
		$template->template = JFilterInput::getInstance()->clean($template->template, 'cmd');

		// Fallback template
		if (!file_exists(JPATH_THEMES . '/' . $template->template . '/index.php'))
		{
			JError::raiseWarning(0, JText::_('JERROR_ALERTNOTEMPLATE'));
			$template->template = 'beez3';

			if (!file_exists(JPATH_THEMES . '/beez3/index.php'))
			{
				$template->template = '';
			}
		}

		// Cache the result
		$this->_template = $template;

		if ($params)
		{
			return $template;
		}

		return $template->template;
	}

	/**
	 * Overrides the default template that would be used
	 *
	 * @param   string  $template     The template name
	 * @param   mixed   $styleParams  The template style parameters
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public function setTemplate($template, $styleParams = null)
	{
		if (is_dir(JPATH_THEMES . '/' . $template))
		{
			$this->_template = new stdClass;
			$this->_template->template = $template;

			if ($styleParams instanceof JRegistry)
			{
				$this->_template->params = $styleParams;
			}
			else
			{
				$this->_template->params = new JRegistry($styleParams);
			}
		}
	}

	/**
	 * Returns the application JMenu object.
	 *
	 * @param   string  $name     The name of the application/client.
	 * @param   array   $options  An optional associative array of configuration settings.
	 *
	 * @return  JMenu  JMenu object.
	 *
	 * @since	1.5
	 */
	public function getMenu($name = null, $options = array())
	{
		$options = array();
		$menu    = parent::getMenu('site', $options);

		return $menu;
	}

	/**
	 * Returns the application JPathway object.
	 *
	 * @param   string  $name     The name of the application.
	 * @param   array   $options  An optional associative array of configuration settings.
	 *
	 * @return  JPathway  A JPathway object
	 *
	 * @since   1.5
	 */
	public function getPathway($name = null, $options = array())
	{
		$options = array();
		$pathway = parent::getPathway('site', $options);

		return $pathway;
	}

	/**
	 * Returns the application JRouter object.
	 *
	 * @param   string  $name     The name of the application.
	 * @param   array   $options  An optional associative array of configuration settings.
	 *
	 * @return  JRouter  A JRouter object
	 *
	 * @since   1.5
	 */
	static public function getRouter($name = null, array $options = array())
	{
		$config = JFactory::getConfig();
		$options['mode'] = $config->get('sef');
		$router = parent::getRouter('site', $options);

		return $router;
	}

	/**
	 * Return the current state of the language filter.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function getLanguageFilter()
	{
		return $this->_language_filter;
	}

	/**
	 * Set the current state of the language filter.
	 *
	 * @param   boolean  $state  The new state
	 *
	 * @return  boolean  The old state
	 *
	 * @since   1.6
	 */
	public function setLanguageFilter($state = false)
	{
		$old = $this->_language_filter;
		$this->_language_filter = $state;

		return $old;
	}
	/**
	 * Return the current state of the detect browser option.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function getDetectBrowser()
	{
		return $this->_detect_browser;
	}

	/**
	 * Set the current state of the detect browser option.
	 *
	 * @param   boolean  $state  The new state
	 *
	 * @return  boolean  The old state
	 *
	 * @since   1.6
	 */
	public function setDetectBrowser($state = false)
	{
		$old = $this->_detect_browser;
		$this->_detect_browser = $state;

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
	 * @param   string   $url         The URL to redirect to. Can only be http/https URL
	 * @param   string   $msg         An optional message to display on redirect.
	 * @param   string   $msgType     An optional message type. Defaults to message.
	 * @param   boolean  $moved       True if the page is 301 Permanently Moved, otherwise 303 See Other is assumed.
	 * @param   boolean  $persistMsg  True if the enqueued messages are passed to the redirection, false else.
	 *
	 * @return  void  Calls exit().
	 *
	 * @since   1.5
	 * @see     JApplication::enqueueMessage()
	 */
	public function redirect($url, $msg = '', $msgType = 'message', $moved = false, $persistMsg = true)
	{
		if (!$persistMsg)
		{
			$this->_messageQueue = array();
		}

		parent::redirect($url, $msg, $msgType, $moved);
	}
}
