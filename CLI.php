<?php

/**
 * Handles command line interaction for Scisr
 */
class Scisr_CLI
{

    public function __construct()
    {
        $this->getopts = new Console_Getopt();
        $this->scisr = new Scisr();
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

    public function process()
    {
        // Get options from the command line
        $args = $this->getopts->readPHPArgv();
        // Remove our own filename
        array_shift($args);
        // Send to the options handler
        $this->parseOpts($args);

        $this->scisr->run();

    }
}
