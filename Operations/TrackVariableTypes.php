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
            T_EQUAL,
            T_AND_EQUAL,
        );
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $varPtr = $phpcsFile->findPrevious(array(T_WHITESPACE), $stackPtr - 1, null, true);
        $varToken = $tokens[$varPtr];

        // If we are not assigning the class to a variable, quit
        if ($varToken['code'] != T_VARIABLE) {
            return;
        }

        $nextPtr = $phpcsFile->findNext(array(T_WHITESPACE), $stackPtr + 1, null, true);
        $nextToken = $tokens[$nextPtr];
        if ($nextToken['code'] == T_NEW) {
            $classPtr = $phpcsFile->findNext(T_STRING, $nextPtr);
            $classToken = $tokens[$classPtr];
            $className = $classToken['content'];
        } else if ($nextToken['code'] == T_VARIABLE) {
            $className = Scisr_VariableTypes::getVariableType($nextToken['content'], $phpcsFile->getFileName(), $nextToken['conditions']);
        }

        if (isset($className) && $className !== null) {
            Scisr_VariableTypes::registerVariableType(
                $varToken['content'],
                $className,
                $phpcsFile->getFileName(),
                $varToken['conditions']
            );
        }
    }
}
