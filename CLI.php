<?php

/**
 * Handles command line interaction for Scisr
 */
class Scisr_CLI implements Scisr_Output
{
    const OPT_NONE = 0;
    const OPT_REQUIRED = 1;

    public function __construct()
    {
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
        $shortOptions = 'at';
        $longOptions = array('aggressive', 'timid');
        $options = $this->getopt($args, $shortOptions, $longOptions);
        $unparsedOptions = $options[1];
        $this->parseActionOpts($action, $unparsedOptions);
        $this->parseOtherOpts($options[0]);
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
        case 'rename-method':
            $class = array_shift($params);
            $oldName = array_shift($params);
            $newName = array_shift($params);
            $this->scisr->setRenameMethod($class, $oldName, $newName);
            break;
        }
    }

    private function parseOtherOpts($params)
    {
        foreach ($params as $key => $value) {
            switch ($key) {
            case "a":
            case "aggressive":
                $this->scisr->setEditMode(Scisr::MODE_AGGRESSIVE);
                break;
            case "t":
            case "timid":
                $this->scisr->setEditMode(Scisr::MODE_TIMID);
                break;
            }
        }
    }

    /**
     * For testing use only. Dependency injection.
     * @ignore
     * @param Scisr
     */
    public function setScisr($scisr)
    {
        $this->scisr = $scisr;
    }

    /**
     * Process the CLI arguments and run Scisr
     * @param array $args the command arguments - for use in testing only
     */
    public function process($args=null)
    {
        // Get options from the command line
        if ($args === null) {
            global $argv;
            $args = $argv;
        }
        // Remove our own filename
        array_shift($args);
        // Send to the options handler
        $this->parseOpts($args);
        // Run Scisr
        $this->scisr->run();
    }

    public function outputString($message)
    {
        echo trim($message) . "\n";
    }

    /**
     * Parse command line options
     *
     * Believe me, I'm not happy to be reinventing the wheel here. It's just 
     * that all the other wheels PHP and third parties have to offer in this 
     * department are inferior. This wheel is inferior too, but at least in ways 
     * that work for me.
     *
     * @param array $args the array of arguments from the command line
     * @param string $shortOpts short options as proscribed by PHP's getopt()
     * @param string $longOpts long options as proscribed by PHP's getopt()
     */
    protected function getopt($args, $shortOpts, $longOpts)
    {
        $longOpts = $this->parseLongOpts($longOpts);
        $shortOpts = $this->parseShortOpts($shortOpts);
        $len = count($args);
        $i = 0;
        $parsedOptions = array();
        $nonOptions = array();
        while ($i < $len) {
            $curr = $args[$i];
            if (substr($curr, 0, 2) == '--'
                && array_key_exists($opt = substr($curr, 2), $longOpts)
            ) {
                if ($longOpts[$opt] == self::OPT_REQUIRED) {
                    $value = $args[++$i];
                } else {
                    $value = null;
                }
                $parsedOptions[$opt] = $value;
            } else if (substr($curr, 0, 1) == '-'
                && array_key_exists($opt = substr($curr, 1), $shortOpts)
            ) {
                if ($shortOpts[$opt] == self::OPT_REQUIRED) {
                    $value = $args[++$i];
                } else {
                    $value = null;
                }
                $parsedOptions[$opt] = $value;
            } else {
                $nonOptions[] = $curr;
            }
            $i++;
        }
        return array($parsedOptions, $nonOptions);
    }

    /**
     * Helper function to {@link $this->getopt()}
     */
    private function parseShortOpts($opts)
    {
        $result = array();
        $opts = preg_split('/(\w:?:?)/', $opts, null, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($opts as $opt) {
            if ($opt == '') continue;
            if (substr($opt, -2) == '::') {
                // We aren't handling this case for now
            } else if (substr($opt, -1) == ':') {
                $name = substr($opt, 0, -1);
                $req = self::OPT_REQUIRED;
            } else {
                $name = $opt;
                $req = self::OPT_NONE;
            }
            $result[$name] = $req;
        }
        return $result;
    }

    /**
     * Helper function to {@link $this->getopt()}
     */
    private function parseLongOpts($opts)
    {
        $result = array();
        foreach ($opts as $opt) {
            if (substr($opt, -2) == '==') {
                // We aren't handling this case for now
            } else if (substr($opt, -1) == '=') {
                $name = substr($opt, 0, -1);
                $req = self::OPT_REQUIRED;
            } else {
                $name = $opt;
                $req = self::OPT_NONE;
            }
            $result[$name] = $req;
        }
        return $result;
    }

}
