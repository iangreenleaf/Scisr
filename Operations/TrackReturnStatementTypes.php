<?php

/**
 * Tracks type information from return statements in functions
 */
class Scisr_Operations_TrackReturnStatementTypes
    extends Scisr_Operations_AbstractVariableTypeOperation
    implements PHP_CodeSniffer_Sniff
{

    public function register()
    {
        return array(
            T_RETURN,
        );
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $nextPtr = $phpcsFile->findNext(array(T_WHITESPACE), $stackPtr + 1, null, true);
        $nextToken = $tokens[$nextPtr];
        if ($nextToken['code'] == T_NEW) {
            // Find the class name
            $classPtr = $phpcsFile->findNext(T_STRING, $nextPtr);
            $classToken = $tokens[$classPtr];
            $className = $classToken['content'];
        } else if ($nextToken['code'] == T_VARIABLE || $nextToken['code'] == T_STRING) {
            $endPtr = $this->getEndOfVar($nextPtr, $tokens);
            $className = $this->resolveFullVariableType($nextPtr, $endPtr, $phpcsFile);
        }

        if (isset($className) && $className !== null
            && ($funcDefPtr = array_search(T_FUNCTION, $tokens[$stackPtr]['conditions'])) !== false
        ) {
            $funcPtr = $phpcsFile->findNext(T_STRING, $funcDefPtr);
            $funcName = '*' . $tokens[$funcPtr]['content'];
            $this->setVariableType($funcPtr, $className, $phpcsFile, $funcName);
        }

    }

}
