<?php
/**
 * @package     Joomla.Administrator
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
 * @package     Joomla.Administrator
 * @subpackage  Application
 * @since       3.0
 */
final class AdministratorApplicationWeb extends JApplicationCms
{
	/**
	 * Class constructor.
	 *
	 * @param   mixed  $input   An optional argument to provide dependency injection for the application's
	 *                          input object.  If the argument is a JInput object that object will become
	 *                          the application's input object, otherwise a default input object is created.
	 * @param   mixed  $config  An optional argument to provide dependency injection for the application's
	 *                          config object.  If the argument is a JRegistry object that object will become
	 *                          the application's config object, otherwise a default config object is created.
	 * @param   mixed  $client  An optional argument to provide dependency injection for the application's
	 *                          client object.  If the argument is a JApplicationWebClient object that object will become
	 *                          the application's client object, otherwise a default client object is created.
	 *
	 * @since   3.0
	 * @deprecated  4.0
	 */
	public function __construct(JInput $input = null, JRegistry $config = null, JApplicationWebClient $client = null)
	{
		parent::__construct();

		// Register the application to JFactory
		JFactory::$application = $this;

		// Register the application name
		$this->_name = 'administrator';

		// Register the client ID
		$this->_clientId = 1;

		// Set the root in the URI based on the application name
		JURI::root(null, str_ireplace('/' . $this->getName(), '', JURI::base(true)));
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
			if ($component === null)
			{
				$component = AdministratorApplicationHelper::findOption();
			}

			// Load the document to the API
			$this->loadDocument();

			// Set up the params
			$document = JFactory::getDocument();

			// Register the document object with JFactory
			JFactory::$document = $document;

			switch ($document->getType())
			{
				case 'html':
					$document->setMetaData('keywords', $this->getCfg('MetaKeys'));

					// Get the template
					$template = $this->getTemplate(true);

					// Store the template and its params to the config
					$this->set('theme', $template->template);
					$this->set('themeParams', $template->params);

					break;

				default:
					break;
			}

			$document->setTitle($this->getCfg('sitename') . ' - ' . JText::_('JADMINISTRATION'));
			$document->setDescription($this->getCfg('MetaDesc'));
			$document->setGenerator('Joomla! - Open Source Content Management');

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
		// Initialise the application
		$this->initialiseApp(array('language' => $this->getUserState('application.lang')));

		// Mark afterInitialise in the profiler.
		JDEBUG ? $this->profiler->mark('afterInitialise') : null;

		// Route the application
		$this->route();

		// Mark afterRoute in the profiler.
		JDEBUG ? $this->profiler->mark('afterRoute') : null;

		// Dispatch the application
		$this->dispatch();

		// Mark afterDispatch in the profiler.
		JDEBUG ? $this->profiler->mark('afterDispatch') : null;
	}

	/**
	 * Return a reference to the JRouter object.
	 *
	 * @param   string  $name     The name of the application.
	 * @param   array   $options  An optional associative array of configuration settings.
	 *
	 * @return	JRouter
	 * @since	3.0
	 */
	public static function getRouter($name = 'administrator', array $options = array())
	{
		jimport('joomla.application.router');

		$router = parent::getRouter($name, $options);

		return $router;
	}

	/**
	 * Gets the name of the current template.
	 *
	 * @param   boolean  $params  True to return the template parameters
	 *
	 * @return  string  The name of the template.
	 *
	 * @since   3.0
	 */
	public function getTemplate($params = false)
	{
		if (is_object($this->template))
		{
			if ($params)
			{
				return $this->template;
			}
			return $this->template->template;
		}

		$admin_style = JFactory::getUser()->getParam('admin_style');

		// Load the template name from the database
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('template, s.params');
		$query->from('#__template_styles as s');
		$query->leftJoin('#__extensions as e ON e.type='.$db->quote('template').' AND e.element=s.template AND e.client_id=s.client_id');

		if ($admin_style)
		{
			$query->where('s.client_id = 1 AND id = '.(int)$admin_style. ' AND e.enabled = 1', 'OR');
		}

		$query->where('s.client_id = 1 AND home = 1', 'OR');
		$query->order('home');
		$db->setQuery($query);
		$template = $db->loadObject();

		$template->template = JFilterInput::getInstance()->clean($template->template, 'cmd');
		$template->params = new JRegistry($template->params);

		if (!file_exists(JPATH_THEMES . '/' . $template->template . '/index.php'))
		{
			$template->params = new JRegistry();
			$template->template = 'bluestork';
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
		/*
		 * If a language was specified it has priority,
		 * otherwise use user or default language settings
		 */
		if (empty($options['language']))
		{
			$user = JFactory::getUser();
			$lang = $user->getParam('admin_language');

			// Make sure that the user's language exists
			if ($lang && JLanguage::exists($lang))
			{
				$options['language'] = $lang;
			}
			else
			{
				$params = JComponentHelper::getParams('com_languages');
				$options['language'] = $params->get('administrator', $this->config->get('language', 'en-GB'));
			}
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
				// As a last ditch fail to english
				$options['language'] = 'en-GB';
			}
		}

		// Execute the parent initialiseApp method.
		parent::initialiseApp($options);

		// Load the language to the API
		$this->loadLanguage();

		// Load the language from the API
		$lang = $this->getLanguage();

		// Register the language object with JFactory
		JFactory::$language = $lang;

		// Load Library language
		$lang->load('lib_joomla', JPATH_ADMINISTRATOR);
	}

