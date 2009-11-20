<?php
require_once 'SingleFileTest.php';

/**
 * @runTestsInSeparateProcesses
 */
class RenameClassTest extends Scisr_SingleFileTest
{

    public function renameAndCompare($original, $expected, $oldname='Foo', $newname='Baz', $aggressive=false) {
        $this->populateFile($original);

        $s = new Scisr();
        if ($aggressive) {
            $s->setEditMode(Scisr::MODE_AGGRESSIVE);
        }
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

    public function testRenameClassNameInCommentsWhenAggressive() {
        $orig = <<<EOL
<?php
/**
 * This variable has something to do with Foo.
 * But it doesn't concern NotFoo.
 */
\$f = someFunction();
// Yo dawg I heard you liked Foo so I got you some Foo
\$g = 1;
EOL;
        $expected = <<<EOL
<?php
/**
 * This variable has something to do with Baz.
 * But it doesn't concern NotFoo.
 */
\$f = someFunction();
// Yo dawg I heard you liked Baz so I got you some Baz
\$g = 1;
EOL;
        $this->renameAndCompare($orig, $expected, 'Foo', 'Baz', true);
    }

    public function testDontRenameClassNameInCommentsWhenNotAggressive() {
        $orig = <<<EOL
<?php
/**
 * This variable has something to do with Foo.
 * But it doesn't concern NotFoo.
 */
\$f = someFunction();
// Yo dawg I heard you liked Foo so I got you some Foo
\$g = 1;
EOL;
        $expected = <<<EOL
<?php
/**
 * This variable has something to do with Foo.
 * But it doesn't concern NotFoo.
 */
\$f = someFunction();
// Yo dawg I heard you liked Foo so I got you some Foo
\$g = 1;
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameClassNameInStringWhenAggressive() {
        $orig = <<<EOL
<?php
\$a = "this string concerns Foo";
\$b = "Foo as \$x does Foo this \$arr['one']";
\$c = 'this Foo too';
\$d = "but don't touch NotFoo"
EOL;
        $expected = <<<EOL
<?php
\$a = "this string concerns Baz";
\$b = "Baz as \$x does Baz this \$arr['one']";
\$c = 'this Baz too';
\$d = "but don't touch NotFoo"
EOL;
        $this->renameAndCompare($orig, $expected, 'Foo', 'Baz', true);
    }

    public function testDontRenameClassNameInStringWhenNotAggressive() {
        $orig = <<<EOL
<?php
\$a = "this string concerns Foo";
\$b = "Foo as \$x does Foo this \$arr['one']";
\$c = 'this Foo too';
\$d = "but don't touch NotFoo"
EOL;
        $expected = <<<EOL
<?php
\$a = "this string concerns Foo";
\$b = "Foo as \$x does Foo this \$arr['one']";
\$c = 'this Foo too';
\$d = "but don't touch NotFoo"
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    /**
     * If we have multiple sniffs that will register a change (for example, the
     * PHPDoc parser + the regular word matcher when we're in aggressive mode),
     * we want to make sure they only (effectively) make the change once. We
     * test this by changing to a word of different length.
     * @todo move this to a functional suite
     */
    public function testDontDoubleRename() {
        $orig = <<<EOL
<?php
/**
 * @return Quark this is a return val
 */
function someFunction() { }
EOL;
        $expected = <<<EOL
<?php
/**
 * @return Foo this is a return val
 */
function someFunction() { }
EOL;
        $this->renameAndCompare($orig, $expected, 'Quark', 'Foo');
    }

}
