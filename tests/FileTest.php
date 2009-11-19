<?php
require_once 'SingleFileTest.php';

/**
 * @runTestsInSeparateProcesses
 */
class FileTest extends Scisr_SingleFileTest
{

    /**
     * When there are two pending changes on the same line, the first one
     * will affect the offset of the second. We test this by changing to a
     * word of different length.
     */
    public function testOffsetIncreasedByOtherChange() {
        $original = <<<EOL
<?php
/**
 * @return Quark this is a return Quark
 */
function someFunction() { }
EOL;

        $this->populateFile($original);
        $f = new Scisr_File($this->test_file);
        $f->addEdit(3, 12, 5, 'Bazzle');
        $f->addEdit(3, 35, 5, 'Bazzle');
        $f->process();

        $expected = <<<EOL
<?php
/**
 * @return Bazzle this is a return Bazzle
 */
function someFunction() { }
EOL;
        $this->compareFile($expected);
    }

    public function testOffsetDecreasedByOtherChange() {
        $original = <<<EOL
<?php
/**
 * @return Quark this is a return Quark
 */
function someFunction() { }
EOL;

        $this->populateFile($original);
        $f = new Scisr_File($this->test_file);
        $f->addEdit(3, 12, 5, 'Foo');
        $f->addEdit(3, 35, 5, 'Foo');
        $f->process();

        $expected = <<<EOL
<?php
/**
 * @return Foo this is a return Foo
 */
function someFunction() { }
EOL;
        $this->compareFile($expected);
    }

    public function testConflictingOffsets() {
        $original = <<<EOL
<?php
// Stub
EOL;

        $this->populateFile($original);
        $f = new Scisr_File($this->test_file);
        $f->addEdit(2, 1, 5, 'Replacement');
        $f->addEdit(2, 3, 4, 'Foo');
        $this->setExpectedException('Exception');
        $f->process();

    }

}
