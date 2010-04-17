<?php
require_once 'MultipleFileTest.php';

/**
 * @runTestsInSeparateProcesses
 */
class ScisrSystemTest extends Scisr_Tests_MultipleFileTestCase
{

    /**
     * Run scisr in a shell
     * @param array $args arguments to pass to the shell program
     * @param boolean $success true if the program should exit successfully
     * @return string the output of the program
     */
    public function runShellScisr($args, $success) {
        $scisrExecutable = dirname(__FILE__) . '/../scisr.php';
        $args = implode(' ', $args);
        $output = array();
        $status = 0;
        exec("$scisrExecutable $args", $output, $status);
        if ($success) {
            $this->assertEquals(0, $status);
        } else {
            $this->assertNotEquals(0, $status);
        }
        return implode("\n", $output);
    }

    public function testChangedFileNotification() {
        $this->populateDir(dirname(__FILE__) . '/_files/cliFixture', $this->test_dir);
        $args = array('rename-class', 'Foo', 'Bar', $this->test_dir);
        $output = $this->runShellScisr($args, true);
        $this->assertRegexp('/changed 2 files/i', $output);
    }

    public function testTimidNotification() {
        $this->populateDir(dirname(__FILE__) . '/_files/cliFixture', $this->test_dir);
        $args = array('rename-class', 'Foo', 'Bar', '--timid', $this->test_dir);
        $output = $this->runShellScisr($args, true);
        $this->assertRegexp('/changed 0 files/i', $output);
        $this->assertRegexp('/test\.php/', $output);
        $this->assertRegexp('/test2\.php/', $output);
        $this->assertRegexp('/not applied/i', $output);
    }

    public function testNotChangedNotification() {
        $this->populateDir(dirname(__FILE__) . '/_files/cliFixture', $this->test_dir);
        $args = array('rename-class', 'NotFoo', 'NotBar', $this->test_dir);
        $output = $this->runShellScisr($args, true);
        $this->assertRegexp('/changed 0 files/i', $output);
    }

    public function testTentativeChangeNotification() {
        $this->populateDir(dirname(__FILE__) . '/_files/cliFixture', $this->test_dir);
        $args = array('rename-class', 'Foo', 'Bar', $this->test_dir);
        $output = $this->runShellScisr($args, true);
        $this->assertRegexp('/1 file.*not applied/i', $output);
        $this->assertNotRegexp('/test\.php/', $output);
        $this->assertRegexp('/test2\.php/', $output);
    }

    public function testMixedChangeNotification() {
        $this->populateDir(dirname(__FILE__) . '/_files/mixedChangesFixture', $this->test_dir);
        $args = array('rename-class', 'Foo', 'Bar', $this->test_dir);
        $output = $this->runShellScisr($args, true);
        $this->assertRegexp('/changed 1 file/i', $output);
        $this->assertRegexp('/1 file.*not applied/i', $output);
        $this->assertRegexp('/notchanged\.php/', $output);
        $this->assertNotRegexp('/\<changed\.php/', $output);
    }

    /**
     * @ticket 24
     */
    public function testTentativeChangesDoubleReported() {
        $this->populateDir(dirname(__FILE__) . '/_files/systemClassFileFixture', $this->test_dir);
        $args = array('rename-class-file', 'Foo', 'Bar', $this->test_dir);
        $output = $this->runShellScisr($args, true);
        $this->assertRegexp('/changed 2 files/i', $output);
        $this->assertNotRegexp('/not applied/i', $output);
    }

    public function testTwoTentativeChangesOnOneLine() {
        $this->populateDir(dirname(__FILE__) . '/_files/doubleChangeFixture', $this->test_dir);
        $args = array('rename-class', 'Foo', 'Bar', $this->test_dir);
        $output = $this->runShellScisr($args, true);
        $this->assertRegexp('/2 possible changes in 1 file/i', $output);
        $this->assertRegexp('/foo\.php/i', $output);
        $this->assertNotRegexp('/foo\.php.*\n.*foo\.php/i', $output);
    }

    public function testPrintUsageOnBadArgs() {
        $args = array('foo', 'bar', 'baz', $this->test_dir);
        $output = $this->runShellScisr($args, false);
        $this->assertRegExp('/usage/i', $output);
    }

    public function testPrintUsageOnHelp() {
        $args = array('--help');
        $output = $this->runShellScisr($args, true);
        $this->assertRegExp('/usage/i', $output);
    }

    public function testIgnoreFiles() {
        $this->populateDir(dirname(__FILE__) . '/_files/ignoreFilesFixture', $this->test_dir);
        $args = array('rename-class', 'Foo', 'Bar', '--ignore', 'test2.php,dir1/nested,dir2', $this->test_dir);
        $output = $this->runShellScisr($args, true);
        $this->compareDir(dirname(__FILE__) . '/_files/ignoreFilesFixture-after-rename', $this->test_dir);
    }

    public function testExtensions() {
        $this->populateDir(dirname(__FILE__) . '/_files/fileExtensionsFixture', $this->test_dir);
        $args = array('rename-class', 'Foo', 'Bar', '--extensions', 'inc,foo', $this->test_dir);
        $output = $this->runShellScisr($args, true);
        $this->compareDir(dirname(__FILE__) . '/_files/fileExtensionsFixture-after-rename', $this->test_dir);
    }

}
