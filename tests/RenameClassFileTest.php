<?php
require_once 'SingleFileTest.php';

class RenameClassFileTest extends Scisr_SingleFileTest
{

    public function renameAndCompare($original, $expected, $oldname='Foo', $newname='Baz', $oldfile='Foo.php', $newfile='Baz.php', $aggressive=false) {
        $this->populateFile($original);

        $s = $this->getScisr();
        if ($aggressive) {
            $s->setEditMode(ScisrRunner::MODE_AGGRESSIVE);
        }
        $s->setRenameClass($oldname, $newname);
        $s->setRenameFile($oldfile, $newfile);
        $s->addFile($this->test_file);
        $s->run();

        $this->compareFile($expected);

    }

    /**
     * @dataProvider binaryProvider
     */
    public function testRenameClassFile($aggressive) {
        $orig = <<<EOL
<?php
require_once("Foo.php");
\$x = new Foo();
EOL;
        $expected = <<<EOL
<?php
require_once("Baz.php");
\$x = new Baz();
EOL;
        $this->renameAndCompare($orig, $expected, 'Foo', 'Baz', 'Foo.php', 'Baz.php', $aggressive);
    }

    public function binaryProvider() {
        return array(array(true), array(false));
    }

}
