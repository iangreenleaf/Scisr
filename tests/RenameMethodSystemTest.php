<?php
require_once 'MultipleFileTest.php';

/**
 * @runTestsInSeparateProcesses
 */
class RenameMethodSystemTest extends Scisr_Tests_MultipleFileTestCase
{

    public function testRenameMethodAndCompareFile() {
        $this->populateDir(dirname(__FILE__) . '/_files/cliFixture', $this->test_dir);

        $s = new Scisr();
        $s->setRenameMethod('Foo', 'bar', 'quark', false);
        $s->addFile($this->test_dir . '/test.php');
        $s->run();

        $this->compareFile(dirname(__FILE__) . '/_files/cliFixture-after-rename-method/test.php', $this->test_dir . '/test.php');
    }

    public function testRenameMethodAndCompareDir() {
        $this->populateDir(dirname(__FILE__) . '/_files/cliFixture', $this->test_dir);

        $s = new Scisr();
        $s->setRenameMethod('Foo', 'bar', 'quark', false);
        $s->addFile($this->test_dir);
        $s->run();

        $this->compareFile(dirname(__FILE__) . '/_files/cliFixture-after-rename-method', $this->test_dir);
    }

    public function testRenameMethodWithIncludedFile() {
        $this->populateDir(dirname(__FILE__) . '/_files/renameMethodWithIncludesFixture', $this->test_dir);

        $s = new Scisr();
        $s->setRenameMethod('Foo', 'bar', 'baz', false);
        $s->addFile($this->test_dir);
        $s->run();

        $this->compareDir(dirname(__FILE__) . '/_files/renameMethodWithIncludesFixture-after-rename', $this->test_dir);
    }

}
