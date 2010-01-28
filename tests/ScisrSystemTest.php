<?php
require_once '../Scisr.php';
require_once './MultipleFileTest.php';

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
        $this->markTestIncomplete();
    }

    public function testNotChangedNotification() {
        $this->markTestIncomplete();
    }

    public function testAllNotifications() {
        $this->markTestIncomplete();
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

}