	/**
	 * Login authentication function
	 *
	 * @param   array  $credentials  Array('username' => string, 'password' => string)
	 * @param   array  $options      Array('remember' => boolean)
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.0
	 */
	public function login($credentials, $options = array())
	{
		// The minimum group
		$options['group'] = 'Public Backend';

		// Make sure users are not auto-registered
		$options['autoregister'] = false;

		// Set the application login entry point
		if (!array_key_exists('entry_url', $options))
		{
			$options['entry_url'] = JURI::base() . 'index.php?option=com_users&task=login';
		}

		// Set the access control action to check.
		$options['action'] = 'core.login.admin';

		$result = parent::login($credentials, $options);

		if (!($result instanceof Exception))
		{
			$lang = $this->input->getCmd('lang', 'en-GB');
			$lang = preg_replace('/[^A-Z-]/i', '', $lang);
			$this->setUserState('application.lang', $lang);

			self::purgeMessages();
		}

		return $result;
	}

	/**
	 * Purge the jos_messages table of old messages
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public static function purgeMessages()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$user = JFactory::getUser();

		$userid = $user->get('id');

		$query->select('*');
		$query->from($db->quoteName('#__messages_cfg'));
		$query->where($db->quoteName('user_id') . ' = ' . (int) $userid, 'AND');
		$query->where($db->quoteName('cfg_name') . ' = ' . $db->quote('auto_purge'), 'AND');
		$db->setQuery($query);
		$config = $db->loadObject();

		// Check if auto_purge value set
		if (is_object($config) and $config->cfg_name == 'auto_purge')
		{
			$purge = $config->cfg_value;
		}
		else
		{
			// If no value set, default is 7 days
			$purge = 7;
		}

		// If purge value is not 0, then allow purging of old messages
		if ($purge > 0)
		{
			// Purge old messages at day set in message configuration
			$past = JFactory::getDate(time() - $purge * 86400);
			$pastStamp = $past->toSql();

			$query->clear();
			$query->delete($db->quoteName('#__messages'));
			$query->where($db->quoteName('date_time') . ' < ' . $db->Quote($pastStamp), 'AND');
			$query->where($db->quoteName('user_id_to') . ' = ' . (int) $userid, 'AND');
			$db->setQuery($query);
			$db->query();
		}
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
	 * @param   string   $url      The URL to redirect to. Can only be http/https URL
	 * @param   string   $msg      An optional message to display on redirect.
	 * @param   string   $msgType  An optional message type. Defaults to message.
	 * @param   boolean  $moved    True if the page is 301 Permanently Moved, otherwise 303 See Other is assumed.
	 *
	 * @return  void  Calls exit().
	 *
	 * @since   3.0
	 *
	 * @see     JApplication::enqueueMessage()
	 */
	public function redirect($url, $msg = '', $msgType = 'message', $moved = false)
	{
		// Check for relative internal links.
		if (preg_match('#^index2?\.php#', $url))
		{
			$url = JURI::base() . $url;
		}

		// Strip out any line breaks.
		$url = preg_split("/[\r\n]/", $url);
		$url = $url[0];

		/*
		 * If we don't start with a http we need to fix this before we proceed.
		 * We could validly start with something else (e.g. ftp), though this would
		 * be unlikely and isn't supported by this API.
		 */
		if (!preg_match('#^http#i', $url))
		{
			$uri = JURI::getInstance();
			$prefix = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));

