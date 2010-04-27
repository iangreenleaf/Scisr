<?php

/**
 * Tracks variable types
 *
 * When a variable is assigned a value of an instantiated class object, we try 
 * to catch it with this sniff and store it for later reference.
 */
class Scisr_Operations_TrackVariableTypes
    extends Scisr_Operations_AbstractVariableTypeOperation
    implements PHP_CodeSniffer_Sniff
{

    public function register()
    {
        return array(
            T_EQUAL,
            T_AND_EQUAL,
        );
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $varName = null;
        $tokens = $phpcsFile->getTokens();
        $varPtr = $phpcsFile->findPrevious(array(T_WHITESPACE), $stackPtr - 1, null, true);

        if ($tokens[$varPtr]['code'] != T_VARIABLE) {
            $varName = $this->resolveFullVariableType($varPtr, $phpcsFile, false);
        }

        $nextPtr = $phpcsFile->findNext(array(T_WHITESPACE), $stackPtr + 1, null, true);
        $nextToken = $tokens[$nextPtr];
        $className = $this->resolveFullVariableType($nextPtr, $phpcsFile);

        if (isset($className) && $className !== null) {
            $this->setVariableType($varPtr, $className, $phpcsFile, $varName);
        }
    }

}
