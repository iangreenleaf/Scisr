<?php

/**
 * Handles command line interaction for Scisr
 *
 * @todo We could extend PHP_CodeSniffer_CLI and make use of some of the parsing 
 * stuff instead of rolling our own. We would gain some flexibility in where 
 * flags can be placed in the list, and it would fit reasonably well with the 
 * need for "scisr command [variable arity args]".
 *
 * Downsides are that it won't quite work out of the box ($argv overrides our 
 * testing stuff), and it's not flexible about --flag=val vs. --flag val.
 */
class Scisr_CLI implements Scisr_Output
{
    const OPT_NONE = 0;
    const OPT_REQUIRED = 1;

    /**
     * If true, quit without running and show usage instead
     * @var boolean
     */
    private $showHelp = false;

    public function __construct($output=null)
    {
        if ($output === null) {
            $output = new Scisr_CLI_Output();
        }
        $this->output = $output;
        $this->scisr = new Scisr($this->output);
    }

    /**
     * Parse command line options
     * @param array $args array of options passed to the program
     */
    protected function parseOpts($args)
    {
        // Parse all other options
        $shortOptions = 'athi:e:';
        $longOptions = array('aggressive', 'timid', 'help', 'ignore=', 'extensions=');
        $options = $this->getopt($args, $shortOptions, $longOptions);
        $unparsedOptions = $options[1];

        $this->parseOtherOpts($options[0]);
        if ($this->showHelp) {
            return;
        }

        $this->parseActionOpts($unparsedOptions);

        if (count($unparsedOptions) == 0) {
            throw new Exception('No paths provided to examine');
        }
        $this->scisr->addFiles($unparsedOptions);
    }

    private function parseActionOpts(&$params)
    {
        // Get the action name
        $action = $this->getArg($params);

        $actionOpts = array();
        switch ($action) {
        case 'rename-class':
            $oldName = $this->getArg($params);
            $newName = $this->getArg($params);
            $this->scisr->setRenameClass($oldName, $newName);
            break;
        case 'rename-method':
            $class = $this->getArg($params);
            $oldName = $this->getArg($params);
            $newName = $this->getArg($params);
            $this->scisr->setRenameMethod($class, $oldName, $newName);
            break;
        case 'rename-file':
            $oldName = $this->getArg($params);
            $newName = $this->getArg($params);
            $this->scisr->setRenameFile($oldName, $newName);
            break;
        default:
            throw new Exception("Command \"$action\" not recognized");
        }
    }

    private function getArg(&$params)
    {
        $arg = array_shift($params);
        if ($arg === null) {
            throw new Exception("Not enough arguments");
        }
        return $arg;
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
            case "i":
            case "ignore":
                // We doctor and pass this value in to let phpcs run a pattern on it
                $fakekey = 'ignore=' . $value;
                $cli = new PHP_CodeSniffer_CLI();
                $result = $cli->processLongArgument($fakekey, null, array());
                $this->scisr->setIgnorePatterns($result['ignored']);
                break;
            case "e":
            case "extensions":
                // We doctor and pass this value in to let phpcs run a pattern on it
                $fakekey = 'extensions=' . $value;
                $cli = new PHP_CodeSniffer_CLI();
                $result = $cli->processLongArgument($fakekey, null, array());
                $this->scisr->setAllowedFileExtensions($result['extensions']);
                break;
            case "help":
                $this->showHelp = true;
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
        try {
            $this->parseOpts($args);
            if ($this->showHelp) {
                $this->printUsage();
                return 0;
            }
        } catch (Exception $e) {
            $this->outputString('Error: ' . $e->getMessage());
            $this->outputString("\n");
            $this->printUsage();
            return 2;
        }
        // Run Scisr
        $this->scisr->run();
        return 0;
    }

    // We proxy output through here for simplicity
    public function outputString($message)
    {
        $this->output->outputString($message);
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
            if (substr($curr, 0, 2) == '--') {

                $opt = substr($curr, 2);

                // If there is a "=", split this argument into opt and value
                if (($pos = strpos($opt, '=')) !== false) {
                    $value = substr($opt, $pos + 1);
                    $opt = substr($opt, 0, $pos);
                    if ($longOpts[$opt] != self::OPT_REQUIRED) {
                        throw new Exception("Value given for option \"$opt\", which does not accept a value");
                    }
                } else if ($longOpts[$opt] == self::OPT_REQUIRED) {
                    $value = $args[++$i];
                } else {
                    $value = null;
                }

                if (!array_key_exists($opt, $longOpts)) {
                    throw new Exception("Option \"$opt\" not recognized");
                }

                $parsedOptions[$opt] = $value;

            } else if (substr($curr, 0, 1) == '-') {

                $opt = substr($curr, 1, 1);

                if (strlen($curr) > 2) {
                    $value = substr($curr, 2);
                    if ($shortOpts[$opt] != self::OPT_REQUIRED) {
                        throw new Exception("Value given for option \"$opt\", which does not accept a value");
                    }
                } else if ($shortOpts[$opt] == self::OPT_REQUIRED) {
                    $value = $args[++$i];
                } else {
                    $value = null;
                }

                if (!array_key_exists($opt, $shortOpts)) {
                    throw new Exception("Option \"$opt\" not recognized");
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

    /**
     * Print usage information to our output handler
     */
    public function printUsage()
    {
        $usage = <<<EOL
Usage:
  scisr.php rename-class OldName NewName [options] [files]
  scisr.php rename-method OwningClassName oldMethodName newMethodName [options] [files]
  scisr.php rename-file old/file_name new/dir/new_file_name [options] [files]

[files] is any number of files and/or directories to be searched and modified.

Options:
  -t, --timid           Do not make changes to the files, just list filenames
                        with line numbers.

  -a, --aggressive      Make changes even when we're not sure they're correct.

  -e<extensions>, --extensions=<extensions>
                        Specify a comma-separated list of allowed file extensions.

  -i<patterns>, --ignore=<patterns>
                        Specify a comma-separated list of patterns used to
                        ignore directories and files.

  -h, --help            Print usage instructions.
EOL;
        $this->outputString($usage);

    }

}
