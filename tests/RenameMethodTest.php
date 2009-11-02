<?php
require_once 'PHPUnit/Framework.php';
require_once '../Scisr.php';

/**
 * @runTestsInSeparateProcesses
 */
class RenameMethodTest extends PHPUnit_Framework_TestCase
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

    public function renameAndCompare($original, $expected, $class='Foo', $oldmethod='bar', $newmethod='baz') {
        $this->populateFile($original);

        $s = new Scisr();
        $s->setRenameMethod($class, $oldmethod, $newmethod);
        $s->addFile($this->test_file);
        $s->run();

        $this->compareFile($expected);

    }

    public function testRenameMethodDeclaration() {
        $orig = <<<EOL
<?php
class Foo {
    function bar() {
    }
}
EOL;
        $expected = <<<EOL
<?php
class Foo {
    function baz() {
    }
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameMethodInstantiatedCall() {
        $orig = <<<EOL
<?php
\$f = new Foo();
\$result = \$f->bar();
EOL;
        $expected = <<<EOL
<?php
\$f = new Foo();
\$result = \$f->baz();
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameMethodStaticCall() {
        $orig = <<<EOL
<?php
\$result = Foo::bar();
EOL;
        $expected = <<<EOL
<?php
\$result = Foo::baz();
EOL;
        $this->renameAndCompare($orig, $expected);
    }

}
