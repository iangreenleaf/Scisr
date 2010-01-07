<?php
require_once 'PHPUnit/Framework.php';
require_once '../Scisr.php';

/**
 * This test case is in a somewhat awkward place. Right now we are only unit 
 * testing getStartOfVar(), and are depending on the functional test cases for 
 * the other functionality of this class. We should probably have it one way or 
 * the other, not in-between like this.
 * @runTestsInSeparateProcesses
 */
class AbstractVariableTypeTest extends PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider variableProvider
     */
    public function testGetStartOfVar($code, $startContent, $endContent) {
        // Tokenize the code
        $tokenizer = new PHP_CodeSniffer_Tokenizers_PHP();
        $tokens = $tokenizer->tokenizeString($code);
        // Find the pointer to the end token
        foreach ($tokens as $ptr => $token) {
            if ($token['content'] == $endContent) {
                $endPtr = $ptr;
            }
        }
        // Fire up the tester
        $tester = new AbstractVariableTypeTester();
        $startPtr = $tester->exposeGetStartOfVar($endPtr, $tokens);

        $this->assertEquals($startContent, $tokens[$startPtr]['content']);
    }

    public function variableProvider() {
        $a = array();
        $a[] = array('<?php $a;', '$a', '$a');
        $a[] = array('<?php $b; $a;', '$a', '$a');
        $a[] = array('<?php $b;$a;', '$a', '$a');
        $a[] = array("<?php\n\$a;", '$a', '$a');

        // Temporary solution so we can test what we can avoid the
        // php-eating-whitespace problem and test what's actually important
        $php = "<?php\n\n";

        $a[] = array($php . ' $a->b->c->d;', '$a', 'd');
        $a[] = array($php . ' $a->$b->$$c->d;', '$a', 'd');
        $a[] = array($php . ' $a->b()->c()->d();', '$a', 'd');
        $a[] = array($php . " \$a->b()\n\t->c()\n\t->d();", '$a', 'd');
        $a[] = array($php . ' $a->b->c()->d;', '$a', 'd');
        $a[] = array($php . ' $a->b($foo, 1, "bar", $baz[3])->c->d;', '$a', 'd');
        return $a;
    }

}

/**
 * A test class
 *
 * This class' only purpose is to extend Scisr_Operations_AbstractVariableTypeOperation
 * and provide a public interface to the protected methods for testing purposes.
 */
class AbstractVariableTypeTester extends Scisr_Operations_AbstractVariableTypeOperation
{
    public function exposeGetStartOfVar($ptr, $tokens)
    {
        return $this->getStartOfVar($ptr, $tokens);
    }
    public function register() {
        //STUB
    }
    public function process(PHP_CodeSniffer_File $f, $p) {
        //STUB
    }
}
