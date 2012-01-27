<?php
require_once '../ScisrRunner.php';

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

    public function getScisr($className = 'ScisrRunner')
    {
        return ScisrRunner::createRunner($className, $this->getDb());
    }

}
