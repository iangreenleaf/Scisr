<?php
require_once 'PHPUnit/Framework.php';
require_once '../Scisr.php';

/**
 * @runTestsInSeparateProcesses
 * @todo This test case uses shell commands. Rework with some recursive PHP funcs.
 */
class ScisrTest extends PHPUnit_Framework_TestCase
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

    public function testRenameClassAndCompareFile() {
        $this->populateDir(dirname(__FILE__) . '/_files/cliFixture', $this->test_dir);

        $s = new Scisr();
        $s->setRenameClass('Foo', 'Baz');
        $s->addFile($this->test_dir . '/test.php');
        $s->run();

        $this->compareFile(dirname(__FILE__) . '/_files/cliFixture-after-rename-class/test.php', $this->test_dir . '/test.php');
    }

    public function testRenameClassAndCompareFileWithRelativeDir() {
        $this->populateDir(dirname(__FILE__) . '/_files/cliFixture', $this->test_dir);

        $s = new Scisr();
        $s->setRenameClass('Foo', 'Baz');
        $s->addFile($this->rel_test_dir . '/test.php');
        $s->run();

        $this->compareFile(dirname(__FILE__) . '/_files/cliFixture-after-rename-class/test.php', $this->test_dir . '/test.php');
    }

    public function testRenameClassAndCompareDir() {
        $this->populateDir(dirname(__FILE__) . '/_files/cliFixture', $this->test_dir);

        $s = new Scisr();
        $s->setRenameClass('Foo', 'Baz');
        $s->addFile($this->test_dir);
        $s->run();

        $this->compareDir(dirname(__FILE__) . '/_files/cliFixture-after-rename-class', $this->test_dir);
    }

    public function testRenameFileAndCompareDir() {
        $this->populateDir(dirname(__FILE__) . '/_files/renameFileFixture', $this->test_dir);

        $s = new Scisr();
        $s->setRenameFile('stuff.php', 'things.php');
        $s->addFile($this->test_dir);
        $s->run();

        $this->compareDir(dirname(__FILE__) . '/_files/renameFileFixture-after-rename-file', $this->test_dir);
    }

    public function testRenameFileToNewDirAndCompareDir() {
        $this->populateDir(dirname(__FILE__) . '/_files/renameFileFixture', $this->test_dir);

        $s = new Scisr();
        $s->setRenameFile('stuff.php', 'otherfolder/things.php');
        $s->addFile($this->test_dir);
        $s->run();

        $this->compareDir(dirname(__FILE__) . '/_files/renameFileFixture-after-rename-file-new-dir', $this->test_dir);
    }

    public function testChangedFileNotification() {
        $this->markTestIncomplete();
    }

    public function testNotChangedNotification() {
        $this->markTestIncomplete();
    }

    public function testAllNotifications() {
        $this->markTestIncomplete();
    }

}
