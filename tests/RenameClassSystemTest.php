<?php
require_once '../Scisr.php';
require_once './MultipleFileTest.php';

/**
 * @runTestsInSeparateProcesses
 */
class RenameClassSystemTest extends Scisr_Tests_MultipleFileTestCase
{

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

}
