<?php
require_once 'PHPUnit/Framework.php';
require_once '../Scisr.php';

class Scisr_TestCase extends PHPUnit_Framework_TestCase
{

    public function setUp() {
        Scisr_Db::clearDb();
        chdir(dirname(__FILE__));
    }

    public function tearDown() {
        Scisr_Db::clearDb();
        Scisr_ChangeRegistry::clearAll();
        chdir(dirname(__FILE__));
    }

}
