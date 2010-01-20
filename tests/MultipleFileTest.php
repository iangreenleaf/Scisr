<?php
require_once 'PHPUnit/Framework.php';
require_once '../Scisr.php';

/**
 * @runTestsInSeparateProcesses
 * @todo This test case uses shell commands. Rework with some recursive PHP funcs.
 */
class Scisr_Tests_MultipleFileTestCase extends PHPUnit_Framework_TestCase
{
    public function setUp() {
        $this->rel_test_dir = 'myTestDir';
        $this->test_dir = dirname(__FILE__) . '/' . $this->rel_test_dir;
        mkdir($this->test_dir);
    }

    public function tearDown() {
        // Cheat and just use a shell command
        shell_exec("rm -r $this->test_dir");
    }

    public function populateDir($fixture, $dir) {
        // Cheat and just use a shell command
        shell_exec("cp -r $fixture/* $dir");
    }

    public function compareDir($expected, $dir) {
        // Cheat and just use a shell command
        $diff = shell_exec("diff -r $expected $dir");
        $this->assertEquals('', $diff);
    }

    public function compareFile($expected, $actual) {
        $contents = file_get_contents($actual);
        $expectedContents = file_get_contents($expected);
        $this->assertEquals($expectedContents, $contents);
    }

}
