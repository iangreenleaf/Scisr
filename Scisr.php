<?php

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
     * @param PHP_CodeSniffer_Sniff the listener to add
     */
    public function addListener(PHP_CodeSniffer_Sniff $listener)
    {
        $this->listeners[] = $listener;
    }
}

// Fire up the sniffer
$sniffer = new Scisr_CodeSniffer();
$sniffer->addListener(new Scisr_Operations_ChangeClassName());
$sniffer->populateTokenListeners();
// For now, just run it against a test file
$sniffer->processFile(dirname(__FILE__) . '/test.php');
