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
            $varPtr = $this->getStartOfVar($varPtr, $tokens);
            $varName = $this->resolveFullVariableType($varPtr, $stackPtr - 1, $phpcsFile);
        }

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

        if (isset($className) && $className !== null) {
            $this->setVariableType($varPtr, $className, $phpcsFile, $varName);
        }
    }

}
