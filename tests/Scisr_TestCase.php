<?php
require_once 'PHPUnit/Framework.php';
require_once '../Scisr.php';

class Scisr_TestCase extends PHPUnit_Framework_TestCase
{

    public function setUp() {
        chdir(dirname(__FILE__));
    }

    public function tearDown() {
        Scisr_ChangeRegistry::clearAll();
        chdir(dirname(__FILE__));
    }

    public function getDb()
    {
        return new PDO('sqlite::memory:');
    }

    public function getScisr($className = 'Scisr')
    {
        return Scisr::createScisr($className, $this->getDb());
    }

}
