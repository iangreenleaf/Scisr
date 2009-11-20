<?php
require_once(dirname(__FILE__) . '/Console/Getopt.php');

/**
 * Handles command line interaction for Scisr
 */
class Scisr_CLI implements Scisr_Output
{

    public function __construct()
    {
        $this->getopts = new Console_Getopt();
        $this->scisr = new Scisr($this);
    }

    /**
     * Parse command line options
     * @param array $args array of options passed to the program
     * @todo catch PEAR errors
     */
    protected function parseOpts($args)
    {
        // Get the action name
        // TODO validate it
        $action = array_shift($args);
        // Parse all other options
        $shortOptions = '';
        $longOptions = array();
        // Don't know why it's getopt2(), but getopt() seems to misbehave
        $options = $this->getopts->getopt2($args, $shortOptions, $longOptions);
        $unparsedOptions = $options[1];
        $this->parseActionOpts($action, $unparsedOptions);
        $this->scisr->addFiles($unparsedOptions);
    }

    private function parseActionOpts($action, &$params)
    {
        $actionOpts = array();
        switch ($action) {
        case 'rename-class':
            $oldName = array_shift($params);
            $newName = array_shift($params);
            $this->scisr->setRenameClass($oldName, $newName);
            break;
        }
    }

    /**
     * For testing use only. Dependency injection.
     * @ignore
     * @param Scisr
     */
    public function setScisr($scisr) {
        $this->scisr = $scisr;
    }

    /**
     * Process the CLI arguments and run Scisr
     * @param array $args the command arguments - for use in testing only
     */
    public function process($args=null)
    {
        // Turn off strict error reporting for Console_Getopt
        error_reporting(E_ERROR);
        // Get options from the command line
        if ($args === null) {
            $args = $this->getopts->readPHPArgv();
        }
        // Remove our own filename
        array_shift($args);
        // Send to the options handler
        $this->parseOpts($args);
        // Turn strict error reporting back on
        error_reporting(E_ALL | E_STRICT);
        // Run Scisr
        $this->scisr->run();
    }

    public function outputString($message)
    {
        echo trim($message) . "\n";
    }
}