			if ($url[0] == '/')
			{
				// We just need the prefix since we have a path relative to the root.
				$url = $prefix . $url;
			}
			else
			{
				// It's relative to where we are now, so lets add that.
				$parts = explode('/', $uri->toString(array('path')));
				array_pop($parts);
				$path = implode('/', $parts) . '/';
				$url = $prefix . $path . $url;
			}
		}

		// If the message exists, enqueue it.
		if (trim($msg))
		{
			$this->enqueueMessage($msg, $msgType);
		}

		// Persist messages if they exist.
		if (count($this->_messageQueue))
		{
			$session = JFactory::getSession();
			$session->set('application.queue', $this->_messageQueue);
		}

		// If the headers have been sent, then we cannot send an additional location header
		// so we will output a javascript redirect statement.
		if (headers_sent())
		{
			echo "<script>document.location.href='" . htmlspecialchars($url) . "';</script>\n";
		}
		else
		{
			$document = JFactory::getDocument();
			jimport('joomla.environment.browser');
			$navigator = JBrowser::getInstance();
			jimport('phputf8.utils.ascii');
			if ($navigator->isBrowser('msie') && !utf8_is_ascii($url))
			{
				// MSIE type browser and/or server cause issues when url contains utf8 character,so use a javascript redirect method
				echo '<html><head><meta http-equiv="content-type" content="text/html; charset=' . $document->getCharset() . '" />'
				. '<script>document.location.href=\'' . htmlspecialchars($url) . '\';</script></head></html>';
			}
			elseif (!$moved && $navigator->isBrowser('konqueror'))
			{
				// WebKit browser (identified as konqueror by Joomla!) - Do not use 303, as it causes subresources
				// reload (https://bugs.webkit.org/show_bug.cgi?id=38690)
				echo '<html><head><meta http-equiv="content-type" content="text/html; charset=' . $document->getCharset() . '" />'
				. '<meta http-equiv="refresh" content="0; url=' . htmlspecialchars($url) . '" /></head></html>';
			}
			else
			{
				// All other browsers, use the more efficient HTTP header method
				header($moved ? 'HTTP/1.1 301 Moved Permanently' : 'HTTP/1.1 303 See other');
				header('Location: ' . $url);
				header('Content-Type: text/html; charset=' . $document->getCharset());
			}
		}
		$this->close();
	}

	/**
	 * Rendering is the process of pushing the document buffers into the template
	 * placeholders, retrieving data from the document and pushing it into
	 * the application response buffer.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	protected function render()
	{
		// Get the JInput object
		$input = $this->input;

		$component = $input->getCmd('option', 'com_login');
		$file = $input->getCmd('tmpl', 'index');

		if ($component == 'com_login')
		{
			$file = 'login';
		}

		// Safety check for when configuration.php root_user is in use.
		$config = JFactory::getConfig();
		$rootUser = $config->get('root_user');
		if (property_exists('JConfig', 'root_user')
			&& (JFactory::getUser()->get('username') == $rootUser || JFactory::getUser()->id === (string) $rootUser)
		)
		{
			JError::raiseNotice(
				200,
				JText::sprintf(
					'JWARNING_REMOVE_ROOT_USER',
					'index.php?option=com_config&task=application.removeroot&' . JSession::getFormToken() . '=1'
				)
			);
		}

		// Setup the document options.
		$options = array(
			'template' => $this->get('theme'),
			'file' => $file . '.php',
			'params' => $this->get('themeParams')
		);

		if ($this->get('themes.base'))
		{
			$options['directory'] = $this->get('themes.base');
		}
		// Fall back to constants.
		else
		{
			$options['directory'] = defined('JPATH_THEMES') ? JPATH_THEMES : (defined('JPATH_BASE') ? JPATH_BASE : __DIR__) . '/themes';
		}

		// Parse the document.
		$this->document->parse($options);

		// Render the document.
		$data = $this->document->render($this->get('cache_enabled'), $options);

		// Set the application output data.
		$this->setBody($data);
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
		$uri = JURI::getInstance();

		if ($this->getCfg('force_ssl') >= 1 && strtolower($uri->getScheme()) != 'https')
		{
			// Forward to https
			$uri->setScheme('https');
			$this->redirect((string) $uri);
		}

		// Trigger the onAfterRoute event.
		JPluginHelper::importPlugin('system');
		$this->triggerEvent('onAfterRoute');
	}
}
