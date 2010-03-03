<?php
require_once 'SingleFileTest.php';

/**
 * @runTestsInSeparateProcesses
 */
class RenameMethodTest extends Scisr_SingleFileTest
{

    public function renameAndCompare($original, $expected, $class='Foo', $oldmethod='bar', $newmethod='baz', $aggressive=false, $inheritance=false) {
        $this->populateFile($original);

        $s = new Scisr();
        if ($aggressive) {
            $s->setEditMode(Scisr::MODE_AGGRESSIVE);
        }
        $s->setRenameMethod($class, $oldmethod, $newmethod, $inheritance);
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

    public function testDontRenameSimilarMethodCallsInClass() {
        $orig = <<<EOL
<?php
class Foo {
    protected \$x;
    function quark() {
        \$this->x->bar();
    }
}
EOL;
        $expected = <<<EOL
<?php
class Foo {
    protected \$x;
    function quark() {
        \$this->x->bar();
    }
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testDontRenameMethodDeclarationInOtherClass() {
        $orig = <<<EOL
<?php
class Quark {
    function bar() {
    }
}
EOL;
        $this->renameAndCompare($orig, $orig);
    }

    public function testRenameMethodInstantiatedCall() {
        $orig = <<<EOL
<?php
\$f = new Foo();
\$result = \$f->bar();
\$f2 = \$f;
\$result = \$f2->bar();
EOL;
        $expected = <<<EOL
<?php
\$f = new Foo();
\$result = \$f->baz();
\$f2 = \$f;
\$result = \$f2->baz();
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testDontRenameMethodOnUntypedVar() {
        $orig = <<<EOL
<?php
\$result = \$f->bar();
\$f2 = somefunc();
\$result = \$f2->bar();
EOL;
        $this->renameAndCompare($orig, $orig);
    }

    public function testRenameMethodInstantiatedInOtherClassProperty() {
        $orig = <<<EOL
<?php
\$a = new Car();
\$a->my_f = new Foo();
\$a->my_f->bar();
EOL;
        $expected = <<<EOL
<?php
\$a = new Car();
\$a->my_f = new Foo();
\$a->my_f->baz();
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameMethodInstantiatedCallWithNewlines() {
        $orig = <<<EOL
<?php
\$a->f = new Foo();
\$a->f
    ->bar()
    ->quark();
EOL;
        $expected = <<<EOL
<?php
\$a->f = new Foo();
\$a->f
    ->baz()
    ->quark();
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameMethodInstantiatedClassProperty() {
        $orig = <<<EOL
<?php
class Quack {
    protected \$f;
    function quark() {
        \$this->f = new Foo();
        \$this->f->bar();
    }
}
EOL;
        $expected = <<<EOL
<?php
class Quack {
    protected \$f;
    function quark() {
        \$this->f = new Foo();
        \$this->f->baz();
    }
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameMethodInstantiatedClassPropertyFromOutsideOfClass() {
        $orig = <<<EOL
<?php
class Quack {
    public \$f;
    function quark() {
        \$this->f = new Foo();
    }
}
\$q = new Quack();
\$q->f->bar();
EOL;
        $expected = <<<EOL
<?php
class Quack {
    public \$f;
    function quark() {
        \$this->f = new Foo();
    }
}
\$q = new Quack();
\$q->f->baz();
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameMethodWithCallBeforeDeclaration() {
        $orig = <<<EOL
<?php
\$q = new Quack();
\$q->f->bar();
class Quack {
    public \$f;
    function quark() {
        \$this->f = new Foo();
    }
}
EOL;
        $expected = <<<EOL
<?php
\$q = new Quack();
\$q->f->baz();
class Quack {
    public \$f;
    function quark() {
        \$this->f = new Foo();
    }
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameClassPropertyCallWithConstructorInstantiation() {
        $orig = <<<EOL
<?php
class Quack {
    protected \$f;
    function __construct() {
        \$this->f = new Foo();
    }
    function quark() {
        \$this->f->bar();
    }
}
EOL;
        $expected = <<<EOL
<?php
class Quack {
    protected \$f;
    function __construct() {
        \$this->f = new Foo();
    }
    function quark() {
        \$this->f->baz();
    }
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameMethodInsideOwningClass() {
        $orig = <<<EOL
<?php
class Foo {
    function quark() {
        \$this->bar();
        \$foo = \$this;
        \$foo->bar();
    }
}
EOL;
        $expected = <<<EOL
<?php
class Foo {
    function quark() {
        \$this->baz();
        \$foo = \$this;
        \$foo->baz();
    }
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRecognizeOverwrittenVariables() {
        $orig = <<<EOL
<?php
\$a = new NotFoo();
\$b = new Foo();

\$a = new Foo();
\$b = new NotFoo();

\$a->bar();
\$b->bar();
EOL;
        $expected = <<<EOL
<?php
\$a = new NotFoo();
\$b = new Foo();

\$a = new Foo();
\$b = new NotFoo();

\$a->baz();
\$b->bar();
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testDontCountVariablesOverwrittenLater() {
        $orig = <<<EOL
<?php
\$a = new Foo();
\$b = new NotFoo();

\$a->bar();
\$b->bar();

\$a = new NotFoo();
\$b = new Foo();
EOL;
        $expected = <<<EOL
<?php
\$a = new Foo();
\$b = new NotFoo();

\$a->baz();
\$b->bar();

\$a = new NotFoo();
\$b = new Foo();
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameWithScopedVariable() {
        $orig = <<<EOL
<?php
function quark(\$param) {
    \$f = new Foo();
    return \$f->bar();
}
EOL;
        $expected = <<<EOL
<?php
function quark(\$param) {
    \$f = new Foo();
    return \$f->baz();
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testOnlyRenameIfInScope() {
        $orig = <<<EOL
<?php
\$f = new NotFoo();
function quark(\$param) {
    \$f = new Foo();
    return \$f->bar();
}
\$result = \$f->bar();
EOL;
        $expected = <<<EOL
<?php
\$f = new NotFoo();
function quark(\$param) {
    \$f = new Foo();
    return \$f->baz();
}
\$result = \$f->bar();
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameGlobalVariable() {
        $orig = <<<EOL
<?php
\$f = new Foo();
function quark(\$param) {
    global \$f;
    return \$f->bar();
}
EOL;
        $expected = <<<EOL
<?php
\$f = new Foo();
function quark(\$param) {
    global \$f;
    return \$f->baz();
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testDontRenameNonGlobalVariable() {
        $orig = <<<EOL
<?php
\$f = new Foo();
function quark(\$param) {
    return \$f->bar();
}
EOL;
        $expected = <<<EOL
<?php
\$f = new Foo();
function quark(\$param) {
    return \$f->bar();
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameWhenInsideIf() {
        $orig = <<<EOL
<?php
if (true) {
    \$f = new Foo(true);
} else {
    \$f = new Foo(false);
}
\$result = \$f->bar();
EOL;
        $expected = <<<EOL
<?php
if (true) {
    \$f = new Foo(true);
} else {
    \$f = new Foo(false);
}
\$result = \$f->baz();
EOL;
        $this->renameAndCompare($orig, $expected);
    }

	public function testRenameFunctionParamWithTypeHint() {
        $orig = <<<EOL
<?php
function doStuff(\$f1, Foo \$f2, NotFoo \$f3) {
    \$f1->bar();
    \$f2->bar();
    \$f3->bar();
}
EOL;
        $expected = <<<EOL
<?php
function doStuff(\$f1, Foo \$f2, NotFoo \$f3) {
    \$f1->bar();
    \$f2->baz();
    \$f3->bar();
}
EOL;
        $this->renameAndCompare($orig, $expected);
	}

	public function testRenameMethodParamWithTypeHint() {
        $orig = <<<EOL
<?php
class Quark {
    function doStuff(\$f1, Foo \$f2, NotFoo \$f3) {
        \$f1->bar();
        \$f2->bar();
        \$f3->bar();
    }
}
EOL;
        $expected = <<<EOL
<?php
class Quark {
    function doStuff(\$f1, Foo \$f2, NotFoo \$f3) {
        \$f1->bar();
        \$f2->baz();
        \$f3->bar();
    }
}
EOL;
        $this->renameAndCompare($orig, $expected);
	}

    public function testRenameMethodReturnValueWithPHPDocType() {
        $orig = <<<EOL
<?php
class Quark {
    /**
     * A method that does something
     * @return Foo
     */
    public function getObj(\$param=null) {
        // STUB
    }
    public function doSomething() {
        \$eff = \$this->getObj();
        \$eff->bar();
    }
}

\$q = new Quark();
\$f1 = \$q->getObj();
\$f2 = \$q->getObj(\$somevar);
\$f1->bar();
\$f2->bar();
Quark::getObj()->bar();
EOL;
        $expected = <<<EOL
<?php
class Quark {
    /**
     * A method that does something
     * @return Foo
     */
    public function getObj(\$param=null) {
        // STUB
    }
    public function doSomething() {
        \$eff = \$this->getObj();
        \$eff->baz();
    }
}

\$q = new Quark();
\$f1 = \$q->getObj();
\$f2 = \$q->getObj(\$somevar);
\$f1->baz();
\$f2->baz();
Quark::getObj()->baz();
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameFunctionReturnValueWithPHPDocType() {
        $orig = <<<EOL
<?php
/**
 * A function that does something
 * @return Foo
 */
function getObj() {
    // STUB
}

\$f = getObj();
\$result = \$f->bar();
EOL;
        $expected = <<<EOL
<?php
/**
 * A function that does something
 * @return Foo
 */
function getObj() {
    // STUB
}

\$f = getObj();
\$result = \$f->baz();
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testDontConfuseMethodAndFunctionReturnTypes() {
        $orig = <<<EOL
<?php
/**
 * @return Zork
 */
function getObj() {
}
/**
 * @return Foo
 */
function getObj2() {
}
class Quark {
    /**
     * @return Foo
     */
    public function getObj(\$param=null) {
        // STUB
    }
    /**
     * @return Zork
     */
    public function getObj2() {
        // STUB
    }
}

\$a = getObj();
\$b = getObj2();
\$c = Quark::getObj();
\$d = Quark::getObj2();
\$a->bar();
\$b->bar();
\$c->bar();
\$d->bar();
EOL;
        $expected = <<<EOL
<?php
/**
 * @return Zork
 */
function getObj() {
}
/**
 * @return Foo
 */
function getObj2() {
}
class Quark {
    /**
     * @return Foo
     */
    public function getObj(\$param=null) {
        // STUB
    }
    /**
     * @return Zork
     */
    public function getObj2() {
        // STUB
    }
}

\$a = getObj();
\$b = getObj2();
\$c = Quark::getObj();
\$d = Quark::getObj2();
\$a->bar();
\$b->baz();
\$c->baz();
\$d->bar();
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameFunctionReturnValueWithCallBeforeDeclaration() {
        $orig = <<<EOL
<?php
\$f = getObj();
\$result = \$f->bar();

/**
 * A function that does something
 * @return Foo
 */
function getObj() {
    // STUB
}
EOL;
        $expected = <<<EOL
<?php
\$f = getObj();
\$result = \$f->baz();

/**
 * A function that does something
 * @return Foo
 */
function getObj() {
    // STUB
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameWithStackedCallsBeforeDeclarations() {
        $orig = <<<EOL
<?php
\$q = new Q();
\$q->a->bar();

class Q {
    function func() {
        \$this->a = \$this->func2();
    }
    /**
     * @return Foo
     */
    function func2() {
    }
}
EOL;
        $expected = <<<EOL
<?php
\$q = new Q();
\$q->a->baz();

class Q {
    function func() {
        \$this->a = \$this->func2();
    }
    /**
     * @return Foo
     */
    function func2() {
    }
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameWithEarlyAssignment() {
        $orig = <<<EOL
<?php
\$x = new X();
\$x->y->z = new Foo();

class X {
    function func() {
        \$this->y = new Y();
    }
}
class Y {
    function func() {
        \$this->z->bar();
    }
}
EOL;
        $expected = <<<EOL
<?php
\$x = new X();
\$x->y->z = new Foo();

class X {
    function func() {
        \$this->y = new Y();
    }
}
class Y {
    function func() {
        \$this->z->baz();
    }
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testDontClobberSimilarNamedVar() {
        $orig = <<<EOL
<?php
\$x->yz = new Foo();
\$x->y = new NotFoo();
\$x->yz->bar();
\$x->y->bar();
EOL;
        $expected = <<<EOL
<?php
\$x->yz = new Foo();
\$x->y = new NotFoo();
\$x->yz->baz();
\$x->y->bar();
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameFunctionParameterWithPHPDocType() {
        $orig = <<<EOL
<?php
/**
 * @param Foo \$f take a parameter
 */
function quark(\$f) {
    \$f->bar();
}
EOL;
        $expected = <<<EOL
<?php
/**
 * @param Foo \$f take a parameter
 */
function quark(\$f) {
    \$f->baz();
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameFunctionParameterWithPHPDocTypeWithoutVarName() {
        $this->markTestIncomplete();
        $orig = <<<EOL
<?php
/**
 * @param Foo a parameter
 */
function quark(\$f) {
    \$f->bar();
}
EOL;
        $expected = <<<EOL
<?php
/**
 * @param Foo a parameter
 */
function quark(\$f) {
    \$f->baz();
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameFunctionParameterWithPHPDocTypeWithoutVarNameOrDescription() {
        $this->markTestIncomplete();
        $orig = <<<EOL
<?php
/**
 * @param Foo
 */
function quark(\$f) {
    \$f->bar();
}
EOL;
        $expected = <<<EOL
<?php
/**
 * @param Foo
 */
function quark(\$f) {
    \$f->baz();
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameMethodParameterWithPHPDocType() {
        $orig = <<<EOL
<?php
class Quark {
    /**
     * @param Foo \$f take a parameter
     */
    function quack(\$f) {
        \$f->bar();
    }
}
EOL;
        $expected = <<<EOL
<?php
class Quark {
    /**
     * @param Foo \$f take a parameter
     */
    function quack(\$f) {
        \$f->baz();
    }
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameClassPropertyCallWithPHPDocType() {
        $orig = <<<EOL
<?php
class Quark {
    /**
     * Some class property
     * @var Foo
     */
    protected \$f;
    function quack() {
        \$this->f->bar();
    }
}
EOL;
        $expected = <<<EOL
<?php
class Quark {
    /**
     * Some class property
     * @var Foo
     */
    protected \$f;
    function quack() {
        \$this->f->baz();
    }
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameUnassignedVariableWithZendTypeHint() {
        $orig = <<<EOL
<?php
/* @var \$f Foo */
\$result = \$f->bar();
EOL;
        $expected = <<<EOL
<?php
/* @var \$f Foo */
\$result = \$f->baz();
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameUnassignedVariableWithKomodoTypeHint() {
        $orig = <<<EOL
<?php
/* @var Foo */
\$f = getClass();
\$result = \$f->bar();
EOL;
        $expected = <<<EOL
<?php
/* @var Foo */
\$f = getClass();
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

    public function testRenameMethodNameInCommentsWhenAggressive() {
        $orig = <<<EOL
<?php
/**
 * We are talking about Foo::bar() here, also known as Foo->bar but not Foo::barnacle().
 * Also when we just talk about bar or bar() not barnacle or Ebar(), that should only be
 * renamed when aggressive.
 */
EOL;
        $expected = <<<EOL
<?php
/**
 * We are talking about Foo::baz() here, also known as Foo->baz but not Foo::barnacle().
 * Also when we just talk about baz or baz() not barnacle or Ebar(), that should only be
 * renamed when aggressive.
 */
EOL;
        $this->renameAndCompare($orig, $expected, 'Foo', 'bar', 'baz', true);
    }

    public function testOnlyRenameSomeMethodNamesInCommentsWhenNotAggressive() {
        $orig = <<<EOL
<?php
/**
 * We are talking about Foo::bar() here, also known as Foo->bar but not Foo::barnacle().
 * Also when we just talk about bar or bar() not barnacle, that should only be
 * renamed when aggressive.
 */
EOL;
        $expected = <<<EOL
<?php
/**
 * We are talking about Foo::baz() here, also known as Foo->baz but not Foo::barnacle().
 * Also when we just talk about bar or bar() not barnacle, that should only be
 * renamed when aggressive.
 */
EOL;
        $this->renameAndCompare($orig, $expected, 'Foo', 'bar', 'baz', false);
    }

    public function testRenameMethodNameInStringWhenAggressive() {
        $orig = <<<EOL
<?php
\$x = "string with Foo::bar or Foo->bar() and bar and bar()";
EOL;
        $expected = <<<EOL
<?php
\$x = "string with Foo::baz or Foo->baz() and baz and baz()";
EOL;
        $this->renameAndCompare($orig, $expected, 'Foo', 'bar', 'baz', true);
    }

    public function testOnlyRenameSomeMethodNamesInStringWhenNotAggressive() {
        $orig = <<<EOL
<?php
\$x = "string with Foo::bar or Foo->bar() and bar and bar()";
EOL;
        $expected = <<<EOL
<?php
\$x = "string with Foo::baz or Foo->baz() and bar and bar()";
EOL;
        $this->renameAndCompare($orig, $expected, 'Foo', 'bar', 'baz', false);
    }

    public function testRenameInChildClass() {
        $orig = <<<EOL
<?php
class FooFoo extends Foo {
    public function bar() { }
}
\$x = new FooFoo();
\$x->bar();
EOL;
        $expected = <<<EOL
<?php
class FooFoo extends Foo {
    public function baz() { }
}
\$x = new FooFoo();
\$x->baz();
EOL;
        $this->renameAndCompare($orig, $expected, 'Foo', 'bar', 'baz', false, true);
    }

    public function testDontRenameInChildClassWhenNoInheritance() {
        $orig = <<<EOL
<?php
class FooFoo extends Foo {
    public function bar() { }
}
\$x = new Foo();
\$x->bar();
\$x = new FooFoo();
\$x->bar();
EOL;
        $expected = <<<EOL
<?php
class FooFoo extends Foo {
    public function bar() { }
}
\$x = new Foo();
\$x->baz();
\$x = new FooFoo();
\$x->bar();
EOL;
        $this->renameAndCompare($orig, $expected, 'Foo', 'bar', 'baz', false, false);
    }

    public function testRenameInSecondChildClass() {
        $orig = <<<EOL
<?php
class FooFoo extends Foo {
}
class FooFooFoo extends FooFoo {
    public function bar() { }
}
\$x = new FooFooFoo();
\$x->bar();
EOL;
        $expected = <<<EOL
<?php
class FooFoo extends Foo {
}
class FooFooFoo extends FooFoo {
    public function baz() { }
}
\$x = new FooFooFoo();
\$x->baz();
EOL;
        $this->renameAndCompare($orig, $expected, 'Foo', 'bar', 'baz', false, true);
    }

    public function testRenameInImplementsClass() {
        $orig = <<<EOL
<?php
class MyFoo implements Foo {
    public function bar() { }
}
\$x = new MyFoo();
\$x->bar();
EOL;
        $expected = <<<EOL
<?php
class MyFoo implements Foo {
    public function baz() { }
}
\$x = new MyFoo();
\$x->baz();
EOL;
        $this->renameAndCompare($orig, $expected, 'Foo', 'bar', 'baz', false, true);
    }

    public function testRenameInExtendsImplementsClass() {
        $orig = <<<EOL
<?php
class MyFoo implements Foo {
}
class MySpecialFoo extends MyFoo {
    public function bar() { }
}
\$x = new MySpecialFoo();
\$x->bar();
EOL;
        $expected = <<<EOL
<?php
class MyFoo implements Foo {
}
class MySpecialFoo extends MyFoo {
    public function baz() { }
}
\$x = new MySpecialFoo();
\$x->baz();
EOL;
        $this->renameAndCompare($orig, $expected, 'Foo', 'bar', 'baz', false, true);
    }

    /**
     * @dataProvider includeTypeProvider
     */
    public function testGetPropertyTypeFromIncludedFile($includeCall) {
        $incFile = dirname($this->test_file) . '/my_included_file.php';
        $orig = <<<EOL
<?php
$includeCall("$incFile");
\$f->bar();
EOL;
        $expected = <<<EOL
<?php
$includeCall("$incFile");
\$f->baz();
EOL;
        $this->renameAndCompareWithIncludes($orig, $expected, $incFile);
    }

    public function includeTypeProvider() {
        return array(
            array('include'),
            array('include_once'),
            array('require'),
            array('require_once'),
        );
    }

    /**
     * @dataProvider includeRelativePathProvider
     */
    public function testGetPropertyTypeFromIncludedFileWithRelativePath($incFile, $pathFromRoot) {
        $orig = <<<EOL
<?php
require("$incFile");
\$f->bar();
EOL;
        $expected = <<<EOL
<?php
require("$incFile");
\$f->baz();
EOL;
        $this->renameAndCompareWithIncludes($orig, $expected, dirname($this->test_file) . $pathFromRoot);
    }

    public function includeRelativePathProvider() {
        return array(
            array('otherfolder/my_included_file.php', '/otherfolder/my_included_file.php'),
            array('./otherfolder/my_included_file.php', '/otherfolder/my_included_file.php'),
        );
    }

    private function renameAndCompareWithIncludes($orig, $expected, $includedFile) {
        $this->populateFile($orig);

        $s = new Scisr();
        $sniffer = new MockSniffer();
        $sniffer->incFile = $includedFile;
        $sniffer->test_file = $this->test_file;
        $s->setSniffer($sniffer);
        $s->setRenameMethod('Foo', 'bar', 'baz', false);
        $s->addFile($this->test_file);
        $s->run();

        $this->compareFile($expected);
    }

    public function testDontGetPropertyTypeFromFileNotIncluded() {
        $incFile = dirname($this->test_file) . '/my_included_file.php';
        $orig = <<<EOL
<?php
\$f->bar();
EOL;
        $expected = <<<EOL
<?php
\$f->bar();
EOL;
        $this->renameAndCompareWithIncludes($orig, $expected, $incFile);
    }

    public function testCircularTypes() {
        $orig = <<<EOL
<?php
\$f = new Quark();
\$f2 = new Quark();
\$a = \$f->a;
\$f2->a = \$a;
\$f->a = new Foo();
\$f2->a->bar();
EOL;
        $expected = <<<EOL
<?php
\$f = new Quark();
\$f2 = new Quark();
\$a = \$f->a;
\$f2->a = \$a;
\$f->a = new Foo();
\$f2->a->baz();
EOL;
        $this->renameAndCompare($orig, $expected, 'Foo', 'bar', 'baz', false, true);
    }

}

/**
 * @todo document
 */
class MockSniffer extends Scisr_CodeSniffer
{
    public function process($files, $local=false)
    {
        Scisr_Db_VariableTypes::registerVariableType('$f', 'Foo', $this->incFile, 0, 4);
        parent::process($files, $local);
    }
}
