<?php
require_once 'PHPUnit/Framework.php';
require_once '../Scisr.php';

/**
 * @runTestsInSeparateProcesses
 */
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

        $s = new Scisr();
        $s->setRenameClass($oldname, $newname);
        $s->addFile($this->test_file);
        $s->run();

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

    public function testRenameClassInstantiation() {
        $orig = <<<EOL
<?php
\$myVar = new Foo();
EOL;
        $expected = <<<EOL
<?php
\$myVar = new Baz();
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameClassStaticCall() {
        $orig = <<<EOL
<?php
\$result = Foo::bar();
EOL;
        $expected = <<<EOL
<?php
\$result = Baz::bar();
EOL;
        $this->renameAndCompare($orig, $expected);
    }

}
