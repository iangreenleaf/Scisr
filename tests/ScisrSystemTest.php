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

    /**
     * @dataProvider badArgsProvider
     */
    public function testPrintUsageOnBadArgs($args) {
        $output = $this->runShellScisr($args, false);
        $this->assertRegExp('/usage/i', $output);
    }

    public function badArgsProvider() {
        return array(
            array(array('foo')),
            array(array('foo', 'bar', 'baz', $this->getTestDir())),
            array(array('rename-class', 'bar')),
        );
    }

}
