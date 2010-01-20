<?php
require_once '../Scisr.php';
require_once './MultipleFileTest.php';

/**
 * @runTestsInSeparateProcesses
 */
class RenameFileSystemTest extends Scisr_Tests_MultipleFileTestCase
{

    public function testRenameFileAndCompareDir() {
        $this->populateDir(dirname(__FILE__) . '/_files/renameFileFixture', $this->test_dir);

        $s = new Scisr();
        $s->setRenameFile($this->test_dir . '/stuff.php', $this->test_dir . '/things.php');
        $s->addFile($this->test_dir);
        $s->run();

        $this->compareDir(dirname(__FILE__) . '/_files/renameFileFixture-after-rename-file', $this->test_dir);
    }

    public function testRenameFileToNewDirAndCompareDir() {
        $this->populateDir(dirname(__FILE__) . '/_files/renameFileFixture', $this->test_dir);

        $s = new Scisr();
        $s->setRenameFile($this->test_dir . '/stuff.php', $this->test_dir . '/otherfolder/things.php');
        $s->addFile($this->test_dir);
        $s->run();

        $this->compareDir(dirname(__FILE__) . '/_files/renameFileFixture-after-rename-file-new-dir', $this->test_dir);
    }

    public function testRenameFileWithRelativePath() {
        $this->populateDir(dirname(__FILE__) . '/_files/renameFileFixture', $this->test_dir);
        chdir($this->test_dir);

        $s = new Scisr();
        $s->setRenameFile('stuff.php', 'things.php');
        $s->addFile($this->test_dir);
        $s->run();

        $this->compareDir(dirname(__FILE__) . '/_files/renameFileFixture-after-rename-file', $this->test_dir);
    }

}
