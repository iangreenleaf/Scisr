<?php
require_once 'SingleFileTest.php';

/**
 * @runTestsInSeparateProcesses
 */
class RenameClassTest extends Scisr_SingleFileTest
{

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

    public function testRenamePhpDocVar() {
        $orig = <<<EOL
<?php
class Bar {
    /**
     * This is a class object!
     * @var Foo
     */
    public \$a;
}
EOL;
        $expected = <<<EOL
<?php
class Bar {
    /**
     * This is a class object!
     * @var Baz
     */
    public \$a;
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenamePhpDocParam() {
        $orig = <<<EOL
<?php
/**
 * Do things
 * @param Bar \$b a function parameter
 * @param Foo \$f a function parameter
 */
function doThings(\$f) {
}
EOL;
        $expected = <<<EOL
<?php
/**
 * Do things
 * @param Bar \$b a function parameter
 * @param Baz \$f a function parameter
 */
function doThings(\$f) {
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenamePhpDocReturn() {
        $orig = <<<EOL
<?php
/**
 * Do things
 * @param int \$a a number
 * @return Foo this is a return val
 */
function doThings(\$a) {
}
EOL;
        $expected = <<<EOL
<?php
/**
 * Do things
 * @param int \$a a number
 * @return Baz this is a return val
 */
function doThings(\$a) {
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    /**
     * Test a type hint as recommended by Zend Studio
     */
    public function testRenameZendTypeHint() {
        $orig = <<<EOL
<?php
/* @var \$f Foo */
\$f = someFunction();
EOL;
        $expected = <<<EOL
<?php
/* @var \$f Baz */
\$f = someFunction();
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    /**
     * Test a type hint as recommended by Komodo Edit
     */
    public function testRenameKomodoTypeHint() {
        $orig = <<<EOL
<?php
/* @var Foo */
\$f = someFunction();
EOL;
        $expected = <<<EOL
<?php
/* @var Baz */
\$f = someFunction();
EOL;
        $this->renameAndCompare($orig, $expected);
    }


}
