<?php
require_once 'PHPUnit/Framework.php';
require_once '../CLI.php';

/**
 * @runTestsInSeparateProcesses
 */
class CLITest extends PHPUnit_Framework_TestCase
{
    public function testRenameClassCLI() {
        $this->markTestIncomplete();
        $args = array('scisr_executable', 'rename-class', 'Foo', 'Baz', $this->test_dir);
        $c = new Scisr_CLI();
        $c->process($args);
    }
}
