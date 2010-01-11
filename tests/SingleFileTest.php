<?php
require_once 'PHPUnit/Framework.php';
require_once '../Scisr.php';

class Scisr_SingleFileTest extends PHPUnit_Framework_TestCase
{

    public function setUp() {
        mkdir(dirname(__FILE__) . '/d1');
        mkdir(dirname(__FILE__) . '/d1/d2');
        $this->test_file = dirname(__FILE__) . '/d1/d2/myTestFile.php';
        touch($this->test_file);
    }

    public function tearDown() {
        unlink($this->test_file);
        rmdir(dirname(__FILE__) . '/d1/d2');
        rmdir(dirname(__FILE__) . '/d1');
    }

    public function populateFile($contents) {
        $handle = fopen($this->test_file, 'w');
        fwrite($handle, $contents);
    }

    public function compareFile($expected) {
        $contents = file_get_contents($this->test_file);
        $this->assertEquals($expected, $contents);
    }

}
