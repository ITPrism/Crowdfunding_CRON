<?php
/**
 * @package      Crowdfunding
 * @subpackage   CLI
 * @author       Todor Iliev
 * @copyright    Copyright (C) 2015 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license      GNU General Public License version 3 or later; see LICENSE.txt
 */

/**
 * Crowdfunding CRON.
 *
 * This is a command-line script to help with management of Crowdfunding Platform.
 *
 * Called with no arguments: php crowdfunding_cron.php
 *                           Load CRON plug-ins and triggers event "onCronExecute".
 *
 * Called with --notify:     php crowdfunding_cron.php --notify
 *                           Load CRON plug-ins and triggers event "onCronNotify".
 *
 * Called with --update:     php crowdfunding_cron.php --update
 *                           Load CRON plug-ins and triggers event "onCronUpdate".
 */

// Make sure we're being called from the command line, not a web interface
if (PHP_SAPI !== 'cli') {
    die('This is a command line only application.');
}

// We are a valid entry point.
const _JEXEC = 1;

// Load system defines
if (file_exists(dirname(__DIR__) . '/defines.php')) {
    require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES')) {
    define('JPATH_BASE', dirname(__DIR__));
    require_once JPATH_BASE . '/includes/defines.php';
}

// Get the framework.
require_once JPATH_LIBRARIES . '/import.legacy.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';

// Import the configuration.
require_once JPATH_CONFIGURATION . '/configuration.php';

// Configure error reporting to maximum for CLI output.
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * A command line cron job to run the crowdfunding platform cron job.
 *
 * @package      Crowdfunding
 * @subpackage   CLI
 */
class CrowdfundingCronCli extends JApplicationCli
{
    /**
     * Start time for the process.
     *
     * @var    string
     */
    private $time = null;

    public function doExecute()
    {
        // Print a blank line.
        $this->out('Crowdfunding CRON');
        $this->out('============================');

        // Initialize the time value.
        $this->time = microtime(true);

        // Remove the script time limit.
        @set_time_limit(0);

        // Fool the system into thinking we are running as JSite with Smart Search as the active component.
        $_SERVER['HTTP_HOST'] = 'domain.com';
        JFactory::getApplication('site');

        // Get options.
        $notify  = $this->input->getString('notify', false);
        $update  = $this->input->getString('update', false);

        $context = $this->input->getCmd('context');

        // Import the finder plugins.
        JPluginHelper::importPlugin('crowdfundingcron');

        try {
            if ($notify) {
                $context = 'com_crowdfunding.cron.notify.' . $context;
                $this->out('notify context: '.$context);
                $this->out('============================');

                JEventDispatcher::getInstance()->trigger('onCronNotify', array($context));
            } elseif ($update) {
                $context = 'com_crowdfunding.cron.update.' . $context;
                $this->out('update context: '.$context);
                $this->out('============================');

                JEventDispatcher::getInstance()->trigger('onCronUpdate', array($context));

            } else { // Execute

                $context = 'com_crowdfunding.cron.execute.' . $context;
                $this->out('execute context: '.$context);
                $this->out('============================');

                JEventDispatcher::getInstance()->trigger('onCronExecute', array($context));
            }

        } catch (Exception $e) {
            $this->logErrors($e->getMessage());
            $this->out($e->getMessage());
        }

        // Total reporting.
        $this->out(JText::sprintf('Total Processing Time: %s seconds.', round(microtime(true) - $this->time, 3)), true);

        // Print a blank line at the end.
        $this->out();
    }

    protected function logErrors($content)
    {
        $config = JFactory::getConfig();

        if (is_writable($config->get('log_path'))) {
            $logFile = $config->get('log_path').DIRECTORY_SEPARATOR.'error_cron.txt';
            file_put_contents($logFile, $content .'\n', FILE_APPEND);
        }
    }
}

// Instantiate the application object, passing the class name to JCli::getInstance
// and use chaining to execute the application.
JApplicationCli::getInstance('CrowdfundingCronCli')->execute();
