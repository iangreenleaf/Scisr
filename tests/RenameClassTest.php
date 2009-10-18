<?php
require_once 'PHPUnit/Framework.php';
require_once '../Scisr.php';

class RenameClassTest extends PHPUnit_Framework_TestCase
{
    public function setUp() {
        $this->test_file = dirname(__FILE__) . '/myTestFile.php';
        touch($this->test_file);
    }

    public function tearDown() {
        unlink($this->test_file);
    }

    public function populateFile($contents) {
        $handle = fopen($this->test_file, 'w');
        fwrite($handle, $contents);
    }

    public function compareFile($expected) {
        $contents = file_get_contents($this->test_file);
        $this->assertEquals($expected, $contents);
    }

    public function renameAndCompare($original, $expected, $oldname='Foo', $newname='Baz') {
        $this->populateFile($original);

        $sniffer = new Scisr_CodeSniffer();
        $sniffer->addListener(new Scisr_Operations_ChangeClassName($oldname, $newname));
        $sniffer->process($this->test_file);
        $changes = Scisr_ChangeRegistry::get('storedChanges');
        $file = new Scisr_File($this->test_file);
        foreach ($changes as $change) {
            $file->addEdit($change['line'], $change['column'], $change['length'], $change['replacement']);
        }
        $file->process();

        $this->compareFile($expected);

    }

    public function testRenameClassDeclaration() {
        $orig = <<<EOL
<?php
class Foo {
    function bar() {
    }
}
EOL;
        $expected = <<<EOL
<?php
class Baz {
    function bar() {
    }
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }
}
