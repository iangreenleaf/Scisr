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

        $this->doRenameFile($this->test_dir . '/stuff.php', $this->test_dir . '/things.php');

        $this->compareDir(dirname(__FILE__) . '/_files/renameFileFixture-after-rename-file', $this->test_dir);
    }

    public function testRenameFileToNewDirAndCompareDir() {
        $this->populateDir(dirname(__FILE__) . '/_files/renameFileFixture', $this->test_dir);

        $this->doRenameFile($this->test_dir . '/stuff.php', $this->test_dir . '/otherfolder/things.php');

        $this->compareDir(dirname(__FILE__) . '/_files/renameFileFixture-after-rename-file-new-dir', $this->test_dir);
    }

    public function testRenameFileWithRelativePath() {
        $this->populateDir(dirname(__FILE__) . '/_files/renameFileFixture', $this->test_dir);
        chdir($this->test_dir);

        $this->doRenameFile('stuff.php', 'things.php');

        $this->compareDir(dirname(__FILE__) . '/_files/renameFileFixture-after-rename-file', $this->test_dir);
    }

	/**
	 * @dataProvider includesRenameProvider
	 */
    public function testRenameFileAltersIncludes($oldName, $newName, $expectedDir) {
        $this->populateDir(dirname(__FILE__) . '/_files/renameFileIncludesFixture', $this->test_dir);
        chdir($this->test_dir);

		$this->doRenameFile($oldName, $newName);

        $this->compareDir(dirname(__FILE__) . '/_files/' . $expectedDir, $this->test_dir);
    }

	public function includesRenameProvider() {
		return array(
			array('test.php', 'otherfolder/test.php', 'renameFileIncludesFixture-after-rename-1'),
			array('otherfolder/stuff.php', 'stuff.php', 'renameFileIncludesFixture-after-rename-2'),
		);
	}

    public function testRenameFileAltersIncludesSwitchPlaces() {
        $this->populateDir(dirname(__FILE__) . '/_files/renameFileIncludesFixture', $this->test_dir);
        chdir($this->test_dir);

		$this->doRenameFile('otherfolder/stuff.php', 'stuff.php');
		$this->doRenameFile('test.php', 'otherfolder/test.php');

        $this->compareDir(dirname(__FILE__) . '/_files/renameFileIncludesFixture-after-rename-3', $this->test_dir);
    }

	public function doRenameFile($old, $new) {
        $s = new Scisr();
        $s->setRenameFile($old, $new);
        $s->addFile($this->test_dir);
        $s->run();
	}

}
