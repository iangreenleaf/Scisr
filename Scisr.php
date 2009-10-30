<?php

// Turn on error reporting
error_reporting(E_ALL | E_STRICT);
// Register our autoloader
spl_autoload_register('scisrAutoload');
// Include the main CodeSniffer file (this will register its own autoloader as well)
require_once('PHP/CodeSniffer.php');

/**
 * An autoload function for Scisr
 * @param string
 */
function scisrAutoload($className)
{
    if (strpos($className, 'Scisr_') === 0) {
        $className = substr($className, 6);
        $path = str_replace('_', '/', $className).'.php';
        if (is_file(dirname(__FILE__).'/'.$path) === true) {
            include dirname(__FILE__).'/'.$path;
        }
    }
}

/**
 * Handles the job of finding and parsing files.
 */
class Scisr_CodeSniffer extends PHP_CodeSniffer
{

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

class Scisr
{

    /**
     * @var array
     */
    protected $listeners = array();

    public function setRenameClass($oldClass, $newClass)
    {
        $this->listeners[] = new Scisr_Operations_ChangeClassName($oldClass, $newClass);
    }

    public function addFile($filename) {
        $this->files[] = $filename;
    }

    public function run()
    {

        // Run the sniffer
        $sniffer = new Scisr_CodeSniffer();
        foreach ($this->listeners as $listener) {
            $sniffer->addListener($listener);
        }
        $sniffer->process($this->files);

        // Now make the actual changes that we've planned
        $changes = Scisr_ChangeRegistry::get('storedChanges');
        foreach ($changes as $file) {
            $file->process();
        }

    }

}

