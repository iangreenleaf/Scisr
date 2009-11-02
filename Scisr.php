<?php

// Turn on error reporting
error_reporting(E_ALL | E_STRICT);
// Register our autoloader
spl_autoload_register('scisrAutoload');
// Include the main CodeSniffer file (this will register its own autoloader as well)
require_once(dirname(__FILE__) . '/PHP/CodeSniffer.php');

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

/**
 * The main Scisr controller
 *
 * Coordinates the tasks to take place for the given action(s)
 */
class Scisr
{

    /**
     * CodeSniffer listener objects to be used
     * @var array
     */
    protected $_listeners = array();

    /**
     * Rename a class
     * @param string $oldClass the class to be renamed
     * @param string $newClass the new class name to be given
     */
    public function setRenameClass($oldClass, $newClass)
    {
        $this->_listeners[] = new Scisr_Operations_ChangeClassName($oldClass, $newClass);
    }

    /**
     * Rename a class method
     * @param string $class the class that contains the method to be renamed
     * @param string $oldMethod the method to be renamed
     * @param string $newMethod the new method name
     */
    public function setRenameMethod($class, $oldMethod, $newMethod)
    {
        $this->_listeners[] = new Scisr_Operations_ChangeMethodName($class, $oldMethod, $newMethod);
    }

    /**
     * Add a file or directory to be parsed
     * @param string $filename the path to the file or directory
     */
    public function addFile($filename)
    {
        $this->files[] = $filename;
    }

    /**
     * Add multiple files or directories to be parsed
     * @param array an array of file or directory paths
     */
    public function addFiles($fileArray)
    {
        array_map(array($this, 'addFile'), $fileArray);
    }

    /**
     * Perform the requested changes
     */
    public function run()
    {

        // Run the sniffer
        $sniffer = new Scisr_CodeSniffer();
        foreach ($this->_listeners as $listener) {
            $sniffer->addListener($listener);
        }
        $sniffer->process($this->files);

        // Now make the actual changes that we've planned
        $changes = Scisr_ChangeRegistry::get('storedChanges');
        //TODO notice if there are no changes
        foreach ($changes as $file) {
            $file->process();
        }

    }

}

