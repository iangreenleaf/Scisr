<?php

/**
 * Tracks $this inside class scopes
 *
 * When a variable is assigned a value of an instantiated class object, we try 
 * to catch it with this sniff and store it for later reference.
 */
class Scisr_Operations_TrackClassTypes
    extends Scisr_Operations_AbstractVariableTypeOperation
    implements PHP_CodeSniffer_Sniff
{

    public function register()
    {
        return array(
            T_CLASS,
        );
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $classNamePtr = $phpcsFile->findNext(T_STRING, $stackPtr);
        $tokenInfo = $tokens[$classNamePtr];
        $className = $tokenInfo['content'];
        $scopeOpen = $tokens[$stackPtr]['scope_opener'];
        Scisr_VariableTypes::registerVariableType('$this', $className, $phpcsFile->getFileName(), $scopeOpen);
    }

}
