<?php
require_once 'PHPUnit/Framework.php';
require_once '../Scisr.php';

/**
 * @runTestsInSeparateProcesses
 * @todo This test case uses shell commands. Rework with some recursive PHP funcs.
 */
class CLITest extends PHPUnit_Framework_TestCase
{
    public function setUp() {
        $this->test_dir = dirname(__FILE__) . '/myTestDir';
    }

    public function tearDown() {
        // Cheat and just use a shell command
        shell_exec("rm -r $this->test_dir");
    }

    public function populateDir($fixture, $dir) {
        // Cheat and just use a shell command
        shell_exec("cp -r $fixture $dir");
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

    public function testRenameAndCompareFile() {
        $this->populateDir(dirname(__FILE__) . '/_files/cliFixture', $this->test_dir);

        $s = new Scisr();
        $s->setRenameClass('Foo', 'Baz');
        $s->addFile($this->test_dir . '/test.php');
        $s->run();

        $this->compareFile(dirname(__FILE__) . '/_files/cliFixture-after-rename-class/test.php', $this->test_dir . '/test.php');

    }

    public function testRenameAndCompareDir() {
        $this->populateDir(dirname(__FILE__) . '/_files/cliFixture', $this->test_dir);

        $s = new Scisr();
        $s->setRenameClass('Foo', 'Baz');
        $s->addFile($this->test_dir);
        $s->run();

        $this->compareDir(dirname(__FILE__) . '/_files/cliFixture-after-rename-class', $this->test_dir);

    }

}
