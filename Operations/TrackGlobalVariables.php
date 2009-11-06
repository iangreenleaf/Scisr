<?php

/**
 * Tracks global variables
 *
 * We keep track of which variables are global in which scopes.
 */
class Scisr_Operations_TrackGlobalVariables extends Scisr_Operations_AbstractVariableTypeOperation implements PHP_CodeSniffer_Sniff
{

    public function register()
    {
        return array(
            T_GLOBAL,
        );
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $varPtr = $phpcsFile->findNext(T_VARIABLE, $stackPtr);
        $varToken = $tokens[$varPtr];
        $this->setGlobal($varPtr, $phpcsFile);
    }
}
