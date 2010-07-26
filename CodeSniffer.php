<?php
/**
 * Handles the job of finding and parsing files.
 */
class Scisr_CodeSniffer extends PHP_CodeSniffer
{
    private $_dbFiles;

    /**
     * If we can cache the results of process()
     * @var boolean
     */
    private $cacheable = false;

    public function __construct(Scisr_Db_Files $dbFiles, $verbosity=0, $tabWidth=0)
    {
        $this->_dbFiles = $dbFiles;
        // PHP_CodeSniffer messes up the cwd, so restore it after we construct
        $cwd = getcwd();
        parent::__construct($verbosity, $tabWidth);
        chdir($cwd);
        $this->setAllowedFileExtensions(array('php', 'inc', 'html'));
    }

    public function __destruct()
    {
        // no need to reset cwd
    }

    /**
     * Add a listener
     * @param PHP_CodeSniffer_Sniff the listener to add. Unlike
     * PHP_CodeSniffer's methods, this one takes an instantiated object.
     */
    public function addListener(PHP_CodeSniffer_Sniff $listener)
    {
        $this->listeners[] = $listener;
    }

    /**
     * Clear all registered listeners
     */
    public function clearListeners()
    {
        $this->listeners = array();
    }

    public function process($files, $local=false, $cacheable=false)
    {
        $this->_cacheable = $cacheable;
        parent::process($files, $local);
    }

    public function processFile($file, $contents=null)
    {
        if ($this->_cacheable) {
            // If we have cached results and they're not stale, don't bother processing
            $cacheTime = $this->_dbFiles->getTimeParsed($file);
            $mtime = filemtime($file);
            if ($cacheTime !== null && $cacheTime > $mtime) {
                return;
            }
        }

        parent::processFile($file, $contents);

        if ($this->_cacheable) {
            $this->_dbFiles->registerFile($file);
        }
    }
}
