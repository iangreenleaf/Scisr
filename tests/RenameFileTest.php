<?php
require_once 'SingleFileTest.php';

/**
 * @runTestsInSeparateProcesses
 */
class RenameFileTest extends Scisr_SingleFileTest
{

    public function renameAndCompare($original, $expected, $oldname='Foo.php', $newname='Baz.php', $aggressive=false) {
        $this->populateFile($original);

        $s = new Scisr();
        if ($aggressive) {
            $s->setEditMode(Scisr::MODE_AGGRESSIVE);
        }
        $s->setRenameFile($oldname, $newname);
        $s->addFile($this->test_file);
        $s->run();

        $this->compareFile($expected);

    }

    /**
     * @dataProvider includeProvider
     */
    public function testSimpleRename($orig) {
        $orig = "<?php\n$orig";
        $expected = str_replace('Foo.php', 'Baz.php', $orig);
        $this->renameAndCompare($orig, $expected);
    }

    public function includeProvider() {
        return array(
            array('require("Foo.php");'),
            array('include("Foo.php");'),
            array('require_once("Foo.php");'),
            array('include_once("Foo.php");'),
            array('require_once "Foo.php";'),
            array("require_once('Foo.php');"),
            array('require_once(    "Foo.php"   );'),
        );
    }

    /**
     * @dataProvider partialMatchProvider
     */
    public function testDontRenamePartialMatches($orig) {
        $orig = "<?php\n$orig";
        $this->renameAndCompare($orig, $orig);
    }

    public function partialMatchProvider() {
        return array(
            array('require_once("Foo.php.old");'),
            array('require_once("Foo.php/actualfile");'),
            array('require_once("notmyfolder/Foo.php");'),
            array('require_once("Foo.php" . ".old");'),
            array('require_once("notmyfolder/" . "Foo.php");'),
            array('require_once(SOME_CONSTANT . "Foo.php");'),
            array('require_once(function_call(2) . "Foo.php");'),
            array('require_once(function_call("Foo.php"));'),
        );
    }

}
