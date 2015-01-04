<?php
/**
 * Joomla! System plugin - NewRelic
 *
 * @author Yireo (info@yireo.com)
 * @copyright Copyright 2015
 * @license GNU Public License
 * @link http://www.yireo.com
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

// Import the parent class
jimport( 'joomla.plugin.plugin' );

/*
 * Plugin-class
 */
class plgSystemNewRelic extends JPlugin
{
    /**
     * Constructor
     *
     * @access public
     * @param object $subject
     * @param array $config
     */
    public function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);

        // Load tracers
        if ($this->isEnabled() == true) {

            // Set the app-name
            $appname = trim($this->params->get('appname'));
            $license = trim($this->params->get('license'));
            $xmit = true; // @warning: This gives a slight performance overhead - check the NewRelic docs for details
            if(!empty($appname)) newrelic_set_appname($appname, $license, $xmit);

            // Common settings
            newrelic_capture_params(true);

            // Custom tracers
            newrelic_add_custom_tracer('JApplication::dispatch');
        }
    }

    /**
     * Event onAfterInitialise
     *
     * @access public
     * @param null
     * @return null
     */
    public function onAfterInitialise()
    {
        // Don't do anything if NewRelic is not installed
        if ($this->isEnabled() == false) return false;
    }

    /**
     * Event onAfterInitialise
     *
     * @access public
     * @param null
     * @return null
     */
    public function onAfterRender()
    {
        // Don't do anything if NewRelic is not installed
        if ($this->isEnabled() == false) return false;

        // Load variables
        $application = JFactory::getApplication();
        $document = JFactory::getDocument();
        $user = JFactory::getUser();

        // Set a flag for the current user
        $username = ($user->id > 0) ? $user->username : 'Guest';
        newrelic_add_custom_parameter('user', $username);

        // Set a flag for the current component
        $component = JRequest::getCmd('option');
        newrelic_add_custom_parameter('component', $component);

        // Set user attributes
        newrelic_set_user_attributes($username, $username, $component);

        // Modify the body
        $body = JResponse::getBody();
        $newRelicHeader = newrelic_get_browser_timing_header();
        $newRelicFooter = newrelic_get_browser_timing_footer();
        $body = str_replace('<title>', $newRelicHeader.'<title>', $body);
        $body = str_replace('</body>', $newRelicFooter.'</body>', $body);
        JResponse::setBody($body);
    }

    /*
     * Helper method to check if NewRelic is available
     * 
     * @access public
     * @param null
     * @return boolean
     */
    protected function isEnabled()
    {
        if (extension_loaded('newrelic') == true) {
            $application = JFactory::getApplication();
            if ($application->isSite() == false) {
                return false;
            }

            if(isset($_SERVER['PHP_SELF']) && strpos($_SERVER['PHP_SELF'], 'index.php') === false) {
                return false;
            }
            return true;
        }

        return false;
    }
}

/*
 * @todo:
 * - newrelic_notice_error()
 */
