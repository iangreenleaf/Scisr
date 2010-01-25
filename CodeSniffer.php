<?php
/**
 * Handles the job of finding and parsing files.
 */
class Scisr_CodeSniffer extends PHP_CodeSniffer
{

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
}
