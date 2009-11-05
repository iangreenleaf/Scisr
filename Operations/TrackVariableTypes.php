<?php

/**
 * Tracks variable types
 *
 * When a variable is assigned a value of an instantiated class object, we try 
 * to catch it with this sniff and store it for later reference.
 */
class Scisr_Operations_TrackVariableTypes implements PHP_CodeSniffer_Sniff
{

    public function register()
    {
        return array(
            T_NEW,
        );
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $prevPtr = $phpcsFile->findPrevious(array(T_WHITESPACE), $stackPtr - 1, null, true);
        $prevToken = $tokens[$prevPtr];
        $classPtr = $phpcsFile->findNext(T_STRING, $stackPtr);
        $classToken = $tokens[$classPtr];
        $className = $classToken['content'];
        // See if we are assigning the class to a variable
        if ($prevToken['code'] == T_EQUAL || $prevToken['code'] == T_AND_EQUAL) {
            $varPtr = $phpcsFile->findPrevious(array(T_WHITESPACE), $prevPtr - 1, null, true);
            $varToken = $tokens[$varPtr];
            if ($varToken['code'] == T_VARIABLE) {
                Scisr_VariableTypes::registerVariableType(
                    $varToken['content'],
                    $className,
                    $phpcsFile->getFileName(),
                    array_keys($varToken['conditions'])
                );
            }
        }
    }
}
