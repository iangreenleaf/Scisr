<?php
require_once '../Scisr.php';
require_once './MultipleFileTest.php';

/**
 * @runTestsInSeparateProcesses
 */
class RenameMethodSystemTest extends Scisr_Tests_MultipleFileTestCase
{

    public function testRenameMethodAndCompareFile() {
        $this->markTestIncomplete();
    }

    public function testRenameMethodAndCompareFileWithRelativeDir() {
        $this->markTestIncomplete();
    }

    public function testRenameMethodAndCompareDir() {
        $this->markTestIncomplete();
    }

    public function testRenameMethodWithIncludedFile() {
        $this->populateDir(dirname(__FILE__) . '/_files/renameMethodWithIncludesFixture', $this->test_dir);

        $s = new Scisr();
        $s->setRenameMethod('Foo', 'bar', 'baz');
        $s->addFile($this->test_dir);
        $s->run();

        $this->compareDir(dirname(__FILE__) . '/_files/renameMethodWithIncludesFixture-after-rename', $this->test_dir);
    }

}
