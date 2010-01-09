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

}
