<?php
require_once 'Scisr_TestCase.php';

/**
 * This test case is in a somewhat awkward place. Right now we are only unit 
 * testing getStartOfVar(), and are depending on the functional test cases for 
 * the other functionality of this class. We should probably have it one way or 
 * the other, not in-between like this.
 */
class AbstractVariableTypeTest extends Scisr_TestCase
{

    /**
     * @dataProvider specificityProvider
     */
    public function testGetVariableSpecificity($name, $specificity) {
        $this->assertEquals($specificity, Scisr_Operations_AbstractVariableTypeOperation::getVariableSpecificity($name));
    }

    public function specificityProvider() {
        return array(
            array('Foo', 0),
            array('$foo', 1),
            array('*myFunc', 1),
            array('Foo->a', 1),
            array('Foo->*myMethod', 1),
            array('$foo->a', 2),
            array('$foo->*myMethod', 2),
            array('*myFunc->a', 2),
            array('*myFunc->*myMethod', 2),
            array('Foo->a->b->c->d', 4),
            array('$foo->*myMethod->a->*method2->b->*three', 6),
        );
    }

    /**
     * @dataProvider variableProvider
     * @param string $code the code to tokenize
     * @param string $startContent the token content to start our var
     * @param string $endContent the token content to end our var
     * @param int $endPtrOffset a number of pointers past $endContent where our
     * variable actually ends. A hack to get around hard-to-identify endings
     * like parentheses.
     */
    public function testGetEndOfVar($code, $startContent, $endContent, $endPtrOffset=0) {
        $tokens = $this->getTokens($code);
        // Find the pointer to the end token
        foreach ($tokens as $ptr => $token) {
            if ($token['content'] == $startContent) {
                $startPtr = $ptr;
            }
            if ($token['content'] == $endContent) {
                $expectedEndPtr = $ptr;
            }
        }
        $expectedEndPtr += $endPtrOffset;
        // Fire up the tester
        $tester = new AbstractVariableTypeTester();
        $endPtr = $tester->exposeGetEndOfVar($startPtr, $tokens);

        $this->assertEquals($expectedEndPtr, $endPtr);
    }

    /**
     * @dataProvider variableProvider
     * @see testGetEndOfVar
     */
    public function testGetStartOfVar($code, $startContent, $endContent, $endPtrOffset=0) {
        $tokens = $this->getTokens($code);
        // Find the pointer to the end token
        foreach ($tokens as $ptr => $token) {
            if ($token['content'] == $endContent) {
                $endPtr = $ptr;
            }
        }
        $endPtr += $endPtrOffset;
        // Fire up the tester
        $tester = new AbstractVariableTypeTester();
        $startPtr = $tester->exposeGetStartOfVar($endPtr, $tokens);

        $this->assertEquals($startContent, $tokens[$startPtr]['content']);
    }

    /**
     * Tokenize a string, PHP_CodeSniffer style
     * @param string $code the code to be tokenized
     * @return array an array of tokens, complete with all the extra information PHPCS provides
     */
    private function getTokens($code) {
        new PHP_CodeSniffer();
        $tokenizer = new PHP_CodeSniffer_Tokenizers_PHP();
        return PHP_CodeSniffer_File::tokenizeString($code, $tokenizer);
    }

    public function variableProvider() {
        $a = array();
        $a[] = array('<?php $a;', '$a', '$a');
        $a[] = array('<?php $b; $a;', '$a', '$a');
        $a[] = array('<?php $b;$a;', '$a', '$a');
        $a[] = array('<?php $b;$a;', '$b', '$b');
        $a[] = array('<?php $$a;', '$', '$a');
        $a[] = array("<?php\n\$a;", '$a', '$a');

        // Temporary solution so we can avoid the
        // php-eating-whitespace problem and test what's actually important
        $php = "<?php\n\n";

        $a[] = array($php . ' $a = 1;', '$a', '$a');
        $a[] = array($php . ' $a->b = 1;', '$a', 'b');
        $a[] = array($php . ' $a = $b;', '$a', '$a');
        $a[] = array($php . ' $a=$b;', '$a', '$a');

        $a[] = array($php . ' $a->b->c->d;', '$a', 'd');
        $a[] = array($php . ' $a->$b->$$c->d;', '$a', 'd');
        $a[] = array($php . ' $a->b()->c()->d();', '$a', 'd', 2);
        $a[] = array($php . " \$a->b()\n\t->c()\n\t->d();", '$a', 'd', 2);
        $a[] = array($php . ' $a->b->c()->d;', '$a', 'd');
        $a[] = array($php . ' $a->b($foo, 1, "bar", $baz[3])->c->d;', '$a', 'd');

        $a[] = array($php . ' a()->b;', 'a', 'b');
        $a[] = array($php . ' AClass::a()->b;', 'AClass', 'b');

        $a[] = array($php . ' somefunc($a)->b;', '$a', '$a');
        $a[] = array($php . ' somefunc($a->b())->c;', '$a', 'b', 2);
        $a[] = array($php . ' somefunc($a,$b);', '$a', '$a');
        $a[] = array($php . ' somefunc($a->b($c)->d,$e);', '$a', 'd');
        $a[] = array($php . ' array($a,$b);', '$a', '$a');
        $a[] = array($php . ' array($a,SOMECONST);', '$a', '$a');
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
    public function __construct()
    {
        parent::__construct(new Scisr_ChangeRegistry);
    }

    public function exposeGetStartOfVar($ptr, $tokens)
    {
        return $this->getStartOfVar($ptr, $tokens);
    }
    public function exposeGetEndOfVar($ptr, $tokens)
    {
        return $this->getEndOfVar($ptr, $tokens);
    }
    public function register() {
        //STUB
    }
    public function process(PHP_CodeSniffer_File $f, $p) {
        //STUB
    }
}
