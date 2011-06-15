<?php
/**
 * Split a file containing several classes into separate files in the output dir. 
 */
class Scisr_Operations_SplitClassFile extends Scisr_Operations_AbstractChangeOperation implements PHP_CodeSniffer_Sniff
{

    public $outputDir;

    public function __construct(Scisr_ChangeRegistry $changeRegistry, $outputDir)
    {
        parent::__construct($changeRegistry);
        $this->outputDir = $outputDir;
    }

    public function register()
    {
        return array(
            T_CLASS,
            /*
            T_NEW,
            T_EXTENDS,
            T_PAAMAYIM_NEKUDOTAYIM,
            T_FUNCTION,
            */
        );
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        /* TODO : Get any comments from the tokens above */
        // $i now at the end of the part we need.
        $this->addClass($stackPtr, $phpcsFile );
    }

    private function addClass($classPtr, $phpcsFile) {
        $tokens = $phpcsFile->getTokens();
        $endPointer = $this->getEndPointer($classPtr, $phpcsFile);
        $startPointer = $this->getStartPointer($classPtr, $phpcsFile);

        $classNamePtr = $phpcsFile->findNext(T_STRING, $classPtr);
        $className = $tokens[$classNamePtr]['content'];

        //print "Got class $className from: $startPointer -> $classPtr -   $endPointer \n";
        $content = array();
        for ($i = $startPointer; $i < $endPointer ; $i++) {
          $content[] = $tokens[$i]['content'];
        }
        $content[] = "\n";
        $this->_changeRegistry->createFile($this->outputDir . "/" . $className . ".php", implode("", $content));
        
    }

    private function getStartPointer($stackPtr, $phpcsFile) {
        $tokens = $phpcsFile->getTokens();

        $start = $phpcsFile->findPrevious(PHP_CodeSniffer_Tokens::$commentTokens, $stackPtr,0, false );

        return ($start && $start < $stackPtr ) ? $start  : $stackPtr;
    }
    /**
     * after the end of the class, comes .. the next class.
     *
     **/
    private function getEndPointer($stackPtr, $phpcsFile) {
        $tokens = $phpcsFile->getTokens();
        return $tokens[$stackPtr]['scope_closer'] + 1;
    }
}
