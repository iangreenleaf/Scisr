<?php
require_once 'SingleFileTest.php';

/**
 * @runTestsInSeparateProcesses
 */
class RenameMethodTest extends Scisr_SingleFileTest
{

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

    public function testRenameMethodReturnValueWithPHPDocType() {
        $this->markTestIncomplete();
    }

    public function testRenameFunctionReturnValueWithPHPDocType() {
        $this->markTestIncomplete();
    }

    public function testRenameFunctionReturnValueWithCallBeforeDeclaration() {
        $this->markTestIncomplete();
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

    public function testRenameMethodParameterWithPHPDocType() {
        $orig = <<<EOL
<?php
class Foo {
    /**
     * @param Foo \$f take a parameter
     */
    function quark(\$f) {
        \$f->bar();
    }
}
EOL;
        $expected = <<<EOL
<?php
class Foo {
    /**
     * @param Foo \$f take a parameter
     */
    function quark(\$f) {
        \$f->baz();
    }
}
EOL;
        $this->renameAndCompare($orig, $expected);
    }

    public function testRenameClassPropertyCallWithPHPDocType() {
        $orig = <<<EOL
<?php
class Foo {
    /**
     * Some class property
     * @var Foo
     */
    protected \$f;
    function quark() {
        \$this->f->bar();
    }
}
EOL;
        $expected = <<<EOL
<?php
class Foo {
    /**
     * Some class property
     * @var Foo
     */
    protected \$f;
    function quark() {
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

}
