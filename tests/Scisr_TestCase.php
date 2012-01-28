<?php
require_once 'ScisrRunner.php';

class Scisr_TestCase extends PHPUnit_Framework_TestCase
{

    private $db;

    public function setUp() {
        $this->db = new PDO('sqlite::memory:');
        chdir(dirname(__FILE__));
    }

    public function tearDown() {
        Scisr_ChangeRegistry::clearAll();
        chdir(dirname(__FILE__));
    }

    public function getDb()
    {
        return $this->db;
    }

    public function getScisr($className = 'ScisrRunner')
    {
        return ScisrRunner::createRunner($className, $this->getDb());
    }

}
