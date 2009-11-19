<?php
require_once 'SingleFileTest.php';

/**
 * @runTestsInSeparateProcesses
 */
class FileTest extends Scisr_SingleFileTest
{

    public function testSimpleChange() {
        $original = <<<EOL
<?php
// This is a stub
EOL;

        $this->populateFile($original);
        $f = new Scisr_File($this->test_file);
        $f->addEdit(2, 9, 2, 'may be');
        $f->process();

        $expected = <<<EOL
<?php
// This may be a stub
EOL;
        $this->compareFile($expected);
    }

    public function testNoChanges() {
        $original = <<<EOL
<?php
// This is a stub
EOL;

        $this->populateFile($original);
        $f = new Scisr_File($this->test_file);
        $f->process();

        $this->compareFile($original);
    }

    public function testMultipleChanges() {
        $original = <<<EOL
<?php
// This is a stub
// Second line
EOL;

        $this->populateFile($original);
        $f = new Scisr_File($this->test_file);
        $f->addEdit(2, 9, 2, 'may be');
        $f->addEdit(3, 4, 6, 'Another');
        $f->process();

        $expected = <<<EOL
<?php
// This may be a stub
// Another line
EOL;
        $this->compareFile($expected);
    }

    /**
     * When there are two pending changes on the same line, the first one
     * will affect the offset of the second. We test this by changing to a
     * word of different length.
     */
    public function testOffsetIncreasedByOtherChange() {
        $original = <<<EOL
<?php
// @return Quark this is a return Quark
EOL;

        $this->populateFile($original);
        $f = new Scisr_File($this->test_file);
        $f->addEdit(2, 12, 5, 'Bazzle');
        $f->addEdit(2, 35, 5, 'Bazzle');
        $f->process();

        $expected = <<<EOL
<?php
// @return Bazzle this is a return Bazzle
EOL;
        $this->compareFile($expected);
    }

    public function testOffsetDecreasedByOtherChange() {
        $original = <<<EOL
<?php
// @return Quark this is a return Quark
EOL;

        $this->populateFile($original);
        $f = new Scisr_File($this->test_file);
        $f->addEdit(2, 12, 5, 'Foo');
        $f->addEdit(2, 35, 5, 'Foo');
        $f->process();

        $expected = <<<EOL
<?php
// @return Foo this is a return Foo
EOL;
        $this->compareFile($expected);
    }

    /**
     * I don't think this scenario should ever actually occur, but it will make
     * me feel better if we're robust enough to handle it.
     */
    public function testAddChangesOutOfOrder() {
        $original = <<<EOL
<?php
// @return Quark this is a return Quark
// Second line
EOL;

        $this->populateFile($original);
        $f = new Scisr_File($this->test_file);
        $f->addEdit(2, 35, 5, 'Bazzle');
        $f->addEdit(3, 4, 6, 'Another');
        $f->addEdit(2, 12, 5, 'Bazzle');
        $f->process();

        $expected = <<<EOL
<?php
// @return Bazzle this is a return Bazzle
// Another line
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
        $f->addEdit(2, 4, 3, 'Replacement');
        $f->addEdit(2, 6, 2, 'Foo');
        $this->setExpectedException('Exception');
        $f->process();
    }

    public function testConflictingOffsetsWithOffsetAdjustment() {
        $original = <<<EOL
<?php
// Stub
EOL;

        $this->populateFile($original);
        $f = new Scisr_File($this->test_file);
        $f->addEdit(2, 1, 2, '/**/');
        $f->addEdit(2, 4, 3, 'Replacement');
        $f->addEdit(2, 6, 2, 'Foo');
        $this->setExpectedException('Exception');
        $f->process();
    }

    public function testNoChangesToFileWhenConflictFound() {
        $original = <<<EOL
<?php
// Stub
EOL;

        $this->populateFile($original);
        $f = new Scisr_File($this->test_file);
        $f->addEdit(2, 4, 3, 'Replacement');
        $f->addEdit(2, 6, 2, 'Foo');
        try {
            $f->process();
        } catch (Exception $e) {
            // Do nothing
        }
        // We should have aborted all changes to the file
        $this->compareFile($original);
    }

    public function testAcceptNearlyConflictingOffsets() {
        $original = <<<EOL
<?php
// this is a stub?
EOL;

        $this->populateFile($original);
        $f = new Scisr_File($this->test_file);
        $f->addEdit(2, 12, 6, 'something else');
        $f->addEdit(2, 18, 1, '!');
        $f->process();

        $expected = <<<EOL
<?php
// this is something else!
EOL;

        $this->compareFile($expected);
    }

}
