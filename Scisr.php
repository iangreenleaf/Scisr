<?php

spl_autoload_register('scisrAutoload');
require_once('PHP/CodeSniffer.php');

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

class Scisr_CodeSniffer extends PHP_CodeSniffer
{

    public function addListener($listener)
    {
        $this->listeners[] = $listener;
    }
}

$sniffer = new Scisr_CodeSniffer();
$sniffer->addListener(new Scisr_Operations_ChangeClassName());
$sniffer->populateTokenListeners();
$sniffer->processFile(dirname(__FILE__) . '/test.php');
