<?php
require_once 'SingleFileTest.php';

/**
 * @runTestsInSeparateProcesses
 */
class ScisrTest extends Scisr_SingleFileTest
{

    public function parseCode($code) {
        $this->populateFile($code);

        $s = new Scisr();
        $s->setRenameClass('dummy', 'dummy2');
        $s->addFile($this->test_file);
        $s->run();

        $this->compareFile($code);

    }

    public function testEmptyFile() {
        $this->parseCode('');
    }

    /**
     * @group bug1
     */
    public function testNoPHPCode() {
        $code = <<<EOF
<html>
    <div>Here is some content that isn't PHP code</div>
</html>
EOF;
        $this->parseCode($code);
    }
}
