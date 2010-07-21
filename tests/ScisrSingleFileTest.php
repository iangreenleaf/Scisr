<?php
require_once 'SingleFileTest.php';

class ScisrTest extends Scisr_SingleFileTest
{

    public function parseCode($code) {
        $this->populateFile($code);

        $s = new Scisr();
        $s->setRenameClass('dummy', 'dummy2');
        $s->addFile($this->test_file);
        $s->run();

        $s = new Scisr();
        $s->setRenameMethod('DummyClass', 'dummy', 'dummy2', false);
        $s->addFile($this->test_file);
        $s->run();

        $this->compareFile($code);

    }

    public function testEmptyFile() {
        $this->parseCode('');
    }

    /**
     * @ticket 1
     */
    public function testNoPHPCode() {
        $code = <<<EOF
<html>
    <div>Here is some content that isn't PHP code</div>
</html>
EOF;
        $this->parseCode($code);
    }

    /**
     * @ticket 5
     */
    public function testEmptyComment() {
        $code = <<<EOL
<?php
/**
 */
function someFunction() { }
EOL;
        $this->parseCode($code);

        $code = <<<EOL
<?php
/***/
function someFunction() { }
EOL;
        $this->parseCode($code);
    }

    /**
     * @ticket 5
     */
    public function testEmptyVarTag() {
        $code = <<<EOL
<?php
/**
 * @var
 */
\$foo;
EOL;
        $this->parseCode($code);
    }

    /**
     * @ticket 8
     */
    public function testEmptyParamTag() {
        $code = <<<EOL
<?php
/**
 * @param
 */
function someFunction() { }
EOL;
        $this->parseCode($code);
    }

    public function testEmptyReturnTag() {
        $code = <<<EOL
<?php
/**
 * @return
 */
function someFunction() { }
EOL;
        $this->parseCode($code);
    }

    public function testTagWithoutFunction() {
        $code = <<<EOL
<?php
/**
 * @param string \$myVar a param
 * @return int something
 */
\$foo = 1;
EOL;
        $this->parseCode($code);
    }

    public function testTagWithoutVar() {
        $code = <<<EOL
<?php
/**
 * @var int
 */
echo "blah";
EOL;
        $this->parseCode($code);
    }

    /**
     * @ticket 11
     */
    public function testIncompleteVar() {
        $code = <<<EOL
<?php
/**
 * @var \$foo
 */
\$foo = 1;
EOL;
        $this->parseCode($code);
    }

    public function testIgnoreMisplacedReturn() {
        $code = <<<EOL
<?php
// This is actually "valid" PHP
return "Something";
EOL;
        $this->parseCode($code);
    }

}
