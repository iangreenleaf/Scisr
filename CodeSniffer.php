<?php
/**
 * Handles the job of finding and parsing files.
 */
class Scisr_CodeSniffer extends PHP_CodeSniffer
{

    /**
     * If we can cache the results of process()
     * @var boolean
     */
    private $cacheable = false;

    public function __construct($verbosity=0, $tabWidth=0)
    {
        // PHP_CodeSniffer messes up the cwd, so restore it after we construct
        $cwd = getcwd();
        parent::__construct($verbosity, $tabWidth);
        chdir($cwd);
        $this->setAllowedFileExtensions(array('php', 'inc', 'html'));
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

    public function process($files, $local=false, $cacheable=false)
    {
        $this->_cacheable = $cacheable;
        parent::process($files, $local);
    }

    public function processFile($file, $contents=null)
    {
        if ($this->_cacheable) {
            // If we have cached results and they're not stale, don't bother processing
            $cacheTime = Scisr_Db_Files::getTimeParsed($file);
            $stat = stat($file);
            $mtime = $stat['mtime'];
            if ($cacheTime !== null && $cacheTime > $mtime) {
                return;
            }
        }

        parent::processFile($file, $contents);

        if ($this->_cacheable) {
            Scisr_Db_Files::registerFile($file);
        }
    }
}
