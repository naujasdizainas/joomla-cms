<?php
/**
 * @package     Joomla.Installation
 * @subpackage  Application
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

/**
 * Joomla! Installation Application class
 *
 * @package     Joomla.Installation
 * @subpackage  Application
 * @since       3.0
 */
final class InstallationApplicationWeb extends JApplicationCms
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
		$this->_name = 'installation';

		// Register the client ID
		$this->_clientId = 2;

		// Set the root in the URI based on the application name
		JURI::root(null, str_ireplace('/' . $this->getName(), '', JURI::base(true)));
	}

	/**
	 * Method to display errors in language parsing
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function debugLanguage()
	{
		ob_start();
		$lang = JFactory::getLanguage();
		echo '<h4>Parsing errors in language files</h4>';
		$errorfiles = $lang->getErrorFiles();

		if (count($errorfiles))
		{
			echo '<ul>';

			foreach ($errorfiles as $file => $error)
			{
				echo "<li>$error</li>";
			}
			echo '</ul>';
		}
		else
		{
			echo '<pre>None</pre>';
		}

		echo '<h4>Untranslated Strings</h4>';
		echo '<pre>';
		$orphans = $lang->getOrphans();

		if (count($orphans))
		{
			ksort($orphans, SORT_STRING);

			foreach ($orphans as $key => $occurance)
			{
				$guess = str_replace('_', ' ', $key);

				$parts = explode(' ', $guess);
				if (count($parts) > 1)
				{
					array_shift($parts);
					$guess = implode(' ', $parts);
				}

				$guess = trim($guess);

				$key = trim(strtoupper($key));
				$key = preg_replace('#\s+#', '_', $key);
				$key = preg_replace('#\W#', '', $key);

				// Prepare the text
				$guesses[] = $key . '="' . $guess . '"';
			}

			echo "\n\n# " . $file . "\n\n";
			echo implode("\n", $guesses);
		}
		else
		{
			echo 'None';
		}
		echo '</pre>';
		$debug = ob_get_clean();
		$this->appendBody($debug);
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
	public function dispatch()
	{
		try
		{
			// Load the document to the API
			$this->loadDocument();

			// Set up the params
			$document	= $this->getDocument();
			$config		= $this->config;
			$user		= JFactory::getUser();

			// Register the document object with JFactory
			JFactory::$document = $document;

			switch ($document->getType())
			{
				case 'html' :
					// Set metadata
					$document->setTitle(JText::_('INSTL_PAGE_TITLE'));
					break;
				default :
					break;
			}

			// Define component path
			define('JPATH_COMPONENT', JPATH_BASE);
			define('JPATH_COMPONENT_SITE', JPATH_SITE);
			define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR);

			// Start the output buffer.
			ob_start();

			// Execute the task.
			$controller = JControllerLegacy::getInstance('Installation');
			$controller->execute($this->input->getCmd('task'));
			$controller->redirect();

			// Get output from the buffer and clean it.
			$contents = ob_get_contents();
			ob_end_clean();

			$document->setBuffer($contents, 'installation');
			$document->setTitle(JText::_('INSTL_PAGE_TITLE'));
		}

		// Mop up any uncaught exceptions.
		catch (Exception $e)
		{
			$code = $e->getCode();
			echo $e->getMessage();
			jexit($code);
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
		$this->initialiseApp();

		// Dispatch the application
		$this->dispatch();
	}

	/**
	 * Returns the language code and help url set in the localise.xml file.
	 * Used for forcing a particular language in localised releases.
	 *
	 * @return  mixed  False on failure, array on success.
	 *
	 * @since   3.0
	 */
	public function getLocalise()
	{
		$xml = simplexml_load_file(JPATH_INSTALLATION . '/localise.xml');

		if (!$xml)
		{
			return false;
		}

		// Check that it's a localise file
		if ($xml->getName() != 'localise')
		{
			return false;
		}

		$ret = array();

		$ret['language'] = (string) $xml->forceLang;
		$ret['helpurl'] = (string) $xml->helpurl;
		$ret['debug'] = (string) $xml->debug;
		$ret['sampledata'] = (string) $xml->sampledata;

		return $ret;
	}

	/**
 	 * Returns the installed language files in the administrative and
 	 * front-end area.
 	 *
 	 * @param   boolean  $db
 	 *
 	 * @return  array  Array with installed language packs in admin and site area
	 *
	 * @since   3.0
 	 */
 	public function getLocaliseAdmin($db = false)
 	{
		jimport('joomla.filesystem.folder');

		// Read the files in the admin area
		$path = JLanguage::getLanguagePath(JPATH_ADMINISTRATOR);
		$langfiles['admin'] = JFolder::folders($path);

		// Read the files in the site area
		$path = JLanguage::getLanguagePath(JPATH_SITE);
		$langfiles['site'] = JFolder::folders($path);

		if ($db)
		{
			$langfiles_disk = $langfiles;
			$langfiles = array();
			$langfiles['admin'] = array();
			$langfiles['site'] = array();
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('element','client_id')));
			$query->from($db->quoteName('#__extensions'));
			$query->where($db->quoteName('type') . ' = ' . $db->quote('language'));
			$db->setQuery($query);
			$langs = $db->loadObjectList();
			foreach ($langs as $lang)
			{
				switch ($lang->client_id)
				{
					// Site
					case 0:
						if (in_array($lang->element, $langfiles_disk['site']))
						{
							$langfiles['site'][] = $lang->element;
						}
						break;

					// Administrator
					case 1:
						if (in_array($lang->element, $langfiles_disk['admin']))
						{
							$langfiles['admin'][] = $lang->element;
						}
						break;
				}
			}
		}

		return $langfiles;
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
		if ($params)
		{
			$template = new stdClass;
			$template->template = 'template';
			$template->params = new JRegistry;
			return $template;
		}

		return 'template';
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
		// Get the localisation information provided in the localise.xml file.
		$forced = $this->getLocalise();

		// Check the request data for the language.
		if (empty($options['language']))
		{
			$requestLang = $this->input->getCmd('lang', null);
			if (!is_null($requestLang))
			{
				$options['language'] = $requestLang;
			}
		}

		// Check the session for the language.
		if (empty($options['language']))
		{
			$sessionLang = $this->getSession()->get('setup.language');
			if (!is_null($sessionLang))
			{
				$options['language'] = $sessionLang;
			}
		}

		// This could be a first-time visit - try to determine what the client accepts.
		if (empty($options['language']))
		{
			if (!empty($forced['language']))
			{
				$options['language'] = $forced['language'];
			}
			else
			{
				$options['language'] = JLanguageHelper::detectLanguage();
				if (empty($options['language']))
				{
					$options['language'] = 'en-GB';
				}
			}
		}

		// Give the user English
		if (empty($options['language']))
		{
			$options['language'] = 'en-GB';
		}

		// Set the language in the class
		$this->config->set('language', $options['language']);
		$this->config->set('debug_lang', $forced['debug']);
		$this->config->set('sampledata', $forced['sampledata']);
	}

	/**
	 * Allows the application to load a custom or default session.
	 *
	 * The logic and options for creating this object are adequately generic for default cases
	 * but for many applications it will make sense to override this method and create a session,
	 * if required, based on more specific needs.
	 *
	 * @param   JSession  $session  An optional session object. If omitted, the session is created.
	 *
	 * @return  JApplicationWeb This method is chainable.
	 *
	 * @since   3.0
	 */
	public function loadSession(JSession $session = null)
	{
		$options = array();
		$options['name'] = JApplication::getHash($this->config->get('session_name'));

		$session = JFactory::getSession($options);
		$session->initialise($this->input);
		$session->start();
		if (!$session->get('registry') instanceof JRegistry)
		{
			// Registry has been corrupted somehow
			$session->set('registry', new JRegistry('session'));
		}

		// Set the session object.
		$this->session = $session;

		return $this;
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
	public function render()
	{
		$file = $this->input->getCmd('tmpl', 'index');

		$options = array(
			'template' => 'template',
			'file' => $file . '.php',
			'directory' => JPATH_THEMES,
			'params' => '{}'
		);

		// Parse the document.
		$this->document->parse($options);

		// Render the document.
		$data = $this->document->render($this->get('cache_enabled'), $options);

		// Set the application output data.
		$this->setBody($data);

		if ($this->config->get('debug_lang'))
		{
			$this->debugLanguage();
		}
	}

	/**
	 * Set configuration values
	 *
	 * @param   array   $vars       Array of configuration values
	 * @param   string  $namespace  The namespace
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function setCfg(array $vars = array(), $namespace = 'config')
	{
		$this->config->loadArray($vars, $namespace);
	}
}
